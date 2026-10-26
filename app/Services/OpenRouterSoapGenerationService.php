<?php

namespace App\Services;

use App\Contracts\SoapGenerationService;
use App\Models\Kunjungan;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterSoapGenerationService implements SoapGenerationService
{
    public function generate(Kunjungan $kunjungan, string $transcript): array
    {
        $key = config('medscribe.openrouter.api_key');
        if (! $key) {
            throw new RuntimeException('OPENROUTER_API_KEY belum dikonfigurasi. Draf SOAP tidak dibuat.');
        }

        $segments = $kunjungan->wawancara?->transcript_segments ?? [];
        $transcriptInput = $segments
            ? collect($segments)->map(fn ($segment) => sprintf('[%s-%s] %s: %s', $segment['startTime'] ?? '?', $segment['endTime'] ?? '?', $segment['speakerRole'] ?? 'unknown', $segment['text'] ?? ''))->implode("\n")
            : $transcript;
        $vitals = $kunjungan->pemeriksaanAwal;
        $vitalInput = json_encode([
            'tekanan_darah' => $vitals?->tekanan_darah,
            'nadi' => $vitals?->nadi,
            'suhu_tubuh' => $vitals?->suhu_tubuh,
            'laju_pernapasan' => $vitals?->laju_pernapasan,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $response = Http::withToken($key)
            ->acceptJson()
            ->timeout(config('medscribe.openrouter.timeout'))
            ->post(rtrim(config('medscribe.openrouter.base_url'), '/').'/chat/completions', [
                'model' => config('medscribe.openrouter.model'),
                'temperature' => 0.1,
                'messages' => [
                    ['role' => 'system', 'content' => <<<'PROMPT'
Anda adalah asisten medis AI profesional yang menganalisis transkrip konsultasi dokter-pasien dan menyusunnya menjadi draf catatan SOAP dalam Bahasa Indonesia.

Aturan wajib:
1. Ekstrak informasi medis hanya dari transkrip dan TTV yang diberikan.
2. Jangan membuat asumsi medis, diagnosis tambahan, hasil pemeriksaan, gejala negatif, riwayat, alergi, atau obat yang tidak disebutkan.
3. Bedakan informasi yang disampaikan pasien dan dokter.
4. Jangan memasukkan PII seperti nama pasien, alamat, nomor telepon, tempat kerja, atau identitas lain ke dalam hasil.
5. Gunakan terminologi medis Bahasa Indonesia yang baku.
6. Diagnosis yang diucapkan dokter tetap merupakan diagnosis kerja/kemungkinan, bukan diagnosis final.
7. Obat, dosis, frekuensi, dan instruksi hanya boleh ditulis jika disebutkan secara eksplisit.
8. Informasi yang tidak tersedia harus ditulis eksplisit sebagai "Tidak disebutkan" atau "Belum tersedia", bukan diisi dengan asumsi.
9. ICD-10 hanya saran dan bukan diagnosis final.
10. Hasil adalah draf yang wajib ditinjau dan diverifikasi dokter.

Kembalikan satu objek JSON valid saja, tanpa markdown dan tanpa code fence, dengan field berikut:
- subjective: string berisi subbagian "Keluhan Utama", "Riwayat Penyakit Sekarang", dan "Riwayat Medis / Alergi / Obat yang Dikonsumsi".
- objective: string berisi subbagian "Tanda-Tanda Vital", "Hasil Pemeriksaan Fisik", dan "Hasil Laboratorium/Penunjang".
- assessment: string berisi subbagian "Diagnosis Utama / Diagnosis Kerja" dan "Diagnosis Banding" jika disebutkan.
- plan: string berisi subbagian "Rencana Terapi / Pengobatan", "Rencana Pemeriksaan Lanjutan", dan "Edukasi & Instruksi Pasien".
- icd10Suggestions: array berisi saran kode jika memang didukung transkrip, atau array kosong.
- missingInformation: array berisi informasi klinis penting yang tidak tersedia.
- reviewWarnings: array berisi peringatan review dokter.
PROMPT
                    ],
                    ['role' => 'user', 'content' => "Transkrip yang sudah ditinjau:\n{$transcriptInput}\n\nTTV yang diinput manual (gunakan hanya nilai ini):\n{$vitalInput}\n\nJangan masukkan identitas pasien. Hasil harus berupa satu objek JSON."]
                ],
            ]);

        if ($response->failed()) {
            $status = $response->status();
            throw new RuntimeException($status === 429 ? 'OpenRouter mencapai batas penggunaan/rate limit. Tidak ada model berbayar yang digunakan.' : 'OpenRouter gagal merespons (HTTP '.$status.').');
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        $content = is_string($content) ? trim(preg_replace('/^```(?:json)?|```$/m', '', $content)) : null;
        $data = $content ? json_decode($content, true) : null;
        if (! is_array($data)) {
            throw new RuntimeException('Respons SOAP dari OpenRouter bukan JSON yang valid. Draf sebelumnya dipertahankan.');
        }
        foreach (['subjective', 'objective', 'assessment', 'plan', 'icd10Suggestions', 'missingInformation', 'reviewWarnings'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw new RuntimeException("Respons SOAP tidak memiliki field {$field}. Draf sebelumnya dipertahankan.");
            }
        }

        return [
            'subjective_text' => (string) $data['subjective'],
            'objective_text' => (string) $data['objective'],
            'assessment_text' => (string) $data['assessment'],
            'plan_text' => (string) $data['plan'],
            'kode_icd10' => is_array($data['icd10Suggestions']) ? ($data['icd10Suggestions'][0]['code'] ?? $data['icd10Suggestions'][0] ?? null) : null,
            'icd10_suggestions' => $data['icd10Suggestions'],
            'missing_information' => $data['missingInformation'],
            'review_warnings' => $data['reviewWarnings'],
            'source_type' => 'openrouter_text_only',
        ];
    }
}

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
        @set_time_limit(180);

        $key = config('medscribe.openrouter.api_key');
        if (! $key) {
            throw new RuntimeException('OPENROUTER_API_KEY belum dikonfigurasi. Draf SOAP tidak dibuat.');
        }

        $segments = $kunjungan->wawancara?->transcript_segments ?? [];
        $transcriptInput = $segments
            ? collect($segments)->map(fn ($segment) => sprintf('[%s-%s] %s: %s', $segment['startTime'] ?? '?', $segment['endTime'] ?? '?', $segment['speakerRole'] ?? 'unknown', $segment['text'] ?? ''))->implode("\n")
            : $transcript;
        $vitals = $kunjungan->pemeriksaanAwal;
        $pasien = $kunjungan->pasien;
        $umur = $pasien?->umur ?: ($pasien?->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->age : null);

        $patientContext = [
            'usia' => $umur ? "{$umur} tahun" : 'Tidak disebutkan',
            'jenis_kelamin' => $pasien?->jenis_kelamin ?: 'Tidak disebutkan',
        ];

        $vitalData = [
            'tekanan_darah' => $vitals?->tekanan_darah ?: ($vitals?->tekanan_darah_sistolik && $vitals?->tekanan_darah_diastolik ? "{$vitals->tekanan_darah_sistolik}/{$vitals->tekanan_darah_diastolik} mmHg" : null),
            'nadi' => $vitals?->nadi ? "{$vitals->nadi} x/menit" : null,
            'suhu_tubuh' => $vitals?->suhu_tubuh ? "{$vitals->suhu_tubuh} °C" : null,
            'laju_pernapasan' => $vitals?->laju_pernapasan ? "{$vitals->laju_pernapasan} x/menit" : null,
            'saturasi_oksigen' => $vitals?->saturasi_oksigen ? "{$vitals->saturasi_oksigen} %" : null,
            'berat_badan' => $vitals?->berat_badan ? "{$vitals->berat_badan} kg" : null,
            'tinggi_badan' => $vitals?->tinggi_badan ? "{$vitals->tinggi_badan} cm" : null,
            'catatan_perawat' => $vitals?->catatan ?: null,
        ];
        $vitalInput = json_encode(array_filter($vitalData, fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $contextInput = json_encode($patientContext, JSON_UNESCAPED_UNICODE);

        $verify = config('medscribe.openrouter.verify_ssl', true);
        if ($verify) {
            $caBundle = config('medscribe.openrouter.ca_bundle') ?: (file_exists(storage_path('cacert.pem')) ? storage_path('cacert.pem') : true);
            $verify = $caBundle;
        }

        $response = Http::withToken($key)
            ->withOptions(['verify' => $verify])
            ->acceptJson()
            ->timeout(config('medscribe.openrouter.timeout'))
            ->post(rtrim(config('medscribe.openrouter.base_url'), '/').'/chat/completions', [
                'model' => config('medscribe.openrouter.model'),
                'temperature' => 0.1,
                'messages' => [
                    ['role' => 'system', 'content' => <<<'PROMPT'
Anda adalah asisten medis AI profesional yang menganalisis transkrip konsultasi dokter-pasien dan data TTV aktual untuk menyusun draf catatan SOAP dalam Bahasa Indonesia klinis yang baku.

Aturan wajib:
1. Ekstrak informasi medis hanya dari transkrip yang diverifikasi dan data TTV yang diberikan.
2. Jangan membuat asumsi medis, diagnosis tambahan, hasil pemeriksaan, gejala negatif, riwayat, alergi, atau obat yang tidak disebutkan.
3. Bedakan secara tegas informasi yang disampaikan pasien (Subjective) dan temuan klinis/TTV (Objective).
4. Jangan memasukkan PII seperti nama pasien, alamat, nomor telepon, atau identitas pribadi ke dalam hasil.
5. Gunakan terminologi medis Bahasa Indonesia yang baku (misal: Hipertensi, Diabetes melitus, Nyeri dada, Sesak napas).
6. Diagnosis dokter yang belum pasti tetap merupakan diagnosis kerja/kemungkinan, bukan diagnosis pasti.
7. Obat, dosis, dan frekuensi hanya boleh dicantumkan jika disebutkan secara eksplisit.
8. Bagian yang informasinya tidak tersedia harus ditulis sebagai "Tidak disebutkan" atau "Belum tersedia", bukan diisi karangan.
9. Rekomendasi ICD-10 hanya saran yang didukung transkrip.
10. Hasil adalah draf dokumentasi klinis yang wajib ditinjau dokter.

Kembalikan satu objek JSON valid saja, tanpa markdown dan tanpa code fence, dengan field berikut:
- subjective: string berisi subbagian "Keluhan Utama", "Riwayat Penyakit Sekarang", dan "Riwayat Penyakit Dahulu / Alergi / Obat".
- objective: string berisi subbagian "Tanda-Tanda Vital", "Hasil Pemeriksaan Fisik", dan "Pemeriksaan Penunjang".
- assessment: string berisi subbagian "Diagnosis Utama / Diagnosis Kerja" dan "Diagnosis Banding" jika ada.
- plan: string berisi subbagian "Rencana Terapi / Pengobatan", "Rencana Tindak Lanjut / Lab", dan "Edukasi Pasien".
- icd10Suggestions: array berisi saran kode ICD-10 yang didukung transkrip, atau array kosong.
- missingInformation: array berisi daftar informasi klinis penting yang belum tersedia.
- reviewWarnings: array berisi peringatan review bagi dokter.
PROMPT
                    ],
                    ['role' => 'user', 'content' => "Konteks Pasien:\n{$contextInput}\n\nTanda-Tanda Vital & Catatan Klinis:\n{$vitalInput}\n\nTranskrip Wawancara Klinis yang Telah Diverifikasi:\n{$transcriptInput}\n\nJangan masukkan nama atau nomor kontak pasien. Kembalikan satu objek JSON valid sesuai format."]
                ],
            ]);

        if ($response->failed()) {
            $status = $response->status();
            $detail = data_get($response->json(), 'error.message');
            if ($status === 503) {
                $model = config('medscribe.openrouter.model');
                throw new RuntimeException("Model OpenRouter '{$model}' sedang sibuk/overload (HTTP 503). Silakan coba lagi atau ganti OPENROUTER_MODEL di .env (misal: openrouter/free atau google/gemini-2.0-flash-exp:free).");
            }
            if ($status === 429) {
                throw new RuntimeException('OpenRouter mencapai batas penggunaan/rate limit. Tidak ada model berbayar yang digunakan.');
            }
            $message = 'OpenRouter gagal merespons (HTTP '.$status.').';
            if ($detail) {
                $message .= ' Detail: '.$detail;
            }
            throw new RuntimeException($message);
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

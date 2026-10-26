<?php

namespace App\Services;

use App\Models\PemeriksaanAwal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiSoapService
{
    /**
     * Memproses berkas audio wawancara klinis dokter-pasien menggunakan Google Gemini API
     * untuk menghasilkan draf catatan medis SOAP (Subjective, Objective, Assessment, Plan)
     * beserta estimasi kode ICD-10.
     *
     * @param string $audioFullPath Path absolut berkas audio pada filesystem lokal
     * @param array $patientData Data demografi pasien (nama, no_rm, umur, jenis_kelamin)
     * @param PemeriksaanAwal|null $pemeriksaanAwal Data TTV & catatan awal perawat
     * @return array Status dan hasil ekstraksi draf SOAP
     */
    public function generateSoapFromAudio(string $audioFullPath, array $patientData, ?PemeriksaanAwal $pemeriksaanAwal = null): array
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-1.5-flash');

        // 1. Verifikasi ketersediaan kredensial API (Safety Rule)
        if (empty($apiKey)) {
            return [
                'success' => false,
                'configured' => false,
                'message' => 'API Key Gemini (GEMINI_API_KEY) belum dikonfigurasi di server. Rekaman berhasil disimpan secara aman. Dokter dapat menyusun draf SOAP secara manual.',
            ];
        }

        // 2. Verifikasi berkas audio fisik
        if (!file_exists($audioFullPath) || filesize($audioFullPath) === 0) {
            return [
                'success' => false,
                'configured' => true,
                'message' => 'Berkas rekaman audio tidak ditemukan pada penyimpanan server atau berukuran 0 byte.',
            ];
        }

        try {
            // 3. Tentukan MIME Type audio yang tepat
            $extension = strtolower(pathinfo($audioFullPath, PATHINFO_EXTENSION));
            $mimeType = match ($extension) {
                'ogg' => 'audio/ogg',
                'mp3' => 'audio/mp3',
                'wav' => 'audio/wav',
                'mp4', 'm4a' => 'audio/mp4',
                'webm' => 'audio/webm',
                default => 'audio/webm',
            };

            // Baca konten audio dan konversi ke base64
            $audioBytes = file_get_contents($audioFullPath);
            $base64Audio = base64_encode($audioBytes);

            // 4. Susun konteks klinis pendukung dari data pasien dan TTV perawat
            $ttvInfo = 'Belum ada data TTV perawat.';
            if ($pemeriksaanAwal) {
                $ttvParts = [];
                if ($pemeriksaanAwal->tekanan_darah) $ttvParts[] = 'TD: ' . $pemeriksaanAwal->tekanan_darah . ' mmHg';
                if ($pemeriksaanAwal->nadi) $ttvParts[] = 'Nadi: ' . $pemeriksaanAwal->nadi . ' x/menit';
                if ($pemeriksaanAwal->suhu_tubuh) $ttvParts[] = 'Suhu: ' . $pemeriksaanAwal->suhu_tubuh . ' °C';
                if ($pemeriksaanAwal->laju_pernapasan) $ttvParts[] = 'RR: ' . $pemeriksaanAwal->laju_pernapasan . ' x/menit';
                if ($pemeriksaanAwal->saturasi_oksigen) $ttvParts[] = 'SpO2: ' . $pemeriksaanAwal->saturasi_oksigen . ' %';
                if ($pemeriksaanAwal->berat_badan) $ttvParts[] = 'BB: ' . $pemeriksaanAwal->berat_badan . ' kg';
                if ($pemeriksaanAwal->tinggi_badan) $ttvParts[] = 'TB: ' . $pemeriksaanAwal->tinggi_badan . ' cm';
                if ($pemeriksaanAwal->catatan) $ttvParts[] = 'Catatan Awal Perawat: "' . $pemeriksaanAwal->catatan . '"';
                
                $ttvInfo = implode(', ', $ttvParts);
            }

            $prompt = <<<EOT
Anda adalah asisten AI medis untuk dokter di sistem rekam medis elektronik MedScribe AI.
Tugas Anda adalah mendengarkan rekaman audio konsultasi medis antara dokter dan pasien terlampir, lalu mengekstrak informasi anamnesis dan menyusun DRAF CATATAN MEDIS SOAP (Subjective, Objective, Assessment, Plan) dalam Bahasa Indonesia medis baku.

Data Pasien:
- Nama: {$patientData['nama']}
- No. Rekam Medis: {$patientData['no_rm']}
- Umur: {$patientData['umur']}
- Jenis Kelamin: {$patientData['jenis_kelamin']}

Data Pengukuran Tanda Vital (TTV) & Catatan Nurse Station:
{$ttvInfo}

Panduan Klinis Pengisian SOAP:
1. SUBJECTIVE (S):
   Rangkum keluhan utama pasien, durasi gejala, riwayat penyakit sekarang (RPS), riwayat alergi obat/makanan, dan riwayat pengobatan sebelumnya yang terungkap dalam wawancara.
2. OBJECTIVE (O):
   Integrasikan data TTV dari perawat di atas serta temuan pemeriksaan fisik dokter yang disebutkan dalam audio.
3. ASSESSMENT (A):
   Tetapkan diagnosis kerja / diagnosis klinis dokter dan diagnosis banding (jika relevan). Cantumkan kode klasifikasi penyakit internasional ICD-10 yang paling sesuai.
4. PLAN (P):
   Tuliskan rencana terapi (obat-obatan/resep), rencana pemeriksaan penunjang (laboratorium/rontgen jika ada), edukasi pola hidup untuk pasien, serta rencana kontrol ulang.
5. KODE ICD-10:
   Tuliskan kode ICD-10 primer yang paling relevan (contoh: J00, J02.9, I10, K29.7, E11.9).

PENTING - KESELAMATAN KLINIS:
- Seluruh isi harus berakar pada percakapan audio nyata dan data TTV yang diberikan.
- JANGAN memfabrikasi gejala fiktif yang tidak disebutkan dalam audio.
- Format respon WAJIB berupa JSON murni tanpa markdown pembungkus (backtick).

Struktur JSON:
{
  "subjective": "...",
  "objective": "...",
  "assessment": "...",
  "plan": "...",
  "kode_icd10": "..."
}
EOT;

            // 5. Panggil Gemini API (REST Endpoint)
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Audio,
                                    ],
                                ],
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                Log::warning('Gemini API Audio Processing Failed', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'configured' => true,
                    'message' => 'Layanan AI Gemini merespons dengan kesalahan (' . $response->status() . '): ' . $errorMessage . '. Rekaman audio tetap tersimpan dengan aman.',
                ];
            }

            // 6. Parsing hasil JSON dari respon Gemini
            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Bersihkan markdown code blocks jika ada (e.g. ```json ... ```)
            $cleanJson = trim($rawText);
            if (str_starts_with($cleanJson, '```')) {
                $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', $cleanJson);
                $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
                $cleanJson = trim($cleanJson);
            }

            $parsed = json_decode($cleanJson, true);

            if (!is_array($parsed) || empty($parsed['subjective'])) {
                // Fallback jika respon bukan format JSON yang diharapkan
                return [
                    'success' => true,
                    'configured' => true,
                    'data' => [
                        'subjective' => $rawText,
                        'objective' => $ttvInfo,
                        'assessment' => 'Perlu konfirmasi dokter',
                        'plan' => 'Perlu telaah lebih lanjut',
                        'kode_icd10' => null,
                    ],
                    'message' => 'Draf SOAP berhasil dibuat, namun memerlukan penyesuaian format oleh dokter.',
                ];
            }

            return [
                'success' => true,
                'configured' => true,
                'data' => [
                    'subjective' => trim($parsed['subjective'] ?? ''),
                    'objective' => trim($parsed['objective'] ?? $ttvInfo),
                    'assessment' => trim($parsed['assessment'] ?? ''),
                    'plan' => trim($parsed['plan'] ?? ''),
                    'kode_icd10' => trim($parsed['kode_icd10'] ?? ''),
                ],
                'message' => 'Rekaman berhasil diproses dan draf SOAP telah disusun secara otomatis oleh AI.',
            ];

        } catch (\Throwable $e) {
            Log::error('Exception in GeminiSoapService::generateSoapFromAudio', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'configured' => true,
                'message' => 'Gagal memproses draf SOAP via AI: ' . $e->getMessage() . '. Rekaman audio tetap tersimpan dengan aman.',
            ];
        }
    }
}

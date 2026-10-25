<?php

namespace App\Services;

use App\Contracts\TranscriptionService;

class MockTranscriptionService implements TranscriptionService
{
    public function transcribe(array $answers): string
    {
        $labels = [
            'keluhan_utama' => 'Keluhan utama',
            'mulai_keluhan' => 'Awal keluhan',
            'awal_muncul' => 'Perjalanan awal keluhan',
            'pola_keluhan' => 'Pola keluhan',
            'gejala_penyerta' => 'Gejala penyerta',
            'faktor_pemicu' => 'Faktor memperberat atau meringankan',
            'riwayat_penyakit' => 'Riwayat penyakit relevan',
            'alergi' => 'Alergi',
            'obat' => 'Obat yang sedang digunakan',
            'informasi_lain' => 'Informasi lain yang relevan',
        ];

        $lines = [];
        foreach ($labels as $key => $label) {
            $answer = trim((string) ($answers[$key] ?? ''));
            if ($answer !== '') {
                $lines[] = "{$label}: {$answer}";
            }
        }

        return implode("\n", $lines);
    }
}

<?php

namespace App\Services;

use App\Contracts\SoapGenerationService;
use App\Models\Kunjungan;

class MockSoapGenerationService implements SoapGenerationService
{
    public function __construct(private MockSoapDraftService $draftService) {}

    public function generate(Kunjungan $kunjungan, string $transcript): array
    {
        $subjective = $transcript !== ''
            ? $transcript
            : 'Belum ada jawaban wawancara yang dicatat.';

        return [
            'subjective_text' => $subjective,
            'objective_text' => $this->draftService->formatVitals($kunjungan->pemeriksaanAwal),
            'assessment_text' => 'Penilaian awal berdasarkan transkrip simulasi. Diagnosis dan kesesuaian kode perlu ditinjau dokter.',
            'plan_text' => 'Rencana belum ditentukan. Dokter perlu meninjau dan melengkapi rencana berdasarkan pemeriksaan klinis.',
            'kode_icd10' => 'J02.9',
            'icd10_suggestions' => [
                ['code' => 'J02.9', 'description' => 'Faringitis akut, tidak spesifik', 'confidence' => 'sedang'],
                ['code' => 'J03.9', 'description' => 'Tonsilitis akut, tidak spesifik', 'confidence' => 'rendah'],
            ],
            'missing_information' => [],
            'review_warnings' => [],
            'source_type' => 'mock_generation',
        ];
    }
}

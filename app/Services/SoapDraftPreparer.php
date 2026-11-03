<?php

namespace App\Services;

use App\Contracts\SoapGenerationService;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use Throwable;

/**
 * Menyiapkan draf SOAP untuk ditinjau dokter: membuat draf pertama dari transkrip
 * yang sudah ditinjau, atau menandai draf lama bila transkrip/TTV sumbernya berubah.
 */
class SoapDraftPreparer
{
    public const ICD10_OPTIONS = [
        'J02.9' => 'Faringitis akut, tidak spesifik (simulasi)',
        'R07.0' => 'Nyeri tenggorokan (simulasi)',
    ];

    public function __construct(
        private MockSoapDraftService $draftService,
        private SoapGenerationService $soapGenerationService,
    ) {}

    /**
     * @return array{draf: DrafSoap, transcript: string, generationError: ?string}
     */
    public function prepare(Kunjungan $kunjungan): array
    {
        $kunjungan->loadMissing(['pemeriksaanAwal', 'drafSoap', 'wawancara']);
        $transcript = $kunjungan->wawancara?->transkrip ?: '';
        $sourceHash = hash('sha256', $transcript);
        $ttvSnapshot = $this->draftService->vitalsSnapshot($kunjungan->pemeriksaanAwal);
        $draf = $kunjungan->drafSoap;
        $generationError = null;
        $requiresTranscriptReview = filled($transcript) && ! $kunjungan->wawancara?->transcript_reviewed_at;

        if (! $draf && ! filled($transcript)) {
            $generationError = 'Transkrip wawancara yang sudah ditinjau belum tersedia. Draf SOAP belum dibuat.';
            $draf = new DrafSoap([
                'subjective_text' => '', 'objective_text' => '', 'assessment_text' => '', 'plan_text' => '',
                'needs_review' => true, 'icd10_suggestions' => [], 'missing_information' => ['Transkrip wawancara'], 'review_warnings' => [],
            ]);
        } elseif (! $draf) {
            try {
                if ($requiresTranscriptReview) {
                    throw new \RuntimeException('Tinjau dan simpan koreksi transkrip serta label speaker sebelum membuat draf SOAP.');
                }
                $generated = $this->soapGenerationService->generate($kunjungan, $transcript);
                $draf = DrafSoap::create(array_merge($generated, [
                    'id_kunjungan' => $kunjungan->id_kunjungan,
                    'id_pengguna' => $kunjungan->id_dokter,
                    'source_transcript_hash' => $sourceHash,
                    'source_ttv_snapshot' => $ttvSnapshot,
                    'needs_review' => false,
                ]));
            } catch (Throwable $exception) {
                report($exception);
                $generationError = $exception->getMessage();
                $draf = DrafSoap::create([
                    'id_kunjungan' => $kunjungan->id_kunjungan,
                    'id_pengguna' => $kunjungan->id_dokter,
                    'subjective_text' => '',
                    'objective_text' => '',
                    'assessment_text' => '',
                    'plan_text' => '',
                    'source_transcript_hash' => $sourceHash,
                    'source_ttv_snapshot' => $ttvSnapshot,
                    'needs_review' => true,
                    'icd10_suggestions' => [],
                    'missing_information' => [],
                    'review_warnings' => [],
                    'source_type' => 'manual',
                ]);
            }
        } else {
            $needsReview = $draf->source_transcript_hash !== $sourceHash
                || $draf->source_ttv_snapshot !== $ttvSnapshot;
            if ($needsReview && ! $draf->needs_review) {
                $draf->update(['needs_review' => true]);
            }
        }

        return [
            'draf' => $draf,
            'transcript' => $transcript,
            'generationError' => $generationError,
        ];
    }
}

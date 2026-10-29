<?php

namespace App\Http\Controllers;

use App\Contracts\SoapGenerationService;
use App\Models\DrafSoap;
use App\Models\Finalisasi;
use App\Models\Kunjungan;
use App\Services\MockSoapDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SoapReviewController extends Controller
{
    public function __construct(
        private MockSoapDraftService $draftService,
        private SoapGenerationService $soapGenerationService,
    ) {}

    public function edit(Kunjungan $kunjungan): View
    {
        $this->authorizeVisit($kunjungan);
        $kunjungan->load(['pasien', 'dokter', 'pemeriksaanAwal', 'drafSoap', 'finalisasi', 'wawancara']);
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
                $draf = new DrafSoap([
                    'subjective_text' => '', 'objective_text' => '', 'assessment_text' => '', 'plan_text' => '',
                    'needs_review' => true, 'icd10_suggestions' => [], 'missing_information' => [], 'review_warnings' => [],
                ]);
            }
        } else {
            $needsReview = $draf->source_transcript_hash !== $sourceHash
                || $draf->source_ttv_snapshot !== $ttvSnapshot;
            if ($needsReview && ! $draf->needs_review) {
                $draf->update(['needs_review' => true]);
            }
        }

        return view('soap.review', [
            'kunjungan' => $kunjungan,
            'draf' => $draf,
            'transcript' => $transcript,
            'icd10Options' => ['J02.9' => 'Faringitis akut, tidak spesifik (simulasi)', 'R07.0' => 'Nyeri tenggorokan (simulasi)'],
            'generationError' => $generationError,
        ]);
    }

    public function save(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        if (! $this->transcriptIsReviewed($kunjungan)) {
            return back()->with('warning', 'Tinjau dan simpan transkrip sebelum menyimpan SOAP.');
        }
        $validated = $this->validateDraft($request);

        DB::transaction(function () use ($validated, $kunjungan) {
            $kunjungan->pasien()->update([
                'no_rekam_medis' => $validated['no_rekam_medis'],
                'nama_pasien' => $validated['nama_pasien'],
                'tanggal_lahir' => $validated['tanggal_lahir'] ?: null,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?: null,
            ]);
            $kunjungan->drafSoap()->updateOrCreate([], array_merge(
                ['id_pengguna' => $kunjungan->id_dokter],
                collect($validated)->only(['subjective_text', 'objective_text', 'assessment_text', 'plan_text', 'kode_icd10'])->all(),
                $this->sourceMetadata($kunjungan)
            ));
            $kunjungan->update(['status' => 'SOAP Belum Diverifikasi']);
        });

        return back()->with('status', 'Perubahan draf SOAP berhasil disimpan.');
    }

    public function generate(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        $kunjungan->load(['pemeriksaanAwal', 'wawancara']);
        $transcript = $kunjungan->wawancara?->transkrip ?: '';

        if (! filled($transcript)) {
            return back()->with('warning', 'Draf SOAP tidak dapat dibuat tanpa transkrip wawancara yang sudah ditinjau.');
        }

        if (! $kunjungan->wawancara?->transcript_reviewed_at) {
            return back()->with('warning', 'Tinjau dan simpan transkrip serta label speaker sebelum membuat draf SOAP.');
        }

        try {
            $generated = $this->soapGenerationService->generate($kunjungan, $transcript);
            DrafSoap::updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                array_merge($generated, [
                    'id_pengguna' => $kunjungan->id_dokter,
                    ...$this->sourceMetadata($kunjungan),
                ])
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('warning', $exception->getMessage());
        }

        return back()->with('status', 'Draf SOAP berhasil dibuat ulang menggunakan Nemotron melalui OpenRouter.');
    }

    public function verify(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        if (! $this->transcriptIsReviewed($kunjungan)) {
            return back()->with('warning', 'Tinjau dan simpan transkrip sebelum memverifikasi SOAP.');
        }
        $validated = $this->validateDraft($request);

        DB::transaction(function () use ($validated, $kunjungan, $request) {
            $kunjungan->pasien()->update([
                'no_rekam_medis' => $validated['no_rekam_medis'],
                'nama_pasien' => $validated['nama_pasien'],
                'tanggal_lahir' => $validated['tanggal_lahir'] ?: null,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?: null,
            ]);
            $kunjungan->drafSoap()->updateOrCreate([], array_merge(
                ['id_pengguna' => $kunjungan->id_dokter],
                collect($validated)->only(['subjective_text', 'objective_text', 'assessment_text', 'plan_text', 'kode_icd10'])->all(),
                $this->sourceMetadata($kunjungan)
            ));
            Finalisasi::updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                ['id_dokter' => $request->user()->id_pengguna, 'status' => 'Terverifikasi', 'FINAL' => true]
            );
            $kunjungan->update(['status' => 'SOAP Terverifikasi']);
        });

        return back()->with('status', 'Draf SOAP berhasil diverifikasi oleh dokter.');
    }

    private function validateDraft(Request $request): array
    {
        return $request->validate([
            'no_rekam_medis' => ['required', 'string', 'max:30'],
            'nama_pasien' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'subjective_text' => ['required', 'string'],
            'objective_text' => ['required', 'string'],
            'assessment_text' => ['required', 'string'],
            'plan_text' => ['required', 'string'],
            'kode_icd10' => ['nullable', 'string', 'max:255'],
        ], [
            '*.required' => 'Field ini wajib diisi.',
        ]);
    }

    private function sourceMetadata(Kunjungan $kunjungan): array
    {
        $transcript = $kunjungan->wawancara?->transkrip ?: '';

        return [
            'source_transcript_hash' => hash('sha256', $transcript),
            'source_ttv_snapshot' => $this->draftService->vitalsSnapshot($kunjungan->pemeriksaanAwal),
            'needs_review' => false,
            'source_type' => 'local_reviewed_transcript',
        ];
    }

    private function transcriptIsReviewed(Kunjungan $kunjungan): bool
    {
        $wawancara = $kunjungan->relationLoaded('wawancara')
            ? $kunjungan->wawancara
            : $kunjungan->wawancara()->first();

        return $wawancara !== null
            && filled($wawancara->transkrip)
            && $wawancara->transcript_reviewed_at !== null;
    }

    private function authorizeVisit(Kunjungan $kunjungan): void
    {
        $userId = (int) auth()->user()->id_pengguna;

        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);

            return;
        }

        abort_unless((int) $kunjungan->id_dokter === $userId, 403, 'Kunjungan ini ditugaskan kepada dokter lain.');
    }
}

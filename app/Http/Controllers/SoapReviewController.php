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

class SoapReviewController extends Controller
{
    public function __construct(
        private MockSoapDraftService $draftService,
        private SoapGenerationService $soapGenerationService,
    ) {}

    public function edit(Kunjungan $kunjungan): View
    {
        $kunjungan->load(['pasien', 'dokter', 'pemeriksaanAwal', 'drafSoap', 'finalisasi', 'wawancara']);
        $transcript = $kunjungan->wawancara?->transkrip ?: MockSoapDraftService::TRANSCRIPT;
        $sourceHash = hash('sha256', $transcript);
        $ttvSnapshot = $this->draftService->vitalsSnapshot($kunjungan->pemeriksaanAwal);
        $draf = $kunjungan->drafSoap;

        if (! $draf) {
            $generated = $this->soapGenerationService->generate($kunjungan, $transcript);
            $draf = DrafSoap::create(array_merge($generated, [
                'id_kunjungan' => $kunjungan->id_kunjungan,
                'id_pengguna' => $kunjungan->id_dokter,
                'source_transcript_hash' => $sourceHash,
                'source_ttv_snapshot' => $ttvSnapshot,
                'needs_review' => false,
            ]));
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
        ]);
    }

    public function save(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
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

    public function verify(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
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
        $transcript = $kunjungan->wawancara?->transkrip ?: MockSoapDraftService::TRANSCRIPT;

        return [
            'source_transcript_hash' => hash('sha256', $transcript),
            'source_ttv_snapshot' => $this->draftService->vitalsSnapshot($kunjungan->pemeriksaanAwal),
            'needs_review' => false,
            'source_type' => $kunjungan->wawancara?->transkrip ? 'manual_simulation' : 'legacy_simulation',
        ];
    }
}

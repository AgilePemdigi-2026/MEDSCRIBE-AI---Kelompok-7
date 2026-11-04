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
use Throwable;

class SoapReviewController extends Controller
{
    public function __construct(
        private MockSoapDraftService $draftService,
        private SoapGenerationService $soapGenerationService,
    ) {}

    /**
     * Review SOAP kini menjadi bagian halaman rekam medis; URL lama diarahkan ke sana.
     */
    public function edit(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);

        if (! $kunjungan->id_antrean) {
            return redirect()->route('dokter.dashboard')
                ->with('warning', 'Kunjungan ini tidak terhubung ke antrean pasien.');
        }

        return redirect()->to(route('dokter.rekam-medis', $kunjungan->id_antrean).'#soap');
    }

    public function save(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        if (! $this->transcriptIsReviewed($kunjungan)) {
            return back()->withFragment('soap')->with('warning', 'Tinjau dan simpan transkrip sebelum menyimpan SOAP.');
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
            Finalisasi::where('id_kunjungan', $kunjungan->id_kunjungan)->update([
                'status' => 'Perlu Ditinjau',
                'FINAL' => false,
            ]);
            $kunjungan->update(['status' => Kunjungan::STATUS_SOAP_BELUM_DIVERIFIKASI]);
        });

        return back()->withFragment('soap')->with('status', 'Perubahan draf SOAP berhasil disimpan.');
    }

    public function generate(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        $kunjungan->load(['pemeriksaanAwal', 'wawancara']);
        $transcript = $kunjungan->wawancara?->transkrip ?: '';

        if (! filled($transcript)) {
            return back()->withFragment('soap')->with('warning', 'Draf SOAP tidak dapat dibuat tanpa transkrip wawancara yang sudah ditinjau.');
        }

        if (! $kunjungan->wawancara?->transcript_reviewed_at) {
            return back()->withFragment('soap')->with('warning', 'Tinjau dan simpan transkrip serta label speaker sebelum membuat draf SOAP.');
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

            return back()->withFragment('soap')->with('warning', $exception->getMessage());
        }

        return back()->withFragment('soap')->with('status', 'Draf SOAP berhasil dibuat ulang menggunakan Nemotron melalui OpenRouter.');
    }

    public function verify(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        if (! $this->transcriptIsReviewed($kunjungan)) {
            return back()->withFragment('soap')->with('warning', 'Tinjau dan simpan transkrip sebelum memverifikasi SOAP.');
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
                ['id_dokter' => $request->user()->id_pengguna, 'status' => 'Terverifikasi', 'FINAL' => false]
            );

            $tindakLanjut = $kunjungan->tindakLanjut()->first();
            $kunjungan->update([
                'status' => $tindakLanjut?->statusKunjungan() ?? Kunjungan::STATUS_SOAP_TERVERIFIKASI,
            ]);
        });

        return back()->withFragment('soap')->with('status', 'Draf SOAP berhasil diverifikasi oleh dokter.');
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
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->hospital_id) {
            $targetHospitalId = $kunjungan->hospital_id ?? $kunjungan->pasien?->hospital_id;
            if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain.');
            }
        }

        $userId = (int) $user->id_pengguna;

        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);

            return;
        }

        abort_unless((int) $kunjungan->id_dokter === $userId, 403, 'Kunjungan ini ditugaskan kepada dokter lain.');
    }
}

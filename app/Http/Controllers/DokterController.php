<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\ParameterLoinc;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use App\Services\AudioProbeException;
use App\Services\InterviewAudioUploadException;
use App\Services\InterviewAudioUploadService;
use App\Services\SoapDraftPreparer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DokterController extends Controller
{
    /**
     * Menampilkan Halaman Rekam Medis Pasien untuk Dokter Poliklinik.
     * Mengintegrasikan data pasien, TTV Nurse Station, perekaman audio wawancara klinis,
     * serta menerapkan pembatasan isolasi data rumah sakit (Multi-Tenancy).
     */
    public function showRekamMedis(Request $request, int $id_antrean, SoapDraftPreparer $soapDraftPreparer): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isDokter()) {
            abort(403, 'Akses ditolak: Hanya dokter poliklinik yang berwenang membuka rekam medis.');
        }

        // 1. Ambil antrean dan data pasien terkait
        $antrean = Antrean::with(['pasien', 'hospital'])->findOrFail($id_antrean);

        // Validasi batasan rumah sakit: Dokter hanya dapat mengakses pasien rumah sakitnya
        $this->authorizeHospitalAccess($user, $antrean);

        if (! $antrean->pasien) {
            return redirect()
                ->route('dokter.dashboard')
                ->with('warning', 'Data pasien tidak ditemukan atau telah dibatalkan.');
        }

        // 2. Hubungkan atau buat kunjungan aktif untuk antrean ini
        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $antrean->id_antrean],
            [
                'hospital_id' => $user->hospital_id ?? $antrean->hospital_id,
                'id_pasien' => $antrean->id_pasien,
                'id_dokter' => $user->id_pengguna,
                'tanggal' => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                'status' => 'Sedang Diperiksa',
            ]
        );

        $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

        // Jika dokter atau hospital_id belum terasosiasi, perbarui
        $updated = false;
        if (! $kunjungan->id_dokter) {
            $kunjungan->id_dokter = $user->id_pengguna;
            $updated = true;
        }
        if (! $kunjungan->hospital_id && $user->hospital_id) {
            $kunjungan->hospital_id = $user->hospital_id;
            $updated = true;
        }
        if ($updated) {
            $kunjungan->save();
        }

        if ($antrean->status === 'Siap Periksa') {
            $antrean->update(['status' => 'Sedang Diperiksa']);
            $kunjungan->update(['status' => 'Sedang Diperiksa']);
        }

        // 3. Ambil data pemeriksaan awal & TTV dari perawat
        $pemeriksaanAwal = PemeriksaanAwal::with('perawat')
            ->where('id_kunjungan', $kunjungan->id_kunjungan)
            ->first();

        // 4. Ambil rekaman audio wawancara klinis terakhir
        $rekamanAudio = RekamanAudio::where('id_kunjungan', $kunjungan->id_kunjungan)
            ->latest('id_rekaman')
            ->first();
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
        $workflowState = $this->workflowState($kunjungan, $pemeriksaanAwal, $wawancara);

        // 5. Draf SOAP baru disiapkan setelah transkrip ditinjau, agar draf kosong tidak terbentuk lebih awal
        $soap = null;
        if (in_array($workflowState, ['soap-ready', 'soap', 'completed'], true)) {
            $kunjungan->setRelation('pemeriksaanAwal', $pemeriksaanAwal);
            $kunjungan->setRelation('wawancara', $wawancara);
            $soap = $soapDraftPreparer->prepare($kunjungan);
        }
        $finalisasi = $kunjungan->finalisasi()->first();
        $tindakLanjut = $kunjungan->tindakLanjut()
            ->with(['eResep', 'rujukanLab.parameter'])
            ->first();
        $parameterLoinc = ParameterLoinc::query()
            ->where('is_active', true)
            ->orderBy('nama_pemeriksaan')
            ->get();

        $tahapTindakLanjut = match (true) {
            $tindakLanjut !== null => 'done',
            $finalisasi?->isLocked() === true => 'locked',
            $workflowState !== 'completed' => 'locked',
            default => 'active',
        };

        // 6. Format kode kunjungan medis spesifik rumah sakit
        $tglStr = str_replace('-', '', $kunjungan->tanggal ?: date('Ymd'));
        $prefix = $user->hospital?->hospital_code ? 'RS'.$user->hospital->hospital_code : 'RSUA';
        $idKunjunganCode = $prefix.'-'.$tglStr.'-'.str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT);

        $pasien = $antrean->pasien;
        $umur = $pasien->umur ?: ($pasien->tanggal_lahir ? Carbon::parse($pasien->tanggal_lahir)->age : null);

        return view('dashboard.rekam-medis', compact(
            'user',
            'antrean',
            'pasien',
            'umur',
            'kunjungan',
            'pemeriksaanAwal',
            'rekamanAudio',
            'wawancara',
            'workflowState',
            'idKunjunganCode',
            'soap',
            'finalisasi',
            'tindakLanjut',
            'tahapTindakLanjut',
            'parameterLoinc'
        ));
    }

    /**
     * Memproses upload berkas audio wawancara klinis dari browser
     * dan menyimpannya secara aman pada penyimpanan server.
     * Audio diproses oleh queue STT lokal setelah upload.
     */
    public function uploadAudio(Request $request, int $id_antrean): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isDokter()) {
            return response()->json([
                'success' => false,
                'message' => 'Otorisasi ditolak: Hanya dokter yang berhak mengunggah rekaman klinis.',
            ], 403);
        }

        $antrean = Antrean::with('pasien')->findOrFail($id_antrean);
        $this->authorizeHospitalAccess($user, $antrean);

        $kunjungan = DB::transaction(function () use ($antrean, $user): Kunjungan {
            // Serialize legacy visit creation before the upload service locks the visit row.
            $lockedAntrean = Antrean::whereKey($antrean->id_antrean)->lockForUpdate()->firstOrFail();
            $kunjungan = Kunjungan::firstOrCreate(
                ['id_antrean' => $lockedAntrean->id_antrean],
                [
                    'hospital_id' => $user->hospital_id ?? $lockedAntrean->hospital_id,
                    'id_pasien' => $lockedAntrean->id_pasien,
                    'id_dokter' => $user->id_pengguna,
                    'tanggal' => $lockedAntrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                    'status' => 'Sedang Diperiksa',
                ]
            );
            $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

            return $kunjungan->fresh();
        });

        $request->validate([
            'audio' => [
                'required', 'file', 'max:'.config('medscribe.audio.max_size_kb'),
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a',
            ],
        ]);
        $file = $request->file('audio');
        abort_if(($file->getSize() ?? 0) <= 0, 422, 'Berkas audio kosong.');

        try {
            $result = app(InterviewAudioUploadService::class)->upload($kunjungan, $file, false);
        } catch (AudioProbeException|InterviewAudioUploadException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], $exception->statusCode);
        } catch (\Throwable) {
            Log::warning('Clinical audio upload failed.', ['queue' => true]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan rekaman audio. Periksa format dan ukuran file, lalu coba lagi.',
            ], 500);
        }

        $recording = $result['recording'];
        $isExisting = $result['outcome'] === 'existing';

        return response()->json([
            'success' => true,
            'message' => $isExisting
                ? 'Rekaman ini sedang diproses. Tidak dibuat rekaman baru.'
                : 'Rekaman disimpan dan diantrikan ke service transkripsi lokal.',
            'audio_id' => $recording->id_rekaman,
            'status' => $recording->status === 'transcribing' ? 'transcribing' : 'queued',
            'audio_stream' => route('dokter.rekam-medis.stream-audio', $antrean->id_antrean),
        ], 202);
    }

    /**
     * Mengalirkan (streaming) berkas audio wawancara klinis secara aman
     * untuk diperdengarkan kembali oleh dokter pemeriksa di browser.
     */
    public function streamAudio(Request $request, int $id_antrean): BinaryFileResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isDokter()) {
            abort(403, 'Akses ditolak.');
        }

        $antrean = Antrean::with('pasien')->findOrFail($id_antrean);
        $this->authorizeHospitalAccess($user, $antrean);

        $kunjungan = Kunjungan::where('id_antrean', $antrean->id_antrean)->firstOrFail();
        $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

        $rekaman = RekamanAudio::where('id_kunjungan', $kunjungan->id_kunjungan)
            ->latest('id_rekaman')
            ->firstOrFail();

        $fullPath = $this->resolveAudioPath($rekaman->path_audio);

        if (! $fullPath) {
            abort(404, 'Berkas audio tidak ditemukan.');
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'ogg' => 'audio/ogg',
            'mp3' => 'audio/mp3',
            'wav' => 'audio/wav',
            'mp4', 'm4a' => 'audio/mp4',
            default => 'audio/webm',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($fullPath).'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Memvalidasi otorisasi data multi-tenancy: dokter hanya dapat mengakses data rumah sakitnya.
     */
    protected function authorizeHospitalAccess(Pengguna $user, Antrean $antrean): void
    {
        if (! $user->hospital_id) {
            return; // Akun legacy tanpa pembatasan rumah sakit spesifik
        }

        $targetHospitalId = $antrean->hospital_id ?? $antrean->pasien?->hospital_id;

        if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain.');
        }
    }

    /**
     * Helper untuk menyelesaikan lokasi path fisik berkas audio pada filesystem server
     */
    private function resolveAudioPath(string $relativePath): ?string
    {
        try {
            $pathDisk = Storage::disk('local')->path($relativePath);
            if (file_exists($pathDisk)) {
                return $pathDisk;
            }
        } catch (\Throwable) {
        }

        $path1 = storage_path('app/'.$relativePath);
        if (file_exists($path1)) {
            return $path1;
        }

        $path2 = storage_path('app/private/'.$relativePath);
        if (file_exists($path2)) {
            return $path2;
        }

        return null;
    }

    private function authorizeKunjungan(Kunjungan $kunjungan, int $userId): void
    {
        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);

            return;
        }

        abort_unless((int) $kunjungan->id_dokter === $userId, 403, 'Kunjungan ini ditugaskan kepada dokter lain.');
    }

    private function workflowState(Kunjungan $kunjungan, ?PemeriksaanAwal $pemeriksaanAwal, ?Wawancara $wawancara): string
    {
        if ($kunjungan->isSoapVerified()) {
            return 'completed';
        }

        if ($wawancara?->transcript_reviewed_at && $wawancara->status === 'Wawancara Selesai') {
            return 'soap';
        }

        if ($wawancara?->transcript_reviewed_at) {
            return 'soap-ready';
        }

        if ($wawancara?->processing_status === 'completed' && filled($wawancara->transkrip)) {
            return 'transcript';
        }

        if ($wawancara?->processing_status === 'failed') {
            return 'failed';
        }

        if (in_array($wawancara?->processing_status, ['queued', 'transcribing'], true)) {
            return 'processing';
        }

        if ($wawancara?->status === 'Wawancara Berlangsung') {
            return 'recording';
        }

        return $pemeriksaanAwal ? 'interview' : 'ttv';
    }
}

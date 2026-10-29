<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInterviewAudio;
use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\PemeriksaanAwal;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class DokterController extends Controller
{
    /**
     * Menampilkan Halaman Rekam Medis Pasien untuk Dokter Poliklinik.
     * Mengintegrasikan data pasien, TTV Nurse Station, perekaman audio wawancara klinis,
     * serta penelaahan draf catatan medis SOAP.
     */
    public function showRekamMedis(Request $request, int $id_antrean): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isDokter()) {
            abort(403, 'Akses ditolak: Hanya dokter poliklinik yang berwenang membuka rekam medis.');
        }

        // 1. Ambil antrean dan data pasien terkait
        $antrean = Antrean::with('pasien')->findOrFail($id_antrean);

        if (! $antrean->pasien) {
            return redirect()
                ->route('dokter.dashboard')
                ->with('warning', 'Data pasien tidak ditemukan atau telah dibatalkan.');
        }

        // 2. Hubungkan atau buat kunjungan aktif untuk antrean ini
        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $antrean->id_antrean],
            [
                'id_pasien' => $antrean->id_pasien,
                'id_dokter' => $user->id_pengguna,
                'tanggal' => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                'status' => 'Sedang Diperiksa',
            ]
        );

        $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

        // Jika dokter belum terasosiasi atau status masih awal, perbarui
        if (! $kunjungan->id_dokter) {
            $kunjungan->id_dokter = $user->id_pengguna;
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

        // Fallback jika belum terhubung via id_kunjungan tapi ada di hari yang sama
        if (! $pemeriksaanAwal) {
            $pemeriksaanAwal = PemeriksaanAwal::with('perawat')
                ->whereHas('kunjungan', function ($q) use ($antrean) {
                    $q->where('id_pasien', $antrean->id_pasien);
                })
                ->latest('id_pemeriksaan')
                ->first();
        }

        // 4. Ambil rekaman audio wawancara klinis terakhir
        $rekamanAudio = RekamanAudio::where('id_kunjungan', $kunjungan->id_kunjungan)
            ->latest('id_rekaman')
            ->first();
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
        $workflowState = $this->workflowState($kunjungan, $pemeriksaanAwal, $wawancara);

        // 5. Format kode kunjungan medis
        $tglStr = str_replace('-', '', $kunjungan->tanggal ?: date('Ymd'));
        $idKunjunganCode = 'RSUA-'.$tglStr.'-'.str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT);

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
            'idKunjunganCode'
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

        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $antrean->id_antrean],
            [
                'id_pasien' => $antrean->id_pasien,
                'id_dokter' => $user->id_pengguna,
                'tanggal' => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                'status' => 'Sedang Diperiksa',
            ]
        );
        $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

        $interview = Wawancara::firstOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            ['status' => 'Wawancara Berlangsung', 'jawaban' => []]
        );

        $request->validate([
            'audio' => [
                'required', 'file', 'max:'.config('medscribe.audio.max_size_kb'),
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a',
            ],
        ]);
        $file = $request->file('audio');
        abort_if(($file->getSize() ?? 0) <= 0, 422, 'Berkas audio kosong.');
        $duration = $this->probeDuration($file->getRealPath());
        abort_if($duration !== null && $duration > config('medscribe.audio.max_duration_seconds'), 422, 'Durasi audio melebihi batas konfigurasi.');

        try {
            DB::beginTransaction();

            $path = $file->store('interviews/'.$kunjungan->id_kunjungan, 'local');
            $rekaman = RekamanAudio::create([
                'id_kunjungan' => $kunjungan->id_kunjungan,
                'id_wawancara' => $interview->id_wawancara,
                'path_audio' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'duration_seconds' => $duration,
                'status' => 'queued',
            ]);
            $interview->update(['processing_status' => 'queued', 'processing_error' => null]);
            ProcessInterviewAudio::dispatch($rekaman->id_rekaman);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rekaman disimpan dan diantrikan ke service transkripsi lokal.',
                'audio_id' => $rekaman->id_rekaman,
                'status' => 'queued',
                'audio_stream' => route('dokter.rekam-medis.stream-audio', $antrean->id_antrean),
            ], 202);

        } catch (\Throwable) {
            DB::rollBack();
            Log::warning('Clinical audio upload failed.', ['queue' => true]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan rekaman audio. Periksa format dan ukuran file, lalu coba lagi.',
            ], 500);
        }
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

        $antrean = Antrean::findOrFail($id_antrean);
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

    private function probeDuration(?string $path): ?int
    {
        if (! $path || ! is_file($path)) {
            Log::warning('Audio duration probe could not access temporary upload.', ['path' => $path]);
            abort(422, 'Berkas audio sementara tidak dapat diakses untuk validasi durasi.');
        }

        $probe = config('medscribe.audio.ffprobe_path', 'ffprobe');
        if ((str_contains($probe, '/') || str_contains($probe, '\\') || str_ends_with(strtolower($probe), '.exe')) && ! is_file($probe)) {
            Log::warning('Configured ffprobe executable does not exist.', ['probe' => $probe]);
            abort(503, 'ffprobe tidak dapat dijalankan untuk validasi durasi audio.');
        }

        try {
            $process = new Process([
                $probe, '-v', 'error', '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1', $path,
            ]);
            $process->setTimeout(15);
            $process->run();
        } catch (\Throwable $exception) {
            Log::warning('ffprobe could not be started for audio duration validation.', [
                'path' => $path,
                'probe' => $probe,
                'exception' => $exception->getMessage(),
            ]);
            abort(503, 'ffprobe tidak dapat dijalankan untuk validasi durasi audio.');
        }

        $duration = trim($process->getOutput());

        if (! $process->isSuccessful()) {
            Log::warning('ffprobe failed to read uploaded audio.', [
                'path' => $path,
                'exit_code' => $process->getExitCode(),
                'stderr' => trim($process->getErrorOutput()),
            ]);
            abort(422, 'ffprobe gagal membaca berkas audio. Pastikan format audio valid.');
        }

        if (! is_numeric($duration)) {
            Log::warning('ffprobe returned an invalid audio duration.', [
                'path' => $path,
                'stdout' => $duration,
            ]);
            abort(422, 'ffprobe mengembalikan durasi audio yang tidak valid.');
        }

        return (int) ceil((float) $duration);
    }

    private function workflowState(Kunjungan $kunjungan, ?PemeriksaanAwal $pemeriksaanAwal, ?Wawancara $wawancara): string
    {
        if ($kunjungan->status === 'SOAP Terverifikasi') {
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

        if (in_array($wawancara?->processing_status, ['queued', 'transcribing'], true)) {
            return 'processing';
        }

        if ($wawancara?->status === 'Wawancara Berlangsung') {
            return 'recording';
        }

        return $pemeriksaanAwal ? 'interview' : 'ttv';
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
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
    public function showRekamMedis(Request $request, int $id_antrean): View|RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isDokter()) {
            abort(403, 'Akses ditolak: Hanya dokter poliklinik yang berwenang membuka rekam medis.');
        }

        // 1. Ambil antrean dan data pasien terkait
        $antrean = Antrean::with(['pasien', 'hospital'])->findOrFail($id_antrean);

        // Validasi batasan rumah sakit: Dokter hanya dapat mengakses pasien rumah sakitnya
        $this->authorizeHospitalAccess($user, $antrean);

        if (!$antrean->pasien) {
            return redirect()
                ->route('dokter.dashboard')
                ->with('warning', 'Data pasien tidak ditemukan atau telah dibatalkan.');
        }

        // 2. Hubungkan atau buat kunjungan aktif untuk antrean ini
        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $antrean->id_antrean],
            [
                'hospital_id' => $user->hospital_id ?? $antrean->hospital_id,
                'id_pasien'   => $antrean->id_pasien,
                'id_dokter'   => $user->id_pengguna,
                'tanggal'     => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                'status'      => 'Sedang Diperiksa',
            ]
        );

        // Jika dokter atau hospital_id belum terasosiasi, perbarui
        $updated = false;
        if (!$kunjungan->id_dokter) {
            $kunjungan->id_dokter = $user->id_pengguna;
            $updated = true;
        }
        if (!$kunjungan->hospital_id && $user->hospital_id) {
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

        // Fallback jika belum terhubung via id_kunjungan tapi ada di hari yang sama
        if (!$pemeriksaanAwal) {
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

        // 5. Format kode kunjungan medis spesifik rumah sakit
        $tglStr = str_replace('-', '', $kunjungan->tanggal ?: date('Ymd'));
        $prefix = $user->hospital?->hospital_code ? 'RS' . $user->hospital->hospital_code : 'RSUA';
        $idKunjunganCode = $prefix . '-' . $tglStr . '-' . str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT);

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
            'idKunjunganCode'
        ));
    }

    /**
     * Memproses upload berkas audio wawancara klinis dari browser
     * dan menyimpannya secara aman pada penyimpanan server.
     */
    public function uploadAudio(Request $request, int $id_antrean): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isDokter()) {
            return response()->json([
                'success' => false,
                'message' => 'Otorisasi ditolak: Hanya dokter yang berhak mengunggah rekaman klinis.',
            ], 403);
        }

        $antrean = Antrean::with('pasien')->findOrFail($id_antrean);
        $this->authorizeHospitalAccess($user, $antrean);

        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $antrean->id_antrean],
            [
                'hospital_id' => $user->hospital_id ?? $antrean->hospital_id,
                'id_pasien'   => $antrean->id_pasien,
                'id_dokter'   => $user->id_pengguna,
                'tanggal'     => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                'status'      => 'Sedang Diperiksa',
            ]
        );

        // Validasi berkas audio
        $request->validate([
            'audio' => ['required', 'file', 'max:35840'], // Max 35MB
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('audio');
            $originalExt = strtolower($file->getClientOriginalExtension());
            $ext = in_array($originalExt, ['webm', 'ogg', 'wav', 'mp3', 'mp4', 'm4a']) ? $originalExt : 'webm';
            $filename = 'rekaman_kunjungan_' . $kunjungan->id_kunjungan . '_' . time() . '_' . uniqid() . '.' . $ext;

            // Simpan pada private local disk (storage/app/audio_rekaman)
            $path = $file->storeAs('audio_rekaman', $filename, 'local');

            // Simpan entitas rekaman audio
            $rekaman = RekamanAudio::create([
                'id_kunjungan'  => $kunjungan->id_kunjungan,
                'path_audio'    => $path,
                'status'        => 'Tersimpan',
                'waktu_selesai' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Rekaman wawancara klinis berhasil disimpan di server.',
                'audio_id'     => $rekaman->id_rekaman,
                'audio_stream' => route('dokter.rekam-medis.stream-audio', $antrean->id_antrean),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error uploading audio in DokterController::uploadAudio', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah dan menyimpan rekaman: ' . $e->getMessage(),
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
        if (!$user || !$user->isDokter()) {
            abort(403, 'Akses ditolak.');
        }

        $antrean = Antrean::with('pasien')->findOrFail($id_antrean);
        $this->authorizeHospitalAccess($user, $antrean);

        $kunjungan = Kunjungan::where('id_antrean', $antrean->id_antrean)->firstOrFail();

        $rekaman = RekamanAudio::where('id_kunjungan', $kunjungan->id_kunjungan)
            ->latest('id_rekaman')
            ->firstOrFail();

        $fullPath = $this->resolveAudioPath($rekaman->path_audio);

        if (!$fullPath) {
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
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            'Accept-Ranges'       => 'bytes',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Memvalidasi otorisasi data multi-tenancy: dokter hanya dapat mengakses data rumah sakitnya.
     */
    protected function authorizeHospitalAccess(Pengguna $user, Antrean $antrean): void
    {
        if (!$user->hospital_id) {
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
        } catch (\Throwable) {}

        $path1 = storage_path('app/' . $relativePath);
        if (file_exists($path1)) {
            return $path1;
        }

        $path2 = storage_path('app/private/' . $relativePath);
        if (file_exists($path2)) {
            return $path2;
        }

        return null;
    }
}

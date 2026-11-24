<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\ParameterLoinc;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use App\Services\Icd10Catalog;
use App\Services\SoapDraftPreparer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // 2. Antrean yang sudah selesai/dibatalkan tanpa kunjungan tidak punya berkas.
        //    Jangan membuat kunjungan baru dari sini (dulu "Lihat Berkas" membuat kunjungan kosong).
        if (in_array($antrean->status, ['Selesai', 'Dibatalkan'], true)
            && ! Kunjungan::where('id_antrean', $antrean->id_antrean)->exists()) {
            return redirect()
                ->route('dokter.dashboard')
                ->with('warning', 'Antrean ini sudah ' . strtolower($antrean->status) . ' dan tidak memiliki berkas rekam medis.');
        }

        // 3. Hubungkan atau buat kunjungan aktif untuk antrean ini
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

        $tahapFinalisasi = match (true) {
            $kunjungan->isFinalized() => 'done',
            $kunjungan->isSoapVerified() => 'active',
            default => 'locked',
        };

        // 6. Format kode kunjungan medis spesifik rumah sakit
        $tglStr = str_replace('-', '', $kunjungan->tanggal ?: date('Ymd'));
        $prefix = $user->hospital?->hospital_code ? 'RS'.$user->hospital->hospital_code : 'RSUA';
        $idKunjunganCode = $prefix.'-'.$tglStr.'-'.str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT);

        $pasien = $antrean->pasien;
        $umur = $pasien->usia_label;

        // Kunjungan lain pasien ini, sebagai riwayat singkat di samping pemeriksaan.
        $riwayatQuery = Kunjungan::where('id_pasien', $pasien->id_pasien)
            ->where('id_kunjungan', '!=', $kunjungan->id_kunjungan);
        $jumlahRiwayat = (clone $riwayatQuery)->count();
        $riwayatKunjungan = $riwayatQuery
            ->with(['drafSoap', 'finalisasi'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_kunjungan')
            ->limit(5)
            ->get();

        // 7. Katalog ICD-10: saran AI, label kode terpilih, dan sumber isian
        $icd10Catalog = app(Icd10Catalog::class);
        $drafSoap = $soap['draf'] ?? null;
        $saranIcd10 = $icd10Catalog->normalisasiSaran($drafSoap?->icd10_suggestions ?? []);
        $kodeIc10 = $drafSoap?->kode_icd10;
        $icd10Label = $icd10Catalog->label($kodeIc10, $saranIcd10);
        $icd10LabelResmi = $icd10Catalog->labelResmi($kodeIc10, $saranIcd10);
        $icd10Ic10 = filled($kodeIc10) && $saranIcd10 !== [] && $saranIcd10[0]['code'] === $kodeIc10;

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
            'tahapFinalisasi',
            'parameterLoinc',
            'saranIcd10',
            'icd10Label',
            'icd10LabelResmi',
            'icd10Ic10',
            'riwayatKunjungan',
            'jumlahRiwayat'
        ));
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

    /**
     * Memvalidasi persetujuan akhir dan memfinalisasi rekam medis secara permanen.
     * Mengubah status rekam medis menjadi 'Final' dan mengunci seluruh perubahan klinis.
     */
    public function finalizeRekamMedis(Request $request, Kunjungan $kunjungan): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isDokter()) {
            abort(403, 'Akses ditolak: Hanya dokter poliklinik yang berwenang memfinalisasi rekam medis.');
        }

        // Cek RS dulu: authorizeKunjungan() bisa menetapkan dokter ke kunjungan yang belum punya dokter,
        // jadi tidak boleh terjadi untuk kunjungan RS lain.
        if ($user->hospital_id) {
            $targetHospitalId = $kunjungan->hospital_id ?? $kunjungan->pasien?->hospital_id;
            if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk memfinalisasi rekam medis dari rumah sakit lain.');
            }
        }

        $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

        if ($kunjungan->isFinalized()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Rekam medis ini sudah berstatus Final.'], 409);
            }

            return redirect()
                ->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])
                ->with('info', 'Rekam medis ini sudah difinalisasi.');
        }

        $request->validate([
            'konfirmasi_persetujuan' => ['accepted'],
            'catatan_finalisasi' => ['nullable', 'string', 'max:1000'],
        ], [
            'konfirmasi_persetujuan.accepted' => 'Anda wajib mencentang pernyataan persetujuan dan verifikasi sebelum memfinalisasi rekam medis.',
        ]);

        // 1. Validasi Persyaratan A: Draf SOAP Terverifikasi & Bagian Lengkap
        $draf = $kunjungan->drafSoap;
        if (! $kunjungan->isSoapVerified() || ! $draf) {
            $msg = 'Draf SOAP wajib diverifikasi oleh dokter terlebih dahulu sebelum finalisasi.';
            if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $msg], 422);
            return redirect()->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])->with('error', $msg);
        }

        if (empty(trim($draf->subjective_text ?? '')) || empty(trim($draf->objective_text ?? '')) || empty(trim($draf->assessment_text ?? '')) || empty(trim($draf->plan_text ?? ''))) {
            $msg = 'Seluruh bagian draf SOAP (Subjective, Objective, Assessment, Plan) harus terisi lengkap sebelum finalisasi.';
            if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $msg], 422);
            return redirect()->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])->with('error', $msg);
        }

        // 2. Validasi Persyaratan B: TTV & Catatan Perawat
        $pemeriksaanAwal = $kunjungan->pemeriksaanAwal;
        if (! $pemeriksaanAwal || empty($pemeriksaanAwal->tekanan_darah) || ! $pemeriksaanAwal->nadi || ! $pemeriksaanAwal->suhu_tubuh) {
            $msg = 'Data pemeriksaan awal TTV dan catatan perawat belum lengkap pada kunjungan ini.';
            if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $msg], 422);
            return redirect()->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])->with('error', $msg);
        }

        // 3. Validasi Persyaratan C: Transkripsi Wawancara Klinis Terverifikasi
        $wawancara = $kunjungan->wawancara;
        if (! $wawancara || empty(trim($wawancara->transkrip ?? '')) || ! $wawancara->transcript_reviewed_at) {
            $msg = 'Transkripsi wawancara klinis wajib diverifikasi terlebih dahulu sebelum finalisasi.';
            if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $msg], 422);
            return redirect()->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])->with('error', $msg);
        }

        // 4. Validasi Persyaratan D: Tindak Lanjut Medis Tersimpan
        if (! $kunjungan->tindakLanjut) {
            $msg = 'Tindak lanjut medis (resep / rujukan laboratorium / selesai tanpa tindakan) wajib disimpan terlebih dahulu.';
            if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $msg], 422);
            return redirect()->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])->with('error', $msg);
        }

        DB::transaction(function () use ($kunjungan, $user, $request, $pemeriksaanAwal) {
            // Nomor izin dibekukan saat final: dokumen tidak ikut berubah kalau profil berubah atau
            // dokter/perawat dimutasi. Perawat yang sudah pindah RS diambil dari riwayat izinnya di RS ini.
            $hospitalId = $kunjungan->hospital_id ?? $kunjungan->pasien?->hospital_id;

            $kunjungan->finalisasi()->updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                [
                    'id_dokter' => $user->id_pengguna,
                    'status' => 'Final',
                    'FINAL' => true,
                    'finalized_at' => now(),
                    'finalized_by' => $user->id_pengguna,
                    'catatan_finalisasi' => $request->input('catatan_finalisasi'),
                    'sip_dokter' => $user->nomorIzinDi($hospitalId),
                    'sipp_perawat' => $pemeriksaanAwal->perawat?->nomorIzinDi($hospitalId),
                ]
            );

            $kunjungan->update(['status' => Kunjungan::STATUS_FINALISASI]);

            if ($kunjungan->antrean) {
                $kunjungan->antrean->update(['status' => 'Selesai']);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Rekam medis berhasil disetujui dan difinalisasi secara permanen.',
                'redirect_url' => route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean]) . '#finalisasi',
            ]);
        }

        return redirect()
            ->route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean])
            ->with('status', 'Rekam medis berhasil disetujui dan difinalisasi.');
    }

    /**
     * Menampilkan Pratinjau Dokumen SOAP Final dalam format cetak A4 Portrait.
     * Hanya dapat diakses setelah rekam medis berhasil difinalisasi.
     */
    public function previewSoap(Request $request, Kunjungan $kunjungan): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isDokter()) {
            abort(403, 'Akses ditolak: Hanya dokter poliklinik yang berwenang melihat preview rekam medis.');
        }

        // Cek RS dulu: authorizeKunjungan() bisa menetapkan dokter ke kunjungan yang belum punya dokter,
        // jadi tidak boleh terjadi untuk kunjungan RS lain.
        if ($user->hospital_id) {
            $targetHospitalId = $kunjungan->hospital_id ?? $kunjungan->pasien?->hospital_id;
            if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain.');
            }
        }

        // Rekam medis final bersifat baca-saja, jadi boleh dibuka semua dokter RS yang sama
        // sebagai riwayat pasien. Yang belum final tetap hanya untuk dokter pemeriksanya.
        if (! $kunjungan->isFinalized()) {
            $this->authorizeKunjungan($kunjungan, $user->id_pengguna);

            return redirect()
                ->to(route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean]) . '#finalisasi')
                ->with('warning', 'Pratinjau SOAP hanya tersedia setelah rekam medis berhasil disetujui dan difinalisasi.');
        }

        $kunjungan->load([
            'pasien',
            'hospital',
            'dokter',
            'pemeriksaanAwal.perawat',
            'drafSoap',
            'tindakLanjut.eResep',
            'tindakLanjut.rujukanLab.parameter',
            'finalisasi.finalizedBy',
            'antrean',
        ]);

        $pasien = $kunjungan->pasien;
        $antrean = $kunjungan->antrean;
        $draf = $kunjungan->drafSoap;
        $pemeriksaanAwal = $kunjungan->pemeriksaanAwal;
        $tindakLanjut = $kunjungan->tindakLanjut;
        $finalisasi = $kunjungan->finalisasi;

        $dokterPemeriksa = $kunjungan->dokter ?? $user;
        $finalizedDoctor = $finalisasi?->finalizedBy ?? $dokterPemeriksa;

        // Nomor izin yang dicetak: salinan saat finalisasi. Rekam medis lama yang belum punya salinan
        // memakai nomor di RS kunjungan ini (bukan nomor di RS baru kalau orangnya sudah dimutasi).
        $hospitalKunjungan = $kunjungan->hospital_id ?? $pasien?->hospital_id;
        $sipDokter = $finalisasi?->sip_dokter ?? $finalizedDoctor?->nomorIzinDi($hospitalKunjungan);
        $sippPerawat = $finalisasi?->sipp_perawat ?? $pemeriksaanAwal?->perawat?->nomorIzinDi($hospitalKunjungan);
        // finalized_at disimpan dalam zona aplikasi (UTC); label "WIB" butuh konversi ke Asia/Jakarta.
        $waktuFinal = ($finalisasi?->finalized_at ? Carbon::parse($finalisasi->finalized_at, 'UTC') : Carbon::now())
            ->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';

        $tanggalPemeriksaan = $kunjungan->tanggal
            ? Carbon::parse($kunjungan->tanggal)->locale('id')->isoFormat('D MMMM Y')
            : Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y');

        $tglStr = str_replace('-', '', $kunjungan->tanggal ?: date('Ymd'));
        $prefix = $kunjungan->hospital?->hospital_code ? 'RS'.$kunjungan->hospital->hospital_code : 'RSUA';
        $idKunjunganCode = $prefix.'-'.$tglStr.'-'.str_pad($antrean?->id_antrean ?? $kunjungan->id_kunjungan, 3, '0', STR_PAD_LEFT);

        $umur = $pasien->usia_label;

        // Dibuka dari riwayat pasien, atau oleh dokter lain: kembali ke halaman riwayat, bukan ke pemeriksaan.
        $kembaliKeRiwayat = $request->query('kembali') === 'pasien'
            || (int) $kunjungan->id_dokter !== (int) $user->id_pengguna
            || ! $antrean;
        $kembaliUrl = $kembaliKeRiwayat
            ? route('dokter.pasien.show', $pasien->id_pasien)
            : route('dokter.rekam-medis', $antrean->id_antrean) . '#finalisasi';
        $kembaliLabel = $kembaliKeRiwayat ? 'Kembali ke riwayat pasien' : 'Kembali ke rekam medis';

        $icdDescription = null;
        if ($draf?->kode_icd10) {
            $icd10Catalog = app(\App\Services\Icd10Catalog::class);
            $icdDescription = $icd10Catalog->labelResmi(
                $draf->kode_icd10,
                $icd10Catalog->normalisasiSaran($draf->icd10_suggestions ?? [])
            );
        }

        return view('dashboard.rekam-medis.preview-soap', compact(
            'kunjungan',
            'pasien',
            'antrean',
            'draf',
            'pemeriksaanAwal',
            'tindakLanjut',
            'finalisasi',
            'dokterPemeriksa',
            'finalizedDoctor',
            'sipDokter',
            'sippPerawat',
            'waktuFinal',
            'tanggalPemeriksaan',
            'idKunjunganCode',
            'umur',
            'icdDescription',
            'user',
            'kembaliUrl',
            'kembaliLabel'
        ));
    }
}

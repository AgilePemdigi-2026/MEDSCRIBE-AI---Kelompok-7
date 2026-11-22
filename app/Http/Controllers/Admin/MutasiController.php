<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\MutasiTenagaMedis;
use App\Models\Pengguna;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pindah penugasan dokter/perawat antar rumah sakit, hanya oleh admin.
 * Pengaju dianggap sudah setuju; admin RS satunya yang menyetujui atau menolak.
 */
class MutasiController extends Controller
{
    public function __construct(private AdminAuditService $audit)
    {
    }

    private function admin(): Pengguna
    {
        $admin = Auth::user();
        abort_unless($admin instanceof Pengguna && $admin->isAdmin() && $admin->hospital_id, 403, 'Akses ditolak: hanya admin rumah sakit.');

        return $admin;
    }

    /** Kunjungan yang masih ditangani dokter ini di RS tertentu dan belum final. */
    public static function kunjunganBelumFinal(Pengguna $staf, int $hospitalId): Collection
    {
        if (! $staf->isDokter()) {
            return collect();
        }

        return Kunjungan::with('pasien')
            ->where('id_dokter', $staf->id_pengguna)
            ->where('hospital_id', $hospitalId)
            ->whereNotIn('status', [Kunjungan::STATUS_FINALISASI, 'Selesai', 'Terkunci', 'Dibatalkan'])
            ->whereDoesntHave('finalisasi', fn ($f) => $f->where('FINAL', true))
            ->orderBy('tanggal')
            ->get();
    }

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $rsId = (int) $admin->hospital_id;
        $tab = in_array($request->query('tab'), ['masuk', 'diajukan', 'riwayat'], true) ? $request->query('tab') : 'masuk';
        $relasi = ['staf', 'rsAsal', 'rsTujuan', 'pengaju', 'pemutus'];

        $masuk = MutasiTenagaMedis::with($relasi)->masukUntuk($rsId)->latest()->get();
        $diajukan = MutasiTenagaMedis::with($relasi)->diajukanOleh($rsId)->latest()->get();
        $riwayat = MutasiTenagaMedis::with($relasi)->melibatkan($rsId)
            ->where('status', '!=', MutasiTenagaMedis::MENUNGGU)
            ->latest('diputuskan_at')->paginate(15, ['*'], 'halaman')->withQueryString();

        // Peringatan untuk admin RS asal: pasien yang belum selesai ditangani staf yang akan pindah.
        $belumFinal = $masuk->mapWithKeys(fn ($m) => [$m->id => self::kunjunganBelumFinal($m->staf, (int) $m->hospital_asal_id)]);

        // Pencarian staf RS lain untuk diminta: hanya lewat email lengkap, tanpa menelusuri daftar.
        $emailCari = trim((string) $request->query('email', ''));
        $hasilCari = null;
        $pesanCari = null;
        if ($emailCari !== '') {
            $kandidat = Pengguna::with('hospital')->where('email', $emailCari)->first();
            $pesanCari = match (true) {
                ! $kandidat || ! ($kandidat->isDokter() || $kandidat->isPerawat()) => 'Tidak ada dokter atau perawat dengan email itu.',
                (int) $kandidat->hospital_id === $rsId => 'Tenaga medis ini sudah bertugas di rumah sakit Anda.',
                ! $kandidat->isActive() => 'Akun tenaga medis ini tidak aktif di rumah sakit asalnya.',
                MutasiTenagaMedis::where('id_pengguna', $kandidat->id_pengguna)->where('status', MutasiTenagaMedis::MENUNGGU)->exists()
                    => 'Tenaga medis ini sedang dalam proses mutasi lain.',
                default => null,
            };
            $hasilCari = $pesanCari ? null : $kandidat;
        }

        return view('admin.mutasi', [
            'admin' => $admin,
            'hospital' => $admin->hospital,
            'tab' => $tab,
            'masuk' => $masuk,
            'diajukan' => $diajukan,
            'riwayat' => $riwayat,
            'belumFinal' => $belumFinal,
            'emailCari' => $emailCari,
            'hasilCari' => $hasilCari,
            'pesanCari' => $pesanCari,
        ]);
    }

    /** Admin RS asal mengusulkan staf pindah ke RS lain. */
    public function lepas(Request $request, Pengguna $user): RedirectResponse
    {
        $admin = $this->admin();
        abort_unless((int) $user->hospital_id === (int) $admin->hospital_id, 403, 'Akses ditolak: tenaga medis ini bukan staf rumah sakit Anda.');
        abort_unless($user->isDokter() || $user->isPerawat(), 403, 'Mutasi hanya untuk dokter atau perawat.');

        $data = $request->validate([
            'hospital_tujuan_id' => ['required', 'integer', 'exists:hospitals,id', 'not_in:' . $admin->hospital_id],
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'hospital_tujuan_id.required' => 'Pilih rumah sakit tujuan.',
            'hospital_tujuan_id.not_in' => 'Rumah sakit tujuan harus berbeda dari rumah sakit Anda.',
            'alasan.required' => 'Alasan mutasi wajib diisi.',
            'alasan.min' => 'Alasan mutasi minimal 5 karakter.',
        ]);

        abort_unless(Hospital::whereKey($data['hospital_tujuan_id'])->where('is_active', true)->exists(), 422, 'Rumah sakit tujuan tidak aktif.');

        if ($pesan = $this->alasanTidakBisaMutasi($user)) {
            return back()->with('warning', $pesan);
        }

        $mutasi = MutasiTenagaMedis::create([
            'id_pengguna' => $user->id_pengguna,
            'hospital_asal_id' => $admin->hospital_id,
            'hospital_tujuan_id' => $data['hospital_tujuan_id'],
            'arah' => MutasiTenagaMedis::LEPAS,
            'alasan' => trim($data['alasan']),
            'diajukan_oleh' => $admin->id_pengguna,
        ]);

        $this->audit->log($admin, 'mutasi_diajukan', $user, null, 'menunggu', 'Ke ' . $mutasi->rsTujuan->hospital_name . ': ' . $mutasi->alasan);

        return redirect()->route('admin.mutasi', ['tab' => 'diajukan'])
            ->with('status', "Permintaan mutasi {$user->nama_lengkap} ke {$mutasi->rsTujuan->hospital_name} terkirim. Menunggu persetujuan admin rumah sakit tujuan.");
    }

    /** Admin RS tujuan meminta staf RS lain (dicari lewat email). */
    public function tarik(Request $request): RedirectResponse
    {
        $admin = $this->admin();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'alasan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan.required' => 'Alasan permintaan wajib diisi.',
            'alasan.min' => 'Alasan permintaan minimal 5 karakter.',
        ]);

        $user = Pengguna::where('email', $data['email'])->first();
        if (! $user || ! ($user->isDokter() || $user->isPerawat())) {
            return back()->withInput()->with('warning', 'Tidak ada dokter atau perawat dengan email itu.');
        }
        if ((int) $user->hospital_id === (int) $admin->hospital_id) {
            return back()->withInput()->with('warning', 'Tenaga medis ini sudah bertugas di rumah sakit Anda.');
        }
        if ($pesan = $this->alasanTidakBisaMutasi($user)) {
            return back()->withInput()->with('warning', $pesan);
        }

        $mutasi = MutasiTenagaMedis::create([
            'id_pengguna' => $user->id_pengguna,
            'hospital_asal_id' => $user->hospital_id,
            'hospital_tujuan_id' => $admin->hospital_id,
            'arah' => MutasiTenagaMedis::TARIK,
            'alasan' => trim($data['alasan']),
            'diajukan_oleh' => $admin->id_pengguna,
        ]);

        $this->audit->log($admin, 'mutasi_diajukan', $user, null, 'menunggu', 'Dari ' . $mutasi->rsAsal->hospital_name . ': ' . $mutasi->alasan);

        return redirect()->route('admin.mutasi', ['tab' => 'diajukan'])
            ->with('status', "Permintaan untuk {$user->nama_lengkap} terkirim. Menunggu persetujuan admin {$mutasi->rsAsal->hospital_name}.");
    }

    public function setujui(MutasiTenagaMedis $mutasi): RedirectResponse
    {
        $admin = $this->admin();
        $this->pastikanPemutus($mutasi, $admin);

        $staf = $mutasi->staf;
        if ((int) $staf->hospital_id !== (int) $mutasi->hospital_asal_id || ! $staf->isActive()) {
            return back()->with('warning', 'Mutasi tidak bisa disetujui: tenaga medis ini sudah tidak aktif di rumah sakit asal.');
        }

        $dilepas = DB::transaction(function () use ($mutasi, $admin, $staf) {
            // Kunjungan yang belum final dilepas agar bisa diambil alih dokter lain di RS asal.
            $dilepas = Kunjungan::whereIn('id_kunjungan', self::kunjunganBelumFinal($staf, (int) $mutasi->hospital_asal_id)->pluck('id_kunjungan'))
                ->update(['id_dokter' => null]);

            // SIP/SIPP berlaku per tempat praktik, jadi dikosongkan; login berikutnya diarahkan ke profil.
            $staf->update(['hospital_id' => $mutasi->hospital_tujuan_id, 'nomor_sip' => null]);

            $mutasi->update([
                'status' => MutasiTenagaMedis::DISETUJUI,
                'diputuskan_oleh' => $admin->id_pengguna,
                'diputuskan_at' => now(),
                'kunjungan_dilepas' => $dilepas,
            ]);

            return $dilepas;
        });

        $this->audit->log($admin, 'mutasi_disetujui', $staf, 'menunggu', 'disetujui', $mutasi->rsAsal->hospital_name . ' → ' . $mutasi->rsTujuan->hospital_name . ': ' . $mutasi->alasan);

        $info = $dilepas ? " {$dilepas} kunjungan yang belum final dilepas dan bisa diambil alih dokter lain." : '';

        return redirect()->route('admin.mutasi', ['tab' => 'riwayat'])
            ->with('status', "{$staf->nama_lengkap} kini bertugas di {$mutasi->rsTujuan->hospital_name}.{$info}");
    }

    public function tolak(Request $request, MutasiTenagaMedis $mutasi): RedirectResponse
    {
        $admin = $this->admin();
        $this->pastikanPemutus($mutasi, $admin);

        $data = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $mutasi->update([
            'status' => MutasiTenagaMedis::DITOLAK,
            'diputuskan_oleh' => $admin->id_pengguna,
            'diputuskan_at' => now(),
            'alasan_penolakan' => trim($data['alasan_penolakan']),
        ]);

        $this->audit->log($admin, 'mutasi_ditolak', $mutasi->staf, 'menunggu', 'ditolak', $mutasi->alasan_penolakan);

        return redirect()->route('admin.mutasi', ['tab' => 'riwayat'])
            ->with('status', "Permintaan mutasi {$mutasi->staf->nama_lengkap} ditolak.");
    }

    public function batal(MutasiTenagaMedis $mutasi): RedirectResponse
    {
        $admin = $this->admin();
        abort_unless($mutasi->hospitalPengajuId() === (int) $admin->hospital_id, 403, 'Hanya rumah sakit pengaju yang bisa membatalkan permintaan ini.');
        if (! $mutasi->isMenunggu()) {
            return back()->with('info', 'Permintaan ini sudah diputuskan.');
        }

        $mutasi->update([
            'status' => MutasiTenagaMedis::DIBATALKAN,
            'diputuskan_oleh' => $admin->id_pengguna,
            'diputuskan_at' => now(),
        ]);

        $this->audit->log($admin, 'mutasi_dibatalkan', $mutasi->staf, 'menunggu', 'dibatalkan');

        return redirect()->route('admin.mutasi', ['tab' => 'riwayat'])
            ->with('status', "Permintaan mutasi {$mutasi->staf->nama_lengkap} dibatalkan.");
    }

    private function pastikanPemutus(MutasiTenagaMedis $mutasi, Pengguna $admin): void
    {
        abort_unless($mutasi->hospitalPemutusId() === (int) $admin->hospital_id, 403, 'Hanya admin rumah sakit yang dituju yang bisa memutuskan permintaan ini.');
        abort_unless($mutasi->isMenunggu(), 409, 'Permintaan ini sudah diputuskan.');
    }

    private function alasanTidakBisaMutasi(Pengguna $user): ?string
    {
        if (! $user->isActive()) {
            return 'Hanya tenaga medis yang aktif yang bisa dimutasi.';
        }
        if (MutasiTenagaMedis::where('id_pengguna', $user->id_pengguna)->where('status', MutasiTenagaMedis::MENUNGGU)->exists()) {
            return "{$user->nama_lengkap} sedang dalam proses mutasi lain.";
        }

        return null;
    }
}

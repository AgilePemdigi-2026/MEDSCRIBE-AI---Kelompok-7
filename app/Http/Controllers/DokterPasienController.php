<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Daftar pasien dan riwayat rekam medis untuk dokter. Dokter boleh membaca rekam medis final
 * semua pasien di rumah sakitnya, termasuk yang ditangani dokter lain. Perawat tidak
 * memakai halaman ini; riwayat perawat cukup kunjungan dan TTV.
 */
class DokterPasienController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $cari = trim((string) $request->query('cari', ''));

        $daftarPasien = Pasien::forHospital($user->hospital_id)
            ->withCount('kunjungan')
            ->withMax('kunjungan as kunjungan_terakhir', 'tanggal')
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($q) use ($cari) {
                    $q->where('nama_pasien', 'like', "%{$cari}%")
                      ->orWhere('no_rekam_medis', 'like', "%{$cari}%")
                      ->orWhere('nik', 'like', "%{$cari}%");
                });
            })
            ->orderByDesc('kunjungan_terakhir')
            ->orderByDesc('id_pasien')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.dokter-pasien', compact('user', 'daftarPasien', 'cari'));
    }

    public function show(int $id): View
    {
        $user = Auth::user();
        $pasien = Pasien::with('hospital')->findOrFail($id);
        $this->authorizeHospital($user, $pasien);

        $riwayatKunjungan = Kunjungan::with(['dokter', 'pemeriksaanAwal', 'drafSoap', 'finalisasi', 'antrean'])
            ->where('id_pasien', $pasien->id_pasien)
            ->orderByDesc('tanggal')
            ->orderByDesc('id_kunjungan')
            ->get();

        return view('dashboard.dokter-pasien-detail', compact('user', 'pasien', 'riwayatKunjungan'));
    }

    private function authorizeHospital(Pengguna $user, Pasien $pasien): void
    {
        if ($user->hospital_id && $pasien->hospital_id && (int) $pasien->hospital_id !== (int) $user->hospital_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk melihat data pasien dari rumah sakit lain.');
        }
    }
}

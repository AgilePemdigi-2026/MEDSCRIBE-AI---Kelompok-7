<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\PemeriksaanAwal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Entry point dashboard umum: mengarahkan ke dashboard spesifik peran.
     */
    public function index(): RedirectResponse|View
    {
        $user = Auth::user();

        if ($user->isDokter()) {
            return redirect()->route('dokter.dashboard');
        }

        if ($user->isPerawat()) {
            return redirect()->route('perawat.dashboard');
        }

        return view('dashboard.generic', compact('user'));
    }

    /**
     * Dashboard Antrean Dokter (Sesuai SRS Gambar 3.2).
     * Menerapkan isolasi data multi-tenancy berbasis rumah sakit dokter.
     */
    public function dokterDashboard(Request $request): View
    {
        $user = Auth::user();
        $hospitalId = $user->hospital_id;

        // Antrean aktif saja: sama seperti Nurse Station, antrean yang dibatalkan dan
        // pasien yang sudah dihapus (soft delete) tidak ikut ditampilkan maupun dihitung.
        $antreanAktif = fn () => Antrean::forHospital($hospitalId)
            ->whereHas('pasien')
            ->where('status', '!=', 'Dibatalkan');

        // Mengambil antrean hari ini dan pasien terkait dengan pembatasan rumah sakit dokter
        $antreanQuery = $antreanAktif()
            ->with(['pasien', 'hospital'])
            ->orderBy('id_antrean', 'desc');

        if ($statusFilter = $request->query('status')) {
            $antreanQuery->where('status', $statusFilter);
        }

        if ($search = trim($request->query('cari', ''))) {
            $antreanQuery->whereHas('pasien', function ($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%")
                  ->orWhere('no_rekam_medis', 'like', "%{$search}%");
            });
        }

        $daftarAntrean = $antreanQuery->get();

        // Statistik antrean terisolasi berdasarkan rumah sakit dokter
        $totalAntrean = $antreanAktif()->count();
        $siapPeriksa = $antreanAktif()->where('status', 'Siap Periksa')->count();
        $menungguTTV = $antreanAktif()->where('status', 'Menunggu TTV')->count();
        $selesai = $antreanAktif()->where('status', 'Selesai')->count();

        return view('dashboard.dokter', compact(
            'user',
            'daftarAntrean',
            'totalAntrean',
            'siapPeriksa',
            'menungguTTV',
            'selesai'
        ));
    }

    /**
     * Dashboard Pemeriksaan Awal & TTV Nurse Station (Sesuai SRS Gambar 3.1 & Mockup Halaman 2).
     */
    public function perawatDashboard(Request $request): View
    {
        return app(PemeriksaanAwalController::class)->index($request);
    }
}

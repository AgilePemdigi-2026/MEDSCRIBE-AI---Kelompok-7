<?php

namespace App\Http\Controllers;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\PemeriksaanAwal;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PemeriksaanAwalController extends Controller
{
    /**
     * Menampilkan dashboard Triase & Pemeriksaan Awal Nurse Station.
     * Sesuai dokumen SRS Bab 4.1 (REQ-1 s/d REQ-3) & Data Mockup Halaman 2.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Ambil daftar antrean aktif
        $antreanQuery = Antrean::with(['pasien'])->orderBy('id_antrean', 'asc');

        // Pencarian berdasarkan nama pasien atau nomor rekam medis
        if ($search = trim($request->query('cari', ''))) {
            $antreanQuery->whereHas('pasien', function ($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%")
                  ->orWhere('no_rekam_medis', 'like', "%{$search}%");
            });
        }

        $daftarAntrean = $antreanQuery->get();

        // 2. Tentukan pasien aktif yang sedang dipilih perawat
        $selectedAntreanId = $request->query('antrean_id');
        $pasienTerpilih = null;

        if ($selectedAntreanId) {
            $pasienTerpilih = $daftarAntrean->firstWhere('id_antrean', (int)$selectedAntreanId);
        }

        // Jika tidak ada di query atau tidak ditemukan, prioritaskan pasien dengan status 'Menunggu TTV'
        if (!$pasienTerpilih) {
            $pasienTerpilih = $daftarAntrean->firstWhere('status', 'Menunggu TTV') ?? $daftarAntrean->first();
        }

        // 3. Ambil data kunjungan dan pemeriksaan awal terkait jika ada
        $kunjungan = null;
        $pemeriksaanAwal = null;
        $idKunjunganCode = null;

        if ($pasienTerpilih) {
            $kunjungan = Kunjungan::where('id_antrean', $pasienTerpilih->id_antrean)
                ->orWhere(function ($q) use ($pasienTerpilih) {
                    $q->where('id_pasien', $pasienTerpilih->id_pasien)
                      ->where('tanggal', $pasienTerpilih->tanggal_kunjungan);
                })
                ->first();

            if ($kunjungan) {
                $pemeriksaanAwal = PemeriksaanAwal::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
            }

            // Format kode kunjungan sesuai standar mockup: RSUA-YYYYMMDD-XXX
            $tglStr = str_replace('-', '', $pasienTerpilih->tanggal_kunjungan ?: date('Ymd'));
            $idKunjunganCode = 'RSUA-' . $tglStr . '-' . str_pad($pasienTerpilih->id_antrean, 3, '0', STR_PAD_LEFT);
        }

        return view('dashboard.perawat', compact(
            'user',
            'daftarAntrean',
            'pasienTerpilih',
            'kunjungan',
            'pemeriksaanAwal',
            'idKunjunganCode'
        ));
    }

    /**
     * Memproses penyimpanan data pemeriksaan awal & TTV dari perawat.
     * Sesuai dokumen SRS Bab 4.1 (REQ-4 s/d REQ-10).
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi input data TTV sesuai ketentuan REQ-6
        $validated = $request->validate([
            'id_antrean'      => ['required', 'integer', 'exists:antrean,id_antrean'],
            'tekanan_darah'   => ['required', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'nadi'            => ['required', 'integer', 'min:30', 'max:250'],
            'suhu_tubuh'      => ['required', 'numeric', 'min:30', 'max:45'],
            'laju_pernapasan' => ['required', 'integer', 'min:5', 'max:60'],
            'berat_badan'     => ['required', 'numeric', 'min:1', 'max:300'],
            'tinggi_badan'    => ['required', 'numeric', 'min:30', 'max:250'],
            'catatan'         => ['required', 'string', 'max:1000'],
        ], [
            'id_antrean.required'      => 'Pasien antrean wajib dipilih.',
            'id_antrean.exists'        => 'Data antrean tidak ditemukan di sistem.',
            'tekanan_darah.required'   => 'Tekanan darah wajib diisi.',
            'tekanan_darah.regex'      => 'Format tekanan darah harus berupa sistol/diastol (contoh: 120/80).',
            'nadi.required'            => 'Denyut nadi wajib diisi.',
            'nadi.integer'             => 'Denyut nadi harus berupa bilangan bulat.',
            'nadi.min'                 => 'Denyut nadi minimal 30 x/menit.',
            'nadi.max'                 => 'Denyut nadi maksimal 250 x/menit.',
            'suhu_tubuh.required'      => 'Suhu tubuh wajib diisi.',
            'suhu_tubuh.numeric'       => 'Suhu tubuh harus berupa angka desimal (contoh: 36.5 atau 38.6).',
            'suhu_tubuh.min'           => 'Suhu tubuh minimal 30 °C.',
            'suhu_tubuh.max'           => 'Suhu tubuh maksimal 45 °C.',
            'laju_pernapasan.required' => 'Laju pernapasan wajib diisi.',
            'laju_pernapasan.integer'  => 'Laju pernapasan harus berupa bilangan bulat.',
            'laju_pernapasan.min'      => 'Laju pernapasan minimal 5 x/menit.',
            'laju_pernapasan.max'      => 'Laju pernapasan maksimal 60 x/menit.',
            'berat_badan.required'     => 'Berat badan wajib diisi.',
            'berat_badan.numeric'      => 'Berat badan harus berupa angka numerik.',
            'tinggi_badan.required'    => 'Tinggi badan wajib diisi.',
            'tinggi_badan.numeric'     => 'Tinggi badan harus berupa angka numerik.',
            'catatan.required'         => 'Catatan awal perawat wajib diisi.',
        ]);

        try {
            DB::beginTransaction();

            $antrean = Antrean::with('pasien')->findOrFail($validated['id_antrean']);

            // 2. Hubungkan atau buat entitas kunjungan (REQ-7)
            $kunjungan = Kunjungan::firstOrCreate(
                ['id_antrean' => $antrean->id_antrean],
                [
                    'id_pasien' => $antrean->id_pasien,
                    'tanggal'   => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                    'status'    => 'Menunggu Dokter',
                ]
            );

            // 3. Simpan data TTV ke tabel pemeriksaan_awal (REQ-7)
            $pemeriksaan = PemeriksaanAwal::updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                [
                    'id_perawat'       => Auth::id(),
                    'tekanan_darah'    => trim($validated['tekanan_darah']),
                    'suhu_tubuh'       => (float) $validated['suhu_tubuh'],
                    'nadi'             => (int) $validated['nadi'],
                    'laju_pernapasan'  => (int) $validated['laju_pernapasan'],
                    'berat_badan'      => (float) $validated['berat_badan'],
                    'tinggi_badan'     => (float) $validated['tinggi_badan'],
                    'catatan'          => trim($validated['catatan']),
                ]
            );

            // 4. Perbarui status antrean pasien menjadi 'Siap Periksa' (REQ-8)
            $antrean->update([
                'status' => 'Siap Periksa',
            ]);

            // 5. Integrasi otomatis ke komponen Objective pada Draf SOAP dokter (REQ-10)
            $suhuFormatted = str_replace('.', ',', (string) round($validated['suhu_tubuh'], 1));
            $formattedObjective = sprintf(
                'TD %s mmHg, Nadi %d x/menit, Suhu %s°C, RR %d x/menit, BB %g kg, TB %g cm',
                $validated['tekanan_darah'],
                $validated['nadi'],
                $suhuFormatted,
                $validated['laju_pernapasan'],
                $validated['berat_badan'],
                $validated['tinggi_badan']
            );

            DrafSoap::updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                [
                    'id_pengguna'    => $kunjungan->id_dokter,
                    'objective_text' => $formattedObjective,
                ]
            );

            // Perubahan TTV membuat verifikasi sebelumnya perlu ditinjau ulang.
            \App\Models\Finalisasi::where('id_kunjungan', $kunjungan->id_kunjungan)->update([
                'status' => 'Perlu Ditinjau',
                'FINAL' => false,
            ]);

            DB::commit();

            // 6. Redirect dengan umpan balik sukses (REQ-9)
            return redirect()
                ->route('perawat.dashboard', [
                    'antrean_id' => $antrean->id_antrean,
                    'cari'       => $request->input('cari_filter'),
                ])
                ->with('status', 'Data berhasil disimpan!')
                ->with('saved_pemeriksaan', true);

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Gagal menyimpan data pemeriksaan: ' . $e->getMessage(),
                ]);
        }
    }
}

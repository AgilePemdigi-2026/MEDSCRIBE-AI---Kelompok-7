<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePasienPemeriksaanRequest;
use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PemeriksaanAwalController extends Controller
{
    /**
     * Menampilkan dashboard Triase & Pemeriksaan Awal Nurse Station.
     * Mendukung pencarian, filter tanggal, dan daftar pasien terdaftar.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // === BAGIAN 1: Daftar Antrean Aktif (Panel Kiri) ===
        $antreanQuery = Antrean::with(['pasien'])
            ->whereHas('pasien')
            ->where('status', '!=', 'Dibatalkan')
            ->orderBy('id_antrean', 'asc');

        $searchAntrean = trim($request->query('cari', ''));
        if ($searchAntrean) {
            $antreanQuery->whereHas('pasien', function ($q) use ($searchAntrean) {
                $q->where('nama_pasien', 'like', "%{$searchAntrean}%")
                  ->orWhere('no_rekam_medis', 'like', "%{$searchAntrean}%");
            });
        }

        $daftarAntrean = $antreanQuery->get();

        // Pasien terpilih dari antrean
        $selectedAntreanId = $request->query('antrean_id');
        $pasienTerpilih = null;

        if ($selectedAntreanId) {
            $pasienTerpilih = $daftarAntrean->firstWhere('id_antrean', (int)$selectedAntreanId);
        }
        if (!$pasienTerpilih) {
            $pasienTerpilih = $daftarAntrean->firstWhere('status', 'Menunggu TTV') ?? $daftarAntrean->first();
        }

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

            $tglStr = str_replace('-', '', $pasienTerpilih->tanggal_kunjungan ?: date('Ymd'));
            $idKunjunganCode = 'RSUA-' . $tglStr . '-' . str_pad($pasienTerpilih->id_antrean, 3, '0', STR_PAD_LEFT);
        }

        // === BAGIAN 2: Daftar Pasien Terdaftar (Tab Baru) ===
        $searchPasien = trim($request->query('cari_pasien', ''));
        $filterTahun = $request->query('tahun');
        $filterBulan = $request->query('bulan');
        $filterTanggal = $request->query('tanggal');

        $pasienQuery = Pasien::query()
            ->orderBy('created_at', 'desc')
            ->orderBy('id_pasien', 'desc');

        // Pencarian nama atau ID pasien
        if ($searchPasien) {
            $pasienQuery->where(function ($q) use ($searchPasien) {
                $q->where('nama_pasien', 'like', "%{$searchPasien}%")
                  ->orWhere('no_rekam_medis', 'like', "%{$searchPasien}%");
            });
        }

        // Filter tanggal berdasarkan created_at (tanggal registrasi)
        if ($filterTahun) {
            $pasienQuery->whereYear('created_at', $filterTahun);
        }
        if ($filterBulan) {
            $pasienQuery->whereMonth('created_at', $filterBulan);
        }
        if ($filterTanggal) {
            $pasienQuery->whereDay('created_at', $filterTanggal);
        }

        // Eager load relasi terakhir
        $daftarPasien = $pasienQuery->paginate(15)->appends($request->query());

        // Ambil tahun yang tersedia untuk filter
        $tahunTersedia = Pasien::selectRaw('YEAR(created_at) as tahun')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->filter()
            ->values();

        // Jika tidak ada tahun tersedia, tambahkan tahun saat ini
        if ($tahunTersedia->isEmpty()) {
            $tahunTersedia = collect([now()->year]);
        }

        // Tentukan tab aktif
        $activeTab = $request->query('tab', 'antrean');

        return view('dashboard.perawat', compact(
            'user',
            'daftarAntrean',
            'pasienTerpilih',
            'kunjungan',
            'pemeriksaanAwal',
            'idKunjunganCode',
            'daftarPasien',
            'searchPasien',
            'filterTahun',
            'filterBulan',
            'filterTanggal',
            'tahunTersedia',
            'activeTab'
        ));
    }

    /**
     * Memproses penyimpanan data pemeriksaan awal & TTV dari perawat
     * untuk pasien yang sudah ada di antrean.
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

            // Parse tekanan darah terpisah
            $tdParts = explode('/', $validated['tekanan_darah']);
            $sistolik = (int) ($tdParts[0] ?? 0);
            $diastolik = (int) ($tdParts[1] ?? 0);

            // 3. Simpan data TTV ke tabel pemeriksaan_awal (REQ-7)
            $pemeriksaan = PemeriksaanAwal::updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                [
                    'id_perawat'               => Auth::id(),
                    'tekanan_darah'            => trim($validated['tekanan_darah']),
                    'tekanan_darah_sistolik'   => $sistolik,
                    'tekanan_darah_diastolik'  => $diastolik,
                    'suhu_tubuh'               => (float) $validated['suhu_tubuh'],
                    'nadi'                     => (int) $validated['nadi'],
                    'laju_pernapasan'          => (int) $validated['laju_pernapasan'],
                    'berat_badan'              => (float) $validated['berat_badan'],
                    'tinggi_badan'             => (float) $validated['tinggi_badan'],
                    'catatan'                  => trim($validated['catatan']),
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

    /**
     * Memproses registrasi pasien baru + input TTV dalam satu transaksi.
     * Menghasilkan ID pasien format YYYYMMDD-NNN secara atomik.
     */
    public function storePasienBaru(StorePasienPemeriksaanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // 1. Generate ID pasien (no_rekam_medis) format YYYYMMDD-NNN secara atomik
            $today = Carbon::now('Asia/Jakarta');
            $datePrefix = $today->format('Ymd');

            $noRekamMedis = $this->generatePatientId($datePrefix);

            // 2. Simpan data pasien
            $pasien = Pasien::create([
                'no_rekam_medis' => $noRekamMedis,
                'nama_pasien'    => $validated['nama_pasien'],
                'umur'           => (int) $validated['umur'],
                'jenis_kelamin'  => $validated['jenis_kelamin'],
            ]);

            // 3. Buat antrean untuk pasien baru
            $antrean = Antrean::create([
                'id_pasien'        => $pasien->id_pasien,
                'tanggal_kunjungan' => $today->toDateString(),
                'status'           => 'Siap Periksa',
            ]);

            // 4. Buat kunjungan
            $kunjungan = Kunjungan::create([
                'id_antrean' => $antrean->id_antrean,
                'id_pasien'  => $pasien->id_pasien,
                'tanggal'    => $today->toDateString(),
                'status'     => 'Menunggu Dokter',
            ]);

            // 5. Simpan data TTV / Pemeriksaan Awal
            $tekananDarah = $validated['tekanan_darah_sistolik'] . '/' . $validated['tekanan_darah_diastolik'];

            PemeriksaanAwal::create([
                'id_kunjungan'             => $kunjungan->id_kunjungan,
                'id_perawat'               => Auth::id(),
                'tekanan_darah'            => $tekananDarah,
                'tekanan_darah_sistolik'   => (int) $validated['tekanan_darah_sistolik'],
                'tekanan_darah_diastolik'  => (int) $validated['tekanan_darah_diastolik'],
                'suhu_tubuh'               => (float) $validated['suhu_tubuh'],
                'saturasi_oksigen'         => (int) $validated['saturasi_oksigen'],
                'nadi'                     => (int) $validated['nadi'],
                'laju_pernapasan'          => (int) $validated['laju_pernapasan'],
                'berat_badan'              => isset($validated['berat_badan']) ? (float) $validated['berat_badan'] : null,
                'tinggi_badan'             => isset($validated['tinggi_badan']) ? (float) $validated['tinggi_badan'] : null,
                'catatan'                  => $validated['catatan'] ?? null,
            ]);

            // 6. Buat Draf SOAP Objective otomatis
            $suhuFormatted = str_replace('.', ',', (string) round($validated['suhu_tubuh'], 1));
            $objectiveParts = [
                sprintf('TD %s mmHg', $tekananDarah),
                sprintf('Nadi %d x/menit', $validated['nadi']),
                sprintf('Suhu %s°C', $suhuFormatted),
                sprintf('RR %d x/menit', $validated['laju_pernapasan']),
                sprintf('SpO2 %d%%', $validated['saturasi_oksigen']),
            ];

            if (!empty($validated['berat_badan'])) {
                $objectiveParts[] = sprintf('BB %g kg', $validated['berat_badan']);
            }
            if (!empty($validated['tinggi_badan'])) {
                $objectiveParts[] = sprintf('TB %g cm', $validated['tinggi_badan']);
            }

            DrafSoap::create([
                'id_kunjungan'   => $kunjungan->id_kunjungan,
                'id_pengguna'    => null,
                'objective_text' => implode(', ', $objectiveParts),
            ]);

            DB::commit();

            return redirect()
                ->route('perawat.dashboard', ['tab' => 'pasien'])
                ->with('status', 'Pasien berhasil didaftarkan dengan ID: ' . $noRekamMedis)
                ->with('new_patient_id', $noRekamMedis);

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Gagal mendaftarkan pasien: ' . $e->getMessage(),
                ]);
        }
    }

    /**
     * Menampilkan detail pasien dan riwayat pemeriksaan.
     */
    public function showPasien(int $id): View
    {
        $user = Auth::user();
        $pasien = Pasien::findOrFail($id);

        // Ambil semua kunjungan beserta pemeriksaan awal terkait
        $riwayatKunjungan = Kunjungan::with(['pemeriksaanAwal', 'pemeriksaanAwal.perawat'])
            ->where('id_pasien', $pasien->id_pasien)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_kunjungan', 'desc')
            ->get();

        return view('dashboard.pasien-detail', compact('user', 'pasien', 'riwayatKunjungan'));
    }

    /**
     * Membatalkan / menghapus registrasi pasien secara aman (Soft Delete).
     * Mempertahankan integritas riwayat medis & rekam audit sesuai SOP.
     */
    public function destroyPasien(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isPerawat()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya perawat yang berhak membatalkan registrasi pasien.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya perawat yang berhak membatalkan registrasi pasien.');
        }

        $pasien = Pasien::findOrFail($id);

        // Validasi perlindungan data rekam medis:
        // Pasien yang belum ada input TTV (pemeriksaan awal) selalu dapat dihapus / dibatalkan.
        // Pembatalan hanya diblokir jika pasien sudah memiliki input TTV dan riwayat pemeriksaan dokter telah selesai.
        $hasTtv = PemeriksaanAwal::whereHas('kunjungan', function ($q) use ($pasien) {
            $q->where('id_pasien', $pasien->id_pasien);
        })->exists();

        $hasCompletedVisit = Kunjungan::where('id_pasien', $pasien->id_pasien)
            ->where('status', 'Selesai')
            ->exists();

        if ($hasTtv && $hasCompletedVisit) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registrasi pasien tidak dapat dibatalkan karena riwayat pemeriksaan dokter telah selesai.',
                ], 422);
            }
            return back()->withErrors([
                'error' => 'Registrasi pasien tidak dapat dibatalkan karena riwayat pemeriksaan dokter telah selesai.',
            ]);
        }

        try {
            DB::beginTransaction();

            $namaPasien = $pasien->nama_pasien;
            $noRekamMedis = $pasien->no_rekam_medis;
            $alasan = $request->input('alasan', 'Pasien tidak hadir / pembatalan registrasi oleh perawat');

            // 1. Simpan metadata audit pembatalan
            $pasien->cancelled_by = $user->id_pengguna;
            $pasien->cancellation_reason = $alasan;
            $pasien->save();

            // 2. Batalkan antrean pasien
            Antrean::where('id_pasien', $pasien->id_pasien)
                ->where('status', '!=', 'Selesai')
                ->update(['status' => 'Dibatalkan']);

            // 3. Batalkan kunjungan pasien yang belum selesai
            Kunjungan::where('id_pasien', $pasien->id_pasien)
                ->where('status', '!=', 'Selesai')
                ->update(['status' => 'Dibatalkan']);

            // Jika pasien belum ada input TTV, batalkan seluruh antrean dan kunjungan terkait
            if (!$hasTtv) {
                Antrean::where('id_pasien', $pasien->id_pasien)->update(['status' => 'Dibatalkan']);
                Kunjungan::where('id_pasien', $pasien->id_pasien)->update(['status' => 'Dibatalkan']);
            }

            // 4. Soft delete pasien (tidak dihapus fisik dari database)
            // Seluruh foreign key, riwayat TTV, dan audit tetap terjaga utuh
            $pasien->delete();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Registrasi pasien {$namaPasien} ({$noRekamMedis}) berhasil dibatalkan.",
                ]);
            }

            return redirect()
                ->route('perawat.dashboard', ['tab' => 'pasien'])
                ->with('status', "Registrasi pasien {$namaPasien} ({$noRekamMedis}) berhasil dibatalkan.");

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membatalkan registrasi pasien: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Gagal membatalkan registrasi pasien: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate ID pasien unik dalam format YYYYMMDD-NNN secara atomik.
     * Menggunakan database locking untuk mencegah race condition.
     *
     * @throws \RuntimeException jika sequence harian telah mencapai 999
     */
    private function generatePatientId(string $datePrefix): string
    {
        // Menggunakan pessimistic locking: query terakhir no_rekam_medis untuk hari ini
        // dengan lock FOR UPDATE agar concurrent request menunggu
        $lastRecord = Pasien::where('no_rekam_medis', 'like', $datePrefix . '-%')
            ->orderBy('no_rekam_medis', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastRecord) {
            // Parse sequence dari ID terakhir
            $parts = explode('-', $lastRecord->no_rekam_medis);
            $lastSequence = (int) ($parts[1] ?? 0);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        // Safety: cegah overflow di atas 999
        if ($nextSequence > 999) {
            throw new \RuntimeException(
                'Batas maksimum registrasi pasien harian (999) telah tercapai untuk tanggal ' . $datePrefix . '. ' .
                'Silakan hubungi administrator sistem.'
            );
        }

        return $datePrefix . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }
}

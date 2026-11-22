<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePasienPemeriksaanRequest;
use App\Http\Requests\UpdatePasienIdentitasRequest;
use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Hospital;
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
     * Mendukung pencarian, filter tanggal, daftar pasien, serta isolasi data rumah sakit (Multi-Tenancy).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $hospitalId = $user->hospital_id;

        // === BAGIAN 1: Daftar Antrean Aktif (Panel Kiri) Terisolasi Berdasarkan Rumah Sakit ===
        $antreanQuery = Antrean::forHospital($hospitalId)
            ->hariIni()
            ->with(['pasien', 'hospital'])
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
            $pasienTerpilih = $daftarAntrean->firstWhere('id_antrean', (int) $selectedAntreanId);
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
            $prefix = $user->hospital?->hospital_code ? 'RS' . $user->hospital->hospital_code : 'RSUA';
            $idKunjunganCode = $prefix . '-' . $tglStr . '-' . str_pad($pasienTerpilih->id_antrean, 3, '0', STR_PAD_LEFT);
        }

        // === BAGIAN 2: Daftar Pasien Terdaftar (Tab Baru) Terisolasi Rumah Sakit ===
        $searchPasien = trim($request->query('cari_pasien', ''));
        $filterTahun = $request->query('tahun');
        $filterBulan = $request->query('bulan');
        $filterTanggal = $request->query('tanggal');

        $pasienQuery = Pasien::forHospital($hospitalId)
            ->with('hospital')
            ->orderBy('created_at', 'desc')
            ->orderBy('id_pasien', 'desc');

        // Pencarian nama atau ID pasien
        if ($searchPasien) {
            $pasienQuery->where(function ($q) use ($searchPasien) {
                $q->where('nama_pasien', 'like', "%{$searchPasien}%")
                  ->orWhere('no_rekam_medis', 'like', "%{$searchPasien}%");
            });
        }

        // Filter tanggal registrasi dalam WIB. created_at tersimpan UTC, sedangkan No. RM
        // dibuat dari tanggal WIB; tanpa konversi, pasien jam 00.00-07.00 WIB masuk tanggal kemarin.
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $bagianTanggalWib = fn (string $bagian) => $sqlite
            ? "CAST(strftime('" . ['YEAR' => '%Y', 'MONTH' => '%m', 'DAY' => '%d'][$bagian] . "', datetime(created_at, '+7 hours')) AS INTEGER)"
            : "{$bagian}(DATE_ADD(created_at, INTERVAL 7 HOUR))";
        foreach (['YEAR' => $filterTahun, 'MONTH' => $filterBulan, 'DAY' => $filterTanggal] as $bagian => $nilai) {
            if ($nilai) {
                $pasienQuery->whereRaw($bagianTanggalWib($bagian) . ' = ?', [(int) $nilai]);
            }
        }

        // Eager load relasi terakhir
        $daftarPasien = $pasienQuery->paginate(15)->appends($request->query());

        // Ambil tahun yang tersedia untuk filter dalam rumah sakit perawat
        // Diambil di PHP agar kompatibel di SQLite maupun MySQL
        $tahunTersedia = Pasien::forHospital($hospitalId)
            ->whereNotNull('created_at')
            ->pluck('created_at')
            ->map(fn ($tanggal) => \Illuminate\Support\Carbon::parse($tanggal)->timezone('Asia/Jakarta')->year)
            ->unique()
            ->sortDesc()
            ->values();

        // Jika tidak ada tahun tersedia, tambahkan tahun saat ini
        if ($tahunTersedia->isEmpty()) {
            $tahunTersedia = collect([now('Asia/Jakarta')->year]);
        }

        // Tentukan tab aktif
        $activeTab = $request->query('tab', 'antrean');

        // Daftar rumah sakit aktif untuk konteks pemilihan jika dibutuhkan
        $activeHospitals = Hospital::active()->orderBy('hospital_name')->get();

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
            'activeTab',
            'activeHospitals'
        ));
    }

    /**
     * Memproses penyimpanan data pemeriksaan awal & TTV dari perawat
     * untuk pasien yang sudah ada di antrean.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // 1. Validasi input data TTV sesuai ketentuan REQ-6
        $validated = $request->validate([
            'id_antrean'      => ['required', 'integer', 'exists:antrean,id_antrean'],
            'tekanan_darah'   => ['required', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'nadi'            => ['required', 'integer', 'min:30', 'max:250'],
            'suhu_tubuh'      => ['required', 'numeric', 'min:30', 'max:45'],
            'laju_pernapasan' => ['required', 'integer', 'min:5', 'max:60'],
            // SpO2 cukup dijepit di jari, jadi tetap bisa diukur pada pasien yang tidak sadar.
            'saturasi_oksigen' => ['required', 'integer', 'min:50', 'max:100'],
            // BB/TB dan catatan boleh kosong: pasien darurat belum tentu bisa ditimbang atau ditanya.
            'berat_badan'     => ['nullable', 'numeric', 'min:1', 'max:300'],
            'tinggi_badan'    => ['nullable', 'numeric', 'min:30', 'max:250'],
            'catatan'         => ['nullable', 'string', 'max:1000'],
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
            'saturasi_oksigen.required' => 'Saturasi oksigen (SpO2) wajib diisi.',
            'saturasi_oksigen.integer' => 'Saturasi oksigen harus berupa bilangan bulat.',
            'saturasi_oksigen.min'     => 'Saturasi oksigen minimal 50%.',
            'saturasi_oksigen.max'     => 'Saturasi oksigen maksimal 100%.',
            'berat_badan.numeric'      => 'Berat badan harus berupa angka numerik.',
            'tinggi_badan.numeric'     => 'Tinggi badan harus berupa angka numerik.',
            'berat_badan.min'          => 'Berat badan minimal 1 kg.',
            'berat_badan.max'          => 'Berat badan maksimal 300 kg.',
            'tinggi_badan.min'         => 'Tinggi badan minimal 30 cm.',
            'tinggi_badan.max'         => 'Tinggi badan maksimal 250 cm.',
            'catatan.max'              => 'Catatan perawat maksimal 1000 karakter.',
        ]);

        $antrean = Antrean::with('pasien')->findOrFail($validated['id_antrean']);

        // Validasi Multi-Tenancy: Perawat tidak boleh menginput TTV untuk RS lain
        if ($user->hospital_id) {
            $targetHospitalId = $antrean->hospital_id ?? $antrean->pasien?->hospital_id;
            if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang menginput TTV untuk rumah sakit lain.');
            }
        }

        try {
            DB::beginTransaction();

            $hospitalId = $user->hospital_id ?? $antrean->hospital_id;

            // 2. Hubungkan atau buat entitas kunjungan (REQ-7)
            $kunjungan = Kunjungan::firstOrCreate(
                ['id_antrean' => $antrean->id_antrean],
                [
                    'hospital_id' => $hospitalId,
                    'id_pasien'   => $antrean->id_pasien,
                    'tanggal'     => $antrean->tanggal_kunjungan ?: Carbon::today()->toDateString(),
                    'status'      => 'Menunggu Dokter',
                ]
            );

            if ($kunjungan->isFinalized()) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Rekam medis untuk kunjungan ini telah difinalisasi dan tidak dapat diubah.');
            }

            if (!$kunjungan->hospital_id && $hospitalId) {
                $kunjungan->hospital_id = $hospitalId;
                $kunjungan->save();
            }

            // Parse tekanan darah terpisah
            $tdParts = explode('/', $validated['tekanan_darah']);
            $sistolik = (int) ($tdParts[0] ?? 0);
            $diastolik = (int) ($tdParts[1] ?? 0);

            // 3. Simpan data TTV ke tabel pemeriksaan_awal (REQ-7)
            $pemeriksaan = PemeriksaanAwal::updateOrCreate(
                ['id_kunjungan' => $kunjungan->id_kunjungan],
                [
                    'id_perawat'              => Auth::id(),
                    'tekanan_darah'           => trim($validated['tekanan_darah']),
                    'tekanan_darah_sistolik'  => $sistolik,
                    'tekanan_darah_diastolik' => $diastolik,
                    'suhu_tubuh'              => (float) $validated['suhu_tubuh'],
                    'saturasi_oksigen'        => (int) $validated['saturasi_oksigen'],
                    'nadi'                    => (int) $validated['nadi'],
                    'laju_pernapasan'         => (int) $validated['laju_pernapasan'],
                    'berat_badan'             => filled($validated['berat_badan'] ?? null) ? (float) $validated['berat_badan'] : null,
                    'tinggi_badan'            => filled($validated['tinggi_badan'] ?? null) ? (float) $validated['tinggi_badan'] : null,
                    'catatan'                 => filled($validated['catatan'] ?? null) ? trim($validated['catatan']) : null,
                ]
            );

            // 4. Antrean yang menunggu TTV menjadi 'Siap Periksa' (REQ-8). Koreksi TTV saat pasien
            //    sudah diperiksa dokter tidak mengembalikan antrean ke 'Siap Periksa'.
            if (blank($antrean->status) || $antrean->status === 'Menunggu TTV') {
                $antrean->update(['status' => 'Siap Periksa']);
            }

            // 5. Integrasi otomatis ke komponen Objective pada Draf SOAP dokter (REQ-10)
            $suhuFormatted = str_replace('.', ',', (string) round($validated['suhu_tubuh'], 1));
            // Urutan: TD, Nadi, Suhu, RR, SpO2, lalu BB/TB kalau diisi.
            $objectiveParts = [
                sprintf('TD %s mmHg', $validated['tekanan_darah']),
                sprintf('Nadi %d x/menit', $validated['nadi']),
                sprintf('Suhu %s°C', $suhuFormatted),
                sprintf('RR %d x/menit', $validated['laju_pernapasan']),
                sprintf('SpO2 %d%%', $validated['saturasi_oksigen']),
            ];
            if (filled($validated['berat_badan'] ?? null)) {
                $objectiveParts[] = sprintf('BB %g kg', $validated['berat_badan']);
            }
            if (filled($validated['tinggi_badan'] ?? null)) {
                $objectiveParts[] = sprintf('TB %g cm', $validated['tinggi_badan']);
            }
            $formattedObjective = implode(', ', $objectiveParts);

            // Ringkasan TTV hanya ditulis ke draf yang belum disusun dokter/AI (stub Objektif).
            // Draf yang sudah berisi tidak ditimpa agar temuan pemeriksaan fisik dokter tidak hilang;
            // SoapDraftPreparer akan menandainya perlu ditinjau karena snapshot TTV berubah.
            $draf = DrafSoap::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
            $drafMasihStub = $draf
                && is_null($draf->source_transcript_hash)
                && blank($draf->subjective_text)
                && blank($draf->assessment_text)
                && blank($draf->plan_text);

            if (! $draf) {
                DrafSoap::create([
                    'id_kunjungan'   => $kunjungan->id_kunjungan,
                    'id_pengguna'    => $kunjungan->id_dokter,
                    'objective_text' => $formattedObjective,
                ]);
            } elseif ($drafMasihStub) {
                $draf->update(['objective_text' => $formattedObjective]);
            } else {
                $draf->update(['needs_review' => true]);
            }

            // Perubahan TTV membuat verifikasi sebelumnya perlu ditinjau ulang.
            \App\Models\Finalisasi::where('id_kunjungan', $kunjungan->id_kunjungan)->update([
                'status' => 'Perlu Ditinjau',
                'FINAL' => false,
            ]);

            if ($kunjungan->isSoapVerified()) {
                $kunjungan->update(['status' => Kunjungan::STATUS_SOAP_BELUM_DIVERIFIKASI]);
            }

            DB::commit();

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
     * Registrasi pasien baru (data diri saja) dalam rumah sakit perawat.
     * Antrean dibuat dengan status "Menunggu TTV"; TTV diisi lewat store().
     * No. RM berformat YYYYMMDD-NNN.
     */
    public function storePasienBaru(StorePasienPemeriksaanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Perawat terikat pada rumah sakit tempatnya bertugas
        $hospitalId = $user->hospital_id ?? ($validated['hospital_id'] ?? null);

        // Umur dari form diutamakan (bisa perkiraan); kalau kosong, hitung dari tanggal lahir.
        $umur = $validated['umur'] ?? null;
        if ($umur === null && filled($validated['tanggal_lahir'] ?? null)) {
            $umur = (int) Carbon::parse($validated['tanggal_lahir'], 'Asia/Jakarta')->diffInYears(Carbon::now('Asia/Jakarta'));
        }

        try {
            DB::beginTransaction();

            $today = Carbon::now('Asia/Jakarta');
            $noRekamMedis = $this->generatePatientId($today->format('Ymd'), $hospitalId);

            $pasien = Pasien::create([
                'hospital_id'    => $hospitalId,
                'no_rekam_medis' => $noRekamMedis,
                'nama_pasien'    => trim($validated['nama_pasien']),
                'tanggal_lahir'  => $validated['tanggal_lahir'] ?? null,
                'umur'           => $umur,
                'jenis_kelamin'  => $validated['jenis_kelamin'] ?? null,
                'nik'            => $validated['nik'] ?? null,
                'no_bpjs'        => $validated['no_bpjs'] ?? null,
                'no_hp'          => $validated['no_hp'] ?? null,
            ]);

            $antrean = Antrean::create([
                'hospital_id'       => $hospitalId,
                'id_pasien'         => $pasien->id_pasien,
                'tanggal_kunjungan' => $today->toDateString(),
                'status'            => 'Menunggu TTV',
            ]);

            Kunjungan::create([
                'hospital_id' => $hospitalId,
                'id_antrean'  => $antrean->id_antrean,
                'id_pasien'   => $pasien->id_pasien,
                'tanggal'     => $today->toDateString(),
                'status'      => 'Menunggu Dokter',
            ]);

            DB::commit();

            // Langsung buka form TTV pasien ini di tab antrean.
            return redirect()
                ->route('perawat.dashboard', ['antrean_id' => $antrean->id_antrean])
                ->with('status', 'Pasien berhasil didaftarkan dengan No. RM ' . $noRekamMedis . '. Lanjutkan dengan mengisi TTV.')
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
     * Menerapkan validasi Multi-Tenancy rumah sakit.
     */
    public function showPasien(int $id): View
    {
        $user = Auth::user();
        $pasien = Pasien::with('hospital')->findOrFail($id);

        if ($user->hospital_id && $pasien->hospital_id && (int) $pasien->hospital_id !== (int) $user->hospital_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk melihat data pasien dari rumah sakit lain.');
        }

        // Ambil riwayat kunjungan beserta pemeriksaan awal terkait
        $riwayatKunjungan = Kunjungan::with(['pemeriksaanAwal', 'pemeriksaanAwal.perawat', 'dokter'])
            ->where('id_pasien', $pasien->id_pasien)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_kunjungan', 'desc')
            ->get();

        return view('dashboard.pasien-detail', compact('user', 'pasien', 'riwayatKunjungan'));
    }

    /**
     * Memperbaiki identitas pasien (nama, tanggal lahir, umur, jenis kelamin, kontak, NIK, BPJS).
     * No. RM dan rumah sakit tetap. Menerapkan validasi Multi-Tenancy rumah sakit.
     */
    public function updatePasien(UpdatePasienIdentitasRequest $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $pasien = Pasien::findOrFail($id);

        if ($user->hospital_id && $pasien->hospital_id && (int) $pasien->hospital_id !== (int) $user->hospital_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah data pasien dari rumah sakit lain.');
        }

        $validated = $request->validated();

        // Sama dengan registrasi: umur dari form diutamakan, kalau kosong dihitung dari tanggal lahir.
        $umur = $validated['umur'] ?? null;
        if ($umur === null && filled($validated['tanggal_lahir'] ?? null)) {
            $umur = (int) Carbon::parse($validated['tanggal_lahir'], 'Asia/Jakarta')->diffInYears(Carbon::now('Asia/Jakarta'));
        }

        $pasien->update([
            'nama_pasien'   => trim($validated['nama_pasien']),
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'umur'          => $umur,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'nik'           => $validated['nik'] ?? null,
            'no_bpjs'       => $validated['no_bpjs'] ?? null,
            'no_hp'         => $validated['no_hp'] ?? null,
        ]);

        return redirect()
            ->route('perawat.pasien.show', $pasien->id_pasien)
            ->with('status', 'Identitas pasien ' . $pasien->nama_pasien . ' berhasil diperbarui.');
    }

    /**
     * Membatalkan / menghapus registrasi pasien secara aman (Soft Delete).
     * Menerapkan validasi Multi-Tenancy rumah sakit.
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

        // Validasi isolasi rumah sakit: Perawat hanya dapat membatalkan pasien di rumah sakitnya
        if ($user->hospital_id && $pasien->hospital_id && (int) $pasien->hospital_id !== (int) $user->hospital_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Anda tidak berwenang membatalkan registrasi pasien rumah sakit lain.',
                ], 403);
            }
            abort(403, 'Akses ditolak: Anda tidak berwenang membatalkan registrasi pasien rumah sakit lain.');
        }

        // Validasi perlindungan data rekam medis
        $hasTtv = PemeriksaanAwal::whereHas('kunjungan', function ($q) use ($pasien) {
            $q->where('id_pasien', $pasien->id_pasien);
        })->exists();

        // Kunjungan yang sudah difinalisasi berstatus "Final" (bukan "Selesai"), atau terkunci lewat tabel finalisasi.
        $statusSelesai = ['Selesai', Kunjungan::STATUS_FINALISASI, 'Terkunci'];
        $hasCompletedVisit = Kunjungan::where('id_pasien', $pasien->id_pasien)
            ->where(function ($q) use ($statusSelesai) {
                $q->whereIn('status', $statusSelesai)
                  ->orWhereHas('finalisasi', fn ($f) => $f->where('FINAL', true));
            })
            ->exists();

        if ($hasCompletedVisit) {
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
                ->whereNotIn('status', $statusSelesai)
                ->update(['status' => 'Dibatalkan']);

            if (!$hasTtv) {
                Antrean::where('id_pasien', $pasien->id_pasien)->update(['status' => 'Dibatalkan']);
                Kunjungan::where('id_pasien', $pasien->id_pasien)->update(['status' => 'Dibatalkan']);
            }

            // 4. Soft delete pasien
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
     */
    private function generatePatientId(string $datePrefix, ?int $hospitalId = null): string
    {
        $query = Pasien::withTrashed()
            ->where('no_rekam_medis', 'like', $datePrefix . '-%')
            ->orderBy('no_rekam_medis', 'desc')
            ->lockForUpdate();

        $lastRecord = $query->first();

        if ($lastRecord) {
            $parts = explode('-', $lastRecord->no_rekam_medis);
            $lastSequence = (int) ($parts[1] ?? 0);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        if ($nextSequence > 999) {
            throw new \RuntimeException(
                'Batas maksimum registrasi pasien harian (999) telah tercapai untuk tanggal ' . $datePrefix . '. ' .
                'Silakan hubungi administrator sistem.'
            );
        }

        return $datePrefix . '-' . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }
}

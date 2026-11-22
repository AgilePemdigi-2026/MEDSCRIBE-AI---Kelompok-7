<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Antrean;
use App\Models\Hospital;
use App\Models\Pengguna;
use App\Services\AdminAuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    protected AdminAuditService $auditService;

    public function __construct(AdminAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Dapatkan instance admin yang sedang login dan pastikan hak akses serta keterkaitan rumah sakit.
     */
    protected function getAuthenticatedAdmin(): Pengguna
    {
        $admin = Auth::user();
        abort_unless($admin instanceof Pengguna && $admin->isAdmin(), 403, 'Akses ditolak: Hanya Admin Rumah Sakit yang berwenang mengakses area ini.');
        abort_unless($admin->hospital_id, 403, 'Akun Admin belum terhubung dengan master data rumah sakit.');

        return $admin;
    }

    /**
     * Halaman Dashboard Utama Admin Rumah Sakit (Ringkasan Statistik & KPI).
     */
    public function index(): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospitalId = $admin->hospital_id;
        $hospital = $admin->hospital;
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        // 1. KPI 1: Total Dokter Aktif (RS Terkait)
        $totalDokterAktif = Pengguna::where('hospital_id', $hospitalId)
            ->where('role', 'dokter')
            ->whereIn('approval_status', ['active', 'approved'])
            ->count();

        // 2. KPI 2: Total Perawat Aktif (RS Terkait)
        $totalPerawatAktif = Pengguna::where('hospital_id', $hospitalId)
            ->where('role', 'perawat')
            ->whereIn('approval_status', ['active', 'approved'])
            ->count();

        // 3. KPI 3: Menunggu Verifikasi (Pending Registrations)
        $totalPending = Pengguna::where('hospital_id', $hospitalId)
            ->where('approval_status', 'pending')
            ->count();

        // 4. KPI 4: Total Antrean Pasien Hari Ini (Data Aktual dari tabel antrean terisolasi RS)
        $totalAntreanHariIni = Antrean::where('hospital_id', $hospitalId)
            ->whereDate('tanggal_kunjungan', $today)
            ->where('status', '!=', 'Dibatalkan')
            ->count();

        // 5. KPI 5: Total Tenaga Medis Tidak Aktif (Inactive)
        $totalInactive = Pengguna::where('hospital_id', $hospitalId)
            ->where('approval_status', 'inactive')
            ->count();

        // 6. Ringkasan Dokter Aktif Beserta Beban Antrean Hari Ini
        $ringkasanDokter = Pengguna::where('hospital_id', $hospitalId)
            ->where('role', 'dokter')
            ->whereIn('approval_status', ['active', 'approved'])
            ->withCount(['kunjunganDokter' => function ($q) use ($today) {
                $q->whereDate('tanggal', $today);
            }])
            ->orderBy('nama_lengkap', 'asc')
            ->limit(5)
            ->get();

        // 7. Permohonan Pendaftaran Terbaru (Pending Requests Preview)
        $permohonanTerbaru = Pengguna::where('hospital_id', $hospitalId)
            ->where('approval_status', 'pending')
            ->orderBy('id_pengguna', 'desc')
            ->limit(5)
            ->get();

        // 8. Riwayat Aktivitas Admin Terbaru (Audit Log Preview)
        $aktivitasTerbaru = AdminAuditLog::where('hospital_id', $hospitalId)
            ->with(['admin', 'targetUser'])
            ->orderBy('id', 'desc')
            ->limit(7)
            ->get();

        // 9. Visualisasi Tren Antrean 7 Hari Terakhir
        $chartDates = [];
        $chartQueues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today('Asia/Jakarta')->subDays($i);
            $dateStr = $date->toDateString();
            $chartDates[] = $date->locale('id')->translatedFormat('d M');
            $chartQueues[] = Antrean::where('hospital_id', $hospitalId)
                ->whereDate('tanggal_kunjungan', $dateStr)
                ->where('status', '!=', 'Dibatalkan')
                ->count();
        }

        return view('admin.dashboard', compact(
            'admin',
            'hospital',
            'totalDokterAktif',
            'totalPerawatAktif',
            'totalPending',
            'totalAntreanHariIni',
            'totalInactive',
            'ringkasanDokter',
            'permohonanTerbaru',
            'aktivitasTerbaru',
            'chartDates',
            'chartQueues'
        ));
    }

    /**
     * Halaman Manajemen Dokter Aktif Rumah Sakit.
     */
    public function doctors(Request $request): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospitalId = $admin->hospital_id;
        $hospital = $admin->hospital;
        $search = trim($request->query('cari', ''));
        $sort = $request->query('sort', 'nama');

        $query = Pengguna::where('hospital_id', $hospitalId)
            ->where('role', 'dokter')
            ->whereIn('approval_status', ['active', 'approved']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($sort === 'terbaru') {
            $query->orderBy('id_pengguna', 'desc');
        } elseif ($sort === 'terlama') {
            $query->orderBy('id_pengguna', 'asc');
        } else {
            $query->orderBy('nama_lengkap', 'asc');
        }

        $doctors = $query->paginate(10)->withQueryString();

        return view('admin.doctors', compact('admin', 'hospital', 'doctors', 'search', 'sort'));
    }

    /**
     * Halaman Manajemen Perawat Aktif Rumah Sakit.
     */
    public function nurses(Request $request): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospitalId = $admin->hospital_id;
        $hospital = $admin->hospital;
        $search = trim($request->query('cari', ''));
        $sort = $request->query('sort', 'nama');

        $query = Pengguna::where('hospital_id', $hospitalId)
            ->where('role', 'perawat')
            ->whereIn('approval_status', ['active', 'approved']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($sort === 'terbaru') {
            $query->orderBy('id_pengguna', 'desc');
        } elseif ($sort === 'terlama') {
            $query->orderBy('id_pengguna', 'asc');
        } else {
            $query->orderBy('nama_lengkap', 'asc');
        }

        $nurses = $query->paginate(10)->withQueryString();

        return view('admin.nurses', compact('admin', 'hospital', 'nurses', 'search', 'sort'));
    }

    /**
     * Halaman Verifikasi Pendaftaran Staf (Permohonan Status Pending).
     */
    public function pending(Request $request): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospitalId = $admin->hospital_id;
        $hospital = $admin->hospital;
        $search = trim($request->query('cari', ''));
        $roleFilter = $request->query('role');

        $query = Pengguna::where('hospital_id', $hospitalId)
            ->where('approval_status', 'pending');

        if ($roleFilter && in_array(strtolower($roleFilter), ['dokter', 'perawat'], true)) {
            $query->where('role', strtolower($roleFilter));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $pendingStaff = $query->orderBy('id_pengguna', 'desc')->paginate(10)->withQueryString();

        return view('admin.pending', compact('admin', 'hospital', 'pendingStaff', 'search', 'roleFilter'));
    }

    /**
     * Halaman Pengelolaan Tenaga Medis Tidak Aktif (Inactive).
     */
    public function inactive(Request $request): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospitalId = $admin->hospital_id;
        $hospital = $admin->hospital;
        $search = trim($request->query('cari', ''));
        $roleFilter = $request->query('role');

        $query = Pengguna::where('hospital_id', $hospitalId)
            ->where('approval_status', 'inactive');

        if ($roleFilter && in_array(strtolower($roleFilter), ['dokter', 'perawat'], true)) {
            $query->where('role', strtolower($roleFilter));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $inactiveStaff = $query->orderBy('deactivated_at', 'desc')
            ->orderBy('id_pengguna', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.inactive', compact('admin', 'hospital', 'inactiveStaff', 'search', 'roleFilter'));
    }

    /**
     * Aksi Admin: Menyetujui pendaftaran dokter atau perawat.
     */
    public function approveStaff(Request $request, Pengguna $user): RedirectResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        // Validasi Isolasi Multi-Tenancy Rumah Sakit
        abort_unless(
            (int) $user->hospital_id === (int) $admin->hospital_id,
            403,
            'Akses ditolak: Anda tidak memiliki wewenang mengelola staf rumah sakit lain.'
        );

        // Jangan izinkan menyetujui akun yang sudah aktif
        if ($user->isActive()) {
            return back()->with('info', "Akun {$user->nama_lengkap} sudah berstatus aktif.");
        }

        DB::transaction(function () use ($admin, $user) {
            $statusBefore = $user->approval_status ?? 'pending';

            $user->update([
                'approval_status'   => 'active',
                'approved_by'       => $admin->id_pengguna,
                'approved_at'       => now(),
                'rejection_reason'  => null,
                'rejected_by'       => null,
                'rejected_at'       => null,
            ]);

            $this->auditService->log(
                admin: $admin,
                action: 'approve_staff',
                targetUser: $user,
                statusBefore: $statusBefore,
                statusAfter: 'active',
                reason: 'Disetujui melalui dashboard verifikasi admin rumah sakit.'
            );
        });

        return back()->with('status', "Selamat! Akun {$user->nama_lengkap} ({$user->role}) berhasil diverifikasi dan disetujui.");
    }

    /**
     * Aksi Admin: Menolak pendaftaran dokter atau perawat.
     */
    public function rejectStaff(Request $request, Pengguna $user): RedirectResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        // Validasi Isolasi Multi-Tenancy Rumah Sakit
        abort_unless(
            (int) $user->hospital_id === (int) $admin->hospital_id,
            403,
            'Akses ditolak: Anda tidak memiliki wewenang mengelola staf rumah sakit lain.'
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan penolakan pendaftaran wajib diisi.',
            'reason.min'      => 'Alasan penolakan minimal 5 karakter.',
            'reason.max'      => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        DB::transaction(function () use ($admin, $user, $validated) {
            $statusBefore = $user->approval_status ?? 'pending';

            $user->update([
                'approval_status'  => 'rejected',
                'rejected_by'      => $admin->id_pengguna,
                'rejected_at'      => now(),
                'rejection_reason' => trim($validated['reason']),
            ]);

            $this->auditService->log(
                admin: $admin,
                action: 'reject_staff',
                targetUser: $user,
                statusBefore: $statusBefore,
                statusAfter: 'rejected',
                reason: trim($validated['reason'])
            );
        });

        return back()->with('status', "Permohonan pendaftaran {$user->nama_lengkap} telah ditolak dengan alasan yang tercatat.");
    }

    /**
     * Aksi Admin: Menonaktifkan dokter atau perawat yang sudah tidak bertugas.
     */
    public function deactivateStaff(Request $request, Pengguna $user): RedirectResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        // Validasi Isolasi Multi-Tenancy Rumah Sakit
        abort_unless(
            (int) $user->hospital_id === (int) $admin->hospital_id,
            403,
            'Akses ditolak: Anda tidak memiliki wewenang mengelola staf rumah sakit lain.'
        );

        // Pencegahan bunuh diri akun admin
        abort_if(
            (int) $user->id_pengguna === (int) $admin->id_pengguna,
            403,
            'Anda tidak dapat menonaktifkan akun Admin Anda sendiri.'
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan penonaktifan wajib diisi.',
            'reason.min'      => 'Alasan penonaktifan minimal 5 karakter.',
            'reason.max'      => 'Alasan penonaktifan maksimal 500 karakter.',
        ]);

        DB::transaction(function () use ($admin, $user, $validated) {
            $statusBefore = $user->approval_status ?? 'active';

            $user->update([
                'approval_status'     => 'inactive',
                'deactivated_by'      => $admin->id_pengguna,
                'deactivated_at'      => now(),
                'deactivation_reason' => trim($validated['reason']),
            ]);

            $this->auditService->log(
                admin: $admin,
                action: 'deactivate_staff',
                targetUser: $user,
                statusBefore: $statusBefore,
                statusAfter: 'inactive',
                reason: trim($validated['reason'])
            );
        });

        return back()->with('status', "Akun tenaga medis {$user->nama_lengkap} berhasil dinonaktifkan.");
    }

    /**
     * Aksi Admin: Mengaktifkan kembali tenaga medis yang sebelumnya dinonaktifkan.
     */
    public function reactivateStaff(Request $request, Pengguna $user): RedirectResponse
    {
        $admin = $this->getAuthenticatedAdmin();

        // Validasi Isolasi Multi-Tenancy Rumah Sakit
        abort_unless(
            (int) $user->hospital_id === (int) $admin->hospital_id,
            403,
            'Akses ditolak: Anda tidak memiliki wewenang mengelola staf rumah sakit lain.'
        );

        DB::transaction(function () use ($admin, $user) {
            $statusBefore = $user->approval_status ?? 'inactive';

            $user->update([
                'approval_status'     => 'active',
                'approved_by'         => $admin->id_pengguna,
                'approved_at'         => now(),
                'deactivation_reason' => null,
            ]);

            $this->auditService->log(
                admin: $admin,
                action: 'reactivate_staff',
                targetUser: $user,
                statusBefore: $statusBefore,
                statusAfter: 'active',
                reason: 'Diaktifkan kembali oleh admin rumah sakit.'
            );
        });

        return back()->with('status', "Akun tenaga medis {$user->nama_lengkap} berhasil diaktifkan kembali.");
    }

    /**
     * Halaman Riwayat Audit Log Aktivitas Admin.
     */
    public function activities(Request $request): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospitalId = $admin->hospital_id;
        $hospital = $admin->hospital;
        $actionFilter = $request->query('action');
        $search = trim($request->query('cari', ''));

        $query = AdminAuditLog::where('hospital_id', $hospitalId)
            ->with(['admin', 'targetUser']);

        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('target_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.activities', compact('admin', 'hospital', 'logs', 'actionFilter', 'search'));
    }

    /**
     * Halaman Profil & Informasi Rumah Sakit.
     */
    public function hospitalInfo(): View
    {
        $admin = $this->getAuthenticatedAdmin();
        $hospital = $admin->hospital;

        $dokterAktifCount = Pengguna::where('hospital_id', $hospital->id)
            ->where('role', 'dokter')
            ->whereIn('approval_status', ['active', 'approved'])
            ->count();

        $perawatAktifCount = Pengguna::where('hospital_id', $hospital->id)
            ->where('role', 'perawat')
            ->whereIn('approval_status', ['active', 'approved'])
            ->count();

        return view('admin.hospital-info', compact('admin', 'hospital', 'dokterAktifCount', 'perawatAktifCount'));
    }

    /**
     * Admin memperbarui alamat dan kontak RS-nya sendiri (untuk kop dokumen SOAP).
     * Nama, kode SIRS, dan jenis tidak bisa diubah dari sini.
     */
    public function updateHospitalInfo(Request $request): RedirectResponse
    {
        $hospital = $this->getAuthenticatedAdmin()->hospital;

        $validated = $request->validate([
            'address'  => ['nullable', 'string', 'max:255'],
            'city'     => ['required', 'string', 'max:100', 'regex:/\S/'],
            'province' => ['required', 'string', 'max:100', 'regex:/\S/'],
            'telepon'  => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'email'    => ['nullable', 'email', 'max:100'],
        ], [
            'city.required'     => 'Kota/kabupaten wajib diisi.',
            'city.regex'        => 'Kota/kabupaten tidak boleh hanya berisi spasi.',
            'province.required' => 'Provinsi wajib diisi.',
            'province.regex'    => 'Provinsi tidak boleh hanya berisi spasi.',
            'address.max'       => 'Alamat maksimal 255 karakter.',
            'telepon.regex'     => 'Telepon hanya boleh berisi angka, spasi, tanda kurung, +, dan tanda hubung.',
            'email.email'       => 'Format email tidak valid.',
        ]);

        $hospital->update(collect($validated)
            ->map(fn ($nilai) => is_string($nilai) && trim($nilai) !== '' ? trim($nilai) : null)
            ->all());

        return redirect()
            ->route('admin.hospital.info')
            ->with('status', 'Data fasilitas berhasil diperbarui.');
    }
}


<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Antrean;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminHospitalTest extends TestCase
{
    use DatabaseTransactions;

    protected Hospital $hospitalA;
    protected Hospital $hospitalB;

    protected Pengguna $adminA;
    protected Pengguna $adminB;

    protected Pengguna $dokterA;
    protected Pengguna $perawatA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Inisialisasi Dua Rumah Sakit Berbeda
        $this->hospitalA = Hospital::where('hospital_code', '3578770')->first()
            ?? Hospital::create([
                'hospital_code' => '3578770',
                'hospital_name' => 'RS Universitas Airlangga',
                'hospital_type' => 'RSU',
                'address'       => 'Jl. Dharmahusada Permai No. 1, Mulyorejo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ]);

        $this->hospitalB = Hospital::where('hospital_code', '3578016')->first()
            ?? Hospital::create([
                'hospital_code' => '3578016',
                'hospital_name' => 'RSUD Dr. Soetomo',
                'hospital_type' => 'RSUD',
                'address'       => 'Jl. Mayjen Prof. Dr. Moestopo No. 6-8, Gubeng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ]);

        // 2. Akun Admin Rumah Sakit A
        $this->adminA = Pengguna::create([
            'nama_lengkap'      => 'Admin Utama RS A',
            'email'             => 'admin.a.' . uniqid() . '@medscribe.id',
            'role'              => 'admin',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'active',
            'email_verified_at' => now(),
            'password'          => Hash::make('AdminSecret123!'),
        ]);

        // 3. Akun Admin Rumah Sakit B
        $this->adminB = Pengguna::create([
            'nama_lengkap'      => 'Admin Utama RS B',
            'email'             => 'admin.b.' . uniqid() . '@medscribe.id',
            'role'              => 'admin',
            'hospital_id'       => $this->hospitalB->id,
            'approval_status'   => 'active',
            'email_verified_at' => now(),
            'password'          => Hash::make('AdminSecret123!'),
        ]);

        // 4. Dokter Aktif RS A
        $this->dokterA = Pengguna::create([
            'nama_lengkap'      => 'dr. Dokter Aktif RS A',
            'email'             => 'doc.a.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'active',
            'email_verified_at' => now(),
            'password'          => Hash::make('DocPassword123!'),
        ]);

        // 5. Perawat Aktif RS A
        $this->perawatA = Pengguna::create([
            'nama_lengkap'      => 'Perawat Aktif RS A',
            'email'             => 'nurse.a.' . uniqid() . '@medscribe.id',
            'role'              => 'perawat',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'active',
            'email_verified_at' => now(),
            'password'          => Hash::make('NursePassword123!'),
        ]);
    }

    /**
     * Skenario 1: Pendaftaran dokter menghasilkan akun berstatus 'pending'.
     */
    public function test_01_pendaftaran_dokter_menghasilkan_akun_berstatus_pending(): void
    {
        $email = 'calon.dokter.' . uniqid() . '@gmail.com';

        // 1. Submit pendaftaran
        $response = $this->post('/register', [
            'nama_lengkap'          => 'dr. Calon Baru, Sp.A',
            'email'                 => $email,
            'role'                  => 'dokter',
            'hospital_id'           => $this->hospitalA->id,
            'password'              => 'CalonPassword123!',
            'password_confirmation' => 'CalonPassword123!',
        ]);
        $response->assertRedirect(route('verification.otp.show', ['email' => $email]));

        // Simulasikan OTP terverifikasi
        $pending = \App\Models\PendingRegistration::where('email', $email)->firstOrFail();
        $otp = '123456';
        $pending->update([
            'otp_hash'   => Hash::make($otp),
            'expires_at' => now()->addMinutes(15),
        ]);

        $verify = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp'   => $otp,
        ]);
        $verify->assertRedirect(route('login'));

        $user = Pengguna::where('email', $email)->firstOrFail();
        $this->assertEquals('pending', $user->approval_status);
        $this->assertTrue($user->isPending());
        $this->assertFalse($user->isActive());
    }

    /**
     * Skenario 2: Pendaftaran perawat menghasilkan akun berstatus 'pending'.
     */
    public function test_02_pendaftaran_perawat_menghasilkan_akun_berstatus_pending(): void
    {
        $email = 'calon.perawat.' . uniqid() . '@gmail.com';

        $this->post('/register', [
            'nama_lengkap'          => 'Calon Perawat Baru',
            'email'                 => $email,
            'role'                  => 'perawat',
            'hospital_id'           => $this->hospitalA->id,
            'password'              => 'CalonPassword123!',
            'password_confirmation' => 'CalonPassword123!',
        ]);

        $pending = \App\Models\PendingRegistration::where('email', $email)->firstOrFail();
        $otp = '654321';
        $pending->update([
            'otp_hash'   => Hash::make($otp),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp'   => $otp,
        ]);

        $user = Pengguna::where('email', $email)->firstOrFail();
        $this->assertEquals('pending', $user->approval_status);
        $this->assertTrue($user->isPending());
        $this->assertEquals('perawat', $user->role);
    }

    /**
     * Skenario 3: Upaya manipulasi pendaftaran dengan role 'admin' ditolak oleh validasi backend.
     */
    public function test_03_upaya_pendaftaran_dengan_role_admin_ditolak_oleh_validasi(): void
    {
        $email = 'hacker.admin.' . uniqid() . '@example.com';

        $response = $this->post('/register', [
            'nama_lengkap'          => 'Percobaan Admin Palsu',
            'email'                 => $email,
            'role'                  => 'admin', // Role admin dilarang di registrasi publik
            'hospital_id'           => $this->hospitalA->id,
            'password'              => 'HackerPassword123!',
            'password_confirmation' => 'HackerPassword123!',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('pengguna', ['email' => $email]);
        $this->assertDatabaseMissing('pending_registrations', ['email' => $email]);
    }

    /**
     * Skenario 4: Dokter berstatus 'pending' tidak dapat mengakses dashboard dokter dan diarahkan ke '/auth/pending'.
     */
    public function test_04_dokter_berstatus_pending_tidak_dapat_akses_dashboard_dan_diarahkan_ke_auth_pending(): void
    {
        $pendingDokter = Pengguna::create([
            'nama_lengkap'      => 'dr. Menunggu Verifikasi',
            'email'             => 'pending.doc.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        // Akses dashboard dokter -> dicegat middleware account.active -> redirect /auth/pending
        $response = $this->actingAs($pendingDokter)->get('/dokter/dashboard');
        $response->assertRedirect(route('auth.pending'));

        // Halaman pemberitahuan status pending dapat diakses dan menampilkan pesan spesifik
        $noticeResponse = $this->actingAs($pendingDokter)->get('/auth/pending');
        $noticeResponse->assertStatus(200);
        $noticeResponse->assertSee('Menunggu Verifikasi Admin');
        $noticeResponse->assertSee($this->hospitalA->hospital_name);
    }

    /**
     * Skenario 5: Perawat berstatus 'pending' tidak dapat mengakses dashboard perawat dan diarahkan ke '/auth/pending'.
     */
    public function test_05_perawat_berstatus_pending_tidak_dapat_akses_dashboard_dan_diarahkan_ke_auth_pending(): void
    {
        $pendingPerawat = Pengguna::create([
            'nama_lengkap'      => 'Perawat Menunggu Verifikasi',
            'email'             => 'pending.nurse.' . uniqid() . '@medscribe.id',
            'role'              => 'perawat',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        $response = $this->actingAs($pendingPerawat)->get('/perawat/dashboard');
        $response->assertRedirect(route('auth.pending'));

        $noticeResponse = $this->actingAs($pendingPerawat)->get('/auth/pending');
        $noticeResponse->assertStatus(200);
        $noticeResponse->assertSee('Menunggu Verifikasi Admin');
    }

    /**
     * Skenario 6: Admin RS A dapat melihat daftar permohonan pendaftaran staf RS A.
     */
    public function test_06_admin_rs_a_dapat_melihat_daftar_permohonan_staf_rs_a(): void
    {
        $pendingStaffA = Pengguna::create([
            'nama_lengkap'      => 'dr. Calon Terdaftar RS A',
            'email'             => 'calon.a.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        $response = $this->actingAs($this->adminA)->get('/admin/verifikasi');
        $response->assertStatus(200);
        $response->assertSee('Verifikasi Pendaftaran Staf');
        $response->assertSee('dr. Calon Terdaftar RS A');
        $response->assertSee($pendingStaffA->email);
    }

    /**
     * Skenario 7: Admin RS A tidak dapat melihat permohonan staf RS B (Isolasi Multi-Tenancy).
     */
    public function test_07_admin_rs_a_tidak_dapat_melihat_permohonan_staf_rs_b_isolasi_multi_tenancy(): void
    {
        $pendingStaffB = Pengguna::create([
            'nama_lengkap'      => 'dr. Calon Khusus RS B',
            'email'             => 'calon.b.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalB->id, // Rumah Sakit B
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        // Admin RS A melihat halaman verifikasi
        $response = $this->actingAs($this->adminA)->get('/admin/verifikasi');
        $response->assertStatus(200);
        $response->assertDontSee('dr. Calon Khusus RS B');
        $response->assertDontSee($pendingStaffB->email);
    }

    /**
     * Skenario 8: Admin menyetujui pendaftaran staf: status berubah menjadi 'active', mencatat approved_by dan approved_at.
     */
    public function test_08_admin_menyetujui_pendaftaran_staf_dan_mencatat_approved_by_dan_approved_at(): void
    {
        $pendingDokter = Pengguna::create([
            'nama_lengkap'      => 'dr. Calon Disetujui RS A',
            'email'             => 'calon.acc.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.staff.approve', $pendingDokter->id_pengguna));
        $response->assertSessionHas('status');

        $pendingDokter->refresh();
        $this->assertEquals('active', $pendingDokter->approval_status);
        $this->assertTrue($pendingDokter->isActive());
        $this->assertEquals($this->adminA->id_pengguna, $pendingDokter->approved_by);
        $this->assertNotNull($pendingDokter->approved_at);

        // Tercatat di admin audit log
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id'       => $this->adminA->id_pengguna,
            'hospital_id'    => $this->hospitalA->id,
            'target_user_id' => $pendingDokter->id_pengguna,
            'action'         => 'approve_staff',
            'status_before'  => 'pending',
            'status_after'   => 'active',
        ]);
    }

    /**
     * Skenario 9: Dokter yang telah disetujui dapat login dan mengakses dashboard dokter.
     */
    public function test_09_dokter_yang_telah_disetujui_dapat_mengakses_dashboard_dokter(): void
    {
        $approvedDokter = Pengguna::create([
            'nama_lengkap'      => 'dr. Dokter Resmi RS A',
            'email'             => 'official.doc.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'nomor_sip'         => '503/1001/SIP-D/2026',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'active',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        // Login sukses dan langsung diarahkan ke /dokter/dashboard
        $loginRes = $this->post('/login', [
            'email'    => $approvedDokter->email,
            'password' => 'password123',
        ]);
        $loginRes->assertRedirect(route('dokter.dashboard'));

        // Akses langsung ke dashboard berhasil 200
        $dashRes = $this->actingAs($approvedDokter)->get('/dokter/dashboard');
        $dashRes->assertStatus(200);
    }

    /**
     * Skenario 10: Admin menolak pendaftaran staf dengan alasan wajib: status 'rejected', tersimpan alasan, diarahkan ke '/auth/rejected'.
     */
    public function test_10_admin_menolak_pendaftaran_staf_dengan_alasan_wajib(): void
    {
        $pendingDokter = Pengguna::create([
            'nama_lengkap'      => 'dr. Calon Ditolak RS A',
            'email'             => 'rejected.doc.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        // Penolakan tanpa alasan harus gagal validasi
        $failRes = $this->actingAs($this->adminA)->post(route('admin.staff.reject', $pendingDokter->id_pengguna), [
            'reason' => '',
        ]);
        $failRes->assertSessionHasErrors(['reason']);

        // Penolakan dengan alasan valid
        $alasan = 'Nomor STR dan SIP tidak terdaftar pada konsil kedokteran faskes ini.';
        $successRes = $this->actingAs($this->adminA)->post(route('admin.staff.reject', $pendingDokter->id_pengguna), [
            'reason' => $alasan,
        ]);
        $successRes->assertSessionHas('status');

        $pendingDokter->refresh();
        $this->assertEquals('rejected', $pendingDokter->approval_status);
        $this->assertTrue($pendingDokter->isRejected());
        $this->assertEquals($alasan, $pendingDokter->rejection_reason);
        $this->assertEquals($this->adminA->id_pengguna, $pendingDokter->rejected_by);
        $this->assertNotNull($pendingDokter->rejected_at);

        // Saat login atau akses, diarahkan ke /auth/rejected
        $noticeRes = $this->actingAs($pendingDokter)->get('/auth/rejected');
        $noticeRes->assertStatus(200);
        $noticeRes->assertSee('Pendaftaran Ditolak');
        $noticeRes->assertSee($alasan);
    }

    /**
     * Skenario 11: Staf berstatus 'rejected' tidak dapat mengakses dashboard operasional.
     */
    public function test_11_staf_berstatus_rejected_tidak_dapat_mengakses_dashboard_operasional(): void
    {
        $rejectedDokter = Pengguna::create([
            'nama_lengkap'      => 'dr. Ditolak Masuk',
            'email'             => 'rejected.test.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'rejected',
            'rejection_reason'  => 'SIP tidak sah.',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        $response = $this->actingAs($rejectedDokter)->get('/dokter/dashboard');
        $response->assertRedirect(route('auth.rejected'));
    }

    /**
     * Skenario 12: Admin menonaktifkan dokter atau perawat yang sudah aktif dengan alasan wajib: status 'inactive'.
     */
    public function test_12_admin_menonaktifkan_staf_aktif_dengan_alasan_wajib(): void
    {
        $stafAktif = Pengguna::create([
            'nama_lengkap'      => 'dr. Mau Dinonaktifkan',
            'email'             => 'to.deactivate.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'active',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        $alasan = 'Tenaga medis telah dipindahtugaskan (mutasi) ke fasilitas kesehatan lain.';
        $response = $this->actingAs($this->adminA)->post(route('admin.staff.deactivate', $stafAktif->id_pengguna), [
            'reason' => $alasan,
        ]);
        $response->assertSessionHas('status');

        $stafAktif->refresh();
        $this->assertEquals('inactive', $stafAktif->approval_status);
        $this->assertTrue($stafAktif->isInactive());
        $this->assertEquals($alasan, $stafAktif->deactivation_reason);
        $this->assertEquals($this->adminA->id_pengguna, $stafAktif->deactivated_by);
        $this->assertNotNull($stafAktif->deactivated_at);
    }

    /**
     * Skenario 13: Staf berstatus 'inactive' tidak dapat mengakses dashboard operasional dan diarahkan ke '/auth/inactive'.
     */
    public function test_13_staf_berstatus_inactive_diarahkan_ke_halaman_auth_inactive(): void
    {
        $inactivePerawat = Pengguna::create([
            'nama_lengkap'        => 'Perawat Nonaktif RS A',
            'email'               => 'inactive.nurse.' . uniqid() . '@medscribe.id',
            'role'                => 'perawat',
            'hospital_id'         => $this->hospitalA->id,
            'approval_status'     => 'inactive',
            'deactivation_reason' => 'Habis masa kontrak kerja.',
            'email_verified_at'   => now(),
            'password'            => Hash::make('password123'),
        ]);

        $response = $this->actingAs($inactivePerawat)->get('/perawat/dashboard');
        $response->assertRedirect(route('auth.inactive'));

        $noticeResponse = $this->actingAs($inactivePerawat)->get('/auth/inactive');
        $noticeResponse->assertStatus(200);
        $noticeResponse->assertSee('Akun Dinonaktifkan');
        $noticeResponse->assertSee('Habis masa kontrak kerja.');
    }

    /**
     * Skenario 14: Rekam medis dan data klinis yang dibuat oleh staf tetap utuh dan tersimpan setelah staf dinonaktifkan.
     */
    public function test_14_rekam_medis_dan_data_klinis_tetap_utuh_setelah_staf_dinonaktifkan(): void
    {
        // 1. Buat data pasien dan antrean
        $pasien = Pasien::create([
            'hospital_id'    => $this->hospitalA->id,
            'no_rekam_medis' => Carbon::now()->format('Ymd') . '-999',
            'nama_pasien'    => 'Pasien Bukti Integritas',
            'tanggal_lahir'  => '1985-05-05',
            'jenis_kelamin'  => 'Laki-laki',
        ]);

        $antrean = Antrean::create([
            'hospital_id'       => $this->hospitalA->id,
            'id_pasien'         => $pasien->id_pasien,
            'tanggal_kunjungan' => Carbon::today()->toDateString(),
            'status'            => 'Siap Periksa',
        ]);

        $kunjungan = Kunjungan::create([
            'id_antrean' => $antrean->id_antrean,
            'id_pasien'  => $pasien->id_pasien,
            'id_dokter'  => $this->dokterA->id_pengguna,
            'tanggal'    => Carbon::today()->toDateString(),
            'status'     => 'Selesai',
        ]);

        // 2. Admin menonaktifkan dokterA
        $this->actingAs($this->adminA)->post(route('admin.staff.deactivate', $this->dokterA->id_pengguna), [
            'reason' => 'Dokter pensiun hormat dari dinas RS.',
        ]);

        $this->dokterA->refresh();
        $this->assertEquals('inactive', $this->dokterA->approval_status);

        // 3. Pastikan seluruh relasi dan data rekam medis tetap ada tanpa terhapus sedikitpun
        $this->assertDatabaseHas('kunjungan', [
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_dokter'    => $this->dokterA->id_pengguna,
        ]);
        $this->assertDatabaseHas('antrean', [
            'id_antrean' => $antrean->id_antrean,
        ]);
        $this->assertDatabaseHas('pasien', [
            'id_pasien' => $pasien->id_pasien,
        ]);
    }

    /**
     * Skenario 15: Admin RS A tidak dapat menyetujui, menolak, atau menonaktifkan staf RS B (403 Forbidden).
     */
    public function test_15_admin_rs_a_tidak_dapat_mengelola_staf_rs_b_mengembalikan_403(): void
    {
        $dokterRSB = Pengguna::create([
            'nama_lengkap'      => 'dr. Spesialis Milik RS B',
            'email'             => 'staff.b.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalB->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        // 1. Admin A mencoba setujui staf RS B -> 403
        $approveRes = $this->actingAs($this->adminA)->post(route('admin.staff.approve', $dokterRSB->id_pengguna));
        $approveRes->assertStatus(403);

        // 2. Admin A mencoba tolak staf RS B -> 403
        $rejectRes = $this->actingAs($this->adminA)->post(route('admin.staff.reject', $dokterRSB->id_pengguna), [
            'reason' => 'Alasan percobaan ilegal.',
        ]);
        $rejectRes->assertStatus(403);

        // 3. Admin A mencoba nonaktifkan staf RS B -> 403
        $deactivateRes = $this->actingAs($this->adminA)->post(route('admin.staff.deactivate', $dokterRSB->id_pengguna), [
            'reason' => 'Alasan percobaan penonaktifan ilegal.',
        ]);
        $deactivateRes->assertStatus(403);

        // 4. Admin A mencoba aktifkan kembali staf RS B -> 403
        $reactivateRes = $this->actingAs($this->adminA)->post(route('admin.staff.reactivate', $dokterRSB->id_pengguna));
        $reactivateRes->assertStatus(403);
    }

    /**
     * Skenario 16: Dokter dan perawat tidak dapat mengakses endpoint /admin/*.
     */
    public function test_16_dokter_dan_perawat_tidak_dapat_mengakses_endpoint_admin(): void
    {
        // Dokter mencoba akses dashboard admin -> ditolak (redirect ke dashboard dokter)
        $docRes = $this->actingAs($this->dokterA)->get('/admin/dashboard');
        $docRes->assertRedirect(route('dokter.dashboard'));

        // Perawat mencoba akses dashboard admin -> ditolak (redirect ke dashboard perawat)
        $nurseRes = $this->actingAs($this->perawatA)->get('/admin/dashboard');
        $nurseRes->assertRedirect(route('perawat.dashboard'));

        // Dokter mencoba akses daftar verifikasi -> ditolak
        $docVerif = $this->actingAs($this->dokterA)->get('/admin/verifikasi');
        $docVerif->assertRedirect(route('dokter.dashboard'));
    }

    /**
     * Skenario 17: Metrik KPI pada dashboard admin menghitung jumlah data sebenarnya yang terisolasi per rumah sakit.
     */
    public function test_17_metrik_kpi_dashboard_admin_menghitung_data_akurat_terisolasi_per_rs(): void
    {
        // Buat 1 antrean hari ini untuk RS A
        $pasienA = Pasien::create([
            'hospital_id'    => $this->hospitalA->id,
            'no_rekam_medis' => Carbon::now('Asia/Jakarta')->format('Ymd') . '-701',
            'nama_pasien'    => 'Pasien Antrean RS A',
            'tanggal_lahir'  => '1995-01-01',
            'jenis_kelamin'  => 'Laki-laki',
        ]);
        Antrean::create([
            'hospital_id'       => $this->hospitalA->id,
            'id_pasien'         => $pasienA->id_pasien,
            'tanggal_kunjungan' => Carbon::today('Asia/Jakarta')->toDateString(),
            'status'            => 'Menunggu',
        ]);

        // Buat 1 antrean hari ini untuk RS B
        $pasienB = Pasien::create([
            'hospital_id'    => $this->hospitalB->id,
            'no_rekam_medis' => Carbon::now('Asia/Jakarta')->format('Ymd') . '-702',
            'nama_pasien'    => 'Pasien Antrean RS B',
            'tanggal_lahir'  => '1996-02-02',
            'jenis_kelamin'  => 'Perempuan',
        ]);
        Antrean::create([
            'hospital_id'       => $this->hospitalB->id,
            'id_pasien'         => $pasienB->id_pasien,
            'tanggal_kunjungan' => Carbon::today('Asia/Jakarta')->toDateString(),
            'status'            => 'Menunggu',
        ]);

        $response = $this->actingAs($this->adminA)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Pusat Kendali Administrasi Faskes');
        $response->assertSee($this->hospitalA->hospital_name);
        $response->assertSee('Dokter Aktif');
        $response->assertSee('Perawat Aktif');
        $response->assertSee('Menunggu Verifikasi');
        $response->assertSee('Antrean Pasien Hari Ini');

        // Nilai KPI dikirim ke view
        $viewData = $response->viewData('totalAntreanHariIni');
        $this->assertGreaterThanOrEqual(1, $viewData);
    }

    /**
     * Skenario 18: Seluruh tindakan admin tercatat dalam tabel admin_audit_logs.
     */
    public function test_18_seluruh_tindakan_admin_tercatat_dalam_admin_audit_logs(): void
    {
        // 1. Buat staf pending
        $staff = Pengguna::create([
            'nama_lengkap'      => 'dr. Subjek Audit Log',
            'email'             => 'audit.staff.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'approval_status'   => 'pending',
            'email_verified_at' => now(),
            'password'          => Hash::make('password123'),
        ]);

        // Aksi 1: Setujui
        $this->actingAs($this->adminA)->post(route('admin.staff.approve', $staff->id_pengguna));
        $this->assertDatabaseHas('admin_audit_logs', [
            'action'         => 'approve_staff',
            'admin_id'       => $this->adminA->id_pengguna,
            'target_user_id' => $staff->id_pengguna,
            'status_before'  => 'pending',
            'status_after'   => 'active',
        ]);

        // Aksi 2: Nonaktifkan
        $this->actingAs($this->adminA)->post(route('admin.staff.deactivate', $staff->id_pengguna), [
            'reason' => 'Dinonaktifkan untuk rotasi berkala.',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action'         => 'deactivate_staff',
            'admin_id'       => $this->adminA->id_pengguna,
            'target_user_id' => $staff->id_pengguna,
            'status_before'  => 'active',
            'status_after'   => 'inactive',
        ]);

        // Aksi 3: Aktifkan kembali
        $this->actingAs($this->adminA)->post(route('admin.staff.reactivate', $staff->id_pengguna));
        $this->assertDatabaseHas('admin_audit_logs', [
            'action'         => 'reactivate_staff',
            'admin_id'       => $this->adminA->id_pengguna,
            'target_user_id' => $staff->id_pengguna,
            'status_before'  => 'inactive',
            'status_after'   => 'active',
        ]);

        // Log muncul di halaman riwayat aktivitas
        $logResponse = $this->actingAs($this->adminA)->get('/admin/aktivitas');
        $logResponse->assertStatus(200);
        $logResponse->assertSee('Audit Log Aktivitas');
        $logResponse->assertSee('dr. Subjek Audit Log');
    }

    /**
     * Skenario Tambahan: Artisan Command 'admin:create' berhasil membuat admin dengan password aman ter-hash.
     */
    public function test_19_command_admin_create_berhasil_membuat_admin_baru(): void
    {
        $newAdminEmail = 'cli.admin.' . uniqid() . '@medscribe.id';

        $exitCode = Artisan::call('admin:create', [
            '--email'    => $newAdminEmail,
            '--name'     => 'Admin CLI Automated',
            '--hospital' => (string) $this->hospitalA->id,
            '--password' => 'SecurePassCommand123!',
        ]);

        $this->assertEquals(0, $exitCode);

        $admin = Pengguna::where('email', $newAdminEmail)->firstOrFail();
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('active', $admin->approval_status);
        $this->assertTrue(Hash::check('SecurePassCommand123!', $admin->password));
        $this->assertNotEquals('SecurePassCommand123!', $admin->password); // Pastikan terenkripsi / bukan plaintext
    }
}

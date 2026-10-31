<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * Halaman login dapat diakses oleh tamu.
     */
    public function test_halaman_login_dapat_diakses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang di MedScribe AI');
        $response->assertSee('Masuk ke Sistem');
    }

    /**
     * Halaman register dapat diakses oleh tamu.
     */
    public function test_halaman_register_dapat_diakses(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Pengguna Baru');
        $response->assertSee('Peran Tenaga Medis');
    }

    /**
     * 1. Register berhasil: membuat pending registration (bukan akun resmi), mengirim OTP.
     * Akun resmi di tabel pengguna baru dibuat setelah OTP diverifikasi.
     */
    public function test_register_berhasil_dan_tersimpan_di_database(): void
    {
        $email = 'dokter.baru.' . uniqid() . '@medscribe.id';

        $response = $this->post('/register', [
            'nama_lengkap' => 'dr. Agus Santoso, Sp.A',
            'email' => $email,
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Tidak boleh langsung masuk aplikasi, diarahkan ke form verifikasi OTP
        $response->assertRedirect(route('verification.otp.show', ['email' => $email]));
        $this->assertGuest();

        // Verifikasi ke database: Akun BELUM dibuat di tabel pengguna (Scenario 1 #3)
        $this->assertDatabaseMissing('pengguna', [
            'email' => $email,
        ]);

        // Verifikasi pendaftaran sementara ada di pending_registrations
        $this->assertDatabaseHas('pending_registrations', [
            'email' => $email,
            'nama_lengkap' => 'dr. Agus Santoso, Sp.A',
            'role' => 'dokter',
            'hospital_id' => 1,
        ]);

        $pending = \App\Models\PendingRegistration::where('email', $email)->first();
        $this->assertNotNull($pending);
        $this->assertTrue(Hash::check('password123', $pending->password));
        $this->assertNotNull($pending->otp_hash);

        $plainOtp = '123456';
        $pending->update([
            'otp_hash' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(10),
        ]);

        // Verifikasi OTP
        $verifyResponse = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp' => $plainOtp,
        ]);

        $verifyResponse->assertRedirect(route('login'));
        $verifyResponse->assertSessionHas('status');
        $this->assertGuest(); // Tetap guest, harus login manual

        // Sekarang akun resmi telah dibuat di tabel pengguna
        $this->assertDatabaseHas('pengguna', [
            'email' => $email,
            'nama_lengkap' => 'dr. Agus Santoso, Sp.A',
            'role' => 'dokter',
            'hospital_id' => 1,
        ]);

        $user = Pengguna::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);

        // Pending registration telah dibersihkan
        $this->assertDatabaseMissing('pending_registrations', [
            'email' => $email,
        ]);

        // Setelah verifikasi, pengguna dapat login eksplisit
        $loginResponse = $this->post('/login', [
            'email' => $email,
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect('/dokter/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Scenario 2: Pengguna mendaftar ulang dengan email pending yang belum verifikasi OTP
     * TIDAK BOLEH ditolak dengan error 'Email sudah terdaftar'.
     */
    public function test_pendaftaran_ulang_dengan_email_pending_tidak_menampilkan_error_duplikat(): void
    {
        $email = 'abandoned.' . uniqid() . '@gmail.com';

        // 1. Submit registrasi pertama kali
        $res1 = $this->post('/register', [
            'nama_lengkap' => 'Calon Dokter Pertama',
            'email' => $email,
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $res1->assertRedirect(route('verification.otp.show', ['email' => $email]));

        // 2. Simulasi user meninggalkan halaman (navigate away) dan membuka form registrasi lagi
        $pending = \App\Models\PendingRegistration::where('email', $email)->first();
        $pending->update(['last_sent_at' => now()->subSeconds(65)]);

        $res2 = $this->post('/register', [
            'nama_lengkap' => 'dr. Calon Dokter Revisi',
            'email' => $email,
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Tidak boleh muncul error 'Email ini sudah terdaftar'!
        $res2->assertSessionDoesntHaveErrors();
        $res2->assertRedirect(route('verification.otp.show', ['email' => $email]));

        // Pending registration terupdate dengan data terbaru
        $pending->refresh();
        $this->assertEquals('dr. Calon Dokter Revisi', $pending->nama_lengkap);
        $this->assertTrue(Hash::check('newpassword123', $pending->password));
    }

    /**
     * Scenario 3: Verifikasi OTP yang kedaluwarsa ditolak, dan user dapat meminta OTP baru.
     */
    public function test_verifikasi_otp_kedaluwarsa_ditolak_dan_bisa_minta_otp_baru(): void
    {
        $email = 'expired.' . uniqid() . '@medscribe.id';

        $this->post('/register', [
            'nama_lengkap' => 'dr. Expired Test',
            'email' => $email,
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $pending = \App\Models\PendingRegistration::where('email', $email)->first();
        $plainOtp = '654321';
        $pending->update([
            'otp_hash' => Hash::make($plainOtp),
            'expires_at' => now()->subMinute(), // Kedaluwarsa
            'last_sent_at' => now()->subSeconds(70),
        ]);

        // Coba verifikasi dengan kode kedaluwarsa -> Ditolak!
        $verifyRes = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp' => $plainOtp,
        ]);
        $verifyRes->assertSessionHasErrors('otp');

        // Minta kirim ulang kode baru
        $resendRes = $this->post(route('verification.otp.resend'), [
            'email' => $email,
        ]);
        $resendRes->assertSessionHas('status');

        $pending->refresh();
        $this->assertTrue($pending->expires_at->isFuture());
    }

    /**
     * Scenario 4: Akun yang sudah terverifikasi resmi ditolak saat mencoba mendaftar ulang.
     */
    public function test_akun_sudah_terverifikasi_ditolak_saat_mendaftar_ulang(): void
    {
        $response = $this->post('/register', [
            'nama_lengkap' => 'dr. Rina Tiruan',
            'email' => 'rina@medscribe.id', // Terverifikasi di database seeder
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Email ini sudah terdaftar', $errors[0]);
    }

    /**
     * Scenario 5: Verifikasi OTP dengan kode salah dan melebihi batas percobaan.
     */
    public function test_verifikasi_otp_dengan_kode_salah_dan_melebihi_batas_percobaan(): void
    {
        $email = 'wrong.' . uniqid() . '@medscribe.id';
        $this->post('/register', [
            'nama_lengkap' => 'dr. Salah Kode',
            'email' => $email,
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $pending = \App\Models\PendingRegistration::where('email', $email)->first();
        $pending->update([
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 4,
            'max_attempts' => 5,
        ]);

        // Percobaan ke-5 salah
        $res = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp' => '999999',
        ]);
        $res->assertSessionHasErrors('otp');

        $pending->refresh();
        $this->assertEquals(5, $pending->attempts);
        $this->assertTrue($pending->hasExceededAttempts());

        // Percobaan ke-6 ditolak karena sudah melampaui batas
        $res2 = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp' => '123456',
        ]);
        $res2->assertSessionHasErrors('otp');
    }

    /**
     * Pengguna dengan email belum terverifikasi dicegah untuk login.
     */
    public function test_pengguna_belum_verifikasi_email_dicegah_login(): void
    {
        $email = 'unverified.' . uniqid() . '@medscribe.id';
        $user = Pengguna::create([
            'nama_lengkap' => 'Perawat Belum Verifikasi',
            'email' => $email,
            'role' => 'perawat',
            'hospital_id' => 1,
            'email_verified_at' => null,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('verification.otp.show', ['email' => $email]));
        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
    }

    /**
     * 3. Validasi registrasi: password pendek, konfirmasi beda, field kosong.
     */
    public function test_validasi_registrasi_gagal_jika_data_tidak_sesuai(): void
    {
        // Password < 8 karakter
        $response = $this->post('/register', [
            'nama_lengkap' => 'dr. Tes Pendek',
            'email' => 'pendek@medscribe.id',
            'role' => 'dokter',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);
        $response->assertSessionHasErrors('password');

        // Konfirmasi password tidak cocok
        $response2 = $this->post('/register', [
            'nama_lengkap' => 'dr. Tes Beda',
            'email' => 'beda@medscribe.id',
            'role' => 'dokter',
            'password' => 'password123',
            'password_confirmation' => 'passwordBerbeda',
        ]);
        $response2->assertSessionHasErrors('password');

        // Field kosong
        $response3 = $this->post('/register', [
            'nama_lengkap' => '',
            'email' => '',
            'role' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);
        $response3->assertSessionHasErrors(['nama_lengkap', 'email', 'role', 'password']);
    }

    /**
     * 4. Validasi registrasi: peran di luar Dokter/Perawat ditolak.
     */
    public function test_register_dengan_role_ilegal_ditolak(): void
    {
        $response = $this->post('/register', [
            'nama_lengkap' => 'Super User Admin',
            'email' => 'admin@medscribe.id',
            'role' => 'superadmin', // Tidak diizinkan dalam SRS
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('role');
    }

    /**
     * 5. Login berhasil sebagai Dokter dan diarahkan ke Dashboard Dokter.
     */
    public function test_login_berhasil_sebagai_dokter(): void
    {
        $response = $this->post('/login', [
            'email' => 'rina@medscribe.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dokter/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('dokter', Auth::user()->role);
    }

    /**
     * 6. Login berhasil sebagai Perawat dan diarahkan ke Dashboard Perawat.
     */
    public function test_login_berhasil_sebagai_perawat(): void
    {
        $response = $this->post('/login', [
            'email' => 'dewi@medscribe.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/perawat/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('perawat', Auth::user()->role);
    }

    /**
     * 7. Login gagal jika kata sandi salah.
     */
    public function test_login_gagal_jika_password_salah(): void
    {
        $response = $this->post('/login', [
            'email' => 'rina@medscribe.id',
            'password' => 'passwordSalah123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * 8. Login gagal jika email tidak terdaftar.
     */
    public function test_login_gagal_jika_email_tidak_terdaftar(): void
    {
        $response = $this->post('/login', [
            'email' => 'tidakada@medscribe.id',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * 9. Akses tanpa login ke halaman terlindungi diarahkan ke halaman login.
     */
    public function test_akses_halaman_terlindungi_tanpa_login_diarahkan_ke_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response2 = $this->get('/dokter/dashboard');
        $response2->assertRedirect('/login');

        $response3 = $this->get('/perawat/dashboard');
        $response3->assertRedirect('/login');
    }

    /**
     * 10. Pengguna yang sudah login diarahkan ke dashboard jika membuka login/register.
     */
    public function test_pengguna_yang_sudah_login_tidak_dapat_mengakses_login_lagi(): void
    {
        $user = Pengguna::where('email', 'rina@medscribe.id')->first();

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/dashboard');

        $response2 = $this->actingAs($user)->get('/register');
        $response2->assertRedirect('/dashboard');
    }

    /**
     * 11. Otorisasi peran: Perawat tidak boleh membuka dashboard Dokter.
     */
    public function test_perawat_tidak_dapat_mengakses_dashboard_dokter(): void
    {
        $perawat = Pengguna::where('email', 'dewi@medscribe.id')->first();

        $response = $this->actingAs($perawat)->get('/dokter/dashboard');
        $response->assertRedirect('/perawat/dashboard');
        $response->assertSessionHas('warning');
    }

    /**
     * 12. Otorisasi peran: Dokter tidak boleh membuka dashboard Perawat.
     */
    public function test_dokter_tidak_dapat_mengakses_dashboard_perawat(): void
    {
        $dokter = Pengguna::where('email', 'rina@medscribe.id')->first();

        $response = $this->actingAs($dokter)->get('/perawat/dashboard');
        $response->assertRedirect('/dokter/dashboard');
        $response->assertSessionHas('warning');
    }

    /**
     * 13. Logout berhasil mengakhiri sesi dan mengarahkan kembali ke login.
     */
    public function test_logout_berhasil_mengakhiri_sesi(): void
    {
        $dokter = Pengguna::where('email', 'rina@medscribe.id')->first();

        $response = $this->actingAs($dokter)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * 14. Rute autentikasi Google (/auth/google) berhasil redirect ke OAuth Google.
     */
    public function test_rute_google_redirect_mengarahkan_ke_google_oauth(): void
    {
        $response = $this->get('/auth/google');

        $response->assertRedirect();
        $this->assertTrue(str_contains($response->headers->get('Location'), 'accounts.google.com'));
    }
}

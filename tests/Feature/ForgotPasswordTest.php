<?php

namespace Tests\Feature;

use App\Models\EmailOtp;
use App\Models\Hospital;
use App\Models\Pengguna;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use DatabaseTransactions;

    protected Pengguna $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = Pengguna::firstOrCreate(
            ['email' => 'dokter.reset@medscribe.id'],
            [
                'nama_lengkap'      => 'dr. Reset Password',
                'role'              => 'dokter',
                'hospital_id'       => 1,
                'email_verified_at' => now(),
                'approval_status'   => 'approved',
                'password'          => Hash::make('oldPassword123'),
            ]
        );
    }

    /**
     * Halaman form permohonan lupa password dapat dibuka.
     */
    public function test_halaman_lupa_password_dapat_diakses(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Lupa Password?');
        $response->assertSee('Masukkan alamat email yang terdaftar untuk menerima kode verifikasi.');
        $response->assertSee('Kirim Kode Verifikasi');
    }

    /**
     * Permintaan OTP pemulihan kata sandi merespons aman (mencegah enumerasi akun).
     */
    public function test_permintaan_reset_password_mengirimkan_otp_dan_merespons_secara_aman(): void
    {
        // 1. Email terdaftar
        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect(route('password.otp.show', ['email' => $this->user->email]));
        $this->assertDatabaseHas('email_otps', [
            'user_id' => $this->user->id_pengguna,
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
        ]);

        // 2. Email tidak terdaftar (anti-enumeration: tetap diarahkan ke halaman OTP tanpa membocorkan eksistensi)
        $fakeEmail = 'tidakada@medscribe.id';
        $responseFake = $this->post(route('password.email'), [
            'email' => $fakeEmail,
        ]);

        $responseFake->assertRedirect(route('password.otp.show', ['email' => $fakeEmail]));
    }

    /**
     * Verifikasi kode OTP salah ditolak dan menghitung jumlah percobaan.
     */
    public function test_verifikasi_otp_lupa_password_dengan_kode_salah_gagal(): void
    {
        $otpRecord = EmailOtp::create([
            'user_id'      => $this->user->id_pengguna,
            'email'        => $this->user->email,
            'otp_hash'     => Hash::make('654321'),
            'purpose'      => EmailOtp::PURPOSE_PASSWORD_RESET,
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        $response = $this->post(route('password.otp.verify'), [
            'email' => $this->user->email,
            'otp'   => '000000',
        ]);

        $response->assertSessionHasErrors('otp');
        $otpRecord->refresh();
        $this->assertEquals(1, $otpRecord->attempts);
        $this->assertNull($otpRecord->consumed_at);
    }

    /**
     * Verifikasi OTP yang benar menghasilkan token reset dan mengarahkan ke form buat password baru.
     */
    public function test_verifikasi_otp_lupa_password_berhasil_dan_mengeluarkan_token_reset(): void
    {
        $plainOtp = '654321';
        $otpRecord = EmailOtp::create([
            'user_id'      => $this->user->id_pengguna,
            'email'        => $this->user->email,
            'otp_hash'     => Hash::make($plainOtp),
            'purpose'      => EmailOtp::PURPOSE_PASSWORD_RESET,
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        $response = $this->post(route('password.otp.verify'), [
            'email' => $this->user->email,
            'otp'   => $plainOtp,
        ]);

        $otpRecord->refresh();
        $this->assertNotNull($otpRecord->consumed_at);

        // Diarahkan ke form reset password baru dengan parameter token
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertTrue(str_contains($location, '/reset-password'));
        $this->assertTrue(str_contains($location, 'token='));
    }

    /**
     * Reset password dengan token valid berhasil memperbarui password dan mengarahkan ke login.
     */
    public function test_reset_password_berhasil_dan_diarahkan_ke_login_dengan_pesan_sukses(): void
    {
        // Siapkan OTP untuk verifikasi
        $plainOtp = '654321';
        $otpRecord = EmailOtp::create([
            'user_id'      => $this->user->id_pengguna,
            'email'        => $this->user->email,
            'otp_hash'     => Hash::make($plainOtp),
            'purpose'      => EmailOtp::PURPOSE_PASSWORD_RESET,
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        // Verifikasi dulu agar mendapatkan redirect dengan reset_token
        $verifyResponse = $this->post(route('password.otp.verify'), [
            'email' => $this->user->email,
            'otp'   => $plainOtp,
        ]);

        $location = $verifyResponse->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);
        $token = $queryParams['token'] ?? null;
        $this->assertNotEmpty($token);

        // Eksekusi pembaruan password baru
        $newPassword = 'NewSecretPassword123!';
        $response = $this->post(route('password.update'), [
            'email'                 => $this->user->email,
            'token'                 => $token,
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Verifikasi ketentuan Phase 3 Step 4:
        // Redirect ke login page dengan pesan exact
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Password berhasil diperbarui. Silakan login menggunakan password baru Anda.');
        $this->assertGuest();

        // Verifikasi password baru terhash di database
        $this->user->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->user->password));

        // Verifikasi dapat login dengan password baru
        $loginResponse = $this->post('/login', [
            'email'    => $this->user->email,
            'password' => $newPassword,
        ]);

        $loginResponse->assertRedirect('/dokter/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Kode OTP registrasi tidak boleh dapat dipakai untuk pemulihan password (Isolasi Tujuan / Purpose Separation).
     */
    public function test_otp_registrasi_tidak_dapat_digunakan_untuk_reset_password(): void
    {
        $plainOtp = '888999';

        // Buat OTP dengan purpose REGISTRATION
        EmailOtp::create([
            'user_id'      => $this->user->id_pengguna,
            'email'        => $this->user->email,
            'otp_hash'     => Hash::make($plainOtp),
            'purpose'      => EmailOtp::PURPOSE_REGISTRATION, // Bukan PASSWORD_RESET
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        // Coba verifikasi di endpoint password reset
        $response = $this->post(route('password.otp.verify'), [
            'email' => $this->user->email,
            'otp'   => $plainOtp,
        ]);

        $response->assertSessionHasErrors('otp');
    }
}

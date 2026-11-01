<?php

namespace Tests\BDD;

use App\Models\EmailOtp;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;

/**
 * BDD Test Suite: Fitur 03 - Lupa Kata Sandi dan Reset Password via OTP
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/03_lupa_dan_reset_password.feature
 */
class LupaResetPasswordBDDTest extends BDDTestCase
{
    /**
     * Skenario: Permintaan reset password mengirimkan OTP ke email terdaftar
     */
    public function test_skenario_permintaan_reset_password_mengirim_otp(): void
    {
        $this->given('Akun pengguna dokter terdaftar di sistem');
        $dokter = $this->createDokter();

        $this->when('Pengguna mengirimkan permintaan forgot-password untuk emailnya');
        $response = $this->post(route('password.email'), [
            'email' => $dokter->email,
        ]);

        $this->then('Sistem menyimpan kode OTP tujuan password reset di database');
        $this->assertDatabaseHas('email_otps', [
            'user_id' => $dokter->id_pengguna,
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
        ]);

        $this->and('Pengguna diarahkan ke halaman input verifikasi OTP');
        $response->assertRedirect(route('password.otp.show', ['email' => $dokter->email]));
    }

    /**
     * Skenario: Permintaan reset password dengan email yang tidak terdaftar
     */
    public function test_skenario_permintaan_reset_password_email_tidak_terdaftar(): void
    {
        $this->given('Email fiktif yang tidak pernah terdaftar');
        $fakeEmail = 'unknown.' . uniqid() . '@medscribe.id';

        $this->when('Pengguna mengirimkan form lupa password dengan email tersebut');
        $response = $this->post(route('password.email'), [
            'email' => $fakeEmail,
        ]);

        $this->then('Sistem merespons secara aman (anti user-enumeration) mengarahkan ke halaman OTP');
        $response->assertRedirect(route('password.otp.show', ['email' => $fakeEmail]));

        $this->and('Tidak ada OTP yang dibuat untuk email fiktif tersebut');
        $this->assertDatabaseMissing('email_otps', [
            'email' => $fakeEmail,
        ]);
    }

    /**
     * Skenario: Verifikasi kode OTP reset password berhasil dan memberikan token sesi
     */
    public function test_skenario_verifikasi_otp_reset_password_berhasil(): void
    {
        $this->given('Pengguna terdaftar dengan kode OTP reset password aktif "789123"');
        $user = $this->createPerawat();
        $plainOtp = '789123';

        $otpRecord = EmailOtp::create([
            'user_id'      => $user->id_pengguna,
            'email'        => $user->email,
            'otp_hash'     => Hash::make($plainOtp),
            'purpose'      => EmailOtp::PURPOSE_PASSWORD_RESET,
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        $this->when('Pengguna memasukkan kode OTP "789123"');
        $response = $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'otp'   => $plainOtp,
        ]);

        $this->then('Kode OTP ditandai telah dikonsumsi (consumed_at terisi)');
        $otpRecord->refresh();
        $this->assertNotNull($otpRecord->consumed_at);

        $this->and('Pengguna diarahkan ke halaman /reset-password disertai parameter token');
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/reset-password', $location);
        $this->assertStringContainsString('token=', $location);
    }

    /**
     * Skenario: Verifikasi kode OTP reset password gagal jika kode salah
     */
    public function test_skenario_verifikasi_otp_reset_password_gagal_jika_salah(): void
    {
        $this->given('Pengguna dengan kode OTP reset "789123"');
        $user = $this->createDokter();

        EmailOtp::create([
            'user_id'      => $user->id_pengguna,
            'email'        => $user->email,
            'otp_hash'     => Hash::make('789123'),
            'purpose'      => EmailOtp::PURPOSE_PASSWORD_RESET,
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        $this->when('Pengguna memasukkan kode OTP yang keliru "000000"');
        $response = $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'otp'   => '000000',
        ]);

        $this->then('Sistem menolak verifikasi dengan error pada session');
        $response->assertSessionHasErrors('otp');
    }

    /**
     * Skenario: Pengguna berhasil menyetel kata sandi baru dan dapat login kembali
     */
    public function test_skenario_pengguna_berhasil_set_kata_sandi_baru(): void
    {
        $this->given('Pengguna telah memverifikasi OTP dan mendapatkan token reset');
        $user = $this->createDokter();
        $plainOtp = '654321';

        EmailOtp::create([
            'user_id'      => $user->id_pengguna,
            'email'        => $user->email,
            'otp_hash'     => Hash::make($plainOtp),
            'purpose'      => EmailOtp::PURPOSE_PASSWORD_RESET,
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
        ]);

        $verifyResponse = $this->post(route('password.otp.verify'), [
            'email' => $user->email,
            'otp'   => $plainOtp,
        ]);

        $location = $verifyResponse->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);
        $token = $queryParams['token'] ?? null;

        $this->when('Pengguna mengirim formulir update password baru "PasswordBaru#2026"');
        $newPassword = 'PasswordBaru#2026';
        $response = $this->post(route('password.update'), [
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $this->then('Password pengguna di database terupdate dengan hash baru');
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));

        $this->and('Pengguna diarahkan ke login dengan notifikasi sukses');
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->and('Pengguna berhasil login menggunakan kata sandi baru tersebut');
        $loginRes = $this->post('/login', [
            'email'    => $user->email,
            'password' => $newPassword,
        ]);
        $loginRes->assertRedirect('/dokter/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}


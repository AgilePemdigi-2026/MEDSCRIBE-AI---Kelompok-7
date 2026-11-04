<?php

namespace Tests\BDD;

use App\Models\PendingRegistration;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;

/**
 * BDD Test Suite: Fitur 02 - Registrasi Tenaga Medis dan Verifikasi OTP Email
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/02_registrasi_dan_verifikasi_otp.feature
 */
class RegistrasiVerifikasiOtpBDDTest extends BDDTestCase
{
    /**
     * Skenario: Registrasi berhasil menyimpan data sementara dan mengirimkan OTP 6-digit ke email
     * @scenario Register Success
     */
    public function test_skenario_registrasi_berhasil_menyimpan_data_pending_dan_mengirim_otp(): void
    {
        $this->given('Rumah sakit aktif RSUA terdaftar di sistem');
        $hospital = $this->getDefaultHospital();
        $email = 'calon.perawat.' . uniqid() . '@medscribe.id';

        $this->when('Calon tenaga medis mengirimkan data registrasi lengkap yang valid');
        $response = $this->post('/register', [
            'nama_lengkap'          => 'Siti Rahma',
            'email'                 => $email,
            'role'                  => 'perawat',
            'hospital_id'           => $hospital->id,
            'password'              => 'Rahasia@2026',
            'password_confirmation' => 'Rahasia@2026',
        ]);

        $this->then('Sistem mencatat data pendaftaran sementara pada tabel pending_registrations');
        $this->assertDatabaseHas('pending_registrations', [
            'email'        => $email,
            'nama_lengkap' => 'Siti Rahma',
            'role'         => 'perawat',
            'hospital_id'  => $hospital->id,
        ]);

        $this->and('Calon pengguna diarahkan ke halaman verifikasi OTP');
        $response->assertRedirect(route('verification.otp.show', ['email' => $email]));
        $response->assertSessionHas('status', 'Registrasi berhasil dimulai. Silakan periksa email Anda dan masukkan kode OTP untuk menyelesaikan pendaftaran.');
    }

    /**
     * Skenario: Registrasi ditolak jika email sudah terdaftar pada akun aktif
     * @scenario Failed Register (email already registered)
     */
    public function test_skenario_registrasi_ditolak_jika_email_sudah_terdaftar(): void
    {
        $this->given('Akun dokter aktif yang sudah terdaftar');
        $existing = $this->createDokter();

        $this->when('Calon pengguna mencoba mendaftar dengan email yang sama');
        $response = $this->post('/register', [
            'nama_lengkap'          => 'dr. Duplikat Akun',
            'email'                 => $existing->email,
            'role'                  => 'dokter',
            'hospital_id'           => $existing->hospital_id,
            'password'              => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $this->then('Sistem menolak registrasi dengan error validasi email');
        $response->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Silakan login menggunakan akun Anda atau gunakan fitur Lupa Password jika Anda tidak dapat mengakses akun.']);
    }

    /**
     * Skenario: Registrasi ditolak jika konfirmasi password tidak cocok
     * @scenario Failed Register (passwords do not match)
     */
    public function test_skenario_registrasi_ditolak_jika_konfirmasi_password_tidak_cocok(): void
    {
        $this->given('Form registrasi dengan password dan konfirmasi yang berbeda');
        $hospital = $this->getDefaultHospital();

        $this->when('Calon pengguna mengirim formulir');
        $response = $this->post('/register', [
            'nama_lengkap'          => 'Calon Perawat',
            'email'                 => 'mismatch.' . uniqid() . '@medscribe.id',
            'role'                  => 'perawat',
            'hospital_id'           => $hospital->id,
            'password'              => 'Password@123',
            'password_confirmation' => 'PasswordBeda@123',
        ]);

        $this->then('Sistem mengembalikan error konfirmasi password');
        $response->assertSessionHasErrors(['password' => 'Konfirmasi kata sandi tidak cocok.']);
    }

    /**
     * Skenario: Verifikasi OTP berhasil mengaktifkan akun dan membuat record pengguna
     * @scenario Verify Email OTP Success
     */
    public function test_skenario_verifikasi_otp_berhasil_mengaktifkan_akun(): void
    {
        $this->given('Data registrasi pending dengan OTP aktif "654321"');
        $hospital = $this->getDefaultHospital();
        $email = 'siti.otp.' . uniqid() . '@medscribe.id';
        $plainOtp = '654321';

        PendingRegistration::create([
            'nama_lengkap' => 'Siti Rahma',
            'email'        => $email,
            'role'         => 'perawat',
            'hospital_id'  => $hospital->id,
            'password'     => Hash::make('Rahasia@2026'),
            'otp_hash'     => Hash::make($plainOtp),
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
            'last_sent_at' => now(),
        ]);

        $this->when('Pengguna memasukkan kode OTP yang benar pada endpoint verifikasi');
        $response = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp'   => $plainOtp,
        ]);

        $this->then('Akun pengguna resmi tercatat di tabel pengguna');
        $user = Pengguna::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals('Siti Rahma', $user->nama_lengkap);
        $this->assertEquals('perawat', $user->role);
        $this->assertNotNull($user->email_verified_at);

        $this->and('Data pending registration dibersihkan');
        $this->assertDatabaseMissing('pending_registrations', ['email' => $email]);

        $this->and('Pengguna diarahkan ke login dengan notifikasi sukses');
        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Verifikasi email berhasil. Akun Anda telah berhasil didaftarkan. Silakan login untuk melanjutkan.');
    }

    /**
     * Skenario: Verifikasi OTP gagal jika kode yang dimasukkan salah
     * @scenario Failed Verify Email OTP (wrong code)
     */
    public function test_skenario_verifikasi_otp_gagal_jika_kode_salah(): void
    {
        $this->given('Data registrasi pending dengan kode OTP "654321"');
        $email = 'wrong.code.' . uniqid() . '@medscribe.id';
        PendingRegistration::create([
            'nama_lengkap' => 'Tes Salah Kode',
            'email'        => $email,
            'role'         => 'dokter',
            'hospital_id'  => $this->getDefaultHospital()->id,
            'password'     => Hash::make('Rahasia@2026'),
            'otp_hash'     => Hash::make('654321'),
            'expires_at'   => now()->addMinutes(10),
            'attempts'     => 0,
            'max_attempts' => 5,
            'last_sent_at' => now(),
        ]);

        $this->when('Pengguna memasukkan kode OTP salah "111111"');
        $response = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp'   => '111111',
        ]);

        $this->then('Sistem menolak verifikasi');
        $response->assertSessionHasErrors(['otp' => 'Kode verifikasi tidak valid. Sisa percobaan: 4 kali.']);

        $this->and('Record pengguna belum dibuat');
        $this->assertDatabaseMissing('pengguna', ['email' => $email]);
    }

    /**
     * Skenario: Verifikasi OTP gagal jika kode OTP telah kedaluwarsa
     * @scenario Failed Verify Email OTP (expired code)
     */
    public function test_skenario_verifikasi_otp_gagal_jika_kedaluwarsa(): void
    {
        $this->given('Data registrasi pending dengan OTP yang telah kedaluwarsa');
        $email = 'expired.code.' . uniqid() . '@medscribe.id';
        $plainOtp = '654321';

        PendingRegistration::create([
            'nama_lengkap' => 'Tes Kedaluwarsa',
            'email'        => $email,
            'role'         => 'dokter',
            'hospital_id'  => $this->getDefaultHospital()->id,
            'password'     => Hash::make('Rahasia@2026'),
            'otp_hash'     => Hash::make($plainOtp),
            'expires_at'   => now()->subMinutes(15), // Telah kadaluarsa
            'attempts'     => 0,
            'max_attempts' => 5,
            'last_sent_at' => now()->subMinutes(15),
        ]);

        $this->when('Pengguna mencoba verifikasi dengan OTP tersebut');
        $response = $this->post(route('verification.otp.verify'), [
            'email' => $email,
            'otp'   => $plainOtp,
        ]);

        $this->then('Sistem menolak verifikasi karena OTP telah kedaluwarsa');
        $response->assertSessionHasErrors(['otp' => 'Kode verifikasi telah kedaluwarsa. Silakan minta kode verifikasi baru.']);
    }

    /**
     * Skenario: Pengguna dapat meminta pengiriman ulang kode OTP baru
     * @scenario Resend Email OTP Success
     */
    public function test_skenario_pengguna_dapat_meminta_pengiriman_ulang_otp(): void
    {
        $this->given('Data registrasi pending dengan OTP lama yang siap untuk di-resend');
        $email = 'resend.code.' . uniqid() . '@medscribe.id';
        $pending = PendingRegistration::create([
            'nama_lengkap' => 'Tes Resend',
            'email'        => $email,
            'role'         => 'perawat',
            'hospital_id'  => $this->getDefaultHospital()->id,
            'password'     => Hash::make('Rahasia@2026'),
            'otp_hash'     => Hash::make('111111'),
            'expires_at'   => now()->addMinutes(2),
            'attempts'     => 1,
            'max_attempts' => 5,
            'last_sent_at' => now()->subSeconds(70), // Melewati batas cooldown 60s
        ]);

        $this->when('Pengguna mengirimkan permintaan resend OTP');
        $response = $this->post(route('verification.otp.resend'), [
            'email' => $email,
        ]);

        $this->then('Sistem berhasil memperbarui masa aktif OTP dan mengembalikan status sukses');
        $response->assertSessionHas('status', 'Kode verifikasi baru telah dikirimkan ke email Anda.');

        $this->and('Masa berlaku expires_at diperpanjang ke masa depan');
        $pending->refresh();
        $this->assertTrue($pending->expires_at->isFuture());
    }
}


<?php

namespace Tests\BDD;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * BDD Test Suite: Fitur 01 - Autentikasi dan Otorisasi Tenaga Medis
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/01_autentikasi_dan_otorisasi.feature
 */
class AutentikasiOtorisasiBDDTest extends BDDTestCase
{
    /**
     * Skenario: Dokter berhasil login dengan kredensial valid dan diarahkan ke dashboard dokter
     * @scenario Doctor Login Success
     */
    public function test_skenario_dokter_berhasil_login_dengan_kredensial_valid(): void
    {
        $this->given('Akun dokter aktif terdaftar di sistem dengan email dan password yang valid');
        $email = 'dokter.bdd.' . uniqid() . '@medscribe.id';
        $password = 'password123';
        $dokter = $this->createDokter([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->when('Dokter mengirimkan formulir login dengan email dan password yang sesuai');
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->then('Dokter berhasil terautentikasi');
        $this->assertAuthenticatedAs($dokter);

        $this->and('Dokter diarahkan menuju dashboard dokter');
        $response->assertRedirect('/dokter/dashboard');
        $response->assertSessionHas('status', 'Selamat datang kembali, ' . $dokter->nama_lengkap . '!');
    }

    /**
     * Skenario: Perawat berhasil login dengan kredensial valid dan diarahkan ke dashboard perawat
     * @scenario Nurse Login Success
     */
    public function test_skenario_perawat_berhasil_login_dengan_kredensial_valid(): void
    {
        $this->given('Akun perawat aktif terdaftar di sistem');
        $email = 'perawat.bdd.' . uniqid() . '@medscribe.id';
        $password = 'password123';
        $perawat = $this->createPerawat([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->when('Perawat mengirimkan formulir login');
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->then('Perawat berhasil terautentikasi');
        $this->assertAuthenticatedAs($perawat);

        $this->and('Perawat diarahkan menuju dashboard perawat');
        $response->assertRedirect('/perawat/dashboard');
    }

    /**
     * Skenario: Login ditolak jika password yang dimasukkan salah
     * @scenario Failed Login (wrong password)
     */
    public function test_skenario_login_ditolak_jika_password_salah(): void
    {
        $this->given('Akun dokter aktif terdaftar');
        $email = 'dokter.wrong.' . uniqid() . '@medscribe.id';
        $this->createDokter([
            'email' => $email,
            'password' => Hash::make('passwordBenar123'),
        ]);

        $this->when('Pengguna mengirimkan password yang salah');
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'passwordSalah999',
        ]);

        $this->then('Pengguna tetap berstatus tamu (guest)');
        $this->assertGuest();

        $this->and('Sistem mengembalikan error kredensial pada session');
        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak valid.']);
    }

    /**
     * Skenario: Login ditolak jika email belum terdaftar
     * @scenario Failed Login (email not registered)
     */
    public function test_skenario_login_ditolak_jika_email_belum_terdaftar(): void
    {
        $this->given('Email belum pernah didaftarkan pada sistem');
        $email = 'tidak.terdaftar.' . uniqid() . '@medscribe.id';

        $this->when('Pengguna mencoba login dengan email tersebut');
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $this->then('Autentikasi gagal dan tetap berstatus tamu');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak valid.']);
    }

    /**
     * Skenario: Login ditolak jika status akun belum disetujui (pending approval)
     * @scenario Failed Login (account pending approval)
     */
    public function test_skenario_login_ditolak_jika_approval_status_pending(): void
    {
        $this->given('Akun perawat dengan approval_status = pending');
        $email = 'pending.' . uniqid() . '@medscribe.id';
        $this->createPerawat([
            'email' => $email,
            'approval_status' => 'pending',
            'password' => Hash::make('password123'),
        ]);

        $this->when('Perawat mencoba login');
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $this->then('Sistem menolak login');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'Akun Anda sedang dalam proses peninjauan persetujuan administratif rumah sakit.']);
    }

    /**
     * Skenario: Pengguna yang sudah login diarahkan ke dashboard saat mengakses halaman login
     * @scenario Authenticated User Redirected From Login Page
     */
    public function test_skenario_pengguna_terautentikasi_diarahkan_dari_halaman_login(): void
    {
        $this->given('Dokter aktif yang sudah login');
        $dokter = $this->createDokter();

        $this->when('Dokter mengakses rute tamu /login');
        $response = $this->actingAs($dokter)->get('/login');

        $this->then('Sistem mengalihkan dokter ke rute /dashboard');
        $response->assertRedirect('/dashboard');
    }

    /**
     * Skenario: Dokter dibatasi mengakses rute Nurse Station perawat
     * @scenario Doctor Blocked from Nurse Station Pages
     */
    public function test_skenario_dokter_dilarang_mengakses_rute_perawat(): void
    {
        $this->given('Dokter aktif yang sedang login');
        $dokter = $this->createDokter();

        $this->when('Dokter mencoba mengakses /perawat/dashboard via JSON request');
        $jsonResponse = $this->actingAs($dokter)->getJson('/perawat/dashboard');

        $this->then('Sistem memberikan respon 403 Forbidden pada permintaan JSON/API');
        $jsonResponse->assertStatus(403);

        $this->and('Pada permintaan browser diarahkan kembali ke dashboard dokter dengan pesan peringatan');
        $webResponse = $this->actingAs($dokter)->get('/perawat/dashboard');
        $webResponse->assertRedirect('/dokter/dashboard');
        $webResponse->assertSessionHas('warning', 'Akses ditolak: Anda tidak memiliki akses ke halaman Nurse Station/Perawat.');
    }

    /**
     * Skenario: Perawat dibatasi mengakses rute poliklinik dokter
     * @scenario Nurse Blocked from Doctor Pages
     */
    public function test_skenario_perawat_dilarang_mengakses_rute_dokter(): void
    {
        $this->given('Perawat aktif yang sedang login');
        $perawat = $this->createPerawat();

        $this->when('Perawat mencoba mengakses /dokter/dashboard via JSON request');
        $jsonResponse = $this->actingAs($perawat)->getJson('/dokter/dashboard');

        $this->then('Sistem memberikan respon 403 Forbidden pada permintaan JSON/API');
        $jsonResponse->assertStatus(403);

        $this->and('Pada permintaan browser diarahkan kembali ke dashboard perawat dengan pesan peringatan');
        $webResponse = $this->actingAs($perawat)->get('/dokter/dashboard');
        $webResponse->assertRedirect('/perawat/dashboard');
        $webResponse->assertSessionHas('warning', 'Akses ditolak: Anda tidak memiliki akses ke halaman Dokter.');
    }

    /**
     * Skenario: Pengguna berhasil logout dan sesi dibersihkan
     * @scenario Logout Success
     */
    public function test_skenario_pengguna_berhasil_logout(): void
    {
        $this->given('Pengguna aktif yang sedang terautentikasi');
        $dokter = $this->createDokter();

        $this->when('Pengguna mengirimkan permintaan POST logout');
        $response = $this->actingAs($dokter)->post('/logout');

        $this->then('Pengguna tidak lagi terautentikasi');
        $this->assertGuest();

        $this->and('Pengguna diarahkan kembali ke halaman login');
        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Anda telah berhasil keluar dari sistem MedScribe AI.');
    }
}

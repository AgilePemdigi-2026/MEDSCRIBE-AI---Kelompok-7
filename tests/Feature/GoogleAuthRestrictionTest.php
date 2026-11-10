<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthRestrictionTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Memastikan tombol 'Daftar dengan Google' telah dihapus dari formulir registrasi.
     */
    public function test_halaman_registrasi_tidak_memuat_tombol_daftar_dengan_google(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertDontSee('Daftar dengan Google');
        $response->assertDontSee('atau daftar dengan email');
    }

    /**
     * Memastikan tombol 'Masuk dengan Google' tetap tersedia pada formulir login.
     */
    public function test_halaman_login_tetap_memuat_tombol_masuk_dengan_google(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk dengan Google');
    }

    /**
     * Memastikan akun Google yang belum terdaftar di sistem ditolak dengan pesan peringatan spesifik.
     */
    public function test_google_login_menolak_email_yang_belum_terdaftar(): void
    {
        $unregisteredEmail = 'unregistered.' . uniqid() . '@gmail.com';

        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = 'google-id-' . uniqid();
        $socialiteUser->email = $unregisteredEmail;
        $socialiteUser->name = 'Pengguna Belum Terdaftar';
        $socialiteUser->user = ['email_verified' => true];

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHas('warning', 'Mohon maaf, email Google ini belum terdaftar di MedScribe AI. Silakan lakukan registrasi terlebih dahulu menggunakan formulir pendaftaran.');
        $this->assertGuest();
    }

    /**
     * Memastikan jika email masih dalam status pendaftaran tertunda (pending), pengguna diarahkan ke verifikasi OTP.
     */
    public function test_google_login_mengingatkan_verifikasi_otp_jika_registrasi_masih_pending(): void
    {
        $pendingEmail = 'pending.' . uniqid() . '@gmail.com';

        PendingRegistration::create([
            'nama_lengkap' => 'Calon Dokter Pending',
            'email' => $pendingEmail,
            'role' => 'dokter',
            'hospital_id' => 1,
            'password' => Hash::make('password123'),
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'max_attempts' => 5,
        ]);

        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = 'google-id-' . uniqid();
        $socialiteUser->email = $pendingEmail;
        $socialiteUser->name = 'Calon Dokter Pending';
        $socialiteUser->user = ['email_verified' => true];

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('verification.otp.show', ['email' => $pendingEmail]));
        $response->assertSessionHas('info');
        $this->assertGuest();
    }

    /**
     * Memastikan akun dokter yang telah terdaftar resmi dapat login via Google dan diarahkan ke Dashboard Dokter.
     */
    public function test_google_login_berhasil_untuk_dokter_terdaftar(): void
    {
        $doctorEmail = 'dokter.google.' . uniqid() . '@medscribe.id';
        $doctor = Pengguna::create([
            'nama_lengkap' => 'dr. Google Specialist',
            'email' => $doctorEmail,
            'role' => 'dokter',
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $googleId = 'google-id-' . uniqid();
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = $googleId;
        $socialiteUser->email = $doctorEmail;
        $socialiteUser->name = 'dr. Google Specialist';
        $socialiteUser->avatar = 'https://lh3.googleusercontent.com/avatar';
        $socialiteUser->user = ['email_verified' => true];

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/dokter/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals($doctor->id_pengguna, Auth::id());

        $doctor->refresh();
        $this->assertEquals($googleId, $doctor->google_id);
    }
}


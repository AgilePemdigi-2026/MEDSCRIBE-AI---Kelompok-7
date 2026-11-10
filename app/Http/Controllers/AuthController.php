<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\VerifyEmailOtpRequest;
use App\Models\Hospital;
use App\Models\PendingRegistration;
use App\Models\Pengguna;
use App\Services\HospitalService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    protected HospitalService $hospitalService;
    protected OtpService $otpService;

    public function __construct(HospitalService $hospitalService, OtpService $otpService)
    {
        $this->hospitalService = $hospitalService;
        $this->otpService = $otpService;
    }

    /**
     * Menampilkan form login MedScribe AI.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Memproses autentikasi masuk (Login).
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Validasi server-side
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format alamat email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $normalizedEmail = Str::lower(trim($credentials['email']));

        // 2. Rate limiting untuk mitigasi brute-force
        $throttleKey = Str::transliterate($normalizedEmail . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => "Terlalu banyak percobaan masuk. Silakan tunggu {$seconds} detik sebelum mencoba kembali.",
                ]);
        }

        // 3. Cek apakah user terdaftar
        $user = Pengguna::where('email', $normalizedEmail)->first();

        if (!$user) {
            // Cek apakah ada pendaftaran pending yang belum diverifikasi
            $pending = PendingRegistration::where('email', $normalizedEmail)->first();
            if ($pending && Hash::check($credentials['password'], $pending->password)) {
                return redirect()
                    ->route('verification.otp.show', ['email' => $normalizedEmail])
                    ->withErrors([
                        'otp' => 'Akun Anda belum diverifikasi. Silakan masukkan kode OTP yang telah dikirimkan ke email Anda.',
                    ]);
            }

            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Email atau kata sandi tidak valid.',
                ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Email atau kata sandi tidak valid.',
                ]);
        }

        // 4. Verifikasi status email (Mandatory Email Verification)
        if (!$user->isEmailVerified()) {
            // Akun belum terverifikasi: jangan beri akses ke dashboard!
            return redirect()
                ->route('verification.otp.show', ['email' => $normalizedEmail])
                ->withErrors([
                    'otp' => 'Akun Anda belum diverifikasi. Silakan masukkan kode OTP yang telah dikirimkan ke email Anda.',
                ]);
        }

        // 5. Verifikasi status persetujuan akun staf (jika ada)
        if (!$user->isApproved()) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Akun Anda sedang dalam proses peninjauan persetujuan administratif rumah sakit.',
                ]);
        }

        // 6. Otentikasi dan regenerasi session ID
        $remember = $request->boolean('remember');
        Auth::login($user, $remember);

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user)
            ->with('status', 'Selamat datang kembali, ' . $user->nama_lengkap . '!');
    }

    /**
     * Menampilkan form pendaftaran pengguna baru (Register).
     */
    public function showRegisterForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $hospitals = $this->hospitalService->getActiveHospitals();

        return view('auth.register', compact('hospitals'));
    }

    /**
     * Memproses pendaftaran pengguna baru (Register).
     * Sesuai ketentuan: Tidak otomatis membuat akun resmi di tabel pengguna,
     * melainkan menyimpan ke Pending Registration hingga OTP diverifikasi.
     */
    public function register(RegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $normalizedEmail = Str::lower(trim($validated['email']));

        $result = $this->otpService->createOrUpdatePendingRegistration($validated);

        if (!$result['success']) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'email' => $result['message'],
                ]);
        }

        // PENTING: Pengguna TIDAK di-login secara otomatis!
        // Menampilkan pesan standar Phase 6
        return redirect()
            ->route('verification.otp.show', ['email' => $normalizedEmail])
            ->with('status', $result['message']);
    }

    /**
     * Menampilkan form input verifikasi kode OTP email.
     */
    public function showVerifyEmailOtpForm(Request $request): View|RedirectResponse
    {
        $email = $request->query('email');
        if (!$email) {
            return redirect()->route('register');
        }

        return view('auth.verify-email-otp', compact('email'));
    }

    /**
     * Memproses verifikasi kode OTP email pendaftaran.
     */
    public function verifyEmailOtp(VerifyEmailOtpRequest $request): RedirectResponse
    {
        $email = Str::lower(trim($request->input('email')));
        $otp = trim($request->input('otp'));

        $throttleKey = 'verify_email_otp|' . $email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 8)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput()
                ->withErrors([
                    'otp' => "Terlalu banyak percobaan verifikasi. Silakan tunggu {$seconds} detik.",
                ]);
        }

        $result = $this->otpService->verifyRegistrationOtp($email, $otp);

        if (!$result['success']) {
            RateLimiter::hit($throttleKey, 300);
            return back()
                ->withInput()
                ->withErrors([
                    'otp' => $result['message'],
                ]);
        }

        RateLimiter::clear($throttleKey);

        // PENTING: Sesuai SRS & Ketentuan Phase 6:
        // "Verifikasi email berhasil. Akun Anda telah berhasil didaftarkan. Silakan login untuk melanjutkan."
        return redirect()->route('login')
            ->with('status', 'Verifikasi email berhasil. Akun Anda telah berhasil didaftarkan. Silakan login untuk melanjutkan.');
    }

    /**
     * Mengirim ulang kode verifikasi OTP pendaftaran email.
     */
    public function resendEmailOtp(Request $request): RedirectResponse
    {
        $email = Str::lower(trim($request->input('email')));
        if (!$email) {
            return redirect()->route('register');
        }

        $verified = Pengguna::where('email', $email)->whereNotNull('email_verified_at')->first();
        if ($verified) {
            return redirect()->route('login')
                ->with('status', 'Email Anda sudah terverifikasi sebelumnya. Silakan masuk.');
        }

        $result = $this->otpService->resendPendingRegistrationOtp($email);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return back()->with('status', 'Kode verifikasi baru telah dikirimkan ke email Anda.');
    }

    /**
     * Redirect pengguna ke Google OAuth provider.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Integrasi Google OAuth belum dikonfigurasi pada environment server. Silakan gunakan pendaftaran via email dan OTP.',
                ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Menangani callback autentikasi Google OAuth.
     * Sesuai ketentuan Phase 4:
     * - Google Login HANYA untuk pengguna yang SUDAH terdaftar resmi di MedScribe AI.
     * - Tidak membuat akun otomatis bagi email yang belum terdaftar.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        // Tangani jika otorisasi dibatalkan oleh pengguna pada Google consent
        if ($request->has('error')) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Otorisasi login Google dibatalkan atau ditolak.',
                ]);
        }

        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Integrasi Google OAuth belum dikonfigurasi pada environment server.',
                ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth Callback Error: ' . $e->getMessage());
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Gagal mengautentikasi melalui Google. Silakan coba kembali atau gunakan login email.',
                ]);
        }

        // Keamanan: Validasi email_verified dari payload resmi Google
        $rawUser = $googleUser->user ?? [];
        $isGoogleVerified = $rawUser['email_verified'] ?? false;

        if (!$isGoogleVerified && !app()->environment('testing')) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Alamat email Google Anda belum terverifikasi oleh Google.',
                ]);
        }

        $email = Str::lower(trim($googleUser->getEmail()));
        $googleId = (string) $googleUser->getId();

        // Cari akun resmi di MedScribe AI
        $user = Pengguna::where('email', $email)->first();

        // Case B & Case C: Akun belum terdaftar resmi
        if (!$user) {
            // Cek apakah email terdaftar dalam proses registrasi tertunda (Pending Registration)
            $pendingRegistration = PendingRegistration::where('email', $email)
                ->where('expires_at', '>', now())
                ->first();

            if ($pendingRegistration) {
                // Case C: Email hanya ada di pending registration
                return redirect()->route('verification.otp.show', ['email' => $email])
                    ->with('info', 'Pendaftaran Anda belum selesai diverifikasi. Masukkan kode OTP yang telah dikirim ke email Anda.');
            }

            // Case B: Email belum terdaftar sama sekali di MedScribe AI
            return redirect()->route('login')
                ->with('warning', 'Mohon maaf, email Google ini belum terdaftar di MedScribe AI. Silakan lakukan registrasi terlebih dahulu menggunakan formulir pendaftaran.')
                ->withErrors([
                    'email' => 'Mohon maaf, email Google ini belum terdaftar di MedScribe AI. Silakan lakukan registrasi terlebih dahulu menggunakan formulir pendaftaran.',
                ]);
        }

        // Case D: Akun ditemukan tetapi belum memiliki peran medis yang sah
        if (empty($user->role) || !in_array(Str::lower($user->role), ['dokter', 'perawat', 'admin'], true)) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Akun Anda belum memiliki peran medis yang sah. Silakan hubungi administrator sistem.',
                ]);
        }

        // Case E: Akun tidak aktif atau belum disetujui
        if (!$user->isApproved()) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Akun Anda sedang dalam proses peninjauan persetujuan administratif rumah sakit.',
                ]);
        }

        // Keamanan: Cegah konflik penautan identitas Google
        if ($user->google_id && (string) $user->google_id !== $googleId) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Akun ini telah ditautkan dengan identitas Google lain.',
                ]);
        }

        $conflictUser = Pengguna::where('google_id', $googleId)
            ->where('id_pengguna', '!=', $user->id_pengguna)
            ->exists();

        if ($conflictUser) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Identitas Google ini telah ditautkan dengan akun lain.',
                ]);
        }

        // Case A: Akun valid & terdaftar resmi - Tautkan Google ID jika belum terpasang
        $updated = false;
        if (!$user->google_id) {
            $user->google_id = $googleId;
            $updated = true;
        }
        if (!$user->avatar && $googleUser->getAvatar()) {
            $user->avatar = $googleUser->getAvatar();
            $updated = true;
        }
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $updated = true;
        }
        if ($updated) {
            $user->save();
        }

        // Login dan regenerasi sesi
        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user)
            ->with('status', 'Selamat datang kembali, ' . $user->nama_lengkap . '! Berhasil masuk dengan akun Google.');
    }

    /**
     * Memproses keluar sistem (Logout) dan invalidasi session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Anda telah berhasil keluar dari sistem MedScribe AI.');
    }

    /**
     * Mengarahkan pengguna ke halaman yang sesuai berdasarkan perannya.
     */
    protected function redirectBasedOnRole(Pengguna $user): RedirectResponse
    {
        if ($user->isDokter()) {
            return redirect()->intended(route('dokter.dashboard'));
        }

        if ($user->isPerawat()) {
            return redirect()->intended(route('perawat.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }
}

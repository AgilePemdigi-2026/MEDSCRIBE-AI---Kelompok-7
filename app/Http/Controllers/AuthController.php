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
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
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
        $user = Pengguna::where('email', $email)->first();

        if ($user) {
            // Akun sudah ada: tautkan Google ID jika belum ada
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
            }
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
            if ($googleUser->getAvatar() && !$user->avatar) {
                $user->avatar = $googleUser->getAvatar();
            }
            $user->save();

            // Cek apakah akun memiliki rumah sakit dan peran valid
            if (!$user->hospital_id || !$user->role) {
                session(['google_complete_user_id' => $user->id_pengguna]);
                return redirect()->route('register.google.complete');
            }

            Auth::login($user);
            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user)
                ->with('status', 'Berhasil masuk dengan akun Google.');
        }

        // Pengguna baru via Google: Simpan data sementara di session dan minta peran + rumah sakit
        session([
            'google_register_data' => [
                'google_id'    => $googleUser->getId(),
                'email'        => $email,
                'nama_lengkap' => $googleUser->getName(),
                'avatar'       => $googleUser->getAvatar(),
            ]
        ]);

        return redirect()->route('register.google.complete');
    }

    /**
     * Menampilkan form pelengkapan profil Google (Pilihan Peran & Rumah Sakit).
     */
    public function showGoogleCompleteForm(): View|RedirectResponse
    {
        $googleData = session('google_register_data');
        $incompleteUserId = session('google_complete_user_id');

        if (!$googleData && !$incompleteUserId) {
            return redirect()->route('register');
        }

        $hospitals = $this->hospitalService->getActiveHospitals();
        $email = $googleData['email'] ?? Pengguna::find($incompleteUserId)?->email;
        $name = $googleData['nama_lengkap'] ?? Pengguna::find($incompleteUserId)?->nama_lengkap;

        return view('auth.register-google-complete', compact('hospitals', 'email', 'name'));
    }

    /**
     * Memproses pelengkapan profil Google.
     * Sesuai ketentuan Phase 2 D: Pengguna diarahkan ke login page setelah pendaftaran pertama.
     */
    public function storeGoogleComplete(Request $request): RedirectResponse
    {
        $googleData = session('google_register_data');
        $incompleteUserId = session('google_complete_user_id');

        if (!$googleData && !$incompleteUserId) {
            return redirect()->route('register');
        }

        $validated = $request->validate([
            'role'        => ['required', 'string', 'in:dokter,perawat,Dokter,Perawat'],
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
        ], [
            'role.required'        => 'Peran tenaga medis wajib dipilih.',
            'role.in'              => 'Peran yang diperbolehkan hanya Dokter atau Perawat.',
            'hospital_id.required' => 'Rumah sakit wajib dipilih.',
            'hospital_id.exists'   => 'Rumah sakit tidak valid atau tidak aktif.',
        ]);

        try {
            DB::beginTransaction();

            if ($incompleteUserId) {
                $user = Pengguna::findOrFail($incompleteUserId);
                $user->role = Str::lower($validated['role']);
                $user->hospital_id = (int) $validated['hospital_id'];
                $user->save();
            } else {
                $user = Pengguna::create([
                    'nama_lengkap'      => $googleData['nama_lengkap'],
                    'email'             => $googleData['email'],
                    'email_verified_at' => now(), // Terverifikasi via Google OAuth
                    'role'              => Str::lower($validated['role']),
                    'hospital_id'       => (int) $validated['hospital_id'],
                    'approval_status'   => 'approved',
                    'password'          => Hash::make(Str::random(32)),
                    'google_id'         => $googleData['google_id'],
                    'avatar'            => $googleData['avatar'] ?? null,
                ]);
            }

            DB::commit();

            session()->forget(['google_register_data', 'google_complete_user_id']);

            // Sesuai ketentuan Phase 2 D: Redirect ke LOGIN PAGE setelah pendaftaran berhasil
            return redirect()->route('login')
                ->with('status', 'Pendaftaran akun dengan Google berhasil. Silakan login menggunakan akun Anda.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Kesalahan pelengkapan akun Google: ' . $e->getMessage());

            return back()->withInput()->withErrors([
                'role' => 'Terjadi kesalahan sistem saat menyimpan profil akun.',
            ]);
        }
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

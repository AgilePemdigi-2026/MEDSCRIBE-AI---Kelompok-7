<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // 2. Rate limiting untuk mitigasi brute-force
        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => "Terlalu banyak percobaan masuk. Silakan tunggu {$seconds} detik sebelum mencoba kembali.",
                ]);
        }

        // 3. Verifikasi kredensial terhadap tabel pengguna
        $remember = $request->boolean('remember');
        $normalizedEmail = Str::lower(trim($credentials['email']));

        if (!Auth::attempt(['email' => $normalizedEmail, 'password' => $credentials['password']], $remember)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Email atau kata sandi tidak valid.',
                ]);
        }

        // 4. Bersihkan rate limiter dan regenerasi session ID (mencegah session fixation)
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();

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

        return view('auth.register');
    }

    /**
     * Memproses pendaftaran pengguna baru (Register).
     */
    public function register(Request $request): RedirectResponse
    {
        // 1. Validasi server-side yang ketat
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:pengguna,email'],
            'role' => ['required', 'string', 'in:dokter,perawat,Dokter,Perawat'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 100 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Alamat email maksimal 100 karakter.',
            'email.unique' => 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau masuk ke akun Anda.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'role.in' => 'Peran yang diperbolehkan hanya Dokter atau Perawat.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // 2. Simpan data pengguna ke tabel pengguna dengan hashing password
        try {
            DB::beginTransaction();

            $pengguna = Pengguna::create([
                'nama_lengkap' => trim($validated['nama_lengkap']),
                'email'        => Str::lower(trim($validated['email'])),
                'role'         => Str::lower(trim($validated['role'])),
                'password'     => Hash::make($validated['password']),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'email' => 'Terjadi kesalahan saat menyimpan data akun. Silakan coba kembali.',
                ]);
        }

        // 3. Login otomatis dan buat session yang aman
        Auth::login($pengguna);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($pengguna)
            ->with('status', 'Pendaftaran akun berhasil! Selamat datang di MedScribe AI, ' . $pengguna->nama_lengkap . '.');
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyPasswordResetOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Langkah 1: Menampilkan halaman permintaan lupa password.
     */
    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Langkah 1 Submit: Mengirim kode OTP pemulihan kata sandi.
     */
    public function sendResetOtp(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = Str::lower(trim($request->input('email')));
        $throttleKey = 'forgot_password_req|' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput()
                ->withErrors([
                    'email' => "Terlalu banyak permintaan reset kata sandi. Silakan tunggu {$seconds} detik.",
                ]);
        }

        RateLimiter::hit($throttleKey, 300);

        $result = $this->otpService->sendPasswordResetOtp($email);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => $result['message'],
                ]);
        }

        return redirect()
            ->route('password.otp.show', ['email' => $email])
            ->with('status', $result['message']);
    }

    /**
     * Langkah 2: Menampilkan form verifikasi kode OTP pemulihan kata sandi.
     */
    public function showVerifyOtpForm(Request $request): View|RedirectResponse
    {
        $email = $request->query('email');
        if (!$email) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password-verify', compact('email'));
    }

    /**
     * Langkah 2 Submit: Memverifikasi kode OTP pemulihan kata sandi.
     */
    public function verifyResetOtp(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $email = Str::lower(trim($request->input('email')));
        $otp = trim($request->input('otp'));

        $throttleKey = 'verify_reset_otp|' . $email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 8)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput()
                ->withErrors([
                    'otp' => "Terlalu banyak percobaan verifikasi. Silakan tunggu {$seconds} detik.",
                ]);
        }

        $result = $this->otpService->verifyPasswordResetOtp($email, $otp);

        if (!$result['success']) {
            RateLimiter::hit($throttleKey, 300);
            return back()
                ->withInput()
                ->withErrors([
                    'otp' => $result['message'],
                ]);
        }

        RateLimiter::clear($throttleKey);

        return redirect()
            ->route('password.reset', [
                'email' => $email,
                'token' => $result['reset_token'],
            ])
            ->with('status', $result['message']);
    }

    /**
     * Langkah 2 Resend: Mengirim ulang kode OTP pemulihan kata sandi.
     */
    public function resendResetOtp(Request $request): RedirectResponse
    {
        $email = Str::lower(trim($request->input('email')));
        if (!$email) {
            return redirect()->route('password.request');
        }

        $result = $this->otpService->sendPasswordResetOtp($email);

        if (!$result['success']) {
            return back()->withErrors([
                'otp' => $result['message'],
            ]);
        }

        return back()->with('status', $result['message']);
    }

    /**
     * Langkah 3: Menampilkan form pembuatan kata sandi baru.
     */
    public function showResetForm(Request $request): View|RedirectResponse
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Sesi pemulihan kata sandi tidak valid.']);
        }

        return view('auth.reset-password', compact('email', 'token'));
    }

    /**
     * Langkah 3 Submit: Memperbarui kata sandi baru.
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $email = Str::lower(trim($request->input('email')));
        $token = trim($request->input('token'));
        $password = $request->input('password');

        $result = $this->otpService->resetPasswordWithToken($email, $token, $password);

        if (!$result['success']) {
            return back()
                ->withInput($request->only('email', 'token'))
                ->withErrors([
                    'password' => $result['message'],
                ]);
        }

        // Langkah 4: Kembali ke halaman login dengan notifikasi sukses
        return redirect()->route('login')
            ->with('status', 'Password berhasil diperbarui. Silakan login menggunakan password baru Anda.');
    }
}


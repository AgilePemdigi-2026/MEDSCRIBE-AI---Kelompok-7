<?php

namespace App\Services;

use App\Mail\PasswordResetOtpMail;
use App\Mail\VerifyEmailOtpMail;
use App\Models\EmailOtp;
use App\Models\PendingRegistration;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Masa berlaku kode OTP dalam menit.
     */
    public const OTP_EXPIRY_MINUTES = 10;

    /**
     * Waktu jeda (cooldown) pengiriman ulang OTP dalam detik.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Batas maksimum percobaan salah per kode OTP.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Purpose identifier untuk verifikasi registrasi.
     */
    public const PURPOSE_REGISTRATION = 'registration_verification';

    /**
     * Purpose identifier untuk pemulihan kata sandi (lupa password).
     */
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    /**
     * Membuat atau memperbarui pendaftaran sementara (Pending Registration) dan mengirimkan OTP.
     * Sesuai ketentuan arsitektur baru: Akun resmi di tabel pengguna TIDAK dibuat pada tahap ini.
     */
    public function createOrUpdatePendingRegistration(array $data): array
    {
        $normalizedEmail = Str::lower(trim($data['email']));

        // Cek apakah email sudah terdaftar dan terverifikasi di tabel pengguna
        $existingVerified = Pengguna::where('email', $normalizedEmail)
            ->whereNotNull('email_verified_at')
            ->first();

        if ($existingVerified) {
            return [
                'success' => false,
                'already_registered' => true,
                'message' => 'Email ini sudah terdaftar. Silakan login menggunakan akun Anda atau gunakan fitur Lupa Password jika Anda tidak dapat mengakses akun.',
            ];
        }

        // Hapus akun ghost unverified di tabel pengguna jika ada dari sistem legacy
        Pengguna::where('email', $normalizedEmail)
            ->whereNull('email_verified_at')
            ->delete();

        // Cek apakah ada pending registration sebelumnya
        $pending = PendingRegistration::where('email', $normalizedEmail)->first();

        // Periksa cooldown pengiriman ulang jika ada request berulang dalam waktu singkat
        if ($pending && !$pending->canResend(self::RESEND_COOLDOWN_SECONDS)) {
            $wait = $pending->resendCooldownRemaining(self::RESEND_COOLDOWN_SECONDS);

            // Perbarui data isian formulir terbaru tanpa mengirim OTP baru bila masih cooldown
            $pending->update([
                'nama_lengkap' => trim($data['nama_lengkap']),
                'role'         => Str::lower(trim($data['role'])),
                'hospital_id'  => (int) $data['hospital_id'],
                'password'     => Hash::make($data['password']),
            ]);

            return [
                'success'  => true,
                'cooldown' => true,
                'wait'     => $wait,
                'message'  => "Registrasi berhasil diperbarui. Silakan masukkan kode OTP yang telah dikirim atau tunggu {$wait} detik untuk meminta kode baru.",
            ];
        }

        // Generate 6 digit OTP kriptografis acak
        $plainOtp = (string) random_int(100000, 999999);

        try {
            DB::beginTransaction();

            $pending = PendingRegistration::updateOrCreate(
                ['email' => $normalizedEmail],
                [
                    'nama_lengkap' => trim($data['nama_lengkap']),
                    'role'         => Str::lower(trim($data['role'])),
                    'hospital_id'  => (int) $data['hospital_id'],
                    'password'     => Hash::make($data['password']),
                    'otp_hash'     => Hash::make($plainOtp),
                    'expires_at'   => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                    'attempts'     => 0,
                    'max_attempts' => self::MAX_ATTEMPTS,
                    'last_sent_at' => Carbon::now(),
                ]
            );

            // Kirim email OTP resmi
            Mail::to($normalizedEmail)->send(new VerifyEmailOtpMail(
                $plainOtp,
                $pending->nama_lengkap,
                self::OTP_EXPIRY_MINUTES
            ));

            if (config('app.debug')) {
                Log::info("[MedScribe AI DEV] OTP Registrasi untuk {$normalizedEmail}: {$plainOtp}");
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Registrasi berhasil dimulai. Silakan periksa email Anda dan masukkan kode OTP untuk menyelesaikan pendaftaran.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memproses pending registration: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal mengirimkan kode verifikasi ke email. Silakan periksa koneksi Anda.',
            ];
        }
    }

    /**
     * Memverifikasi kode OTP pendaftaran sementara dan MERILIS akun resmi di tabel pengguna.
     */
    public function verifyPendingRegistration(string $email, string $plainOtp): array
    {
        $normalizedEmail = Str::lower(trim($email));

        // 1. Cek apakah sudah terdaftar dan terverifikasi di tabel pengguna (concurrency protection)
        $alreadyVerified = Pengguna::where('email', $normalizedEmail)
            ->whereNotNull('email_verified_at')
            ->first();

        if ($alreadyVerified) {
            return [
                'success' => true,
                'user'    => $alreadyVerified,
                'message' => 'Email Anda sudah terverifikasi sebelumnya. Silakan login.',
            ];
        }

        $pending = PendingRegistration::where('email', $normalizedEmail)->first();

        // Fallback untuk akun unverified legacy di tabel pengguna
        if (!$pending) {
            $legacyUser = Pengguna::where('email', $normalizedEmail)
                ->whereNull('email_verified_at')
                ->first();

            if ($legacyUser) {
                return $this->verifyLegacyRegistrationOtp($email, $plainOtp);
            }

            return [
                'success' => false,
                'message' => 'Data pendaftaran tidak ditemukan atau sesi telah kedaluwarsa. Silakan lakukan pendaftaran kembali.',
            ];
        }

        // 2. Cek limit percobaan
        if ($pending->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => 'Batas percobaan verifikasi telah terlampaui. Silakan minta kode verifikasi baru.',
            ];
        }

        // 3. Cek kedaluwarsa
        if ($pending->isExpired()) {
            return [
                'success' => false,
                'message' => 'Kode verifikasi telah kedaluwarsa. Silakan minta kode verifikasi baru.',
            ];
        }

        // 4. Validasi kecocokan kode hash OTP
        if (!Hash::check($plainOtp, $pending->otp_hash)) {
            $pending->increment('attempts');
            $remaining = max(0, $pending->max_attempts - $pending->attempts);

            return [
                'success' => false,
                'message' => "Kode verifikasi tidak valid. Sisa percobaan: {$remaining} kali.",
            ];
        }

        // 5. Sukses: Buat Akun Resmi di Tabel pengguna dalam Transaksi Database Atomic!
        try {
            DB::beginTransaction();

            // Kunci baris untuk proteksi konkurensi ganda
            $existing = Pengguna::where('email', $normalizedEmail)->lockForUpdate()->first();

            if ($existing && $existing->email_verified_at) {
                $pending->delete();
                DB::commit();

                return [
                    'success' => true,
                    'user'    => $existing,
                    'message' => 'Verifikasi email berhasil.',
                ];
            }

            // Buat akun resmi di tabel pengguna
            $user = Pengguna::updateOrCreate(
                ['email' => $normalizedEmail],
                [
                    'nama_lengkap'      => $pending->nama_lengkap,
                    'email'             => $normalizedEmail,
                    'password'          => $pending->password, // Password sudah ter-hash dengan Bcrypt
                    'role'              => $pending->role,
                    'hospital_id'       => $pending->hospital_id,
                    'email_verified_at' => Carbon::now(),
                    'approval_status'   => 'approved',
                ]
            );

            // Hapus data pendaftaran sementara
            $pending->delete();

            DB::commit();

            return [
                'success' => true,
                'user'    => $user,
                'message' => 'Verifikasi email berhasil. Akun Anda telah berhasil didaftarkan. Silakan login untuk melanjutkan.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membuat akun resmi pasca verifikasi OTP: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengaktifkan akun. Silakan coba kembali.',
            ];
        }
    }

    /**
     * Mengirim ulang kode OTP untuk pendaftaran sementara (Pending Registration).
     */
    public function resendPendingRegistrationOtp(string $email): array
    {
        $normalizedEmail = Str::lower(trim($email));

        // Cek apakah akun sudah terverifikasi
        $alreadyVerified = Pengguna::where('email', $normalizedEmail)
            ->whereNotNull('email_verified_at')
            ->first();

        if ($alreadyVerified) {
            return [
                'success' => false,
                'already_verified' => true,
                'message' => 'Email Anda sudah terverifikasi sebelumnya. Silakan login.',
            ];
        }

        $pending = PendingRegistration::where('email', $normalizedEmail)->first();

        if (!$pending) {
            // Fallback untuk legacy unverified di pengguna
            $legacyUser = Pengguna::where('email', $normalizedEmail)->whereNull('email_verified_at')->first();
            if ($legacyUser) {
                return $this->sendRegistrationOtp($legacyUser);
            }

            return [
                'success' => false,
                'message' => 'Data pendaftaran tidak ditemukan. Silakan lakukan pendaftaran kembali.',
            ];
        }

        // Cek cooldown
        if (!$pending->canResend(self::RESEND_COOLDOWN_SECONDS)) {
            $wait = $pending->resendCooldownRemaining(self::RESEND_COOLDOWN_SECONDS);
            return [
                'success'  => false,
                'cooldown' => true,
                'wait'     => $wait,
                'message'  => "Mohon tunggu {$wait} detik sebelum meminta kode baru.",
            ];
        }

        $plainOtp = (string) random_int(100000, 999999);

        try {
            $pending->update([
                'otp_hash'     => Hash::make($plainOtp),
                'expires_at'   => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'attempts'     => 0,
                'last_sent_at' => Carbon::now(),
            ]);

            Mail::to($normalizedEmail)->send(new VerifyEmailOtpMail(
                $plainOtp,
                $pending->nama_lengkap,
                self::OTP_EXPIRY_MINUTES
            ));

            if (config('app.debug')) {
                Log::info("[MedScribe AI DEV] OTP Resend untuk {$normalizedEmail}: {$plainOtp}");
            }

            return [
                'success' => true,
                'message' => 'Kode verifikasi baru telah dikirimkan ke email Anda.',
            ];

        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan ulang OTP pendaftaran: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal mengirimkan kode verifikasi ke email. Silakan periksa koneksi Anda.',
            ];
        }
    }

    /**
     * Menghasilkan dan mengirimkan OTP verifikasi email saat registrasi.
     */
    public function sendRegistrationOtp(Pengguna $user): array
    {
        $normalizedEmail = Str::lower(trim($user->email));

        // 1. Cek cooldown pengiriman ulang
        $recentOtp = EmailOtp::where('email', $normalizedEmail)
            ->where('purpose', self::PURPOSE_REGISTRATION)
            ->latest('id')
            ->first();

        if ($recentOtp && $recentOtp->created_at) {
            $unlockAt = $recentOtp->created_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if (!$unlockAt->isPast()) {
                $wait = (int) ceil(abs($unlockAt->diffInSeconds(Carbon::now())));
                return [
                    'success'  => false,
                    'cooldown' => true,
                    'wait'     => $wait,
                    'message'  => "Mohon tunggu {$wait} detik sebelum meminta kode verifikasi baru.",
                ];
            }
        }

        // 2. Generate 6 digit OTP kriptografis acak
        $plainOtp = (string) random_int(100000, 999999);

        try {
            DB::beginTransaction();

            // 3. Batalkan kode OTP lama yang belum terpakai untuk tujuan registrasi
            EmailOtp::where('email', $normalizedEmail)
                ->where('purpose', self::PURPOSE_REGISTRATION)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => Carbon::now()]);

            // 4. Simpan hanya representasi hash dari OTP
            $otpRecord = EmailOtp::create([
                'user_id'      => $user->id_pengguna,
                'email'        => $normalizedEmail,
                'otp_hash'     => Hash::make($plainOtp),
                'purpose'      => self::PURPOSE_REGISTRATION,
                'expires_at'   => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'attempts'     => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
            ]);

            // 5. Kirim email OTP dengan template resmi MedScribe AI
            Mail::to($normalizedEmail)->send(new VerifyEmailOtpMail(
                $plainOtp,
                $user->nama_lengkap,
                self::OTP_EXPIRY_MINUTES
            ));

            if (config('app.debug')) {
                Log::info("[MedScribe AI DEV] OTP Registrasi untuk {$normalizedEmail}: {$plainOtp}");
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Kode verifikasi telah dikirimkan ke email Anda.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal mengirimkan email verifikasi registrasi: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal mengirimkan kode verifikasi ke email. Silakan periksa konfigurasi mail server Anda.',
            ];
        }
    }

    /**
     * Memverifikasi kode OTP registrasi email (mengarahkan ke pending atau legacy).
     */
    public function verifyRegistrationOtp(string $email, string $plainOtp): array
    {
        $normalizedEmail = Str::lower(trim($email));

        if (PendingRegistration::where('email', $normalizedEmail)->exists()) {
            return $this->verifyPendingRegistration($email, $plainOtp);
        }

        return $this->verifyLegacyRegistrationOtp($email, $plainOtp);
    }

    /**
     * Memverifikasi kode OTP legacy untuk akun yang sudah ada di tabel pengguna.
     */
    public function verifyLegacyRegistrationOtp(string $email, string $plainOtp): array
    {
        $normalizedEmail = Str::lower(trim($email));

        $otpRecord = EmailOtp::where('email', $normalizedEmail)
            ->where('purpose', self::PURPOSE_REGISTRATION)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (!$otpRecord) {
            return [
                'success' => false,
                'message' => 'Kode verifikasi tidak ditemukan atau telah digunakan. Silakan minta kode baru.',
            ];
        }

        if ($otpRecord->isExpired()) {
            return [
                'success' => false,
                'message' => 'Kode verifikasi telah kedaluwarsa. Silakan minta kode verifikasi baru.',
            ];
        }

        if ($otpRecord->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => 'Batas percobaan verifikasi telah terlampaui. Silakan minta kode baru.',
            ];
        }

        if (!Hash::check($plainOtp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $remaining = max(0, $otpRecord->max_attempts - $otpRecord->attempts);

            return [
                'success' => false,
                'message' => "Kode verifikasi tidak valid. Sisa percobaan: {$remaining} kali.",
            ];
        }

        try {
            DB::beginTransaction();

            // Tandai OTP telah dikonsumsi (single-use)
            $otpRecord->update(['consumed_at' => Carbon::now()]);

            // Verifikasi akun pengguna di database
            $user = Pengguna::where('email', $normalizedEmail)->first();
            if ($user) {
                $user->email_verified_at = Carbon::now();
                $user->save();
            }

            DB::commit();

            return [
                'success' => true,
                'user'    => $user,
                'message' => 'Verifikasi email berhasil. Silakan login menggunakan akun Anda.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memverifikasi OTP registrasi: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memverifikasi akun.',
            ];
        }
    }

    /**
     * Menghasilkan dan mengirimkan OTP pemulihan kata sandi (Lupa Password).
     * Melindungi dari enumerasi email dengan selalu memberikan respon yang konsisten.
     */
    public function sendPasswordResetOtp(string $email): array
    {
        $normalizedEmail = Str::lower(trim($email));

        // 1. Cek cooldown pengiriman ulang
        $recentOtp = EmailOtp::where('email', $normalizedEmail)
            ->where('purpose', self::PURPOSE_PASSWORD_RESET)
            ->latest('id')
            ->first();

        if ($recentOtp && $recentOtp->created_at) {
            $unlockAt = $recentOtp->created_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);
            if (!$unlockAt->isPast()) {
                $wait = (int) ceil(abs($unlockAt->diffInSeconds(Carbon::now())));
                return [
                    'success'  => false,
                    'cooldown' => true,
                    'wait'     => $wait,
                    'message'  => "Mohon tunggu {$wait} detik sebelum meminta kode pemulihan baru.",
                ];
            }
        }

        $user = Pengguna::where('email', $normalizedEmail)->first();

        // Jika email tidak terdaftar, jangan bocorkan eksistensi akun (anti-enumeration).
        // Kembalikan status sukses generik.
        if (!$user) {
            return [
                'success' => true,
                'message' => 'Jika alamat email terdaftar di sistem, kode verifikasi 6 digit telah dikirimkan.',
            ];
        }

        $plainOtp = (string) random_int(100000, 999999);

        try {
            DB::beginTransaction();

            // Batalkan OTP reset sebelumnya yang belum terpakai
            EmailOtp::where('email', $normalizedEmail)
                ->where('purpose', self::PURPOSE_PASSWORD_RESET)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => Carbon::now()]);

            EmailOtp::create([
                'user_id'      => $user->id_pengguna,
                'email'        => $normalizedEmail,
                'otp_hash'     => Hash::make($plainOtp),
                'purpose'      => self::PURPOSE_PASSWORD_RESET,
                'expires_at'   => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'attempts'     => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
            ]);

            Mail::to($normalizedEmail)->send(new PasswordResetOtpMail(
                $plainOtp,
                $user->nama_lengkap,
                self::OTP_EXPIRY_MINUTES
            ));

            if (config('app.debug')) {
                Log::info("[MedScribe AI DEV] OTP Reset Password untuk {$normalizedEmail}: {$plainOtp}");
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Jika alamat email terdaftar di sistem, kode verifikasi 6 digit telah dikirimkan.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal mengirimkan OTP reset password: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal mengirimkan email pemulihan kata sandi. Silakan periksa koneksi atau coba beberapa saat lagi.',
            ];
        }
    }

    /**
     * Memverifikasi kode OTP pemulihan kata sandi.
     * Jika valid, menghasilkan token otorisasi reset kata sandi sementara.
     */
    public function verifyPasswordResetOtp(string $email, string $plainOtp): array
    {
        $normalizedEmail = Str::lower(trim($email));

        $otpRecord = EmailOtp::where('email', $normalizedEmail)
            ->where('purpose', self::PURPOSE_PASSWORD_RESET)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (!$otpRecord) {
            return [
                'success' => false,
                'message' => 'Kode verifikasi tidak ditemukan atau telah digunakan. Silakan minta kode baru.',
            ];
        }

        if ($otpRecord->isExpired()) {
            return [
                'success' => false,
                'message' => 'Kode verifikasi telah kedaluwarsa. Silakan minta kode verifikasi baru.',
            ];
        }

        if ($otpRecord->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => 'Batas percobaan verifikasi telah terlampaui. Silakan minta kode baru.',
            ];
        }

        if (!Hash::check($plainOtp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $remaining = max(0, $otpRecord->max_attempts - $otpRecord->attempts);

            return [
                'success' => false,
                'message' => "Kode verifikasi tidak valid. Sisa percobaan: {$remaining} kali.",
            ];
        }

        try {
            DB::beginTransaction();

            // Tandai OTP telah dikonsumsi (single-use)
            $otpRecord->update(['consumed_at' => Carbon::now()]);

            // Buat token reset sementara yang aman dan simpan di cache/session
            $resetToken = Str::random(64);
            $cacheKey = 'password_reset_auth:' . sha1($normalizedEmail . $resetToken);

            cache()->put($cacheKey, [
                'email'      => $normalizedEmail,
                'expires_at' => Carbon::now()->addMinutes(15),
            ], now()->addMinutes(15));

            DB::commit();

            return [
                'success'     => true,
                'reset_token' => $resetToken,
                'message'     => 'Verifikasi berhasil. Silakan tentukan kata sandi baru Anda.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyelesaikan verifikasi OTP password reset: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memverifikasi kode.',
            ];
        }
    }

    /**
     * Memperbarui kata sandi dengan token otorisasi yang sah.
     */
    public function resetPasswordWithToken(string $email, string $token, string $newPassword): array
    {
        $normalizedEmail = Str::lower(trim($email));
        $cacheKey = 'password_reset_auth:' . sha1($normalizedEmail . $token);

        $authData = cache()->get($cacheKey);

        if (!$authData || $authData['email'] !== $normalizedEmail) {
            return [
                'success' => false,
                'message' => 'Otorisasi perubahan kata sandi tidak valid atau telah kedaluwarsa. Silakan ulangi langkah pemulihan.',
            ];
        }

        $user = Pengguna::where('email', $normalizedEmail)->first();
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Akun pengguna tidak ditemukan.',
            ];
        }

        try {
            DB::beginTransaction();

            // 1. Update kata sandi dengan hash aman
            $user->password = Hash::make($newPassword);
            // 2. Rotasi remember_token untuk invalidasi sesi lama
            $user->remember_token = Str::random(60);
            $user->save();

            // 3. Hapus token otorisasi reset
            cache()->forget($cacheKey);

            // 4. Batalkan seluruh outstanding password-reset OTP
            EmailOtp::where('email', $normalizedEmail)
                ->where('purpose', self::PURPOSE_PASSWORD_RESET)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => Carbon::now()]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Password berhasil diperbarui. Silakan login menggunakan password baru Anda.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal memperbarui kata sandi pengguna: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan teknis saat memperbarui kata sandi.',
            ];
        }
    }
}


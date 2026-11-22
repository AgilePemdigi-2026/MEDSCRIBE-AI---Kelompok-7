<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokterController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\Icd10Controller;
use App\Http\Controllers\InterviewAudioController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\PemeriksaanAwalController;
use App\Http\Controllers\SoapReviewController;
use App\Http\Controllers\TindakLanjutController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MedScribe AI
|--------------------------------------------------------------------------
| Sistem Autentikasi Tenaga Medis, Verifikasi OTP Email, Pemulihan Password,
| OAuth Google, dan Isolasi Data Multi-Tenancy Berbasis Rumah Sakit.
*/

// Halaman Utama: Redirect cerdas sesuai status sesi pengguna
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Rute Tamu / Guest (Hanya untuk pengguna yang BELUM terautentikasi)
Route::middleware('guest')->group(function () {
    // 1. Login & Registrasi
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    // 2. Verifikasi OTP Email Registrasi (Phase 2)
    Route::get('/verify-email-otp', [AuthController::class, 'showVerifyEmailOtpForm'])->name('verification.otp.show');
    Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp'])->name('verification.otp.verify');
    Route::post('/verify-email-otp/resend', [AuthController::class, 'resendEmailOtp'])->name('verification.otp.resend');

    // 3. Google OAuth 2.0 (Khusus Login Pengguna Terdaftar)
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // 4. Lupa Password & Reset Password via OTP (Phase 3)
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetOtp'])->name('password.email');
    Route::get('/forgot-password/verify-otp', [ForgotPasswordController::class, 'showVerifyOtpForm'])->name('password.otp.show');
    Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyResetOtp'])->name('password.otp.verify');
    Route::post('/forgot-password/resend-otp', [ForgotPasswordController::class, 'resendResetOtp'])->name('password.otp.resend');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});

// Rute Terlindungi (Wajib sesi login aktif & proteksi browser cache-history)
Route::middleware(['auth', 'prevent-back-history'])->group(function () {
    // Keluar sistem (Logout dengan validasi token CSRF)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Halaman pemberitahuan status akun tenaga medis
    Route::get('/auth/pending', [AuthController::class, 'showPendingNotice'])->name('auth.pending');
    Route::get('/auth/rejected', [AuthController::class, 'showRejectedNotice'])->name('auth.rejected');
    Route::get('/auth/inactive', [AuthController::class, 'showInactiveNotice'])->name('auth.inactive');

    // Entry point router peran
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Area Khusus Admin Rumah Sakit (Otorisasi: role admin & akun aktif)
    Route::middleware(['role:admin', 'account.active'])->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/dokter', [AdminDashboardController::class, 'doctors'])->name('admin.doctors');
        Route::get('/admin/perawat', [AdminDashboardController::class, 'nurses'])->name('admin.nurses');
        Route::get('/admin/verifikasi', [AdminDashboardController::class, 'pending'])->name('admin.pending');
        Route::match(['post', 'patch'], '/admin/verifikasi/{user}/terima', [AdminDashboardController::class, 'approveStaff'])->name('admin.staff.approve');
        Route::match(['post', 'patch'], '/admin/verifikasi/{user}/tolak', [AdminDashboardController::class, 'rejectStaff'])->name('admin.staff.reject');
        Route::get('/admin/tenaga-medis-nonaktif', [AdminDashboardController::class, 'inactive'])->name('admin.inactive');
        Route::match(['post', 'patch'], '/admin/tenaga-medis/{user}/nonaktifkan', [AdminDashboardController::class, 'deactivateStaff'])->name('admin.staff.deactivate');
        Route::match(['post', 'patch'], '/admin/tenaga-medis/{user}/aktifkan', [AdminDashboardController::class, 'reactivateStaff'])->name('admin.staff.reactivate');
        Route::get('/admin/aktivitas', [AdminDashboardController::class, 'activities'])->name('admin.activities');
        Route::get('/admin/profil-rs', [AdminDashboardController::class, 'hospitalInfo'])->name('admin.hospital.info');
        Route::put('/admin/profil-rs', [AdminDashboardController::class, 'updateHospitalInfo'])->name('admin.hospital.update');
    });

    // Profil tenaga medis (nama bergelar dan No. SIP/SIPP), dipakai dokter dan perawat
    Route::middleware(['role:dokter,perawat', 'account.active'])->group(function () {
        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
        Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
        Route::post('/profil/lewati', [ProfilController::class, 'lewati'])->name('profil.lewati');
    });

    // Area Khusus Dokter Poliklinik (Otorisasi: role dokter & akun aktif)
    Route::middleware(['role:dokter', 'account.active'])->group(function () {
        Route::get('/dokter/dashboard', [DashboardController::class, 'dokterDashboard'])->name('dokter.dashboard');
        Route::get('/dokter/kunjungan/{kunjungan}/wawancara', [InterviewController::class, 'show'])->name('dokter.interview.show');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/mulai', [InterviewController::class, 'start'])->name('dokter.interview.start');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/selesai', [InterviewController::class, 'finish'])->name('dokter.interview.finish');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/proses', [InterviewAudioController::class, 'processRecording'])->name('dokter.interview.process');
        Route::put('/dokter/kunjungan/{kunjungan}/wawancara/transcript', [InterviewAudioController::class, 'updateTranscript'])->name('dokter.interview.transcript.update');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/verifikasi', [InterviewAudioController::class, 'verifyTranscript'])->name('dokter.interview.transcript.verify');
        Route::get('/dokter/kunjungan/{kunjungan}/soap', [SoapReviewController::class, 'edit'])->name('dokter.soap.edit');
        Route::get('/dokter/kunjungan/{kunjungan}/soap/status', [SoapReviewController::class, 'status'])->name('dokter.soap.status');
        Route::post('/dokter/kunjungan/{kunjungan}/soap/generate', [SoapReviewController::class, 'generate'])->name('dokter.soap.generate');
        Route::put('/dokter/kunjungan/{kunjungan}/soap', [SoapReviewController::class, 'save'])->name('dokter.soap.save');
        Route::post('/dokter/kunjungan/{kunjungan}/soap/verifikasi', [SoapReviewController::class, 'verify'])->name('dokter.soap.verify');
        Route::post('/dokter/kunjungan/{kunjungan}/tindak-lanjut', [TindakLanjutController::class, 'store'])->name('dokter.tindak-lanjut.store');
        Route::post('/dokter/kunjungan/{kunjungan}/finalisasi', [DokterController::class, 'finalizeRekamMedis'])->name('dokter.rekam-medis.finalisasi');
        Route::get('/dokter/kunjungan/{kunjungan}/soap/preview', [DokterController::class, 'previewSoap'])->name('dokter.soap.preview');
        Route::get('/dokter/icd10', [Icd10Controller::class, 'index'])->name('dokter.icd10.index');
        Route::get('/dokter/rekam-medis/{id_antrean}', [DokterController::class, 'showRekamMedis'])->name('dokter.rekam-medis');
        Route::get('/dokter/rekam-medis/{id_antrean}/audio-file', [DokterController::class, 'streamAudio'])->name('dokter.rekam-medis.stream-audio');
    });

    // Area Khusus Perawat Nurse Station (Otorisasi: role perawat & akun aktif - SRS Bab 4.1)
    Route::middleware(['role:perawat', 'account.active'])->group(function () {
        Route::get('/perawat/dashboard', [PemeriksaanAwalController::class, 'index'])->name('perawat.dashboard');
        Route::post('/perawat/pemeriksaan-awal', [PemeriksaanAwalController::class, 'store'])->name('perawat.pemeriksaan.store');
        Route::post('/perawat/pasien-baru', [PemeriksaanAwalController::class, 'storePasienBaru'])->name('perawat.pasien.store');
        Route::get('/perawat/pasien/{id}', [PemeriksaanAwalController::class, 'showPasien'])->name('perawat.pasien.show');
        Route::delete('/perawat/pasien/{id}', [PemeriksaanAwalController::class, 'destroyPasien'])->name('perawat.pasien.destroy');
    });
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokterController;
use App\Http\Controllers\InterviewAudioController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\PemeriksaanAwalController;
use App\Http\Controllers\SoapReviewController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MedScribe AI
|--------------------------------------------------------------------------
| Sistem Autentikasi dan Otorisasi Peran Tenaga Medis (Dokter & Perawat)
| Sesuai Dokumen SRS MedScribe AI dan PDM Relasional.
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
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// Rute Terlindungi (Wajib sesi login aktif & proteksi browser cache-history)
Route::middleware(['auth', 'prevent-back-history'])->group(function () {
    // Keluar sistem (Logout dengan validasi token CSRF)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Entry point router peran
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Area Khusus Dokter Poliklinik (Otorisasi: role dokter)
    Route::middleware('role:dokter')->group(function () {
        Route::get('/dokter/dashboard', [DashboardController::class, 'dokterDashboard'])->name('dokter.dashboard');
        Route::get('/dokter/kunjungan/{kunjungan}/wawancara', [InterviewController::class, 'show'])->name('dokter.interview.show');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/mulai', [InterviewController::class, 'start'])->name('dokter.interview.start');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/selesai', [InterviewController::class, 'finish'])->name('dokter.interview.finish');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/audio', [InterviewAudioController::class, 'upload'])->name('dokter.interview.audio.upload');
        Route::get('/dokter/kunjungan/{kunjungan}/wawancara/audio/status', [InterviewAudioController::class, 'status'])->name('dokter.interview.audio.status');
        Route::post('/dokter/kunjungan/{kunjungan}/wawancara/audio/retry', [InterviewAudioController::class, 'retry'])->name('dokter.interview.audio.retry');
        Route::put('/dokter/kunjungan/{kunjungan}/wawancara/transcript', [InterviewAudioController::class, 'updateTranscript'])->name('dokter.interview.transcript.update');
        Route::get('/dokter/kunjungan/{kunjungan}/soap', [SoapReviewController::class, 'edit'])->name('dokter.soap.edit');
        Route::post('/dokter/kunjungan/{kunjungan}/soap/generate', [SoapReviewController::class, 'generate'])->name('dokter.soap.generate');
        Route::put('/dokter/kunjungan/{kunjungan}/soap', [SoapReviewController::class, 'save'])->name('dokter.soap.save');
        Route::post('/dokter/kunjungan/{kunjungan}/soap/verifikasi', [SoapReviewController::class, 'verify'])->name('dokter.soap.verify');
        Route::get('/dokter/rekam-medis/{id_antrean}', [DokterController::class, 'showRekamMedis'])->name('dokter.rekam-medis');
        Route::post('/dokter/rekam-medis/{id_antrean}/audio', [DokterController::class, 'uploadAudio'])->name('dokter.rekam-medis.upload-audio');
        Route::get('/dokter/rekam-medis/{id_antrean}/audio-file', [DokterController::class, 'streamAudio'])->name('dokter.rekam-medis.stream-audio');
    });

    // Area Khusus Perawat Nurse Station (Otorisasi: role perawat - SRS Bab 4.1)
    Route::middleware('role:perawat')->group(function () {
        Route::get('/perawat/dashboard', [PemeriksaanAwalController::class, 'index'])->name('perawat.dashboard');
        Route::post('/perawat/pemeriksaan-awal', [PemeriksaanAwalController::class, 'store'])->name('perawat.pemeriksaan.store');
        Route::post('/perawat/pasien-baru', [PemeriksaanAwalController::class, 'storePasienBaru'])->name('perawat.pasien.store');
        Route::get('/perawat/pasien/{id}', [PemeriksaanAwalController::class, 'showPasien'])->name('perawat.pasien.show');
        Route::delete('/perawat/pasien/{id}', [PemeriksaanAwalController::class, 'destroyPasien'])->name('perawat.pasien.destroy');
    });
});

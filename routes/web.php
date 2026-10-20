<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
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
    });

    // Area Khusus Perawat Nurse Station (Otorisasi: role perawat)
    Route::middleware('role:perawat')->group(function () {
        Route::get('/perawat/dashboard', [DashboardController::class, 'perawatDashboard'])->name('perawat.dashboard');
    });
});

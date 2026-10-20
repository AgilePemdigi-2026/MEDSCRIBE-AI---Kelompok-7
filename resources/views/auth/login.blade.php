@extends('layouts.auth')

@section('title', 'Masuk - MedScribe AI')

@section('content')
<div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/80 p-6 sm:p-8 transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-600 to-cyan-500 text-white shadow-lg shadow-sky-500/25 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Selamat Datang di MedScribe AI</h1>
        <p class="text-sm text-slate-500 mt-1">Masuk untuk mengakses sistem Rekam Medis Elektronik cerdas</p>
    </div>

    <!-- Alert Notifikasi Sukses -->
    @if (session('status'))
        <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>
                <span class="font-semibold">Informasi:</span> {{ session('status') }}
            </div>
        </div>
    @endif

    <!-- Alert Kesalahan Umum / Kredensial Tidak Valid -->
    @if ($errors->any())
        <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div class="space-y-0.5">
                @foreach ($errors->all() as $error)
                    <p class="font-medium">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Form Login -->
    <form method="POST" action="{{ route('login.submit') }}" id="loginForm" class="space-y-4">
        @csrf

        <!-- Email Input -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Alamat Email <span class="text-rose-500">*</span>
            </label>
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                    </svg>
                </div>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="nama@medscribe.id"
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50/50 border @error('email') border-rose-300 ring-1 ring-rose-200 @else border-slate-300 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 transition"
                >
            </div>
        </div>

        <!-- Password Input -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                    Kata Sandi <span class="text-rose-500">*</span>
                </label>
            </div>
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    placeholder="••••••••"
                    class="block w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50/50 border @error('password') border-rose-300 ring-1 ring-rose-200 @else border-slate-300 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 transition"
                >
                <button
                    type="button"
                    id="togglePassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                    title="Tampilkan / Sembunyikan Kata Sandi"
                >
                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Ingat Saya -->
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    name="remember"
                    id="remember"
                    class="w-4 h-4 text-sky-600 border-slate-300 rounded focus:ring-sky-500/30"
                >
                <span class="text-xs text-slate-600 select-none">Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Tombol Masuk -->
        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-sky-600/25 hover:shadow-lg hover:shadow-sky-600/30 focus:outline-none focus:ring-2 focus:ring-sky-500/40 transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
                <span id="btnText">Masuk ke Sistem</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>

    <!-- Tautan ke Halaman Pendaftaran -->
    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Belum memiliki akun tenaga medis?
            <a href="{{ route('register') }}" class="font-semibold text-sky-600 hover:text-sky-700 hover:underline">
                Daftar Akun Baru
            </a>
        </p>
    </div>

    <!-- Quick Demo Accounts Helper (Mempermudah Testing Pengguna) -->
    <div class="mt-5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-xs">
        <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Akun Uji Demo Cepat
            </span>
            <span class="text-[10px] text-slate-400">Klik untuk isi otomatis</span>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <button
                type="button"
                onclick="fillCredentials('rina@medscribe.id', 'password123')"
                class="py-1.5 px-2 bg-white hover:bg-sky-50 border border-slate-200 hover:border-sky-300 rounded-lg text-left transition cursor-pointer"
            >
                <div class="font-bold text-sky-700 text-[11px]">dr. Rina Anggraini</div>
                <div class="text-[10px] text-slate-500">Dokter Poliklinik</div>
            </button>
            <button
                type="button"
                onclick="fillCredentials('dewi@medscribe.id', 'password123')"
                class="py-1.5 px-2 bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 rounded-lg text-left transition cursor-pointer"
            >
                <div class="font-bold text-emerald-700 text-[11px]">Dewi Pratiwi</div>
                <div class="text-[10px] text-slate-500">Perawat Nurse Station</div>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle Show/Hide Password
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePasswordBtn.addEventListener('click', function () {
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        
        // Ganti icon
        if (isPassword) {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            `;
        } else {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            `;
        }
    });

    // Indikator Loading Saat Form Disubmit
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    loginForm.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Memverifikasi...';
        btnSpinner.classList.remove('hidden');
    });

    // Helper untuk mengisi kredensial demo
    function fillCredentials(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection

@extends('layouts.auth')

@section('title', 'Masuk - MedScribe AI')

@section('content')
<div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] p-6 sm:p-8 shadow-[0_10px_30px_rgba(18,32,86,0.08)] transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] mb-3.5 shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-[#122056]">Selamat Datang di MedScribe AI</h1>
        <p class="text-xs sm:text-sm text-[#69718B] mt-1 font-normal">Sistem Rekam Medis Elektronik Terintegrasi</p>
    </div>

    <!-- Alert Notifikasi Status / Sukses -->
    @if (session('status'))
        <div class="mb-5 p-3.5 rounded-[16px] bg-[#ECFDF5] border border-[#A7F3D0] text-[#059669] text-xs flex items-start gap-2.5 shadow-xs">
            <span class="w-4 h-4 rounded-full bg-[#059669] text-[#FFFFFF] flex items-center justify-center text-[10px] font-bold mt-0.5 flex-shrink-0">✓</span>
            <div>
                <span class="font-semibold text-[#059669]">Informasi:</span>
                <span class="text-[#20294A]">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <!-- Alert Kesalahan Kredensial / Validasi -->
    @if ($errors->any())
        <div class="mb-5 p-3.5 rounded-[16px] bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] text-xs flex items-start gap-2.5 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#DC2626] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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
            <label for="email" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Alamat Email <span class="text-[#DC2626]">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
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
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-[#FAFAFD] border @error('email') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
            </div>
        </div>

        <!-- Password Input -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-[#122056]">
                    Kata Sandi <span class="text-[#DC2626]">*</span>
                </label>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    placeholder="••••••••"
                    class="block w-full pl-10 pr-10 py-2.5 text-sm bg-[#FAFAFD] border @error('password') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
                <button
                    type="button"
                    id="togglePassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#69718B] hover:text-[#122056] focus:outline-none cursor-pointer"
                    title="Tampilkan / Sembunyikan Kata Sandi"
                >
                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                    class="w-4 h-4 text-[#5865DC] accent-[#5865DC] border-[#E4E7F4] rounded-[4px] focus:ring-[#5865DC]"
                >
                <span class="text-xs text-[#69718B] select-none">Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Tombol Masuk (Royal Indigo Primary CTA) -->
        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-medium text-sm rounded-[14px] focus:outline-none focus:ring-3 focus:ring-[#5865DC]/30 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]"
            >
                <span id="btnText">Masuk ke Sistem</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-[#FFFFFF]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>

    <!-- Tautan ke Halaman Pendaftaran -->
    <div class="mt-6 pt-5 border-t border-[#E4E7F4] text-center">
        <p class="text-xs text-[#69718B]">
            Belum memiliki akun tenaga medis?
            <a href="{{ route('register') }}" class="font-medium text-[#5865DC] hover:text-[#4854C9] hover:underline underline-offset-4 ml-1">
                Daftar Akun Baru
            </a>
        </p>
    </div>

    <!-- Quick Demo Accounts Helper (Pale Lavender Cards) -->
    <div class="mt-5 p-3.5 rounded-[16px] bg-[#EEEEFD] border border-[#CBD1EA] text-xs">
        <div class="flex items-center justify-between mb-2.5">
            <span class="font-semibold text-[#122056] flex items-center gap-1.5 text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Akun Uji Coba Demo
            </span>
            <span class="text-[11px] text-[#69718B]">Klik untuk isi</span>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <button
                type="button"
                onclick="fillCredentials('rina@medscribe.id', 'password123')"
                class="py-2 px-2.5 bg-[#FFFFFF] hover:bg-[#FAFAFD] border border-[#E4E7F4] hover:border-[#5865DC] rounded-[12px] text-left transition-all shadow-xs cursor-pointer group"
            >
                <div class="font-semibold text-[#122056] text-[12px] group-hover:text-[#5865DC] transition-colors">dr. Rina Anggraini</div>
                <div class="text-[11px] text-[#69718B]">Dokter Poliklinik</div>
            </button>
            <button
                type="button"
                onclick="fillCredentials('dewi@medscribe.id', 'password123')"
                class="py-2 px-2.5 bg-[#FFFFFF] hover:bg-[#FAFAFD] border border-[#E4E7F4] hover:border-[#5865DC] rounded-[12px] text-left transition-all shadow-xs cursor-pointer group"
            >
                <div class="font-semibold text-[#122056] text-[12px] group-hover:text-[#5865DC] transition-colors">Dewi Pratiwi</div>
                <div class="text-[11px] text-[#69718B]">Perawat Nurse Station</div>
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
        
        if (isPassword) {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            `;
        } else {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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

@extends('layouts.auth')

@section('title', 'Daftar Akun Tenaga Medis - MedScribe AI')

@section('content')
<div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/80 p-6 sm:p-8 transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-600 to-cyan-500 text-white shadow-lg shadow-sky-500/25 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pendaftaran Pengguna Baru</h1>
        <p class="text-sm text-slate-500 mt-1">Lengkapi data tenaga medis untuk mengakses MedScribe AI</p>
    </div>

    <!-- Alert Kesalahan Validasi -->
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

    <!-- Form Register -->
    <form method="POST" action="{{ route('register.submit') }}" id="registerForm" class="space-y-4">
        @csrf

        <!-- Nama Lengkap -->
        <div>
            <label for="nama_lengkap" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Nama Lengkap & Gelar <span class="text-rose-500">*</span>
            </label>
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="nama_lengkap"
                    id="nama_lengkap"
                    value="{{ old('nama_lengkap') }}"
                    required
                    autofocus
                    placeholder="contoh: dr. Ahmad Yani, Sp.PD"
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50/50 border @error('nama_lengkap') border-rose-300 ring-1 ring-rose-200 @else border-slate-300 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 transition"
                >
            </div>
        </div>

        <!-- Alamat Email -->
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
                    placeholder="nama@medscribe.id"
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50/50 border @error('email') border-rose-300 ring-1 ring-rose-200 @else border-slate-300 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 transition"
                >
            </div>
        </div>

        <!-- Pilihan Peran Pengguna (Role Sesuai SRS: Dokter atau Perawat) -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Peran Tenaga Medis <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <!-- Opsi Dokter -->
                <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition select-none bg-white hover:border-sky-400 has-checked:border-sky-600 has-checked:bg-sky-50/50 has-checked:ring-1 has-checked:ring-sky-500">
                    <input
                        type="radio"
                        name="role"
                        value="dokter"
                        {{ old('role', 'dokter') === 'dokter' ? 'checked' : '' }}
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs">🩺</span>
                        <span class="text-sm font-bold text-slate-800">Dokter</span>
                    </div>
                    <span class="text-[11px] text-slate-500 leading-snug">Wawancara klinis, AI SOAP, e-resep & finalisasi</span>
                </label>

                <!-- Opsi Perawat -->
                <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition select-none bg-white hover:border-emerald-400 has-checked:border-emerald-600 has-checked:bg-emerald-50/50 has-checked:ring-1 has-checked:ring-emerald-500">
                    <input
                        type="radio"
                        name="role"
                        value="perawat"
                        {{ old('role') === 'perawat' ? 'checked' : '' }}
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">📋</span>
                        <span class="text-sm font-bold text-slate-800">Perawat</span>
                    </div>
                    <span class="text-[11px] text-slate-500 leading-snug">Nurse Station, pemeriksaan awal & input TTV</span>
                </label>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pemilihan peran mandiri disediakan untuk pengujian prototipe lokal MedScribe AI.
            </p>
        </div>

        <!-- Kata Sandi -->
        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Kata Sandi <span class="text-rose-500">*</span>
            </label>
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
                    minlength="8"
                    placeholder="Minimal 8 karakter"
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

        <!-- Konfirmasi Kata Sandi -->
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
            </label>
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    required
                    minlength="8"
                    placeholder="Ulangi kata sandi"
                    class="block w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50/50 border border-slate-300 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-600 transition"
                >
                <button
                    type="button"
                    id="toggleConfirmPassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                    title="Tampilkan / Sembunyikan Konfirmasi Kata Sandi"
                >
                    <svg id="confirmEyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            <p id="passwordMatchHint" class="hidden text-[11px] text-rose-500 mt-1">Kata sandi dan konfirmasi belum cocok.</p>
        </div>

        <!-- Tombol Daftar -->
        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-sky-600/25 hover:shadow-lg hover:shadow-sky-600/30 focus:outline-none focus:ring-2 focus:ring-sky-500/40 transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
                <span id="btnText">Daftar Akun Tenaga Medis</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>

    <!-- Tautan ke Halaman Login -->
    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Sudah memiliki akun tenaga medis?
            <a href="{{ route('login') }}" class="font-semibold text-sky-600 hover:text-sky-700 hover:underline">
                Masuk ke Sistem
            </a>
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle Show/Hide Password
    function setupPasswordToggle(inputEl, btnEl, iconEl) {
        btnEl.addEventListener('click', function () {
            const isPassword = inputEl.getAttribute('type') === 'password';
            inputEl.setAttribute('type', isPassword ? 'text' : 'password');
            if (isPassword) {
                iconEl.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                `;
            } else {
                iconEl.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        });
    }

    setupPasswordToggle(
        document.getElementById('password'),
        document.getElementById('togglePassword'),
        document.getElementById('eyeIcon')
    );

    setupPasswordToggle(
        document.getElementById('password_confirmation'),
        document.getElementById('toggleConfirmPassword'),
        document.getElementById('confirmEyeIcon')
    );

    // Validasi Cocoknya Password Client-Side
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');
    const matchHint = document.getElementById('passwordMatchHint');

    function checkPasswordMatch() {
        if (confirmInput.value.length > 0 && passwordInput.value !== confirmInput.value) {
            matchHint.classList.remove('hidden');
        } else {
            matchHint.classList.add('hidden');
        }
    }

    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmInput.addEventListener('input', checkPasswordMatch);

    // Indikator Loading Saat Form Disubmit
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    registerForm.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Menyimpan Akun...';
        btnSpinner.classList.remove('hidden');
    });
</script>
@endsection

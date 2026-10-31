@extends('layouts.auth')

@section('title', 'Daftar Akun Tenaga Medis - MedScribe AI')

@section('content')
<div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] p-6 sm:p-8 shadow-[0_10px_30px_rgba(18,32,86,0.08)] transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] mb-3.5 shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-[#122056]">Pendaftaran Pengguna Baru</h1>
        <p class="text-xs sm:text-sm text-[#69718B] mt-1 font-normal">Lengkapi kredensial tenaga medis untuk akses sistem</p>
    </div>

    <!-- Alert Kesalahan Validasi -->
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

    <!-- Form Register -->
    <form method="POST" action="{{ route('register.submit') }}" id="registerForm" class="space-y-4">
        @csrf

        <!-- Nama Lengkap -->
        <div>
            <label for="nama_lengkap" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Nama Lengkap & Gelar <span class="text-[#DC2626]">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="nama_lengkap"
                    id="nama_lengkap"
                    value="{{ old('nama_lengkap') }}"
                    required
                    autofocus
                    placeholder="dr. Ahmad Yani, Sp.PD"
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-[#FAFAFD] border @error('nama_lengkap') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
            </div>
        </div>

        <!-- Alamat Email -->
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
                    placeholder="nama@medscribe.id"
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-[#FAFAFD] border @error('email') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
            </div>
        </div>

        <!-- Pilihan Peran Pengguna (Royal Indigo / Medical Navy Cards) -->
        <div>
            <label class="block text-xs font-semibold text-[#122056] mb-1.5">
                Peran Tenaga Medis <span class="text-[#DC2626]">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2.5">
                <!-- Opsi Dokter -->
                <label class="relative flex flex-col p-3 rounded-[14px] border border-[#E4E7F4] cursor-pointer transition-all select-none bg-[#FAFAFD] hover:border-[#CBD1EA] has-checked:border-[#5865DC] has-checked:bg-[#EEEEFD]/60 has-checked:ring-2 has-checked:ring-[#5865DC]/20">
                    <input
                        type="radio"
                        name="role"
                        value="dokter"
                        {{ old('role', 'dokter') === 'dokter' ? 'checked' : '' }}
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-6 h-6 rounded-[8px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center text-[11px] shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </span>
                        <span class="text-xs font-semibold text-[#122056]">Dokter</span>
                    </div>
                    <span class="text-[11px] text-[#69718B] leading-tight">Wawancara klinis & rekam medis pasien</span>
                </label>

                <!-- Opsi Perawat -->
                <label class="relative flex flex-col p-3 rounded-[14px] border border-[#E4E7F4] cursor-pointer transition-all select-none bg-[#FAFAFD] hover:border-[#CBD1EA] has-checked:border-[#5865DC] has-checked:bg-[#EEEEFD]/60 has-checked:ring-2 has-checked:ring-[#5865DC]/20">
                    <input
                        type="radio"
                        name="role"
                        value="perawat"
                        {{ old('role') === 'perawat' ? 'checked' : '' }}
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-6 h-6 rounded-[8px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center text-[11px] shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </span>
                        <span class="text-xs font-semibold text-[#122056]">Perawat</span>
                    </div>
                    <span class="text-[11px] text-[#69718B] leading-tight">Nurse Station, antrean & TTV</span>
                </label>
            </div>
        </div>

        <!-- Pilihan Rumah Sakit (Phase 4 C) -->
        <div>
            <label for="hospital_id" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Rumah Sakit Tempat Bertugas <span class="text-[#DC2626]">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <select
                    name="hospital_id"
                    id="hospital_id"
                    required
                    class="block w-full pl-10 pr-8 py-2.5 text-sm bg-[#FAFAFD] border @error('hospital_id') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#20294A] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
                    <option value="" disabled {{ old('hospital_id') ? '' : 'selected' }}>Pilih Rumah Sakit (Kota Surabaya)...</option>
                    @foreach ($hospitals as $h)
                        <option value="{{ $h->id }}" {{ (string) old('hospital_id') === (string) $h->id ? 'selected' : '' }}>
                            {{ $h->hospital_name }} ({{ $h->hospital_type ?? 'RSU' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <p class="text-[11px] text-[#69718B] mt-1">Pilot project mencakup rumah sakit resmi di wilayah Kota Surabaya.</p>
        </div>

        <!-- Kata Sandi -->
        <div>
            <label for="password" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Kata Sandi <span class="text-[#DC2626]">*</span>
            </label>
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
                    minlength="8"
                    placeholder="Minimal 8 karakter"
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

        <!-- Konfirmasi Kata Sandi -->
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Konfirmasi Kata Sandi <span class="text-[#DC2626]">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    required
                    minlength="8"
                    placeholder="Ulangi kata sandi"
                    class="block w-full pl-10 pr-10 py-2.5 text-sm bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
                <button
                    type="button"
                    id="toggleConfirmPassword"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#69718B] hover:text-[#122056] focus:outline-none cursor-pointer"
                    title="Tampilkan / Sembunyikan Konfirmasi Kata Sandi"
                >
                    <svg id="confirmEyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            <p id="passwordMatchHint" class="hidden text-[11px] text-[#DC2626] mt-1.5 font-medium">Kata sandi dan konfirmasi belum cocok.</p>
        </div>

        <!-- Tombol Daftar (Royal Indigo Primary CTA) -->
        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-medium text-sm rounded-[14px] focus:outline-none focus:ring-3 focus:ring-[#5865DC]/30 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]"
            >
                <span id="btnText">Daftar & Terima Kode Verifikasi</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-[#FFFFFF]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>

    <!-- Opsi Daftar dengan Google (Phase 2 D) -->
    <div class="mt-4">
        <div class="relative flex items-center justify-center my-3">
            <div class="border-t border-[#E4E7F4] w-full"></div>
            <span class="bg-[#FFFFFF] px-3 text-[11px] text-[#69718B] uppercase tracking-wider absolute">atau</span>
        </div>

        <a
            href="{{ route('auth.google') }}"
            class="w-full py-2.5 px-4 bg-[#FAFAFD] hover:bg-[#F3F4F8] border border-[#E4E7F4] hover:border-[#CBD1EA] text-[#122056] font-medium text-sm rounded-[14px] transition-all flex items-center justify-center gap-2.5 shadow-xs cursor-pointer"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Daftar dengan Google</span>
        </a>
    </div>

    <!-- Tautan ke Halaman Login -->
    <div class="mt-6 pt-5 border-t border-[#E4E7F4] text-center">
        <p class="text-xs text-[#69718B]">
            Sudah memiliki akun tenaga medis?
            <a href="{{ route('login') }}" class="font-medium text-[#5865DC] hover:text-[#4854C9] hover:underline underline-offset-4 ml-1">
                Masuk ke Sistem
            </a>
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle Password Visibility
    function setupToggle(inputEl, btnEl, iconEl) {
        btnEl.addEventListener('click', function () {
            const isPassword = inputEl.getAttribute('type') === 'password';
            inputEl.setAttribute('type', isPassword ? 'text' : 'password');
            if (isPassword) {
                iconEl.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />`;
            } else {
                iconEl.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
            }
        });
    }

    setupToggle(document.getElementById('password'), document.getElementById('togglePassword'), document.getElementById('eyeIcon'));
    setupToggle(document.getElementById('password_confirmation'), document.getElementById('toggleConfirmPassword'), document.getElementById('confirmEyeIcon'));

    // Real-time Password Confirmation Match Check
    const pwd = document.getElementById('password');
    const pwdConfirm = document.getElementById('password_confirmation');
    const matchHint = document.getElementById('passwordMatchHint');

    function checkMatch() {
        if (pwdConfirm.value.length > 0 && pwd.value !== pwdConfirm.value) {
            matchHint.classList.remove('hidden');
        } else {
            matchHint.classList.add('hidden');
        }
    }

    pwd.addEventListener('input', checkMatch);
    pwdConfirm.addEventListener('input', checkMatch);

    // Form Submit Indicator
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Mendaftarkan Akun...';
        btnSpinner.classList.remove('hidden');
    });
</script>
@endsection

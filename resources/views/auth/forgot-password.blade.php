@extends('layouts.auth')

@section('title', 'Lupa Password? - MedScribe AI')

@section('content')
<div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] p-6 sm:p-8 shadow-[0_10px_30px_rgba(18,32,86,0.08)] transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] mb-3.5 shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-[#122056]">Lupa Password?</h1>
        <p class="text-xs sm:text-sm text-[#69718B] mt-1 font-normal">Masukkan alamat email yang terdaftar untuk menerima kode verifikasi.</p>
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

    <!-- Alert Kesalahan / Validasi -->
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

    <!-- Form Permintaan OTP Lupa Password -->
    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm" class="space-y-4">
        @csrf

        <!-- Email Input -->
        <div>
            <label for="email" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Alamat Email Terdaftar <span class="text-[#DC2626]">*</span>
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

        <!-- Tombol Kirim Kode Verifikasi -->
        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-medium text-sm rounded-[14px] focus:outline-none focus:ring-3 focus:ring-[#5865DC]/30 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]"
            >
                <span id="btnText">Kirim Kode Verifikasi</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-[#FFFFFF]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>

    <!-- Tautan Kembali ke Login -->
    <div class="mt-6 pt-5 border-t border-[#E4E7F4] text-center">
        <a href="{{ route('login') }}" class="text-xs font-medium text-[#5865DC] hover:text-[#4854C9] hover:underline underline-offset-4 flex items-center justify-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Halaman Masuk
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const form = document.getElementById('forgotPasswordForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Mengirimkan Kode...';
        btnSpinner.classList.remove('hidden');
    });
</script>
@endsection


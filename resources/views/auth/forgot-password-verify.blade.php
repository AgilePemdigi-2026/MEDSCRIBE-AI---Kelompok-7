@extends('layouts.auth')

@section('title', 'Verifikasi Kode Reset Password - MedScribe AI')

@section('content')
<div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] p-6 sm:p-8 shadow-[0_10px_30px_rgba(18,32,86,0.08)] transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] mb-3.5 shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-[#122056]">Verifikasi Kode Pemulihan</h1>
        <p class="text-xs sm:text-sm text-[#69718B] mt-1 font-normal">Masukkan 6 digit kode OTP pemulihan kata sandi yang telah dikirimkan</p>
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

    <!-- Informasi Email Tujuan -->
    <div class="mb-5 p-3 rounded-[14px] bg-[#EEEEFD] border border-[#CBD1EA] text-center">
        <span class="text-xs text-[#69718B]">Kode OTP dikirim ke:</span>
        <div class="text-sm font-semibold text-[#122056] break-all">{{ $email }}</div>
    </div>

    <!-- Form Verifikasi OTP Reset Password -->
    <form method="POST" action="{{ route('password.otp.verify') }}" id="verifyResetForm" class="space-y-4">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label for="otp" class="block text-xs font-semibold text-[#122056] mb-1.5 text-center">
                Kode Verifikasi (6 Digit) <span class="text-[#DC2626]">*</span>
            </label>
            <div class="relative">
                <input
                    type="text"
                    name="otp"
                    id="otp"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    placeholder="000000"
                    class="block w-full py-3 text-center tracking-[0.5em] text-2xl font-mono font-bold bg-[#FAFAFD] border @error('otp') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#122056] placeholder-[#69718B]/40 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
            </div>
            <p class="text-[11px] text-[#69718B] mt-1.5 text-center">Kode OTP pemulihan berlaku selama 10 menit.</p>
        </div>

        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-medium text-sm rounded-[14px] focus:outline-none focus:ring-3 focus:ring-[#5865DC]/30 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]"
            >
                <span id="btnText">Verifikasi Kode & Lanjutkan</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-[#FFFFFF]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>

    <!-- Form Kirim Ulang OTP -->
    <div class="mt-5 pt-4 border-t border-[#E4E7F4] text-center">
        <form method="POST" action="{{ route('password.otp.resend') }}" id="resendForm">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <p class="text-xs text-[#69718B]">
                Tidak menerima kode verifikasi?
                <button
                    type="submit"
                    id="resendBtn"
                    class="font-medium text-[#5865DC] hover:text-[#4854C9] hover:underline underline-offset-4 ml-1 cursor-pointer disabled:text-[#9CA3AF] disabled:no-underline disabled:cursor-not-allowed"
                >
                    Kirim Ulang Kode
                </button>
            </p>
        </form>
    </div>

    <!-- Tautan Kembali -->
    <div class="mt-4 text-center">
        <a href="{{ route('login') }}" class="text-xs text-[#69718B] hover:text-[#122056] hover:underline underline-offset-4">
            ← Kembali ke Halaman Masuk
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const otpInput = document.getElementById('otp');
    otpInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    const verifyForm = document.getElementById('verifyResetForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    verifyForm.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Memverifikasi...';
        btnSpinner.classList.remove('hidden');
    });
</script>
@endsection


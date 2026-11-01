{{-- Formulir kode OTP 6 digit. Parameter: action, resendAction, formId, submitLabel, validity --}}
<div class="mb-5 flex items-center gap-3 p-3.5 rounded-[18px] bg-pale-lavender border border-border-hover">
    <span class="avatar w-10 h-10 bg-white text-royal-indigo border border-border-hover" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
    </span>
    <div class="min-w-0">
        <span class="block text-xs text-text-secondary">Kode OTP dikirim ke:</span>
        <span class="block text-sm font-semibold text-medical-navy break-all">{{ $email }}</span>
    </div>
</div>

<form method="POST" action="{{ $action }}" id="{{ $formId }}" class="space-y-5" data-submit-loading>
    @csrf
    <input type="hidden" name="email" value="{{ $email }}">

    <div>
        <label for="otp" class="field-label text-center">Kode Verifikasi (6 Digit) <span class="req" aria-hidden="true">*</span></label>
        <input type="text" name="otp" id="otp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
               autocomplete="one-time-code" required autofocus placeholder="000000" aria-describedby="otpHint"
               class="field text-center font-mono text-2xl font-bold tracking-[0.5em] py-3 text-medical-navy @error('otp') is-invalid @enderror">
        <p id="otpHint" class="field-hint text-center">{{ $validity }}</p>
    </div>

    @include('partials.submit-button', ['label' => $submitLabel, 'loading' => 'Memverifikasi...'])
</form>

<form method="POST" action="{{ $resendAction }}" id="resendForm" class="mt-6 pt-5 border-t border-border-subtle text-center text-xs text-text-secondary" data-submit-loading>
    @csrf
    <input type="hidden" name="email" value="{{ $email }}">
    Tidak menerima kode verifikasi?
    <button type="submit" id="resendBtn" data-loading-text="Mengirim..." class="link inline-flex min-h-11 items-center ml-1 disabled:opacity-60">
        <span data-label>Kirim Ulang Kode</span>
    </button>
</form>

<div class="text-center">
    @include('partials.back-link', ['href' => route('login'), 'label' => 'Kembali ke Halaman Masuk'])
</div>

<script>
    document.getElementById('otp').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>

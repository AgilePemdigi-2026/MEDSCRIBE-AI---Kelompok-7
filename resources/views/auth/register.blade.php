@extends('layouts.auth')

@section('title', 'Daftar Akun Tenaga Medis - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Pendaftaran Pengguna Baru</h1>
<p class="mt-1.5 text-sm text-text-secondary">Lengkapi kredensial tenaga medis untuk akses sistem</p>

<div class="mt-7">
    @include('partials.form-alerts')

    <form method="POST" action="{{ route('register.submit') }}" id="registerForm" class="space-y-4" data-submit-loading>
        @csrf

        <div>
            <label for="nama_lengkap" class="field-label">Nama Lengkap &amp; Gelar <span class="req" aria-hidden="true">*</span></label>
            <div class="field-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap') }}" required autofocus autocomplete="name"
                       placeholder="dr. Ahmad Yani, Sp.PD" class="field @error('nama_lengkap') is-invalid @enderror">
            </div>
        </div>

        <div>
            <label for="email" class="field-label">Alamat Email <span class="req" aria-hidden="true">*</span></label>
            <div class="field-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" /></svg>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"
                       placeholder="nama@medscribe.id" class="field @error('email') is-invalid @enderror">
            </div>
        </div>

        @include('partials.role-picker')

        @include('partials.hospital-select', ['showHint' => true])

        @include('partials.password-field', ['id' => 'password', 'name' => 'password', 'label' => 'Kata Sandi', 'placeholder' => 'Minimal 8 karakter', 'autocomplete' => 'new-password', 'minlength' => 8])

        <div>
            @include('partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'label' => 'Konfirmasi Kata Sandi', 'placeholder' => 'Ulangi kata sandi', 'autocomplete' => 'new-password', 'minlength' => 8, 'icon' => 'check', 'describedby' => 'passwordMatchHint'])
            <p id="passwordMatchHint" class="field-error hidden" aria-live="polite">Kata sandi dan konfirmasi belum cocok.</p>
        </div>

        <div class="pt-1">
            @include('partials.submit-button', ['label' => 'Daftar & Terima Kode Verifikasi', 'loading' => 'Mendaftarkan Akun...'])
        </div>
    </form>

    <p class="mt-6 text-xs text-text-secondary text-center">
        Sudah memiliki akun tenaga medis?
        <a href="{{ route('login') }}" class="link ml-1">Masuk ke Sistem</a>
    </p>
</div>
@endsection

@section('scripts')
<script>
    const pwd = document.getElementById('password');
    const pwdConfirm = document.getElementById('password_confirmation');
    const matchHint = document.getElementById('passwordMatchHint');

    function checkMatch() {
        const mismatch = pwdConfirm.value.length > 0 && pwd.value !== pwdConfirm.value;
        matchHint.classList.toggle('hidden', !mismatch);
        pwdConfirm.classList.toggle('is-invalid', mismatch);
    }

    pwd.addEventListener('input', checkMatch);
    pwdConfirm.addEventListener('input', checkMatch);
</script>
@endsection

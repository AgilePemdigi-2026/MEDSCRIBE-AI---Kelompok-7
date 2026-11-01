@extends('layouts.auth')

@section('title', 'Lupa Password? - MedScribe AI')

@section('content')
<span class="avatar w-12 h-12 bg-pale-lavender text-royal-indigo border border-border-hover" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
</span>
<h1 class="mt-4 text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Lupa Password?</h1>
<p class="mt-1.5 text-sm text-text-secondary">Masukkan alamat email yang terdaftar untuk menerima kode verifikasi.</p>

<div class="mt-7">
    @include('partials.form-alerts')

    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm" class="space-y-5" data-submit-loading>
        @csrf

        <div>
            <label for="email" class="field-label">Alamat Email Terdaftar <span class="req" aria-hidden="true">*</span></label>
            <div class="field-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" /></svg>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="nama@medscribe.id" class="field @error('email') is-invalid @enderror">
            </div>
        </div>

        @include('partials.submit-button', ['label' => 'Kirim Kode Verifikasi', 'loading' => 'Mengirimkan Kode...'])
    </form>

    <div class="mt-6 pt-5 border-t border-border-subtle text-center">
        @include('partials.back-link', ['href' => route('login'), 'label' => 'Kembali ke Halaman Masuk'])
    </div>
</div>
@endsection

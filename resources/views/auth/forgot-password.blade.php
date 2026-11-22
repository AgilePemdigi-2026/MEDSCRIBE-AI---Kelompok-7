@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi? - MedScribe AI')

@section('content')
<span class="avatar w-12 h-12 bg-pale-lavender text-royal-indigo border border-border-hover" aria-hidden="true">
    <x-icon name="key-round" class="w-5 h-5" />
</span>
<h1 class="mt-4 text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Lupa Kata Sandi?</h1>
<p class="mt-1.5 text-sm text-text-secondary">Masukkan alamat email yang terdaftar untuk menerima kode verifikasi.</p>

<div class="mt-7">
    @include('partials.form-alerts')

    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm" class="space-y-5" data-submit-loading>
        @csrf

        <div>
            <label for="email" class="field-label">Alamat Email Terdaftar <span class="req" aria-hidden="true">*</span></label>
            <div class="field-icon">
                <x-icon name="at-sign" aria-hidden="true" />
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

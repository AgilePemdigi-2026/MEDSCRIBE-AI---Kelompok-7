@extends('layouts.auth')

@section('title', 'Buat Kata Sandi Baru - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Buat Kata Sandi Baru</h1>
<p class="mt-1.5 text-sm text-text-secondary">Buat kata sandi baru yang aman untuk akun tenaga medis Anda</p>

<div class="mt-7">
    @include('partials.form-alerts')

    <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm" class="space-y-4" data-submit-loading>
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">

        @include('partials.password-field', ['id' => 'password', 'name' => 'password', 'label' => 'Password Baru', 'placeholder' => 'Minimal 8 karakter', 'autocomplete' => 'new-password', 'minlength' => 8, 'autofocus' => true])

        @include('partials.password-field', ['id' => 'password_confirmation', 'name' => 'password_confirmation', 'label' => 'Konfirmasi Password Baru', 'placeholder' => 'Ulangi kata sandi baru', 'autocomplete' => 'new-password', 'minlength' => 8, 'icon' => 'check'])

        <div class="pt-1">
            @include('partials.submit-button', ['label' => 'Simpan Password Baru', 'loading' => 'Menyimpan...'])
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-border-subtle text-center">
        @include('partials.back-link', ['href' => route('login'), 'label' => 'Kembali ke Halaman Masuk'])
    </div>
</div>
@endsection

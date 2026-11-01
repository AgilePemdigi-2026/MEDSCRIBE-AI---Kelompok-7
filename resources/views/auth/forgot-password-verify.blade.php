@extends('layouts.auth')

@section('title', 'Verifikasi Kode Reset Password - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Verifikasi Kode Pemulihan</h1>
<p class="mt-1.5 text-sm text-text-secondary">Masukkan 6 digit kode OTP pemulihan kata sandi yang telah dikirimkan</p>

<div class="mt-7">
    @include('partials.form-alerts')

    @include('partials.otp-form', [
        'action' => route('password.otp.verify'),
        'resendAction' => route('password.otp.resend'),
        'formId' => 'verifyResetForm',
        'submitLabel' => 'Verifikasi Kode & Lanjutkan',
        'validity' => 'Kode OTP pemulihan berlaku selama 10 menit.',
    ])
</div>
@endsection

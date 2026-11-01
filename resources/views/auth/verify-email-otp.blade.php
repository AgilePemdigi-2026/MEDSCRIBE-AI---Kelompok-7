@extends('layouts.auth')

@section('title', 'Verifikasi Email - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Verifikasi Alamat Email</h1>
<p class="mt-1.5 text-sm text-text-secondary">Masukkan 6 digit kode OTP yang telah dikirimkan ke email Anda</p>

<div class="mt-7">
    @include('partials.form-alerts')

    @include('partials.otp-form', [
        'action' => route('verification.otp.verify'),
        'resendAction' => route('verification.otp.resend'),
        'formId' => 'verifyOtpForm',
        'submitLabel' => 'Verifikasi Email',
        'validity' => 'Kode OTP berlaku selama 10 menit sejak diterbitkan.',
    ])
</div>
@endsection

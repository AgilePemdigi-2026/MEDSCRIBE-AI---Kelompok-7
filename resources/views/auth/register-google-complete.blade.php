@extends('layouts.auth')

@section('title', 'Lengkapi Profil Tenaga Medis - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Lengkapi Profil Medis</h1>
<p class="mt-1.5 text-sm text-text-secondary">Tentukan peran profesional dan rumah sakit tempat Anda bertugas</p>

<div class="mt-7">
    <div class="mb-5 flex items-center gap-3 p-3 rounded-[18px] bg-pale-lavender border border-border-hover">
        <span class="avatar w-10 h-10 bg-white border border-border-hover flex items-center justify-center shadow-xs" aria-hidden="true">
            <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
        </span>
        <div class="min-w-0">
            <div class="text-xs font-semibold text-medical-navy truncate">{{ $name ?? 'Tenaga Medis' }}</div>
            <div class="text-[11px] text-text-secondary truncate">{{ $email }} (Terverifikasi Google)</div>
        </div>
    </div>

    @include('partials.form-alerts')

    <form method="POST" action="{{ route('register.google.complete.submit') }}" id="googleCompleteForm" class="space-y-4" data-submit-loading>
        @csrf

        @include('partials.role-picker')

        @include('partials.hospital-select')

        <div class="pt-1">
            @include('partials.submit-button', ['label' => 'Selesaikan Pendaftaran', 'loading' => 'Menyimpan...'])
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-border-subtle text-center">
        @include('partials.back-link', ['href' => route('login'), 'label' => 'Batal dan Kembali ke Halaman Masuk'])
    </div>
</div>
@endsection

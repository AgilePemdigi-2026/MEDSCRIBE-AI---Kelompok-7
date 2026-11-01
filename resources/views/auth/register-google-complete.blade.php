@extends('layouts.auth')

@section('title', 'Lengkapi Profil Tenaga Medis - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Lengkapi Profil Medis</h1>
<p class="mt-1.5 text-sm text-text-secondary">Tentukan peran profesional dan rumah sakit tempat Anda bertugas</p>

<div class="mt-7">
    <div class="mb-5 flex items-center gap-3 p-3 rounded-[18px] bg-pale-lavender border border-border-hover">
        <span class="avatar w-10 h-10 bg-white border border-border-hover text-royal-indigo text-sm" aria-hidden="true">G</span>
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
</div>
@endsection

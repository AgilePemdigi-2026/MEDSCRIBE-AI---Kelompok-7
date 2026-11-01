@extends('layouts.app')

@section('title', 'Dashboard - MedScribe AI')

@section('content')
<div class="max-w-xl mx-auto card spotlight p-8 text-center rise">
    <div class="relative">
        <span class="mx-auto avatar w-14 h-14 bg-gradient-to-br from-medical-navy to-royal-indigo text-white" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        </span>
        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-medical-navy">Selamat Datang, {{ $user->nama_lengkap }}</h1>
        <p class="text-sm text-text-secondary mt-1.5">
            Anda terautentikasi sebagai <strong class="text-royal-indigo-hover font-semibold capitalize">{{ $user->role }}</strong> di sistem Rekam Medis Elektronik MedScribe AI.
        </p>

        <dl class="mt-6 p-4 rounded-[18px] bg-pale-lavender border border-border-hover text-left text-xs divide-y divide-border-hover">
            <div class="flex justify-between items-center gap-4 py-2">
                <dt class="text-text-secondary">ID Pengguna:</dt>
                <dd class="font-mono font-semibold text-medical-navy">#{{ $user->id_pengguna }}</dd>
            </div>
            <div class="flex justify-between items-center gap-4 py-2">
                <dt class="text-text-secondary">Alamat Email:</dt>
                <dd class="font-medium text-medical-navy break-all text-right">{{ $user->email }}</dd>
            </div>
            <div class="flex justify-between items-center gap-4 py-2">
                <dt class="text-text-secondary">Peran Medis:</dt>
                <dd class="font-semibold text-royal-indigo-hover capitalize">{{ $user->role }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection

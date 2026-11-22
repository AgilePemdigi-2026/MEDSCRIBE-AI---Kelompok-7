@extends('layouts.app')

@section('title', 'Dashboard - MedScribe AI')

@section('content')
<div class="max-w-xl mx-auto card spotlight p-8 text-center rise">
    <div class="relative">
        <span class="mx-auto avatar w-14 h-14 bg-pale-lavender text-royal-indigo" aria-hidden="true">
            <x-icon name="user" class="w-6 h-6" />
        </span>
        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-medical-navy">Selamat datang, {{ $user->nama_lengkap }}</h1>
        <p class="text-sm text-text-secondary mt-1.5">
            Anda masuk sebagai <strong class="text-royal-indigo-hover font-semibold">{{ $user->peran_label }}</strong> di MedScribe AI.
        </p>

        <dl class="mt-6 panel text-left text-xs divide-y divide-border-subtle">
            <div class="flex justify-between items-center gap-4 py-2">
                <dt class="text-text-secondary">ID pengguna</dt>
                <dd class="font-mono font-semibold text-medical-navy">#{{ $user->id_pengguna }}</dd>
            </div>
            <div class="flex justify-between items-center gap-4 py-2">
                <dt class="text-text-secondary">Alamat Email</dt>
                <dd class="font-medium text-medical-navy break-all text-right">{{ $user->email }}</dd>
            </div>
            <div class="flex justify-between items-center gap-4 py-2">
                <dt class="text-text-secondary">Peran</dt>
                <dd class="font-semibold text-medical-navy">{{ $user->peran_label }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection

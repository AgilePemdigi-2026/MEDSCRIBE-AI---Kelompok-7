@extends('layouts.app')

@section('title', 'Dashboard - MedScribe AI')

@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm text-center">
    <div class="w-14 h-14 rounded-2xl bg-sky-100 text-sky-700 mx-auto flex items-center justify-center font-bold text-xl mb-4">
        🩺
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Selamat Datang, {{ $user->nama_lengkap }}</h1>
    <p class="text-sm text-slate-500 mt-2">
        Anda masuk sebagai <strong class="text-slate-700 capitalize">{{ $user->role }}</strong> di sistem Rekam Medis Elektronik MedScribe AI.
    </p>

    <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left text-xs space-y-2">
        <div class="flex justify-between">
            <span class="text-slate-500">ID Pengguna:</span>
            <span class="font-mono font-bold text-slate-800">#{{ $user->id_pengguna }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Alamat Email:</span>
            <span class="font-bold text-slate-800">{{ $user->email }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Peran:</span>
            <span class="font-bold text-sky-700 capitalize">{{ $user->role }}</span>
        </div>
    </div>
</div>
@endsection

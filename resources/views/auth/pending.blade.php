@extends('layouts.auth')

@section('title', 'Menunggu Verifikasi Admin - MedScribe AI')

@section('content')
<div class="space-y-6">
    <div class="text-center sm:text-left">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200 mb-4 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-[1.85rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">
            Menunggu Verifikasi Admin
        </h1>
        <p class="mt-1.5 text-sm text-text-secondary">
            Pendaftaran tenaga medis Anda sedang ditinjau oleh pihak rumah sakit.
        </p>
    </div>

    {{-- Pesan Utama Sesuai Spesifikasi MedScribe AI --}}
    <div class="p-4 rounded-[18px] bg-amber-50/80 border border-amber-200 text-amber-900 text-sm leading-relaxed shadow-sm">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-medium text-amber-950">
                    Akun Anda sedang menunggu verifikasi admin <span class="font-semibold">{{ $hospital->hospital_name ?? 'Rumah Sakit' }}</span>. Anda belum dapat mengakses dashboard dan menggunakan fitur operasional sampai akun disetujui.
                </p>
            </div>
        </div>
    </div>

    {{-- Detail Akun Pengguna --}}
    <div class="rounded-[20px] bg-soft-offwhite border border-border-subtle p-4 space-y-3 text-xs">
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Nama Tenaga Medis</span>
            <span class="font-semibold text-medical-navy">{{ $user->nama_lengkap }}</span>
        </div>
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Alamat Email</span>
            <span class="font-semibold text-medical-navy">{{ $user->email }}</span>
        </div>
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Peran Tenaga Medis</span>
            <span class="font-semibold text-royal-indigo uppercase tracking-wider">{{ $user->role }}</span>
        </div>
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Rumah Sakit</span>
            <span class="font-semibold text-medical-navy">{{ $hospital->hospital_name ?? '-' }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-text-secondary">Status Pengajuan</span>
            <span class="tag-id bg-amber-100 text-amber-800 font-medium">Menunggu Verifikasi</span>
        </div>
    </div>

    {{-- Tombol Aksi --}}
    <div class="flex flex-col gap-2.5 pt-2">
        <a href="{{ route('dashboard') }}" class="btn btn-primary w-full justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Muat Ulang Status
        </a>

        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-secondary w-full justify-center text-text-secondary hover:text-danger-solid">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar dari Akun
            </button>
        </form>
    </div>
</div>
@endsection


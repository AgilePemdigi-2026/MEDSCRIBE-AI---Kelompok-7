@extends('layouts.auth')

@section('title', 'Pendaftaran Ditolak - MedScribe AI')

@section('content')
<div class="space-y-6">
    <div class="text-center sm:text-left">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 mb-4 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-[1.85rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">
            Pendaftaran Ditolak
        </h1>
        <p class="mt-1.5 text-sm text-text-secondary">
            Permohonan registrasi akun Anda tidak dapat disetujui oleh admin rumah sakit.
        </p>
    </div>

    {{-- Pesan Utama Sesuai Spesifikasi MedScribe AI --}}
    <div class="p-4 rounded-[18px] bg-rose-50/90 border border-rose-200 text-rose-950 text-sm leading-relaxed shadow-sm">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p>
                    Mohon maaf, permohonan registrasi Anda ditolak oleh admin <span class="font-semibold">{{ $hospital->hospital_name ?? 'Rumah Sakit' }}</span>.
                </p>
                <div class="mt-2.5 p-3 rounded-[12px] bg-white/80 border border-rose-200 font-medium text-rose-900 text-xs">
                    <span class="block text-[11px] font-semibold text-rose-700 uppercase tracking-wider mb-0.5">Alasan Penolakan:</span>
                    {{ $user->rejection_reason ?: 'Informasi atau berkas verifikasi tenaga medis belum memenuhi kriteria rumah sakit.' }}
                </div>
                <p class="mt-2.5 text-xs text-rose-800">
                    Silakan hubungi pihak rumah sakit terkait apabila terdapat ketidaksesuaian informasi.
                </p>
            </div>
        </div>
    </div>

    {{-- Detail Akun --}}
    <div class="rounded-[20px] bg-soft-offwhite border border-border-subtle p-4 space-y-3 text-xs">
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Nama Tenaga Medis</span>
            <span class="font-semibold text-medical-navy">{{ $user->nama_lengkap }}</span>
        </div>
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Email</span>
            <span class="font-semibold text-medical-navy">{{ $user->email }}</span>
        </div>
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Rumah Sakit</span>
            <span class="font-semibold text-medical-navy">{{ $hospital->hospital_name ?? '-' }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-text-secondary">Status</span>
            <span class="tag-id bg-rose-100 text-rose-800 font-medium">Ditolak</span>
        </div>
    </div>

    {{-- Tombol Keluar --}}
    <div class="pt-2">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-secondary w-full justify-center text-text-secondary hover:text-danger-solid">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar dari Sistem
            </button>
        </form>
    </div>
</div>
@endsection


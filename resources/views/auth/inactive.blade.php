@extends('layouts.auth')

@section('title', 'Akun Dinonaktifkan - MedScribe AI')

@section('content')
<div class="space-y-6">
    <div class="text-center sm:text-left">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 border border-slate-300 mb-4 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        </div>
        <h1 class="text-[1.85rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">
            Akun Dinonaktifkan
        </h1>
        <p class="mt-1.5 text-sm text-text-secondary">
            Hak akses operasional akun Anda untuk sementara waktu ditangguhkan oleh pihak rumah sakit.
        </p>
    </div>

    {{-- Pesan Utama --}}
    <div class="p-4 rounded-[18px] bg-slate-50 border border-slate-200 text-slate-900 text-sm leading-relaxed shadow-sm">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p>
                    Akun Anda saat ini berstatus tidak aktif pada <span class="font-semibold">{{ $hospital->hospital_name ?? 'Rumah Sakit' }}</span>.
                </p>
                <div class="mt-2.5 p-3 rounded-[12px] bg-white border border-slate-200 font-medium text-slate-800 text-xs">
                    <span class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-0.5">Keterangan / Alasan:</span>
                    {{ $user->deactivation_reason ?: 'Penyesuaian administratif penugasan atau mutasi tenaga medis.' }}
                </div>
                <p class="mt-2.5 text-xs text-slate-600">
                    Seluruh riwayat rekam medis dan data historis Anda tetap tersimpan dengan aman. Silakan hubungi admin rumah sakit untuk informasi aktivasi kembali.
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
            <span class="text-text-secondary">Alamat Email</span>
            <span class="font-semibold text-medical-navy">{{ $user->email }}</span>
        </div>
        <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
            <span class="text-text-secondary">Rumah Sakit</span>
            <span class="font-semibold text-medical-navy">{{ $hospital->hospital_name ?? '-' }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-text-secondary">Status</span>
            <span class="tag-id bg-slate-200 text-slate-800 font-medium">Nonaktif</span>
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


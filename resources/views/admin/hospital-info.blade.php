@extends('layouts.admin')

@section('title', 'Profil Rumah Sakit - MedScribe AI')

@section('content')
<div class="space-y-6 rise max-w-5xl">

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border-subtle">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">Profil Fasilitas Kesehatan</h1>
            <p class="text-xs text-text-secondary mt-0.5">Informasi resmi fasilitas pelayanan kesehatan dan kredensial administrasi unit</p>
        </div>
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Faskes Terverifikasi & Aktif
        </span>
    </div>

    {{-- Card Profil Utama --}}
    <div class="card p-6 border-l-4 border-l-royal-indigo">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-pale-lavender text-royal-indigo flex items-center justify-center font-bold text-2xl flex-shrink-0 shadow-sm border border-royal-indigo/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="tag-id bg-soft-offwhite text-text-secondary border border-border-subtle">
                            Kode SIRS: {{ $hospital->hospital_code }}
                        </span>
                        <span class="tag-id bg-pale-lavender text-royal-indigo">
                            {{ $hospital->hospital_type ?: 'Rumah Sakit' }}
                        </span>
                    </div>
                    <h2 class="text-xl font-bold text-medical-navy mt-1.5">{{ $hospital->hospital_name }}</h2>
                    <p class="text-xs text-text-secondary mt-0.5 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $hospital->city ?: 'Kota' }}, {{ $hospital->province ?: 'Provinsi' }}
                    </p>
                </div>
            </div>

            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 pt-4 sm:pt-0 border-border-subtle">
                <span class="text-xs text-text-secondary">Administrator Bertugas:</span>
                <span class="text-sm font-semibold text-medical-navy">{{ $admin->nama_lengkap }}</span>
                <span class="text-[11px] text-text-secondary font-mono">{{ $admin->email }}</span>
            </div>
        </div>
    </div>

    {{-- Detail Informasi & Metrik --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Detail Lokasi & Alamat --}}
        <div class="card p-6 space-y-4">
            <h3 class="text-sm font-semibold text-medical-navy flex items-center gap-2 pb-2 border-b border-border-subtle">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Alamat & Lokasi Fasilitas
            </h3>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-text-secondary block">Alamat Lengkap:</span>
                    <p class="font-medium text-medical-navy mt-0.5 leading-relaxed">
                        {{ $hospital->address ?: 'Alamat belum dilengkapi dalam database master.' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <span class="text-text-secondary block">Kota / Kabupaten:</span>
                        <p class="font-medium text-medical-navy mt-0.5">{{ $hospital->city ?: '-' }}</p>
                    </div>
                    <div>
                        <span class="text-text-secondary block">Provinsi:</span>
                        <p class="font-medium text-medical-navy mt-0.5">{{ $hospital->province ?: '-' }}</p>
                    </div>
                </div>

                <div class="pt-2">
                    <span class="text-text-secondary block">Status Operasional MedScribe:</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Aktif Beroperasi
                    </span>
                </div>
            </div>
        </div>

        {{-- Kapasitas Tenaga Medis Terdaftar --}}
        <div class="card p-6 space-y-4">
            <h3 class="text-sm font-semibold text-medical-navy flex items-center gap-2 pb-2 border-b border-border-subtle">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Kapasitas Tenaga Medis Aktif
            </h3>

            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-indigo-50/50 border border-indigo-100 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-text-secondary font-medium">Dokter Poliklinik</span>
                        <div class="text-2xl font-bold text-royal-indigo mt-1">{{ $dokterAktifCount }}</div>
                    </div>
                    <a href="{{ route('admin.doctors') }}" class="text-[11px] font-semibold text-royal-indigo hover:underline mt-3 inline-flex items-center gap-1">
                        Kelola Dokter &rarr;
                    </a>
                </div>

                <div class="p-4 rounded-xl bg-teal-50/50 border border-teal-100 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-text-secondary font-medium">Perawat Nurse Station</span>
                        <div class="text-2xl font-bold text-teal-700 mt-1">{{ $perawatAktifCount }}</div>
                    </div>
                    <a href="{{ route('admin.nurses') }}" class="text-[11px] font-semibold text-teal-700 hover:underline mt-3 inline-flex items-center gap-1">
                        Kelola Perawat &rarr;
                    </a>
                </div>
            </div>

            {{-- Jaminan Multi-Tenancy --}}
            <div class="p-3 rounded-lg bg-soft-offwhite border border-border-subtle text-[11px] text-text-secondary flex items-start gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-royal-indigo flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>
                    <strong>Isolasi Data Terjamin:</strong> Seluruh data pasien, antrean, transkripsi, dan tenaga medis hanya dapat diakses oleh staf terverifikasi dari {{ $hospital->hospital_name }}.
                </span>
            </div>
        </div>

    </div>

</div>
@endsection


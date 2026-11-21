@extends('layouts.admin')

@section('title', 'Profil Rumah Sakit - MedScribe AI')

@section('content')
@php
    $lokasi = collect([$hospital->city, $hospital->province])->filter()->join(', ');
@endphp

<div class="space-y-6">
    <x-page-header eyebrow="Profil fasilitas kesehatan"
                   :title="$hospital->hospital_name"
                   subtitle="Data rumah sakit yang tercatat di MedScribe AI. Hubungi pengelola sistem bila ada data yang perlu diperbarui." />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Data fasilitas --}}
        <section class="card p-5 sm:p-6 lg:col-span-8 rise" style="--i: 1" aria-labelledby="judulDataFaskes">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="judulDataFaskes" class="section-title">Data Fasilitas</h2>
                    <p class="section-subtitle">Identitas dan alamat rumah sakit.</p>
                </div>
                @if ($hospital->is_active)
                    <span class="status status-ok">Aktif di MedScribe</span>
                @else
                    <span class="status status-muted">Tidak aktif</span>
                @endif
            </div>

            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-text-secondary">Nama rumah sakit</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $hospital->hospital_name }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Kode SIRS</dt>
                    <dd class="mt-1"><span class="tag-id">{{ $hospital->hospital_code }}</span></dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Jenis</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $hospital->hospital_type ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Kota dan provinsi</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $lokasi ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-text-secondary">Alamat</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy leading-relaxed">{{ $hospital->address ?: '-' }}</dd>
                </div>
            </dl>
        </section>

        <div class="lg:col-span-4 space-y-6">

            {{-- Tenaga medis aktif --}}
            <section class="card p-5 rise" style="--i: 2" aria-labelledby="judulTenagaMedis">
                <h2 id="judulTenagaMedis" class="section-title">Tenaga Medis Aktif</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <a href="{{ route('admin.doctors') }}" class="panel block hover:border-border-hover transition-colors">
                        <span class="eyebrow">Dokter</span>
                        <span class="mt-1 block text-2xl font-semibold text-medical-navy num">{{ $dokterAktifCount }}</span>
                        <span class="link text-xs">Kelola dokter</span>
                    </a>
                    <a href="{{ route('admin.nurses') }}" class="panel block hover:border-border-hover transition-colors">
                        <span class="eyebrow">Perawat</span>
                        <span class="mt-1 block text-2xl font-semibold text-medical-navy num">{{ $perawatAktifCount }}</span>
                        <span class="link text-xs">Kelola perawat</span>
                    </a>
                </div>
            </section>

            {{-- Admin --}}
            <section class="card p-5 rise" style="--i: 3" aria-labelledby="judulAdmin">
                <h2 id="judulAdmin" class="section-title">Administrator</h2>
                <div class="mt-4 flex items-center gap-3">
                    <span class="avatar w-10 h-10 bg-medical-navy text-white text-xs" aria-hidden="true">{{ $admin->inisial }}</span>
                    <div class="min-w-0">
                        <p class="font-semibold text-medical-navy truncate">{{ $admin->nama_lengkap }}</p>
                        <p class="text-sm text-text-secondary truncate">{{ $admin->email }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

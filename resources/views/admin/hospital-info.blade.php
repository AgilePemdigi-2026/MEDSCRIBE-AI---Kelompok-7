@extends('layouts.admin')

@section('title', 'Profil Rumah Sakit - MedScribe AI')

@section('content')
@php
    $lokasi = collect([$hospital->city, $hospital->province])->filter()->join(', ');
@endphp

<div class="space-y-6">
    <x-page-header eyebrow="Profil fasilitas kesehatan"
                   :title="$hospital->hospital_name"
                   subtitle="Data rumah sakit yang tercatat di MedScribe AI." />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Data fasilitas: identitas registrasi terkunci, alamat dan kontak bisa diubah admin. --}}
        <section class="card p-5 sm:p-6 lg:col-span-8 rise" style="--i: 1" aria-labelledby="judulDataFaskes">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="judulDataFaskes" class="section-title">Data Fasilitas</h2>
                    <p class="section-subtitle">Alamat dan kontak ini dicetak di kop dokumen SOAP.</p>
                </div>
                @if ($hospital->is_active)
                    <span class="status status-ok">Aktif di MedScribe</span>
                @else
                    <span class="status status-muted">Tidak aktif</span>
                @endif
            </div>

            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                <div class="sm:col-span-3">
                    <dt class="text-text-secondary">Nama rumah sakit</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $hospital->hospital_name }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Kode SIRS</dt>
                    <dd class="mt-1"><span class="tag-id">{{ $hospital->hospital_code }}</span></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-text-secondary">Jenis</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $hospital->hospital_type ?: '-' }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-text-secondary">Nama, kode SIRS, dan jenis mengikuti data registrasi faskes. Hubungi pengelola sistem bila perlu diperbarui.</p>

            <form method="POST" action="{{ route('admin.hospital.update') }}" class="mt-6 pt-6 border-t border-border-subtle space-y-4" data-submit-loading>
                @csrf
                @method('PUT')

                <div>
                    <label for="address" class="field-label">Alamat</label>
                    <textarea id="address" name="address" rows="2" maxlength="255"
                              class="field leading-relaxed @error('address') is-invalid @enderror">{{ old('address', $hospital->address) }}</textarea>
                    @error('address')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="field-label">Kota/kabupaten <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="city" name="city" required maxlength="100"
                               value="{{ old('city', $hospital->city) }}"
                               class="field @error('city') is-invalid @enderror">
                        @error('city')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="province" class="field-label">Provinsi <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="province" name="province" required maxlength="100"
                               value="{{ old('province', $hospital->province) }}"
                               class="field @error('province') is-invalid @enderror">
                        @error('province')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="telepon" class="field-label">Telepon</label>
                        <input type="tel" id="telepon" name="telepon" maxlength="30" inputmode="tel"
                               value="{{ old('telepon', $hospital->telepon) }}" placeholder="(031) 5916290"
                               class="field num @error('telepon') is-invalid @enderror">
                        @error('telepon')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="field-label">Email</label>
                        <input type="email" id="email" name="email" maxlength="100"
                               value="{{ old('email', $hospital->email) }}" placeholder="info@rumahsakit.co.id"
                               class="field @error('email') is-invalid @enderror">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    @include('partials.submit-button', ['id' => 'btnSimpanFaskes', 'label' => 'Simpan data fasilitas', 'loading' => 'Menyimpan...', 'class' => 'w-full sm:w-auto px-6'])
                </div>
            </form>
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

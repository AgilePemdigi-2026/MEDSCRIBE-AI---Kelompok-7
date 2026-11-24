@extends('layouts.app')

@section('title', 'Profil - MedScribe AI')

@section('content')
@php
    $dashboardRoute = $user->isDokter() ? 'dokter.dashboard' : 'perawat.dashboard';
    $kepanjanganIzin = $user->isPerawat() ? 'Surat Izin Praktik Perawat' : 'Surat Izin Praktik';
@endphp
<div class="space-y-6">

    @unless ($modeLengkapi)
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-text-secondary rise">
            @include('partials.back-link', ['href' => route($dashboardRoute), 'label' => $user->isDokter() ? 'Dashboard Dokter' : 'Nurse Station'])
            <span class="text-border-hover" aria-hidden="true">›</span>
            <span class="font-semibold text-medical-navy" aria-current="page">Profil</span>
        </nav>
    @endunless

    <x-page-header :title="$modeLengkapi ? 'Lengkapi profil Anda' : 'Profil ' . strtolower($user->peran_label)"
                   :subtitle="'Nama dan ' . $labelIzin . ' di sini dicetak pada dokumen SOAP.'" />

    @if ($modeLengkapi)
        <div class="alert alert-info rise" role="status">
            <x-icon name="info" aria-hidden="true" />
            <div class="flex-1 text-text-body">
                <p class="font-semibold text-medical-navy">{{ $labelIzin }} Anda belum diisi</p>
                <p class="mt-0.5">Isi agar tercetak di dokumen SOAP. Kalau nomornya belum terbit, lewati dulu. Halaman ini akan muncul lagi saat Anda masuk berikutnya.</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <form method="POST" action="{{ route('profil.update') }}" class="card p-5 sm:p-7 space-y-5 lg:col-span-7 rise" style="--i: 1" data-submit-loading>
            @csrf
            @method('PUT')
            @if ($modeLengkapi)<input type="hidden" name="lanjut" value="1">@endif

            <div>
                <label for="nama_lengkap" class="field-label">Nama lengkap dan gelar <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required maxlength="100" autocomplete="name"
                       value="{{ old('nama_lengkap', $user->nama_lengkap) }}"
                       placeholder="{{ $user->isPerawat() ? 'Ns. Nama Lengkap, S.Kep.' : 'dr. Nama Lengkap, Sp.PD' }}"
                       class="field @error('nama_lengkap') is-invalid @enderror">
                @error('nama_lengkap')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="nomor_sip" class="field-label">{{ $labelIzin }} ({{ $kepanjanganIzin }})</label>
                <input type="text" id="nomor_sip" name="nomor_sip" maxlength="50" autocomplete="off" @if ($modeLengkapi) data-dialog-focus autofocus @endif
                       value="{{ old('nomor_sip', $user->nomor_sip ?? $izinTerakhir?->nomor_sip) }}"
                       placeholder="{{ $user->isPerawat() ? 'Contoh: 503/5678/SIPP/436.7.2/2026' : 'Contoh: 503/1234/SIP-D/436.7.2/2026' }}"
                       class="field num @error('nomor_sip') is-invalid @enderror">
                @error('nomor_sip')
                    <p class="field-error">{{ $message }}</p>
                @else
                    @if ($izinTerakhir)
                        <p class="field-hint text-warning-text">
                            Terisi dari {{ $labelIzin }} terakhir Anda di {{ $user->hospital?->hospital_name ?? 'rumah sakit ini' }}
                            (disimpan {{ $izinTerakhir->updated_at?->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y') }}).
                            Pastikan masih berlaku; kalau sudah terbit nomor baru, ganti dengan nomor baru.
                        </p>
                    @else
                        <p class="field-hint">Tulis persis seperti di surat izin. Kalau dikosongkan, dokumen SOAP menampilkan garis titik-titik untuk diisi manual.</p>
                    @endif
                @enderror
            </div>

            <div @class([
                'flex flex-col-reverse sm:flex-row sm:items-center gap-2 pt-5 border-t border-border-subtle',
                'sm:justify-between' => $modeLengkapi,
                'sm:justify-end' => ! $modeLengkapi,
            ])>
                @if ($modeLengkapi)
                    <button type="submit" form="formLewatiProfil" class="btn btn-secondary">Lewati dulu</button>
                @endif
                @include('partials.submit-button', [
                    'id' => 'btnSimpanProfil',
                    'label' => $modeLengkapi ? 'Simpan dan lanjutkan' : 'Simpan profil',
                    'loading' => 'Menyimpan...',
                    'class' => 'w-full sm:w-auto px-6',
                ])
            </div>
        </form>

        <aside class="lg:col-span-5 space-y-6 rise" style="--i: 2">
            <section class="card p-5 sm:p-6" aria-labelledby="judulAkun">
                <h2 id="judulAkun" class="section-title">Akun</h2>
                <p class="section-subtitle">Diatur oleh admin rumah sakit.</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-text-secondary">Email</dt>
                        <dd class="font-semibold text-medical-navy text-right break-all">{{ $user->email ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-text-secondary">Peran</dt>
                        <dd class="font-semibold text-medical-navy">{{ $user->peran_label }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-text-secondary">Rumah sakit</dt>
                        <dd class="font-semibold text-medical-navy text-right">{{ $user->hospital->hospital_name ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Gambaran tampilan di dokumen SOAP, supaya tenaga medis tahu hasil cetaknya. --}}
            <section class="card p-5 sm:p-6" aria-labelledby="judulCetak">
                <h2 id="judulCetak" class="section-title">Tampil di dokumen SOAP</h2>
                @if ($user->isDokter())
                    <div class="mt-4 panel text-center text-xs text-text-body">
                        <p>Dokter penanggung jawab pelayanan</p>
                        <div class="h-10" aria-hidden="true"></div>
                        <p class="font-semibold text-medical-navy border-t border-medical-navy/40 pt-1 mx-auto max-w-[16rem]">{{ $user->nama_lengkap }}</p>
                        <p class="mt-0.5 text-text-secondary">SIP: {{ $user->nomor_sip ?: '............................' }}</p>
                    </div>
                @else
                    <div class="mt-4 panel text-xs text-text-body">
                        <p class="text-text-secondary">Perawat</p>
                        <p class="font-semibold text-medical-navy">{{ $user->nama_lengkap }}{{ $user->nomor_sip ? ' · SIPP ' . $user->nomor_sip : '' }}</p>
                    </div>
                @endif
            </section>
        </aside>
    </div>

    {{-- Form terpisah agar "Lewati dulu" tidak ikut memvalidasi isian profil. --}}
    @if ($modeLengkapi)
        <form id="formLewatiProfil" method="POST" action="{{ route('profil.lewati') }}" class="hidden">@csrf</form>
    @endif
</div>
@endsection

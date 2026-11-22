@extends('layouts.app')

@section('title', 'Profil Dokter - MedScribe AI')

@section('content')
<div class="space-y-6">

    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-text-secondary rise">
        @include('partials.back-link', ['href' => route('dokter.dashboard'), 'label' => 'Dashboard Dokter'])
        <span class="text-border-hover" aria-hidden="true">›</span>
        <span class="font-semibold text-medical-navy" aria-current="page">Profil</span>
    </nav>

    <x-page-header title="Profil dokter"
                   subtitle="Nama dan No. SIP di sini dicetak di bawah tanda tangan pada dokumen SOAP." />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <form method="POST" action="{{ route('dokter.profil.update') }}" class="card p-5 sm:p-7 space-y-5 lg:col-span-7 rise" style="--i: 1" data-submit-loading>
            @csrf
            @method('PUT')

            <div>
                <label for="nama_lengkap" class="field-label">Nama lengkap dan gelar <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required maxlength="100" autocomplete="name"
                       value="{{ old('nama_lengkap', $user->nama_lengkap) }}" placeholder="dr. Nama Lengkap, Sp.PD"
                       class="field @error('nama_lengkap') is-invalid @enderror">
                @error('nama_lengkap')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="nomor_sip" class="field-label">No. SIP (Surat Izin Praktik)</label>
                <input type="text" id="nomor_sip" name="nomor_sip" maxlength="50" autocomplete="off"
                       value="{{ old('nomor_sip', $user->nomor_sip) }}" placeholder="Contoh: 503/1234/SIP-D/436.7.2/2026"
                       class="field num @error('nomor_sip') is-invalid @enderror">
                @error('nomor_sip')
                    <p class="field-error">{{ $message }}</p>
                @else
                    <p class="field-hint">Tulis persis seperti di surat izin praktik. Kalau dikosongkan, dokumen SOAP menampilkan garis titik-titik untuk diisi manual.</p>
                @enderror
            </div>

            <div class="flex justify-end pt-5 border-t border-border-subtle">
                @include('partials.submit-button', ['id' => 'btnSimpanProfil', 'label' => 'Simpan profil', 'loading' => 'Menyimpan...', 'class' => 'w-full sm:w-auto px-6'])
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

            {{-- Gambaran blok tanda tangan di dokumen SOAP, supaya dokter tahu hasil cetaknya. --}}
            <section class="card p-5 sm:p-6" aria-labelledby="judulCetak">
                <h2 id="judulCetak" class="section-title">Tampil di dokumen SOAP</h2>
                <div class="mt-4 panel text-center text-xs text-text-body">
                    <p>Dokter penanggung jawab pelayanan</p>
                    <div class="h-10" aria-hidden="true"></div>
                    <p class="font-semibold text-medical-navy border-t border-medical-navy/40 pt-1 mx-auto max-w-[16rem]">{{ $user->nama_lengkap }}</p>
                    <p class="mt-0.5 text-text-secondary">SIP: {{ $user->nomor_sip ?: '............................' }}</p>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

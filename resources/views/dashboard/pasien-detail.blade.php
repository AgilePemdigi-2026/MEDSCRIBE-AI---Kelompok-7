@extends('layouts.app')

@section('title', 'Detail Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
    $usia = $pasien->umur ?: ($pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->age : null);
    // Sama dengan kode kunjungan di Nurse Station dan rekam medis dokter.
    $prefixKunjungan = $pasien->hospital?->hospital_code ? 'RS' . $pasien->hospital->hospital_code : 'RSUA';
@endphp
<div class="space-y-6">

    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-text-secondary rise">
        @include('partials.back-link', ['href' => route('perawat.dashboard', ['tab' => 'pasien']), 'label' => 'Daftar Pasien'])
        <span class="text-border-hover" aria-hidden="true">›</span>
        <span class="font-semibold text-medical-navy" aria-current="page">{{ $pasien->nama_pasien }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Kartu identitas pasien --}}
        <aside class="card-lavender spotlight lg:col-span-4 p-6 lg:sticky lg:top-28 rise" style="--i: 1">
            <div class="relative">
                <span class="avatar w-20 h-20 text-3xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                    {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                </span>
                <h1 class="mt-5 text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">{{ $pasien->nama_pasien }}</h1>
                <span class="mt-2 tag-id bg-white">{{ $pasien->no_rekam_medis }}</span>

                @php
                    $identitas = [
                        ['Tanggal lahir', $pasien->tanggal_lahir?->locale('id')->isoFormat('D MMMM Y'), false],
                        ['Umur', $usia !== null ? $usia . ' tahun' : null, false],
                        ['Jenis kelamin', $pasien->jenis_kelamin, false],
                        ['No. HP', $pasien->no_hp, true],
                        ['NIK', $pasien->nik, true],
                        ['No. BPJS', $pasien->no_bpjs, true],
                    ];
                @endphp
                <dl class="mt-6 grid grid-cols-2 gap-2">
                    @foreach ($identitas as [$labelIdentitas, $nilaiIdentitas, $angka])
                        <div class="rounded-(--radius-field) bg-white/80 border border-border-hover p-3 min-w-0">
                            <dt class="text-[11px] text-text-secondary">{{ $labelIdentitas }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-medical-navy break-all {{ $angka ? 'num' : '' }}">{{ $nilaiIdentitas ?: '-' }}</dd>
                        </div>
                    @endforeach
                    @if ($pasien->created_at)
                        <div class="col-span-2 rounded-(--radius-field) bg-white/80 border border-border-hover p-3">
                            <dt class="text-[11px] text-text-secondary">Terdaftar</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-medical-navy">{{ $pasien->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6">
                    <button type="button" onclick="openModalHapusPasien({{ $pasien->id_pasien }}, '{{ addslashes($pasien->nama_pasien) }}', '{{ $pasien->no_rekam_medis }}')"
                            class="btn btn-danger-quiet btn-sm w-full" title="Hapus / Batalkan Registrasi Pasien">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Hapus registrasi
                    </button>
                </div>
            </div>
        </aside>

        {{-- Riwayat kunjungan sebagai linimasa --}}
        <section class="lg:col-span-8 rise" style="--i: 2" aria-labelledby="judulRiwayat">
            <div class="flex items-end justify-between gap-3 mb-4">
                <div>
                    <h2 id="judulRiwayat" class="section-title">Riwayat kunjungan &amp; TTV</h2>
                    <p class="section-subtitle"><span class="num" data-ticker="{{ $riwayatKunjungan->count() }}">{{ $riwayatKunjungan->count() }}</span> kunjungan tercatat</p>
                </div>
            </div>

            @forelse ($riwayatKunjungan as $kunjungan)
                @php
                    $pa = $kunjungan->pemeriksaanAwal;
                    $tglStr = str_replace('-', '', $kunjungan->tanggal);
                    $kodeKunjungan = $prefixKunjungan . '-' . $tglStr . '-' . str_pad($kunjungan->id_antrean ?? $kunjungan->id_kunjungan, 3, '0', STR_PAD_LEFT);
                @endphp
                <div class="relative pl-8 pb-6 last:pb-0">
                    <span class="absolute left-[11px] top-3 bottom-0 w-px bg-border-hover" aria-hidden="true"></span>
                    <span class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-white border-2 border-royal-indigo flex items-center justify-center" aria-hidden="true">
                        <span class="w-2 h-2 rounded-full bg-royal-indigo"></span>
                    </span>

                    <article class="card card-hover spotlight overflow-hidden">
                        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-border-subtle">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-sm font-semibold text-medical-navy">{{ \Carbon\Carbon::parse($kunjungan->tanggal)->locale('id')->isoFormat('D MMMM Y') }}</h3>
                                <span class="tag-id">{{ $kodeKunjungan }}</span>
                            </div>
                            @if ($kunjungan->status)
                                <span class="status {{ in_array($kunjungan->status, ['Selesai', 'Final', \App\Models\Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT, \App\Models\Kunjungan::STATUS_SOAP_TERVERIFIKASI]) ? 'status-ok' : 'status-active' }}">{{ $kunjungan->status }}</span>
                            @else
                                <span class="status status-muted">Belum ada status</span>
                            @endif
                        </div>

                        <div class="relative p-5">
                            @if ($pa)
                                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <div class="vital"><dt class="vital-label">Tekanan Darah</dt><dd class="vital-value">{{ $pa->tekanan_darah ?? '-' }}<span class="vital-unit">mmHg</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Nadi</dt><dd class="vital-value">{{ $pa->nadi ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Suhu</dt><dd class="vital-value">{{ $pa->suhu_tubuh ?? '-' }}<span class="vital-unit">°C</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Pernapasan</dt><dd class="vital-value">{{ $pa->laju_pernapasan ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                                    @if ($pa->saturasi_oksigen)
                                        <div class="vital"><dt class="vital-label">SpO2</dt><dd class="vital-value">{{ $pa->saturasi_oksigen }}<span class="vital-unit">%</span></dd></div>
                                    @endif
                                    @if ($pa->berat_badan)
                                        <div class="vital"><dt class="vital-label">Berat Badan</dt><dd class="vital-value">{{ $pa->berat_badan + 0 }}<span class="vital-unit">kg</span></dd></div>
                                    @endif
                                    @if ($pa->tinggi_badan)
                                        <div class="vital"><dt class="vital-label">Tinggi Badan</dt><dd class="vital-value">{{ $pa->tinggi_badan + 0 }}<span class="vital-unit">cm</span></dd></div>
                                    @endif
                                </dl>

                                @if ($pa->catatan)
                                    <div class="mt-4 panel text-sm text-text-body">
                                        <p class="font-semibold text-medical-navy">Catatan perawat</p>
                                        <p class="mt-0.5">{{ $pa->catatan }}</p>
                                    </div>
                                @endif

                                @if ($pa->perawat)
                                    <p class="mt-4 flex items-center gap-2 text-[11px] text-text-secondary">
                                        <span class="avatar w-6 h-6 bg-medical-navy text-white text-[10px]" aria-hidden="true">{{ strtoupper(substr($pa->perawat->nama_lengkap, 0, 1)) }}</span>
                                        Diperiksa oleh: <strong class="text-medical-navy font-semibold">{{ $pa->perawat->nama_lengkap }}</strong>
                                        @if ($pa->created_at)
                                            <span aria-hidden="true">·</span> {{ $pa->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB
                                        @endif
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-text-secondary">Tanda-tanda vital untuk kunjungan ini belum diisi.</p>
                            @endif
                        </div>
                    </article>
                </div>
            @empty
                <div class="card">
                    <x-empty-state title="Belum ada riwayat kunjungan"
                                   hint="Kunjungan dan tanda-tanda vital pasien ini akan tercatat di sini."
                                   icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </div>
            @endforelse
        </section>
    </div>
</div>

@include('partials.modal-hapus-pasien')
@endsection

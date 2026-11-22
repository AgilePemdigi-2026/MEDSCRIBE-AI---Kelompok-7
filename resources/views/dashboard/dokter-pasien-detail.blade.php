@extends('layouts.app')

@section('title', 'Riwayat Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
    $prefixKunjungan = $pasien->hospital?->hospital_code ? 'RS' . $pasien->hospital->hospital_code : 'RSUA';
    $jumlahFinal = $riwayatKunjungan->filter(fn ($k) => $k->isFinalized())->count();
@endphp
<div class="space-y-6">

    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-text-secondary rise">
        @include('partials.back-link', ['href' => route('dokter.pasien.index'), 'label' => 'Daftar Pasien'])
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
                        ['Umur', $pasien->usia_label, false],
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
                </dl>
            </div>
        </aside>

        {{-- Riwayat kunjungan dan rekam medis --}}
        <section class="lg:col-span-8 rise" style="--i: 2" aria-labelledby="judulRiwayatDokter">
            <div class="mb-4">
                <h2 id="judulRiwayatDokter" class="section-title">Riwayat kunjungan &amp; rekam medis</h2>
                <p class="section-subtitle">
                    <span class="num">{{ $riwayatKunjungan->count() }}</span> kunjungan,
                    <span class="num">{{ $jumlahFinal }}</span> rekam medis final
                </p>
            </div>

            @forelse ($riwayatKunjungan as $kunjungan)
                @php
                    $pa = $kunjungan->pemeriksaanAwal;
                    $draf = $kunjungan->drafSoap;
                    $final = $kunjungan->isFinalized();
                    $tglStr = str_replace('-', '', $kunjungan->tanggal);
                    $kodeKunjungan = $prefixKunjungan . '-' . $tglStr . '-' . str_pad($kunjungan->id_antrean ?? $kunjungan->id_kunjungan, 3, '0', STR_PAD_LEFT);
                    $milikSaya = (int) $kunjungan->id_dokter === (int) $user->id_pengguna;
                @endphp
                <div class="relative pl-8 pb-6 last:pb-0">
                    <span class="absolute left-[11px] top-3 bottom-0 w-px bg-border-hover" aria-hidden="true"></span>
                    <span class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-white border-2 {{ $final ? 'border-success-dot' : 'border-royal-indigo' }} flex items-center justify-center" aria-hidden="true">
                        <span class="w-2 h-2 rounded-full {{ $final ? 'bg-success-dot' : 'bg-royal-indigo' }}"></span>
                    </span>

                    <article class="card overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-border-subtle">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-sm font-semibold text-medical-navy">{{ \Carbon\Carbon::parse($kunjungan->tanggal)->locale('id')->isoFormat('D MMMM Y') }}</h3>
                                <span class="tag-id">{{ $kodeKunjungan }}</span>
                            </div>
                            <span class="status {{ $final ? 'status-ok' : 'status-active' }}">{{ $final ? 'Final' : ($kunjungan->status ?: 'Belum ada status') }}</span>
                        </div>

                        <div class="p-5 space-y-4">
                            @if ($final && $draf)
                                <div class="panel text-sm">
                                    <p class="text-[11px] text-text-secondary">Diagnosis</p>
                                    <p class="mt-0.5 font-semibold text-medical-navy">
                                        @if ($draf->kode_icd10)<span class="tag-id mr-1.5">{{ $draf->kode_icd10 }}</span>@endif
                                        {{ \Illuminate\Support\Str::limit($draf->assessment_text, 160) ?: 'Assessment tidak dicatat.' }}
                                    </p>
                                </div>
                            @elseif (! $final)
                                <p class="text-sm text-text-secondary">Rekam medis kunjungan ini belum difinalisasi, jadi isinya belum bisa dibuka sebagai riwayat.</p>
                            @endif

                            @if ($pa)
                                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <div class="vital"><dt class="vital-label">Tekanan Darah</dt><dd class="vital-value">{{ $pa->tekanan_darah ?? '-' }}<span class="vital-unit">mmHg</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Nadi</dt><dd class="vital-value">{{ $pa->nadi ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Suhu</dt><dd class="vital-value">{{ $pa->suhu_tubuh ?? '-' }}<span class="vital-unit">°C</span></dd></div>
                                    <div class="vital"><dt class="vital-label">SpO2</dt><dd class="vital-value">{{ $pa->saturasi_oksigen ?? '-' }}<span class="vital-unit">%</span></dd></div>
                                </dl>
                            @endif

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                                <p class="flex items-center gap-2 text-[11px] text-text-secondary">
                                    <x-icon name="stethoscope" class="w-3.5 h-3.5" aria-hidden="true" />
                                    {{ $kunjungan->dokter?->nama_lengkap ?? 'Dokter belum ditetapkan' }}
                                    @if ($milikSaya)<span class="chip chip-accent">Anda</span>@endif
                                </p>
                                @if ($final)
                                    <a href="{{ route('dokter.soap.preview', ['kunjungan' => $kunjungan->id_kunjungan, 'kembali' => 'pasien']) }}" class="btn btn-primary btn-sm">
                                        <x-icon name="file-text" class="w-3.5 h-3.5" aria-hidden="true" />
                                        Lihat rekam medis
                                    </a>
                                @elseif ($milikSaya && $kunjungan->id_antrean)
                                    <a href="{{ route('dokter.rekam-medis', $kunjungan->id_antrean) }}" class="btn btn-secondary btn-sm">
                                        <x-icon name="arrow-right" class="w-3.5 h-3.5" aria-hidden="true" />
                                        Lanjutkan pemeriksaan
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="card">
                    <x-empty-state title="Belum ada riwayat kunjungan"
                                   hint="Kunjungan pasien ini akan tercatat di sini setelah diperiksa."
                                   icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection

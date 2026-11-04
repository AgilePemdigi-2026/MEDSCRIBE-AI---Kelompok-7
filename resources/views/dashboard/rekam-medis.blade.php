@extends('layouts.app')

@section('title', 'Rekam Medis - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
    $demam = $pemeriksaanAwal && $pemeriksaanAwal->suhu_tubuh && $pemeriksaanAwal->suhu_tubuh > 37.5;
    $sudahDitinjau = in_array($workflowState, ['soap-ready', 'soap', 'completed'], true);
    $terverifikasi = $finalisasi?->FINAL === true || $workflowState === 'completed';

    $tahapWawancara = $workflowState === 'ttv' ? 'locked' : ($sudahDitinjau ? 'done' : 'active');
    $tahapSoap = ! $sudahDitinjau ? 'locked' : ($terverifikasi ? 'done' : 'active');

    $navigasi = [
        ['wawancara', 'Wawancara Klinis', $tahapWawancara],
        ['soap', 'Draf SOAP', $tahapSoap],
        ['tindak-lanjut', 'Tindak Lanjut', $tahapTindakLanjut],
        ['finalisasi', 'Finalisasi', $tahapFinalisasi],
    ];
@endphp

<div class="space-y-6">

    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-text-secondary rise">
        @include('partials.back-link', ['href' => route('dokter.dashboard'), 'label' => 'Dashboard Dokter'])
        <span class="text-border-hover" aria-hidden="true">›</span>
        <span class="font-semibold text-medical-navy" aria-current="page">{{ $pasien->nama_pasien }}</span>
        <span class="tag-id">{{ $idKunjunganCode }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Kolom kiri: identitas, TTV, dan navigasi tahap. Menempel saat menggulir agar TTV selalu terlihat. --}}
        <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-28">
            <section class="card-lavender spotlight p-5 sm:p-6 rise" style="--i: 1" aria-label="Identitas pasien">
                <div class="relative flex items-start gap-4">
                    <span class="avatar w-14 h-14 text-xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                        {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold tracking-tight text-medical-navy">{{ $pasien->nama_pasien }}</h1>
                        <p class="text-sm text-text-body/80">
                            {{ $umur ? $umur . ' tahun' : 'Umur belum diisi' }} · {{ $pasien->jenis_kelamin ?? '-' }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="tag-id bg-white">RM {{ $pasien->no_rekam_medis }}</span>
                            <span class="tag-id tag-id-muted bg-white/80">A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>
                <p class="relative mt-4 text-xs text-text-body/80">
                    {{ \Carbon\Carbon::parse($antrean->tanggal_kunjungan)->locale('id')->isoFormat('D MMMM Y') }} · {{ $user->nama_lengkap }}
                </p>
            </section>

            <section class="card p-5 sm:p-6 space-y-4 rise" style="--i: 2" aria-labelledby="judulTTV">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judulTTV" class="text-base font-semibold text-medical-navy">TTV & Catatan Perawat</h2>
                    @unless ($pemeriksaanAwal)
                        <span class="status status-warn">Belum diisi</span>
                    @endunless
                </div>

                @if ($pemeriksaanAwal)
                    <dl class="grid grid-cols-2 gap-2">
                        <div class="vital"><dt class="vital-label">Tekanan Darah</dt><dd class="vital-value">{{ $pemeriksaanAwal->tekanan_darah ?? '-' }}<span class="vital-unit">mmHg</span></dd></div>
                        <div class="vital"><dt class="vital-label">Nadi</dt><dd class="vital-value">{{ $pemeriksaanAwal->nadi ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                        <div class="vital {{ $demam ? 'is-flagged' : '' }}"><dt class="vital-label">Suhu</dt><dd class="vital-value">{{ $pemeriksaanAwal->suhu_tubuh ?? '-' }}<span class="vital-unit">°C</span></dd></div>
                        <div class="vital"><dt class="vital-label">Pernapasan</dt><dd class="vital-value">{{ $pemeriksaanAwal->laju_pernapasan ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                        <div class="vital"><dt class="vital-label">SpO2</dt><dd class="vital-value">{{ $pemeriksaanAwal->saturasi_oksigen ?? '-' }}<span class="vital-unit">%</span></dd></div>
                        <div class="vital"><dt class="vital-label">BB / TB</dt><dd class="vital-value">{{ $pemeriksaanAwal->berat_badan ?? '-' }}<span class="vital-unit">kg</span> {{ $pemeriksaanAwal->tinggi_badan ?? '-' }}<span class="vital-unit">cm</span></dd></div>
                    </dl>

                    @if ($pemeriksaanAwal->catatan)
                        <div class="p-4 rounded-[18px] bg-pale-lavender border border-border-hover text-sm">
                            <p class="text-text-body leading-relaxed">{{ $pemeriksaanAwal->catatan }}</p>
                        </div>
                    @endif

                    @if ($pemeriksaanAwal->perawat)
                        <p class="flex items-center gap-2 text-[11px] text-text-secondary">
                            <span class="avatar w-6 h-6 bg-medical-navy text-white text-[10px]" aria-hidden="true">{{ strtoupper(substr($pemeriksaanAwal->perawat->nama_lengkap, 0, 1)) }}</span>
                            {{ $pemeriksaanAwal->perawat->nama_lengkap }}
                            @if ($pemeriksaanAwal->created_at)
                                · {{ $pemeriksaanAwal->created_at->timezone('Asia/Jakarta')->format('H:i') }} WIB
                            @endif
                        </p>
                    @endif
                @else
                    <p class="py-6 text-center text-xs text-text-secondary bg-soft-offwhite rounded-[18px] border border-dashed border-border-hover">
                        Perawat belum mengisi pemeriksaan awal.
                    </p>
                @endif
            </section>

            {{-- Stepper vertikal (pola 21st.dev "Vertical Titled Stepper"): garis penghubung hijau untuk tahap yang selesai. --}}
            <nav class="card p-5 sm:p-6 rise hidden lg:block" style="--i: 3" aria-label="Tahap rekam medis">
                <ol>
                    @foreach ($navigasi as $i => [$anchor, $label, $keadaan])
                        <li class="relative">
                            @unless ($loop->last)
                                <span @class([
                                    'absolute left-4 top-10 bottom-1 w-0.5 -translate-x-1/2 rounded-full',
                                    'bg-success-dot' => $keadaan === 'done',
                                    'bg-border-subtle' => $keadaan !== 'done',
                                ]) aria-hidden="true"></span>
                            @endunless
                            <a href="#{{ $anchor }}" class="group flex items-start gap-3 rounded-[14px] {{ $loop->last ? '' : 'pb-6' }}" @if ($keadaan === 'active') aria-current="step" @endif>
                                <span @class([
                                    'avatar w-8 h-8 text-xs transition-transform group-hover:scale-105',
                                    'bg-success-dot text-white' => $keadaan === 'done',
                                    'bg-royal-indigo text-white ring-4 ring-royal-indigo/15' => $keadaan === 'active',
                                    'bg-soft-offwhite text-text-secondary border border-border-subtle' => $keadaan === 'locked',
                                    'bg-white text-text-secondary border border-dashed border-border-hover' => $keadaan === 'soon',
                                ]) aria-hidden="true">
                                    @if ($keadaan === 'done')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <span class="pt-1 min-w-0">
                                    <span class="block text-sm leading-tight transition-colors group-hover:text-royal-indigo {{ in_array($keadaan, ['done', 'active'], true) ? 'font-semibold text-medical-navy' : 'font-medium text-text-secondary' }}">{{ $label }}</span>
                                    <span class="block mt-0.5 text-[11px] text-text-secondary">
                                        {{ ['done' => 'Selesai', 'active' => 'Sedang dikerjakan', 'locked' => 'Terkunci', 'soon' => 'Belum tersedia'][$keadaan] }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </aside>

        {{-- Kolom kanan: empat tahap pemeriksaan dalam satu halaman --}}
        <div class="lg:col-span-8 space-y-6">

            <section id="wawancara" class="card scroll-mt-28 rise {{ $tahapWawancara === 'active' ? 'ring-2 ring-royal-indigo/15' : '' }}" style="--i: 2" aria-labelledby="judulWawancara">
                @include('dashboard.rekam-medis.wawancara')
            </section>

            <section id="soap" class="card scroll-mt-28 rise {{ $tahapSoap === 'active' ? 'ring-2 ring-royal-indigo/15' : '' }}" style="--i: 3" aria-labelledby="judulSoap">
                @include('dashboard.rekam-medis.soap')
            </section>

            <section id="tindak-lanjut" class="card scroll-mt-28 rise {{ $tahapTindakLanjut === 'active' ? 'ring-2 ring-royal-indigo/15' : '' }}" style="--i: 4" aria-labelledby="judulTindakLanjut">
                @include('dashboard.rekam-medis.tindak-lanjut', [
                    'tahapTindakLanjut' => $tahapTindakLanjut,
                    'tindakLanjut' => $tindakLanjut,
                    'parameterLoinc' => $parameterLoinc,
                ])
            </section>

            <section id="finalisasi" class="card scroll-mt-28 rise {{ $tahapFinalisasi === 'active' ? 'ring-2 ring-royal-indigo/15' : '' }}" style="--i: 5" aria-labelledby="judulFinalisasi">
                @include('dashboard.rekam-medis.finalisasi')
            </section>
        </div>
    </div>
</div>
@endsection

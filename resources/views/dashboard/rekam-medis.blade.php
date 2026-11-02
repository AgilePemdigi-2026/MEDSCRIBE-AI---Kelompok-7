@extends('layouts.app')

@section('title', 'Rekam Medis Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
    $demam = $pemeriksaanAwal && $pemeriksaanAwal->suhu_tubuh && $pemeriksaanAwal->suhu_tubuh > 37.5;

    $workflowStep = match ($workflowState) {
        'ttv' => 1,
        'completed' => 5,
        'soap', 'soap-ready' => 4,
        'transcript' => 3,
        default => 2,
    };
    $workflowAction = match ($workflowState) {
        'ttv' => 'TTV Belum Lengkap',
        'interview' => 'Mulai Wawancara',
        'recording' => 'Lanjutkan Wawancara',
        'processing' => 'Lihat Status Transkripsi',
        'transcript' => 'Review Transkrip',
        'soap-ready', 'soap' => 'Lanjut ke SOAP',
        'completed' => 'Lihat Ringkasan Kunjungan',
        default => 'Mulai Wawancara',
    };
    $interviewUrl = route('dokter.interview.show', $kunjungan);
    $nextUrl = in_array($workflowState, ['soap-ready', 'soap', 'completed'], true)
        ? route('dokter.soap.edit', $kunjungan)
        : $interviewUrl;
@endphp
<div class="space-y-6">

    {{-- Breadcrumb + tahapan klinis --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rise">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-text-secondary">
            @include('partials.back-link', ['href' => route('dokter.dashboard'), 'label' => 'Dashboard Dokter'])
            <span class="text-border-hover" aria-hidden="true">›</span>
            <span class="font-semibold text-medical-navy" aria-current="page">{{ $pasien->nama_pasien }}</span>
            <span class="tag-id">{{ $idKunjunganCode }}</span>
        </nav>

        <ol class="pill-nav self-start md:self-auto text-xs" aria-label="Tahapan pemeriksaan">
            @foreach ([1 => 'TTV & Catatan', 2 => 'Wawancara Audio', 3 => 'Review Transkrip', 4 => 'Draf SOAP', 5 => 'Selesai'] as $number => $label)
                <li class="inline-flex items-center gap-1.5 min-h-9 px-3.5 rounded-full font-medium {{ $number < $workflowStep ? 'bg-success-bg text-success-text' : ($number === $workflowStep ? 'bg-medical-navy text-white' : 'text-text-secondary') }}" @if ($number === $workflowStep) aria-current="step" @endif>
                    @if ($number < $workflowStep)
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @endif
                    {{ $number }}. {{ $label }}
                </li>
            @endforeach
        </ol>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Kolom kiri: identitas pasien + TTV --}}
        <div class="lg:col-span-5 space-y-6">
            <section class="card-lavender spotlight p-6 rise" style="--i: 1" aria-label="Identitas pasien">
                <div class="relative flex items-start gap-4">
                    <span class="avatar w-16 h-16 text-2xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                        {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">{{ $pasien->nama_pasien }}</h1>
                        <p class="text-sm text-text-body/80">
                            @if ($umur)
                                {{ $umur }} tahun
                                @if ($pasien->tanggal_lahir)
                                    ({{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->format('d/m/Y') }})
                                @endif
                            @else
                                -
                            @endif
                            · {{ $pasien->jenis_kelamin ?? '-' }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="tag-id bg-white">RM: {{ $pasien->no_rekam_medis }}</span>
                            <span class="tag-id tag-id-muted bg-white/80">Antrean A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>
                <dl class="relative mt-5 grid grid-cols-2 gap-2">
                    <div class="rounded-[16px] bg-white/80 border border-border-hover p-3">
                        <dt class="text-[11px] text-text-secondary">Tgl Kunjungan</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-medical-navy">{{ \Carbon\Carbon::parse($antrean->tanggal_kunjungan)->format('d F Y') }}</dd>
                    </div>
                    <div class="rounded-[16px] bg-white/80 border border-border-hover p-3">
                        <dt class="text-[11px] text-text-secondary">Dokter Pemeriksa</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-medical-navy truncate">{{ $user->nama_lengkap }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card p-5 sm:p-6 space-y-4 rise" style="--i: 2" aria-labelledby="judulTTV">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="judulTTV" class="text-base font-semibold text-medical-navy">TTV & Catatan Perawat</h2>
                        <p class="text-xs text-text-secondary mt-0.5">Pemeriksaan tanda-tanda vital awal Nurse Station</p>
                    </div>
                    @if ($pemeriksaanAwal)
                        <span class="status status-ok">Terverifikasi oleh Perawat</span>
                    @else
                        <span class="status status-warn">Belum Ada Data TTV</span>
                    @endif
                </div>

                @if ($pemeriksaanAwal)
                    @if ($pemeriksaanAwal->perawat)
                        <p class="flex items-center gap-2 text-[11px] text-text-secondary">
                            <span class="avatar w-6 h-6 bg-medical-navy text-white text-[10px]" aria-hidden="true">{{ strtoupper(substr($pemeriksaanAwal->perawat->nama_lengkap, 0, 1)) }}</span>
                            {{ $pemeriksaanAwal->perawat->nama_lengkap }}
                            @if ($pemeriksaanAwal->created_at)
                                · {{ $pemeriksaanAwal->created_at->timezone('Asia/Jakarta')->format('H:i') }} WIB
                            @endif
                        </p>
                    @endif

                    <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-2.5">
                        <div class="vital"><dt class="vital-label">Tekanan Darah</dt><dd class="vital-value">{{ $pemeriksaanAwal->tekanan_darah ?? '-' }}<span class="vital-unit">mmHg</span></dd></div>
                        <div class="vital"><dt class="vital-label">Denyut Nadi</dt><dd class="vital-value">{{ $pemeriksaanAwal->nadi ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                        <div class="vital {{ $demam ? 'is-flagged' : '' }}"><dt class="vital-label">Suhu Tubuh</dt><dd class="vital-value">{{ $pemeriksaanAwal->suhu_tubuh ?? '-' }}<span class="vital-unit">°C</span></dd></div>
                        <div class="vital"><dt class="vital-label">Pernapasan (RR)</dt><dd class="vital-value">{{ $pemeriksaanAwal->laju_pernapasan ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                        <div class="vital"><dt class="vital-label">Saturasi SpO2</dt><dd class="vital-value">{{ $pemeriksaanAwal->saturasi_oksigen ? $pemeriksaanAwal->saturasi_oksigen . '%' : '-' }}</dd></div>
                        <div class="vital"><dt class="vital-label">Berat Badan</dt><dd class="vital-value">{{ $pemeriksaanAwal->berat_badan ?? '-' }}<span class="vital-unit">kg</span></dd></div>
                        <div class="vital"><dt class="vital-label">Tinggi Badan</dt><dd class="vital-value">{{ $pemeriksaanAwal->tinggi_badan ?? '-' }}<span class="vital-unit">cm</span></dd></div>
                    </dl>

                    @if ($pemeriksaanAwal->catatan)
                        <div class="p-4 rounded-[18px] bg-pale-lavender border border-border-hover text-sm">
                            <span class="font-semibold text-medical-navy block mb-1">Catatan Awal Perawat:</span>
                            <p class="text-text-body leading-relaxed">{{ $pemeriksaanAwal->catatan }}</p>
                        </div>
                    @endif
                @else
                    <div class="py-8 text-center text-xs text-text-secondary bg-soft-offwhite rounded-[18px] border border-dashed border-border-hover">
                        Pasien ini belum memiliki catatan pemeriksaan awal TTV dari Nurse Station.
                    </div>
                @endif
            </section>
        </div>

        {{-- Kolom kanan: Status Kunjungan & Navigasi Alur Klinis --}}
        <div class="lg:col-span-7 space-y-6">
            <section class="card spotlight p-6 rise" style="--i: 3" aria-labelledby="judulStatusKunjungan">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-5 border-b border-border-subtle">
                    <div>
                        <span class="tag-id bg-pale-lavender text-royal-indigo font-semibold">Status Kunjungan</span>
                        <h2 id="judulStatusKunjungan" class="text-xl font-bold text-medical-navy mt-2">{{ $workflowAction }}</h2>
                        <p class="text-xs text-text-secondary mt-1">
                            {{ $wawancara ? 'Sesi wawancara ini terhubung ke kunjungan ' . $idKunjunganCode . '.' : 'Belum ada rekaman wawancara yang tersimpan untuk kunjungan ini.' }}
                        </p>
                    </div>
                    <div>
                        @if ($workflowState === 'ttv')
                            <span class="btn btn-secondary cursor-not-allowed opacity-75">Lengkapi TTV Dahulu</span>
                        @else
                            <a href="{{ $nextUrl }}" class="btn btn-primary min-h-12 px-7 shadow-sm text-sm font-semibold inline-flex items-center gap-2">
                                {{ $workflowAction }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Visual Alur Pemeriksaan Klinis --}}
                <div class="mt-6 space-y-4">
                    <h3 class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Panduan Alur Klinis Terintegrasi</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-4 rounded-[16px] {{ $workflowStep > 1 ? 'bg-success-bg/40 border border-success-border/60' : 'bg-soft-offwhite border border-border-subtle' }}">
                            <div class="flex items-center gap-2 text-xs font-semibold {{ $workflowStep > 1 ? 'text-success-text' : 'text-medical-navy' }}">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] {{ $workflowStep > 1 ? 'bg-success-text text-white' : 'bg-pale-lavender text-royal-indigo' }}">1</span>
                                Pemeriksaan Awal TTV
                            </div>
                            <p class="text-[11px] text-text-secondary mt-1">Pencatatan tanda-tanda vital oleh perawat di Nurse Station.</p>
                        </div>

                        <div class="p-4 rounded-[16px] {{ $workflowStep >= 2 ? 'bg-pale-lavender/50 border border-border-hover' : 'bg-soft-offwhite border border-border-subtle' }}">
                            <div class="flex items-center gap-2 text-xs font-semibold {{ $workflowStep >= 2 ? 'text-royal-indigo' : 'text-medical-navy' }}">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] {{ $workflowStep >= 2 ? 'bg-royal-indigo text-white' : 'bg-pale-lavender text-royal-indigo' }}">2</span>
                                Wawancara Audio Klinis
                            </div>
                            <p class="text-[11px] text-text-secondary mt-1">Rekam percakapan dokter-pasien pada halaman perekaman terdedikasi.</p>
                        </div>

                        <div class="p-4 rounded-[16px] {{ $workflowStep >= 3 ? 'bg-pale-lavender/50 border border-border-hover' : 'bg-soft-offwhite border border-border-subtle' }}">
                            <div class="flex items-center gap-2 text-xs font-semibold {{ $workflowStep >= 3 ? 'text-royal-indigo' : 'text-medical-navy' }}">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] {{ $workflowStep >= 3 ? 'bg-royal-indigo text-white' : 'bg-pale-lavender text-royal-indigo' }}">3</span>
                                Review Transkrip Suara
                            </div>
                            <p class="text-[11px] text-text-secondary mt-1">Verifikasi teks hasil Whisper STT dan koreksi segmen pembicara.</p>
                        </div>

                        <div class="p-4 rounded-[16px] {{ $workflowStep >= 4 ? 'bg-pale-lavender/50 border border-border-hover' : 'bg-soft-offwhite border border-border-subtle' }}">
                            <div class="flex items-center gap-2 text-xs font-semibold {{ $workflowStep >= 4 ? 'text-royal-indigo' : 'text-medical-navy' }}">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] {{ $workflowStep >= 4 ? 'bg-royal-indigo text-white' : 'bg-pale-lavender text-royal-indigo' }}">4</span>
                                Telaah &amp; Verifikasi SOAP
                            </div>
                            <p class="text-[11px] text-text-secondary mt-1">Draf catatan SOAP terstruktur dengan saran kode diagnosis ICD-10.</p>
                        </div>
                    </div>
                </div>

                @if ($rekamanAudio)
                    <div class="mt-6 pt-5 border-t border-border-subtle">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-semibold text-medical-navy">Berkas Rekaman Audio Tersimpan</h4>
                                <p class="text-[11px] text-text-secondary mt-0.5">Status: {{ ucfirst($rekamanAudio->status ?? 'tersimpan') }}</p>
                            </div>
                            <a href="{{ route('dokter.rekam-medis.stream-audio', $antrean->id_antrean) }}" target="_blank" class="btn btn-secondary min-h-9 px-4 text-xs font-semibold inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Dengarkan Audio
                            </a>
                        </div>
                    </div>
                @endif
            </section>
        </div>

    </div>
</div>
@endsection

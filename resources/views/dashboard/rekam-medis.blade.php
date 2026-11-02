@extends('layouts.app')

@section('title', 'Rekam Medis Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
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
    <div class="bg-white p-5 sm:p-6 rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <a href="{{ route('dokter.dashboard') }}" class="text-xs font-semibold text-[#5865DC] hover:underline">← Dashboard Dokter</a>
                <h1 class="text-2xl font-bold text-[#122056] mt-3">Rekam Medis Kunjungan</h1>
                <p class="text-sm text-[#69718B] mt-1">Ringkasan pasien dan status alur klinis. Perekaman dilakukan pada satu halaman wawancara utama.</p>
            </div>
            <div class="text-left lg:text-right">
                <p class="text-xs text-[#69718B]">ID Kunjungan</p>
                <p class="font-mono font-bold text-[#5865DC]">KJ-{{ str_pad($kunjungan->id_kunjungan, 4, '0', STR_PAD_LEFT) }}</p>
                <p class="text-xs text-[#69718B] mt-1">Antrean A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 mt-6 text-[11px] font-semibold">
            @foreach ([1 => 'TTV & Rekam Medis', 2 => 'Wawancara Audio', 3 => 'Review Transkrip', 4 => 'Review SOAP', 5 => 'Penyelesaian'] as $number => $label)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border {{ $number < $workflowStep ? 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0]' : ($number === $workflowStep ? 'bg-[#5865DC] text-white border-[#5865DC]' : 'bg-[#FAFAFD] text-[#69718B] border-[#E4E7F4]') }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $number < $workflowStep ? 'bg-[#10B981]' : ($number === $workflowStep ? 'bg-white' : 'bg-[#CBD1EA]') }}"></span>
                    {{ $number }}. {{ $label }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold tracking-widest text-[#5865DC] uppercase">Identitas Pasien</p>
                <h2 class="text-2xl font-bold text-[#122056] mt-1">{{ $pasien->nama_pasien }}</h2>
                <p class="text-sm text-[#69718B] mt-1">No. RM {{ $pasien->no_rekam_medis }} · {{ $pasien->jenis_kelamin ?? '-' }} · {{ $umur ? $umur . ' tahun' : '-' }}</p>
            </div>
            <div class="rounded-xl bg-[#FAFAFD] border border-[#E4E7F4] p-3 text-xs">
                <p class="text-[#69718B]">Dokter Pemeriksa</p>
                <p class="font-bold text-[#122056] mt-1">{{ $user->nama_lengkap }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3 pb-4 border-b border-[#E4E7F4]">
            <div>
                <p class="text-[11px] font-bold tracking-widest text-[#5865DC] uppercase">TTV & Catatan Perawat</p>
                <h2 class="text-lg font-bold text-[#122056] mt-1">Pemeriksaan Awal</h2>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $pemeriksaanAwal ? 'bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]' : 'bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]' }}">
                {{ $pemeriksaanAwal ? 'Tersedia' : 'Belum tersedia' }}
            </span>
        </div>
        @if ($pemeriksaanAwal)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                @foreach ([['Tekanan darah', $pemeriksaanAwal->tekanan_darah, 'mmHg'], ['Nadi', $pemeriksaanAwal->nadi, 'x/menit'], ['Suhu', $pemeriksaanAwal->suhu_tubuh, '°C'], ['Pernapasan', $pemeriksaanAwal->laju_pernapasan, 'x/menit']] as [$label, $value, $unit])
                    <div class="rounded-xl bg-[#FAFAFD] border border-[#E4E7F4] p-3"><span class="text-xs text-[#69718B]">{{ $label }}</span><p class="font-bold text-[#122056] mt-1">{{ filled($value) ? $value . ' ' . $unit : '-' }}</p></div>
                @endforeach
            </div>
            @if ($pemeriksaanAwal->catatan)<p class="mt-4 rounded-xl bg-[#FAFAFD] border border-[#E4E7F4] p-3 text-sm text-[#20294A]">{{ $pemeriksaanAwal->catatan }}</p>@endif
            @if ($pemeriksaanAwal->perawat)<p class="mt-3 text-xs text-[#69718B]">Diperiksa oleh <span class="font-semibold text-[#20294A]">{{ $pemeriksaanAwal->perawat->nama_lengkap }}</span></p>@endif
        @else
            <p class="mt-4 rounded-xl border border-dashed border-[#CBD1EA] p-4 text-sm text-[#69718B]">Kunjungan ini belum memiliki data TTV. Wawancara belum dapat dimulai sebelum pemeriksaan awal tersedia.</p>
        @endif
    </div>

    <div class="bg-white rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold tracking-widest text-[#5865DC] uppercase">Status Kunjungan</p>
            <h2 class="text-xl font-bold text-[#122056] mt-1">{{ $workflowAction }}</h2>
            <p class="text-sm text-[#69718B] mt-1">{{ $wawancara ? 'Sesi wawancara ini terhubung ke kunjungan KJ-' . str_pad($kunjungan->id_kunjungan, 4, '0', STR_PAD_LEFT) . '.' : 'Belum ada sesi wawancara untuk kunjungan ini.' }}</p>
        </div>
        @if ($workflowState === 'ttv')
            <span class="inline-flex justify-center rounded-[14px] bg-[#FAFAFD] border border-[#E4E7F4] px-5 py-3 text-xs font-bold text-[#69718B]">Lengkapi TTV Dahulu</span>
        @else
            <a href="{{ $nextUrl }}" class="inline-flex justify-center rounded-[14px] bg-[#5865DC] hover:bg-[#4854C9] px-5 py-3 text-xs font-bold text-white">{{ $workflowAction }}</a>
        @endif
    </div>
</div>
@endsection

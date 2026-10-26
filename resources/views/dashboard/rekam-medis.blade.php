@extends('layouts.app')

@section('title', 'Rekam Medis Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Workflow Stepper -->
    <div class="bg-[#FFFFFF] p-4 sm:px-6 rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-[#69718B]">
            <a href="{{ route('dokter.dashboard') }}" class="hover:text-[#5865DC] font-medium transition-colors flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Dashboard Dokter</span>
            </a>
            <span class="text-[#CBD1EA]">›</span>
            <span class="text-[#122056] font-semibold">{{ $pasien->nama_pasien }}</span>
            <span class="text-[#CBD1EA]">·</span>
            <span class="font-mono text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2 py-0.5 rounded-[6px] text-[11px] font-bold">
                {{ $idKunjunganCode }}
            </span>
        </div>

        <!-- Clinical Workflow Stages -->
        <div class="flex items-center gap-1 sm:gap-2 text-[11px] font-semibold">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#5865DC]"></span>
                1. TTV & Anamnesis
            </span>
            <span class="text-[#CBD1EA]">›</span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ $rekamanAudio ? 'bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]' : 'bg-[#5865DC] text-[#FFFFFF] shadow-xs' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $rekamanAudio ? 'bg-[#10B981]' : 'bg-[#FFFFFF]' }}"></span>
                2. Rekaman Klinis
            </span>
            <span class="text-[#CBD1EA]">›</span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FAFAFD] text-[#69718B] border border-[#E4E7F4]">
                3. Selesai
            </span>
        </div>
    </div>

    <!-- Patient Demographics Card -->
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center text-xl font-bold shadow-[0_4px_16px_rgba(88,101,220,0.28)] flex-shrink-0">
                    {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-[#122056] tracking-tight">{{ $pasien->nama_pasien }}</h1>
                        <span class="font-mono text-xs font-bold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2.5 py-0.5 rounded-[8px]">
                            RM: {{ $pasien->no_rekam_medis }}
                        </span>
                        <span class="font-mono text-xs font-semibold text-[#122056] bg-[#FAFAFD] border border-[#E4E7F4] px-2 py-0.5 rounded-[8px]">
                            Antrean A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-1.5 text-xs text-[#69718B]">
                        <span>
                            @if ($umur)
                                <strong class="text-[#20294A] font-semibold">{{ $umur }} tahun</strong>
                                @if ($pasien->tanggal_lahir)
                                    ({{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->format('d/m/Y') }})
                                @endif
                            @else
                                -
                            @endif
                        </span>
                        <span class="text-[#CBD1EA]">·</span>
                        <span class="text-[#20294A] font-medium">{{ $pasien->jenis_kelamin ?? '-' }}</span>
                        <span class="text-[#CBD1EA]">·</span>
                        <span>Tgl Kunjungan: <strong class="text-[#20294A]">{{ \Carbon\Carbon::parse($antrean->tanggal_kunjungan)->format('d F Y') }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Doctor Profile Badge & Status -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="p-2.5 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] text-xs">
                    <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">Dokter Pemeriksa</div>
                    <div class="font-bold text-[#122056]">{{ $user->nama_lengkap }}</div>
                </div>
                <a href="{{ route('dokter.dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-[#FFFFFF] hover:bg-[#FAFAFD] border border-[#E4E7F4] hover:border-[#CBD1EA] rounded-[14px] text-xs font-semibold text-[#122056] transition-all shadow-xs cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Dashboard Antrean</span>
                </a>
            </div>
        </div>
    </div>

    <!-- SECTION 1: TTV & CATATAN PERAWAT -->
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-[#E4E7F4]">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-[12px] bg-[#EEEEFD] text-[#5865DC] flex items-center justify-center font-bold text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-[#122056]">TTV &amp; Catatan Perawat</h2>
                    <p class="text-xs text-[#69718B]">Pemeriksaan tanda-tanda vital awal dan observasi klinis Nurse Station</p>
                </div>
            </div>

            @if ($pemeriksaanAwal)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                        Terverifikasi oleh Perawat
                    </span>
                    @if ($pemeriksaanAwal->perawat)
                        <span class="text-xs text-[#69718B] hidden sm:inline">
                            ({{ $pemeriksaanAwal->perawat->nama_lengkap }} · {{ $pemeriksaanAwal->created_at ? $pemeriksaanAwal->created_at->timezone('Asia/Jakarta')->format('H:i') . ' WIB' : '' }})
                        </span>
                    @endif
                </div>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
                    Belum Ada Data TTV
                </span>
            @endif
        </div>

        @if ($pemeriksaanAwal)
            <!-- Vital Signs Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 pt-1">
                <!-- Tekanan Darah -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between">
                    <span class="text-[11px] font-semibold text-[#69718B]">Tekanan Darah</span>
                    <div class="mt-1">
                        <span class="text-base font-bold text-[#122056]">{{ $pemeriksaanAwal->tekanan_darah ?? '-' }}</span>
                        <span class="text-[10px] text-[#69718B] block">mmHg</span>
                    </div>
                </div>

                <!-- Denyut Nadi -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between">
                    <span class="text-[11px] font-semibold text-[#69718B]">Denyut Nadi</span>
                    <div class="mt-1">
                        <span class="text-base font-bold text-[#122056]">{{ $pemeriksaanAwal->nadi ?? '-' }}</span>
                        <span class="text-[10px] text-[#69718B] block">x / menit</span>
                    </div>
                </div>

                <!-- Suhu Tubuh -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between {{ ($pemeriksaanAwal->suhu_tubuh && $pemeriksaanAwal->suhu_tubuh > 37.5) ? 'border-[#FCA5A5] bg-[#FEF2F2]/60' : '' }}">
                    <span class="text-[11px] font-semibold text-[#69718B]">Suhu Tubuh</span>
                    <div class="mt-1 flex items-baseline gap-1">
                        <span class="text-base font-bold {{ ($pemeriksaanAwal->suhu_tubuh && $pemeriksaanAwal->suhu_tubuh > 37.5) ? 'text-[#DC2626]' : 'text-[#122056]' }}">
                            {{ $pemeriksaanAwal->suhu_tubuh ?? '-' }}
                        </span>
                        <span class="text-[10px] text-[#69718B]">°C</span>
                    </div>
                </div>

                <!-- Laju Pernapasan -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between">
                    <span class="text-[11px] font-semibold text-[#69718B]">Pernapasan (RR)</span>
                    <div class="mt-1">
                        <span class="text-base font-bold text-[#122056]">{{ $pemeriksaanAwal->laju_pernapasan ?? '-' }}</span>
                        <span class="text-[10px] text-[#69718B] block">x / menit</span>
                    </div>
                </div>

                <!-- Saturasi Oksigen -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between">
                    <span class="text-[11px] font-semibold text-[#69718B]">Saturasi SpO2</span>
                    <div class="mt-1">
                        <span class="text-base font-bold text-[#122056]">{{ $pemeriksaanAwal->saturasi_oksigen ? $pemeriksaanAwal->saturasi_oksigen . '%' : '-' }}</span>
                        <span class="text-[10px] text-[#69718B] block">arteri</span>
                    </div>
                </div>

                <!-- Berat Badan -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between">
                    <span class="text-[11px] font-semibold text-[#69718B]">Berat Badan</span>
                    <div class="mt-1">
                        <span class="text-base font-bold text-[#122056]">{{ $pemeriksaanAwal->berat_badan ?? '-' }}</span>
                        <span class="text-[10px] text-[#69718B] block">kg</span>
                    </div>
                </div>

                <!-- Tinggi Badan -->
                <div class="p-3 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col justify-between">
                    <span class="text-[11px] font-semibold text-[#69718B]">Tinggi Badan</span>
                    <div class="mt-1">
                        <span class="text-base font-bold text-[#122056]">{{ $pemeriksaanAwal->tinggi_badan ?? '-' }}</span>
                        <span class="text-[10px] text-[#69718B] block">cm</span>
                    </div>
                </div>
            </div>

            <!-- Catatan Awal Perawat -->
            @if ($pemeriksaanAwal->catatan)
                <div class="p-3.5 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] text-xs">
                    <span class="font-bold text-[#122056] block mb-1">Catatan Awal Perawat:</span>
                    <p class="text-[#20294A] leading-relaxed">{{ $pemeriksaanAwal->catatan }}</p>
                </div>
            @endif
        @else
            <div class="py-6 text-center text-xs text-[#69718B] bg-[#FAFAFD] rounded-[18px] border border-dashed border-[#CBD1EA]">
                Pasien ini belum memiliki catatan pemeriksaan awal TTV dari Nurse Station.
            </div>
        @endif
    </div>

    <!-- SECTION 2: REKAM WAWANCARA KLINIS (AUDIO INTERVIEW) -->
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-7 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#E4E7F4]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[14px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-[#122056]">Rekam Wawancara Klinis</h2>
                    <p class="text-xs text-[#69718B]">Rekam dialog anamnesis dan wawancara klinis dokter-pasien secara langsung.</p>
                </div>
            </div>

            <!-- Active Status Badge -->
            <div id="recordingStatusBadge" class="flex items-center gap-2">
                <span id="badgeRecordingText" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]">
                    <span class="w-2 h-2 rounded-full bg-[#5865DC]"></span>
                    Mikrofon Siap
                </span>
            </div>
        </div>

        <!-- Recording Console UI -->
        <div class="p-6 rounded-[22px] bg-[#FAFAFD] border border-[#E4E7F4] flex flex-col items-center justify-center text-center space-y-5">

            <!-- Real Audio Waveform Canvas & Icon -->
            <div class="relative w-full max-w-md h-20 bg-[#FFFFFF] rounded-[18px] border border-[#E4E7F4] flex items-center justify-center overflow-hidden shadow-inner">
                <!-- Live Web Audio API Visualizer Canvas -->
                <canvas id="waveformCanvas" width="440" height="80" class="w-full h-full"></canvas>

                <!-- Idle Microphone Watermark Icon -->
                <div id="visualizerPlaceholder" class="absolute inset-0 flex items-center justify-center text-[#CBD1EA] pointer-events-none">
                    <div class="flex items-center gap-2 text-xs font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#5865DC]/60 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                        <span>Visualizer suara aktif saat rekaman dimulai</span>
                    </div>
                </div>
            </div>

            <!-- Digital Recording Timer Display -->
            <div class="space-y-1">
                <div id="timerDisplay" class="font-mono text-3xl sm:text-4xl font-bold tracking-tight text-[#122056]">
                    00:00
                </div>
                <div id="timerSubtitle" class="text-xs text-[#69718B]">
                    Durasi Wawancara Klinis
                </div>
            </div>

            <!-- Recording Action Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
                <!-- Tombol Mulai Rekam -->
                <button type="button" id="btnMulaiRekam" onclick="startClinicalRecording()"
                        class="px-6 py-3 rounded-[16px] text-xs sm:text-sm font-semibold bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] transition-all flex items-center gap-2.5 cursor-pointer shadow-[0_4px_16px_rgba(88,101,220,0.28)] hover:shadow-[0_6px_20px_rgba(88,101,220,0.36)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span>Mulai Rekam</span>
                </button>

                <!-- Tombol Stop Rekam (Hidden by default) -->
                <button type="button" id="btnStopRekam" onclick="stopClinicalRecording()"
                        class="hidden px-6 py-3 rounded-[16px] text-xs sm:text-sm font-semibold bg-[#DC2626] hover:bg-[#B91C1C] active:bg-[#991B1B] text-[#FFFFFF] transition-all items-center gap-2.5 cursor-pointer shadow-[0_4px_16px_rgba(220,38,38,0.3)] animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5" viewBox="0 0 20 20" fill="currentColor">
                        <rect x="5" y="5" width="10" height="10" rx="2" />
                    </svg>
                    <span>Stop Rekam</span>
                </button>

                <!-- Tombol Rekam Ulang (Hidden by default) -->
                <button type="button" id="btnRekamUlang" onclick="restartRecordingSession()"
                        class="hidden px-4 py-2.5 rounded-[14px] text-xs font-semibold bg-[#FFFFFF] hover:bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA] transition-all items-center gap-2 cursor-pointer shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Rekam Ulang</span>
                </button>
            </div>

            <!-- Audio Player Preview Container -->
            <div id="audioPlayerContainer" class="{{ $rekamanAudio ? '' : 'hidden' }} w-full max-w-md pt-2 space-y-2">
                <div class="text-[11px] font-semibold text-[#69718B] flex items-center justify-between">
                    <span>Pratinjau Hasil Rekaman Audio</span>
                    @if ($rekamanAudio)
                        <span class="text-[#059669] font-medium">✓ Audio Tersimpan di Server</span>
                    @endif
                </div>
                <audio id="audioPreview" controls class="w-full h-10 rounded-[12px] bg-[#FFFFFF] border border-[#CBD1EA]">
                    @if ($rekamanAudio)
                        <source src="{{ route('dokter.rekam-medis.stream-audio', $antrean->id_antrean) }}" type="audio/webm">
                    @endif
                    Browser Anda tidak mendukung elemen pemutar audio.
                </audio>
            </div>

            <!-- Dynamic Notification / Status Alert within Console -->
            <div id="recordFeedbackAlert" class="hidden w-full max-w-lg p-3.5 rounded-[16px] text-xs flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-2.5">
                    <span id="recordFeedbackIcon" class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[11px] flex-shrink-0"></span>
                    <span id="recordFeedbackMessage" class="font-medium leading-relaxed"></span>
                </div>
                <button type="button" onclick="document.getElementById('recordFeedbackAlert').classList.add('hidden')" class="p-1 text-[#69718B] hover:text-[#122056] text-xs cursor-pointer">✕</button>
            </div>

        </div>

        <!-- Section 2 Footer Navigation & Safe Retention Notice -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-[#E4E7F4]">
            <div class="text-xs text-[#69718B] flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#5865DC] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Rekaman audio tersimpan di repositori privat terlindungi untuk dokumentasi rekam medis.</span>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('dokter.dashboard') }}"
                   class="px-5 py-2.5 rounded-[14px] text-xs font-semibold bg-[#FAFAFD] hover:bg-[#EEEEFD] text-[#122056] border border-[#CBD1EA] transition-all flex items-center gap-2 cursor-pointer shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Dashboard Dokter</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Konfigurasi Endpoint & State Global
    const uploadAudioUrl = "{{ route('dokter.rekam-medis.upload-audio', $antrean->id_antrean) }}";
    const csrfToken = "{{ csrf_token() }}";

    let mediaRecorder = null;
    let audioStream = null;
    let recordedChunks = [];
    let recordStartTime = 0;
    let timerInterval = null;
    let isRecording = false;

    // Web Audio API Visualizer State
    let audioCtx = null;
    let analyserNode = null;
    let audioSource = null;
    let animFrameId = null;

    /**
     * Memulai perekaman audio wawancara klinis menggunakan Web Media API
     */
    async function startClinicalRecording() {
        if (isRecording) return;

        const btnMulai = document.getElementById('btnMulaiRekam');
        const btnStop = document.getElementById('btnStopRekam');
        const btnUlang = document.getElementById('btnRekamUlang');
        const statusBadge = document.getElementById('badgeRecordingText');
        const timerSub = document.getElementById('timerSubtitle');
        const placeholder = document.getElementById('visualizerPlaceholder');

        // 1. Validasi Dukungan Browser Web Media API
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showFeedback('error', 'Browser Anda tidak mendukung Web Audio / MediaRecorder API. Gunakan browser modern (Chrome, Edge, Firefox).');
            return;
        }

        // 2. Validasi Konteks Keamanan (HTTPS / Localhost)
        if (window.isSecureContext === false && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            showFeedback('warning', 'Akses mikrofon browser memerlukan koneksi aman (HTTPS) atau origin localhost.');
            return;
        }

        try {
            // Update status UI sebelum prompt izin muncul
            statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]';
            statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#F59E0B] animate-pulse"></span> Menunggu Izin Mikrofon...';
            btnMulai.disabled = true;

            // 3. Permintaan Izin Mikrofon Riil (Browser Native Prompt)
            audioStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                }
            });

            // 4. Pilih MIME Type audio yang didukung browser
            const supportedTypes = [
                'audio/webm;codecs=opus',
                'audio/webm',
                'audio/ogg;codecs=opus',
                'audio/mp4',
                'audio/wav'
            ];
            let selectedMime = '';
            for (const type of supportedTypes) {
                if (MediaRecorder.isTypeSupported(type)) {
                    selectedMime = type;
                    break;
                }
            }

            mediaRecorder = new MediaRecorder(audioStream, selectedMime ? { mimeType: selectedMime } : {});
            recordedChunks = [];

            mediaRecorder.ondataavailable = function(e) {
                if (e.data && e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = async function() {
                handleRecordingFinished(selectedMime);
            };

            // 5. Mulai rekaman dan aktifkan timer
            mediaRecorder.start(250); // potong chunk tiap 250ms
            isRecording = true;
            recordStartTime = Date.now();

            timerInterval = setInterval(updateTimerDisplay, 100);

            // Inisialisasi visualizer audio riil
            startAudioVisualizer(audioStream);

            // Perbarui tampilan UI
            btnMulai.classList.add('hidden');
            btnMulai.disabled = false;
            btnStop.classList.remove('hidden');
            btnStop.classList.add('flex');
            btnUlang.classList.add('hidden');

            statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FEF2F2] text-[#DC2626] border border-[#FECACA] animate-pulse';
            statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#DC2626]"></span> Sedang Merekam Wawancara Klinis';

            timerSub.textContent = 'Merekam suara dokter & pasien secara langsung...';
            if (placeholder) placeholder.classList.add('hidden');

            showFeedback('info', 'Perekaman aktif. Silakan lakukan anamnesis klinis dengan pasien.');

        } catch (err) {
            btnMulai.disabled = false;
            statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]';
            statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#5865DC]"></span> Mikrofon Siap';

            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                showFeedback('error', 'Akses mikrofon ditolak. Izinkan akses mikrofon melalui pengaturan izin situs browser (ikon gembok di bilah alamat), lalu coba kembali.');
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                showFeedback('error', 'Perangkat mikrofon tidak ditemukan. Pastikan mikrofon terpasang pada komputer Anda.');
            } else {
                showFeedback('error', 'Gagal mengakses mikrofon: ' + err.message);
            }
        }
    }

    /**
     * Menghentikan perekaman audio dan melepaskan stream mikrofon
     */
    function stopClinicalRecording() {
        if (!mediaRecorder || !isRecording) return;

        const btnStop = document.getElementById('btnStopRekam');
        const statusBadge = document.getElementById('badgeRecordingText');
        const timerSub = document.getElementById('timerSubtitle');

        btnStop.disabled = true;
        isRecording = false;

        // Hentikan timer
        clearInterval(timerInterval);

        // Hentikan visualizer
        stopAudioVisualizer();

        statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]';
        statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#F59E0B] animate-spin"></span> Menyimpan Rekaman...';

        timerSub.textContent = 'Memfinalisasi berkas audio dan mengunggah ke server...';

        // Hentikan MediaRecorder
        mediaRecorder.stop();

        // Lepas seluruh track audio mikrofon agar lampu indikator mic mati
        if (audioStream) {
            audioStream.getTracks().forEach(track => track.stop());
            audioStream = null;
        }
    }

    /**
     * Memproses berkas audio setelah MediaRecorder selesai berhenti,
     * lalu mengunggah ke Laravel backend via FormData
     */
    async function handleRecordingFinished(mimeType) {
        const btnMulai = document.getElementById('btnMulaiRekam');
        const btnStop = document.getElementById('btnStopRekam');
        const btnUlang = document.getElementById('btnRekamUlang');
        const audioPlayerCont = document.getElementById('audioPlayerContainer');
        const audioPreview = document.getElementById('audioPreview');
        const statusBadge = document.getElementById('badgeRecordingText');
        const timerSub = document.getElementById('timerSubtitle');

        btnStop.classList.add('hidden');
        btnStop.disabled = false;
        btnMulai.classList.remove('hidden');
        btnUlang.classList.remove('hidden');
        btnUlang.classList.add('flex');

        if (recordedChunks.length === 0) {
            showFeedback('error', 'Tidak ada data audio yang tertangkap. Silakan periksa volume mikrofon Anda.');
            return;
        }

        // Susun Blob audio riil
        const audioBlob = new Blob(recordedChunks, { type: mimeType || 'audio/webm' });
        const audioUrl = URL.createObjectURL(audioBlob);

        // Pasang preview audio player
        audioPreview.src = audioUrl;
        audioPlayerCont.classList.remove('hidden');

        // Unggah ke Laravel backend
        showFeedback('info', 'Mengunggah rekaman audio ke server...');

        try {
            const formData = new FormData();
            formData.append('audio', audioBlob, 'wawancara_klinis.webm');

            const response = await fetch(uploadAudioUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]';
                statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#10B981]"></span> Rekaman Tersimpan';
                timerSub.textContent = 'Audio wawancara klinis berhasil disimpan di server.';

                showFeedback('success', result.message || 'Rekaman wawancara klinis berhasil disimpan di server.');
            } else {
                statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FEF2F2] text-[#DC2626] border border-[#FECACA]';
                statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#DC2626]"></span> Gagal Menyimpan';
                showFeedback('error', result.message || 'Gagal menyimpan rekaman ke server.');
            }
        } catch (uploadErr) {
            showFeedback('error', 'Terjadi kendala jaringan saat mengunggah audio: ' + uploadErr.message);
        }
    }

    /**
     * Mereset status perekaman agar dokter dapat memulai sesi rekaman baru
     */
    function restartRecordingSession() {
        if (isRecording) stopClinicalRecording();

        clearInterval(timerInterval);
        stopAudioVisualizer();

        document.getElementById('timerDisplay').textContent = '00:00';
        document.getElementById('timerSubtitle').textContent = 'Durasi Wawancara Klinis';
        document.getElementById('btnRekamUlang').classList.add('hidden');
        document.getElementById('btnMulaiRekam').classList.remove('hidden');
        document.getElementById('btnStopRekam').classList.add('hidden');

        const statusBadge = document.getElementById('badgeRecordingText');
        statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]';
        statusBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#5865DC]"></span> Mikrofon Siap';

        const placeholder = document.getElementById('visualizerPlaceholder');
        if (placeholder) placeholder.classList.remove('hidden');

        showFeedback('info', 'Sesi rekaman direset. Anda dapat menekan Mulai Rekam kembali.');
    }

    /**
     * Memperbarui angka timer secara presisi berdasarkan selisih waktu Date.now()
     */
    function updateTimerDisplay() {
        if (!isRecording) return;
        const elapsed = Math.floor((Date.now() - recordStartTime) / 1000);
        const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
        const secs = String(elapsed % 60).padStart(2, '0');
        document.getElementById('timerDisplay').textContent = `${mins}:${secs}`;
    }

    /**
     * Visualizer audio riil berbasis Web Audio API AnalyserNode
     */
    function startAudioVisualizer(stream) {
        const canvas = document.getElementById('waveformCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyserNode = audioCtx.createAnalyser();
            analyserNode.fftSize = 64;

            audioSource = audioCtx.createMediaStreamSource(stream);
            audioSource.connect(analyserNode);

            const bufferLen = analyserNode.frequencyBinCount;
            const dataArr = new Uint8Array(bufferLen);

            function renderWaveform() {
                if (!isRecording) return;
                animFrameId = requestAnimationFrame(renderWaveform);

                analyserNode.getByteFrequencyData(dataArr);
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const barWidth = (canvas.width / bufferLen) * 1.5;
                let x = 4;

                for (let i = 0; i < bufferLen; i++) {
                    const normHeight = (dataArr[i] / 255);
                    const barHeight = Math.max(4, normHeight * (canvas.height - 10));

                    // Palet Royal Indigo MedScribe AI #5865DC
                    ctx.fillStyle = normHeight > 0.6 ? '#5865DC' : '#818CF8';
                    const y = (canvas.height - barHeight) / 2;

                    // Gambar rounded bar
                    if (ctx.roundRect) {
                        ctx.beginPath();
                        ctx.roundRect(x, y, Math.max(3, barWidth - 3), barHeight, 2);
                        ctx.fill();
                    } else {
                        ctx.fillRect(x, y, Math.max(3, barWidth - 3), barHeight);
                    }

                    x += barWidth + 3;
                }
            }

            renderWaveform();
        } catch (e) {
            console.warn('AudioContext visualizer not supported or blocked:', e);
        }
    }

    function stopAudioVisualizer() {
        if (animFrameId) cancelAnimationFrame(animFrameId);
        if (audioCtx && audioCtx.state !== 'closed') {
            audioCtx.close().catch(() => {});
        }
        const canvas = document.getElementById('waveformCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    /**
     * Menampilkan pesan umpan balik di dalam konsol
     */
    function showFeedback(type, message) {
        const alertBox = document.getElementById('recordFeedbackAlert');
        const iconEl = document.getElementById('recordFeedbackIcon');
        const msgEl = document.getElementById('recordFeedbackMessage');

        if (!alertBox || !iconEl || !msgEl) return;

        alertBox.classList.remove('hidden', 'bg-[#ECFDF5]', 'bg-[#FEF2F2]', 'bg-[#FFFBEB]', 'bg-[#EEEEFD]', 'border-[#A7F3D0]', 'border-[#FECACA]', 'border-[#FDE68A]', 'border-[#CBD1EA]', 'text-[#059669]', 'text-[#DC2626]', 'text-[#D97706]', 'text-[#122056]');

        if (type === 'success') {
            alertBox.classList.add('bg-[#ECFDF5]', 'border', 'border-[#A7F3D0]', 'text-[#059669]');
            iconEl.className = 'w-5 h-5 rounded-full bg-[#10B981] text-[#FFFFFF] flex items-center justify-center font-bold text-[11px]';
            iconEl.textContent = '✓';
        } else if (type === 'error') {
            alertBox.classList.add('bg-[#FEF2F2]', 'border', 'border-[#FECACA]', 'text-[#DC2626]');
            iconEl.className = 'w-5 h-5 rounded-full bg-[#DC2626] text-[#FFFFFF] flex items-center justify-center font-bold text-[11px]';
            iconEl.textContent = '✕';
        } else if (type === 'warning') {
            alertBox.classList.add('bg-[#FFFBEB]', 'border', 'border-[#FDE68A]', 'text-[#D97706]');
            iconEl.className = 'w-5 h-5 rounded-full bg-[#F59E0B] text-[#FFFFFF] flex items-center justify-center font-bold text-[11px]';
            iconEl.textContent = '!';
        } else {
            alertBox.classList.add('bg-[#EEEEFD]', 'border', 'border-[#CBD1EA]', 'text-[#122056]');
            iconEl.className = 'w-5 h-5 rounded-full bg-[#5865DC] text-[#FFFFFF] flex items-center justify-center font-bold text-[11px]';
            iconEl.textContent = 'ℹ';
        }

        msgEl.textContent = message;
    }

    // Safety: Cegah dokter meninggalkan halaman secara tidak sengaja saat rekaman masih berlangsung
    window.addEventListener('beforeunload', function(e) {
        if (isRecording) {
            e.preventDefault();
            e.returnValue = 'Rekaman wawancara klinis sedang berlangsung. Jika Anda meninggalkan halaman ini, rekaman akan hilang.';
            return e.returnValue;
        }
    });
</script>
@endsection

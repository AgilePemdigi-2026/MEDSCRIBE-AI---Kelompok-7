@extends('layouts.app')

@section('title', 'Nurse Station - Input Pemeriksaan Awal & TTV - MedScribe AI')

@section('content')
<div class="space-y-6">

    <!-- Top Status & Breadcrumb (Medical Blueprint Header) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-[#FFFFFF] p-4 sm:px-6 rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)]">
        <div class="flex items-center gap-2.5 text-xs font-bold tracking-wider uppercase">
            <span class="text-[#122056]">PEMERIKSAAN AWAL</span>
            <span class="text-[#CBD1EA]">·</span>
            @if (session('saved_pemeriksaan'))
                <span class="text-[#059669] flex items-center gap-1.5 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                    Pemeriksaan Berhasil Disimpan
                </span>
            @elseif ($pasienTerpilih && $pasienTerpilih->status === 'Siap Periksa')
                <span class="text-[#5865DC] flex items-center gap-1.5 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#5865DC] animate-pulse"></span>
                    TTV Terverifikasi
                </span>
            @else
                <span class="text-[#D97706] flex items-center gap-1.5 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#F59E0B]"></span>
                    Menunggu Input TTV
                </span>
            @endif
        </div>
        <div class="text-xs font-mono text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-3 py-1 rounded-full w-fit">
            /pemeriksaan-awal/{{ $idKunjunganCode ?? 'RSUA-20260919-001' }}
        </div>
    </div>

    <!-- Alert Kesalahan Validasi -->
    @if ($errors->any())
        <div class="p-4 rounded-[18px] bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] text-xs shadow-[0_4px_18px_rgba(18,32,86,0.04)] space-y-1.5">
            <div class="font-bold flex items-center gap-1.5 text-[#DC2626]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Mohon periksa kembali isian formulir:
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-1 text-[#DC2626]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tab Navigation: Antrean vs Daftar Pasien + Action + Input Pasien -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-[#FFFFFF] p-2 rounded-[20px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.04)]">
        <div class="flex items-center gap-1.5">
            <a href="{{ route('perawat.dashboard', ['tab' => 'antrean']) }}"
               class="px-4 py-2 rounded-[14px] text-xs font-semibold transition-all {{ $activeTab === 'antrean' ? 'bg-[#5865DC] text-[#FFFFFF] shadow-[0_2px_8px_rgba(88,101,220,0.28)]' : 'text-[#69718B] hover:text-[#122056] hover:bg-[#FAFAFD]' }}">
                Antrean Aktif
            </a>
            <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}"
               class="px-4 py-2 rounded-[14px] text-xs font-semibold transition-all {{ $activeTab === 'pasien' ? 'bg-[#5865DC] text-[#FFFFFF] shadow-[0_2px_8px_rgba(88,101,220,0.28)]' : 'text-[#69718B] hover:text-[#122056] hover:bg-[#FAFAFD]' }}">
                Daftar Pasien
            </a>
        </div>
        
        <button type="button" onclick="document.getElementById('modalInputPasien').classList.remove('hidden')"
                class="px-4 py-2 rounded-[14px] text-xs font-semibold bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] transition-all flex items-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.25)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Input Pasien</span>
        </button>
    </div>

    @if ($activeTab === 'antrean')
    {{-- ========== TAB ANTREAN ========== --}}
    <!-- Layout 2 Kolom (4 : 8) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Kolom Kiri: Daftar Antrean Pasien (4 cols) -->
        <div class="lg:col-span-4 bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 flex flex-col justify-between">
            <div>
                <!-- Header Antrean -->
                <div class="flex items-center justify-between mb-3.5">
                    <h2 class="text-base font-bold text-[#122056]">Daftar Antrean</h2>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA] text-xs font-semibold">
                        {{ $daftarAntrean->count() }} Pasien
                    </span>
                </div>

                <!-- Form Pencarian Pasien -->
                <form method="GET" action="{{ route('perawat.dashboard') }}" class="mb-4">
                    <input type="hidden" name="tab" value="antrean">
                    @if (request('antrean_id'))
                        <input type="hidden" name="antrean_id" value="{{ request('antrean_id') }}">
                    @endif
                    <label for="cariPasien" class="block text-xs font-semibold text-[#122056] mb-1.5">Cari pasien</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="cariPasien"
                            name="cari"
                            value="{{ request('cari') }}"
                            placeholder="Nama atau No. RM"
                            class="w-full pl-9 pr-8 py-2 text-xs bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all text-[#20294A] placeholder-[#69718B]/70"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        @if (request('cari'))
                            <a href="{{ route('perawat.dashboard', ['tab' => 'antrean', 'antrean_id' => request('antrean_id')]) }}" class="absolute right-2.5 top-2 text-xs text-[#69718B] hover:text-[#122056]">✕</a>
                        @endif
                    </div>
                </form>

                <!-- Daftar Pasien Antrean -->
                <div class="space-y-2.5 max-h-[560px] overflow-y-auto pr-1">
                    @forelse ($daftarAntrean as $antrean)
                        @php
                            $isSelected = $pasienTerpilih && ($pasienTerpilih->id_antrean === $antrean->id_antrean);
                            
                            $usia = '34 th';
                            if ($antrean->pasien && $antrean->pasien->umur) {
                                $usia = $antrean->pasien->umur . ' th';
                            } elseif ($antrean->pasien && $antrean->pasien->tanggal_lahir) {
                                $usia = \Carbon\Carbon::parse($antrean->pasien->tanggal_lahir)->age . ' th';
                            }
                            $jk = ($antrean->pasien && $antrean->pasien->jenis_kelamin === 'Perempuan') ? 'P' : 'L';
                            
                            $jam = sprintf('%02d:%02d WIB', 8 + intdiv(30 + ($antrean->id_antrean - 1) * 5, 60), (30 + ($antrean->id_antrean - 1) * 5) % 60);
                        @endphp

                        <a
                            href="{{ route('perawat.dashboard', ['antrean_id' => $antrean->id_antrean, 'cari' => request('cari'), 'tab' => 'antrean']) }}"
                            class="block p-3.5 rounded-[18px] border transition-all text-left {{ $isSelected ? 'border-[#5865DC] bg-[#EEEEFD]/40 shadow-[0_4px_14px_rgba(88,101,220,0.12)] border-l-4 border-l-[#5865DC]' : 'border-[#E4E7F4] bg-[#FFFFFF] hover:border-[#CBD1EA] hover:bg-[#FAFAFD] hover:-translate-y-0.5' }}"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-[#122056] bg-[#EEEEFD] border border-[#CBD1EA] px-2 py-0.5 rounded-[6px]">
                                    A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="text-[11px] text-[#69718B] font-medium">{{ $jam }}</span>
                            </div>

                            <div class="font-bold text-[#122056] text-sm mt-1.5">
                                {{ $antrean->pasien->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}
                            </div>

                            <div class="text-xs text-[#69718B] mt-0.5">
                                {{ $usia }} · {{ $jk }}
                            </div>

                            <div class="mt-2.5">
                                @if ($antrean->status === 'Siap Periksa')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                                        SIAP DIPANGGIL DOKTER
                                    </span>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
                                        MENUNGGU TTV
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-[#FAFAFD] text-[#69718B] border border-[#CBD1EA]">
                                        {{ strtoupper($antrean->status) }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="py-8 text-center text-xs text-[#69718B]">
                            Tidak ada pasien yang cocok dengan pencarian.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Footer Antrean -->
            <div class="mt-4 pt-3.5 border-t border-[#E4E7F4] text-[11px] text-[#69718B] text-center">
                Antrean diperbarui otomatis setelah data disimpan.
            </div>
        </div>

        <!-- Kolom Kanan: Formulir Pemeriksaan Awal & TTV (8 cols) -->
        <div class="lg:col-span-8 bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-6 sm:p-7 space-y-6">

            @if ($pasienTerpilih)
                <!-- Header Formulir -->
                <div class="flex items-center gap-3 text-[#122056]">
                    <div class="w-9 h-9 rounded-[12px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-[#122056]">Formulir Pemeriksaan Awal & TTV</h1>
                        <p class="text-xs text-[#69718B]">Pengukuran Tanda-Tanda Vital & Anamnesis Awal Perawat</p>
                    </div>
                </div>

                <!-- Banner Identitas Pasien Terpilih -->
                @php
                    $usiaLengkap = '34 tahun';
                    if ($pasienTerpilih->pasien && $pasienTerpilih->pasien->umur) {
                        $usiaLengkap = $pasienTerpilih->pasien->umur . ' tahun';
                    } elseif ($pasienTerpilih->pasien && $pasienTerpilih->pasien->tanggal_lahir) {
                        $usiaLengkap = \Carbon\Carbon::parse($pasienTerpilih->pasien->tanggal_lahir)->age . ' tahun';
                    }
                    $jkLengkap = ($pasienTerpilih->pasien && $pasienTerpilih->pasien->jenis_kelamin) ? $pasienTerpilih->pasien->jenis_kelamin : 'Laki-laki';
                @endphp
                <div class="p-4 sm:p-5 rounded-[20px] bg-gradient-to-r from-[#EEEEFD]/60 to-[#FAFAFD] border border-[#CBD1EA] flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                    <div>
                        <div class="text-lg font-bold text-[#122056]">
                            {{ $pasienTerpilih->pasien->nama_pasien ?? 'Pasien' }}
                        </div>
                        <div class="text-xs text-[#69718B] mt-0.5">
                            {{ $usiaLengkap }} · {{ $jkLengkap }}
                        </div>
                        <div class="text-xs text-[#69718B] mt-1 font-mono">
                            No. RM: <strong class="text-[#5865DC] font-semibold">{{ $pasienTerpilih->pasien->no_rekam_medis ?? '00-12-345' }}</strong>
                            <span class="mx-2 text-[#CBD1EA]">|</span>
                            Kunjungan: <strong class="text-[#122056] font-semibold font-mono">{{ $idKunjunganCode }}</strong>
                        </div>
                    </div>

                    <div>
                        @if ($pasienTerpilih->status === 'Siap Periksa')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                                SIAP DIPANGGIL DOKTER
                            </span>
                        @elseif ($pasienTerpilih->status === 'Menunggu TTV')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
                                MENUNGGU TTV
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-[#FAFAFD] text-[#69718B] border border-[#CBD1EA]">
                                {{ strtoupper($pasienTerpilih->status) }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Form Input TTV & Pemeriksaan Awal -->
                <form method="POST" action="{{ route('perawat.pemeriksaan.store') }}" id="formTTV" class="space-y-5">
                    @csrf
                    <input type="hidden" name="id_antrean" value="{{ $pasienTerpilih->id_antrean }}">
                    <input type="hidden" name="cari_filter" value="{{ request('cari') }}">

                    <div>
                        <div class="mb-3">
                            <h2 class="text-sm font-bold text-[#122056]">Tanda-tanda vital</h2>
                            <p class="text-xs text-[#69718B] mt-0.5">Semua field bertanda * wajib diisi. Masukkan angka sesuai satuan standar klinis.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <!-- 1. Tekanan Darah -->
                            <div>
                                <label for="tekanan_darah" class="block text-xs font-semibold text-[#122056] mb-1">
                                    Tekanan Darah <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="tekanan_darah"
                                        name="tekanan_darah"
                                        placeholder="120/80"
                                        value="{{ old('tekanan_darah', $pemeriksaanAwal->tekanan_darah ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-14 py-2.5 bg-[#FAFAFD] border @error('tekanan_darah') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">
                                        mmHg
                                    </span>
                                </div>
                                @error('tekanan_darah')
                                    <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 2. Denyut Nadi -->
                            <div>
                                <label for="nadi" class="block text-xs font-semibold text-[#122056] mb-1">
                                    Denyut Nadi <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="nadi"
                                        name="nadi"
                                        placeholder="78"
                                        min="30"
                                        max="250"
                                        value="{{ old('nadi', $pemeriksaanAwal->nadi ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-16 py-2.5 bg-[#FAFAFD] border @error('nadi') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">
                                        x/menit
                                    </span>
                                </div>
                                @error('nadi')
                                    <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 3. Suhu Tubuh -->
                            <div>
                                <label for="suhu_tubuh" class="block text-xs font-semibold text-[#122056] mb-1">
                                    Suhu Tubuh <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        step="0.1"
                                        id="suhu_tubuh"
                                        name="suhu_tubuh"
                                        placeholder="36.5"
                                        min="30"
                                        max="45"
                                        value="{{ old('suhu_tubuh', $pemeriksaanAwal->suhu_tubuh ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border @error('suhu_tubuh') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">
                                        °C
                                    </span>
                                </div>
                                @error('suhu_tubuh')
                                    <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                                @enderror

                                <!-- Real-time Alert Warning Suhu Di Atas Normal (>37.5°C) -->
                                @php
                                    $suhuCurrent = (float) old('suhu_tubuh', $pemeriksaanAwal->suhu_tubuh ?? 0);
                                @endphp
                                <p id="suhuWarning" class="text-xs text-[#DC2626] font-semibold mt-1.5 flex items-center gap-1.5 {{ $suhuCurrent > 37.5 ? '' : 'hidden' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#DC2626] flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Peringatan klinis: Suhu di atas rentang normal (&gt;37.5°C)</span>
                                </p>
                            </div>

                            <!-- 4. Laju Pernapasan -->
                            <div>
                                <label for="laju_pernapasan" class="block text-xs font-semibold text-[#122056] mb-1">
                                    Laju Pernapasan <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="laju_pernapasan"
                                        name="laju_pernapasan"
                                        placeholder="18"
                                        min="5"
                                        max="60"
                                        value="{{ old('laju_pernapasan', $pemeriksaanAwal->laju_pernapasan ?? 18) }}"
                                        required
                                        class="w-full pl-3.5 pr-16 py-2.5 bg-[#FAFAFD] border @error('laju_pernapasan') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">
                                        x/menit
                                    </span>
                                </div>
                                @error('laju_pernapasan')
                                    <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 5. Berat Badan -->
                            <div>
                                <label for="berat_badan" class="block text-xs font-semibold text-[#122056] mb-1">
                                    Berat Badan <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        step="0.1"
                                        id="berat_badan"
                                        name="berat_badan"
                                        placeholder="68"
                                        min="1"
                                        max="300"
                                        value="{{ old('berat_badan', $pemeriksaanAwal->berat_badan ?? 68) }}"
                                        required
                                        class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border @error('berat_badan') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">
                                        kg
                                    </span>
                                </div>
                                @error('berat_badan')
                                    <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 6. Tinggi Badan -->
                            <div>
                                <label for="tinggi_badan" class="block text-xs font-semibold text-[#122056] mb-1">
                                    Tinggi Badan <span class="text-[#DC2626]">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        step="0.1"
                                        id="tinggi_badan"
                                        name="tinggi_badan"
                                        placeholder="170"
                                        min="30"
                                        max="250"
                                        value="{{ old('tinggi_badan', $pemeriksaanAwal->tinggi_badan ?? 170) }}"
                                        required
                                        class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border @error('tinggi_badan') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">
                                        cm
                                    </span>
                                </div>
                                @error('tinggi_badan')
                                    <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <!-- 7. Catatan Awal Perawat -->
                    <div>
                        <label for="catatan" class="block text-xs font-semibold text-[#122056] mb-1">
                            Catatan Awal Perawat <span class="text-[#DC2626]">*</span>
                        </label>
                        <textarea
                            id="catatan"
                            name="catatan"
                            rows="3"
                            placeholder="Tuliskan keluhan utama awal, riwayat singkat, atau observasi visual pasien..."
                            required
                            class="w-full px-3.5 py-2.5 bg-[#FAFAFD] border @error('catatan') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-sm text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                        >{{ old('catatan', $pemeriksaanAwal->catatan ?? '') }}</textarea>
                        @error('catatan')
                            <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Integrasi Objective Note -->
                    <div class="p-4 rounded-[18px] bg-[#EEEEFD] border border-[#CBD1EA] text-xs text-[#122056] flex items-start gap-2.5 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#5865DC] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Data tanda-tanda vital di atas akan otomatis terintegrasi ke bagian <strong class="text-[#5865DC]">Objective</strong> pada modul AI SOAP rekam medis dokter.</span>
                    </div>

                    <!-- Tombol Aksi Simpan (Royal Indigo CTA) -->
                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            id="btnSimpanPemeriksaan"
                            class="inline-flex items-center gap-2 py-2.5 px-6 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-semibold text-sm rounded-[14px] transition-all cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)] focus:ring-3 focus:ring-[#5865DC]/30"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Simpan data pemeriksaan</span>
                        </button>
                    </div>

                </form>
            @else
                <div class="py-16 text-center text-[#69718B] space-y-2">
                    <p class="text-sm font-semibold text-[#122056]">Tidak ada pasien yang dipilih</p>
                    <p class="text-xs">Silakan pilih salah satu pasien dari daftar antrean di sebelah kiri.</p>
                </div>
            @endif

        </div>

    </div>

    @else
    {{-- ========== TAB DAFTAR PASIEN TERDAFTAR ========== --}}
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6 space-y-5">

        <!-- Header & Pencarian -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-[#122056]">Daftar Pasien Terdaftar</h2>
                <p class="text-xs text-[#69718B] mt-0.5">Pencarian dan filter berdasarkan tanggal registrasi pasien.</p>
            </div>

            <!-- Search & Filters -->
            <form method="GET" action="{{ route('perawat.dashboard') }}" id="formFilterPasien" class="flex flex-col sm:flex-row items-end gap-3">
                <input type="hidden" name="tab" value="pasien">

                <!-- Search -->
                <div>
                    <label for="cariPasienList" class="block text-xs font-semibold text-[#122056] mb-1.5">Cari pasien</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="cariPasienList"
                            name="cari_pasien"
                            value="{{ $searchPasien }}"
                            placeholder="Cari nama atau ID pasien..."
                            class="w-full sm:w-[240px] pl-9 pr-8 py-2 text-xs bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all text-[#20294A] placeholder-[#69718B]/70"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC] absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        @if ($searchPasien)
                            <button type="button" onclick="document.getElementById('cariPasienList').value='';document.getElementById('formFilterPasien').submit();" class="absolute right-2.5 top-2 text-xs text-[#69718B] hover:text-[#122056] cursor-pointer">✕</button>
                        @endif
                    </div>
                </div>

                <!-- Filter Tahun -->
                <div>
                    <label for="filterTahun" class="block text-xs font-semibold text-[#122056] mb-1.5">Tahun</label>
                    <select id="filterTahun" name="tahun" onchange="this.form.submit()"
                            class="w-full sm:w-[100px] py-2 px-3 text-xs bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all text-[#122056] font-medium cursor-pointer">
                        <option value="">Semua</option>
                        @foreach ($tahunTersedia as $thn)
                            <option value="{{ $thn }}" {{ $filterTahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div>
                    <label for="filterBulan" class="block text-xs font-semibold text-[#122056] mb-1.5">Bulan</label>
                    <select id="filterBulan" name="bulan" onchange="this.form.submit()"
                            class="w-full sm:w-[120px] py-2 px-3 text-xs bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all text-[#122056] font-medium cursor-pointer">
                        <option value="">Semua</option>
                        @php
                            $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        @endphp
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $filterBulan == $i ? 'selected' : '' }}>{{ $namaBulan[$i-1] }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Filter Tanggal -->
                <div>
                    <label for="filterTanggal" class="block text-xs font-semibold text-[#122056] mb-1.5">Tanggal</label>
                    <select id="filterTanggal" name="tanggal" onchange="this.form.submit()"
                            class="w-full sm:w-[80px] py-2 px-3 text-xs bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all text-[#122056] font-medium cursor-pointer">
                        <option value="">Semua</option>
                        @for ($d = 1; $d <= 31; $d++)
                            <option value="{{ $d }}" {{ $filterTanggal == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Tombol Cari & Reset -->
                <div class="flex gap-2">
                    <button type="submit" class="py-2 px-4 bg-[#5865DC] hover:bg-[#4854C9] text-[#FFFFFF] text-xs font-semibold rounded-[14px] transition-all cursor-pointer shadow-[0_2px_8px_rgba(88,101,220,0.22)]">
                        Cari
                    </button>
                    @if ($searchPasien || $filterTahun || $filterBulan || $filterTanggal)
                        <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}" class="py-2 px-4 bg-[#EEEEFD] hover:bg-[#e2e2fb] text-[#122056] text-xs font-semibold rounded-[14px] border border-[#CBD1EA] transition-all">
                            Hapus Filter
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Info filter aktif -->
        @if ($searchPasien || $filterTahun || $filterBulan || $filterTanggal)
            <div class="flex flex-wrap items-center gap-2 text-xs text-[#69718B]">
                <span>Filter aktif:</span>
                @if ($searchPasien)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#EEEEFD] border border-[#CBD1EA] rounded-full font-semibold text-[#5865DC]">
                        "{{ $searchPasien }}"
                    </span>
                @endif
                @if ($filterTahun)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#EEEEFD] border border-[#CBD1EA] rounded-full font-semibold text-[#5865DC]">
                        Tahun {{ $filterTahun }}
                    </span>
                @endif
                @if ($filterBulan)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#EEEEFD] border border-[#CBD1EA] rounded-full font-semibold text-[#5865DC]">
                        {{ $namaBulan[$filterBulan - 1] ?? '' }}
                    </span>
                @endif
                @if ($filterTanggal)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#EEEEFD] border border-[#CBD1EA] rounded-full font-semibold text-[#5865DC]">
                        Tanggal {{ $filterTanggal }}
                    </span>
                @endif
                <span class="text-[#CBD1EA]">·</span>
                <span>{{ $daftarPasien->total() }} hasil ditemukan</span>
            </div>
        @endif

        <!-- Tabel Daftar Pasien -->
        <div class="overflow-x-auto rounded-[20px] border border-[#E4E7F4]">
            <table class="w-full text-xs text-left">
                <thead class="bg-[#FAFAFD] border-b border-[#E4E7F4]">
                    <tr>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap">ID Pasien</th>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap">Nama Lengkap</th>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap">Umur</th>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap">Jenis Kelamin</th>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap">Tgl. Registrasi</th>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap">TTV Terakhir</th>
                        <th class="px-4 py-3.5 font-bold text-[#122056] whitespace-nowrap text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E7F4]">
                    @forelse ($daftarPasien as $pasien)
                        @php
                            $latestKunjungan = $pasien->kunjungan()->with('pemeriksaanAwal')->orderBy('id_kunjungan', 'desc')->first();
                            $latestPemeriksaan = $latestKunjungan?->pemeriksaanAwal;
                            $ttvDate = $latestPemeriksaan?->created_at;
                        @endphp
                        <tr class="hover:bg-[#FAFAFD] transition-colors">
                            <td class="px-4 py-3.5 font-mono font-bold text-[#5865DC] whitespace-nowrap">
                                <span class="bg-[#EEEEFD] border border-[#CBD1EA] px-2.5 py-1 rounded-[8px]">
                                    {{ $pasien->no_rekam_medis }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-[#122056] whitespace-nowrap">
                                {{ $pasien->nama_pasien }}
                            </td>
                            <td class="px-4 py-3.5 text-[#69718B] whitespace-nowrap">
                                @if ($pasien->umur)
                                    {{ $pasien->umur }} tahun
                                @elseif ($pasien->tanggal_lahir)
                                    {{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->age }} tahun
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-[#69718B] whitespace-nowrap">
                                @if ($pasien->jenis_kelamin === 'Laki-laki')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#EEEEFD] text-[#5865DC] font-semibold text-[10px]">Laki-laki</span>
                                @elseif ($pasien->jenis_kelamin === 'Perempuan')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#FEF2F2] text-[#DC2626] font-semibold text-[10px]">Perempuan</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-[#69718B] whitespace-nowrap">
                                @if ($pasien->created_at)
                                    {{ $pasien->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-[#69718B] whitespace-nowrap">
                                @if ($ttvDate)
                                    <span class="font-medium text-[#122056]">{{ $ttvDate->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                                @else
                                    <span class="text-[#CBD1EA]">Belum ada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('perawat.pasien.show', $pasien->id_pasien) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#FFFFFF] hover:bg-[#EEEEFD] border border-[#CBD1EA] rounded-[10px] text-xs font-semibold text-[#5865DC] hover:text-[#4854C9] transition-all shadow-xs"
                                       title="Lihat Detail & Riwayat Pasien">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detail</span>
                                    </a>
                                    <button type="button"
                                            onclick="openModalHapusPasien({{ $pasien->id_pasien }}, '{{ addslashes($pasien->nama_pasien) }}', '{{ $pasien->no_rekam_medis }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#FFFFFF] hover:bg-[#FEF2F2] border border-[#CBD1EA] hover:border-[#FECACA] rounded-[10px] text-xs font-semibold text-[#DC2626] transition-all shadow-xs cursor-pointer"
                                            title="Hapus / Batalkan Registrasi Pasien">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span class="hidden sm:inline">Hapus</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-[#69718B]">
                                <div class="space-y-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto text-[#CBD1EA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-semibold text-[#122056]">Tidak ada pasien ditemukan</p>
                                    <p class="text-xs text-[#69718B]">Coba ubah kata kunci pencarian atau filter tanggal.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($daftarPasien->hasPages())
            <div class="flex justify-center pt-2">
                {{ $daftarPasien->links() }}
            </div>
        @endif

    </div>
    @endif

</div>

{{-- ========== MODAL: Input Pasien Baru + TTV ========== --}}
<div id="modalInputPasien" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-label="Form Input Pasien Baru">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-[#122056]/40 backdrop-blur-sm" onclick="document.getElementById('modalInputPasien').classList.add('hidden')"></div>

    <!-- Modal Content -->
    <div class="fixed inset-0 flex items-start justify-center pt-8 sm:pt-14 px-4 overflow-y-auto pb-8">
        <div class="relative w-full max-w-2xl bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_20px_50px_rgba(18,32,86,0.18)] p-6 sm:p-8 space-y-6">

            <!-- Modal Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-[14px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center shadow-[0_2px_10px_rgba(88,101,220,0.28)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[#122056]">Input Pasien Baru</h2>
                        <p class="text-xs text-[#69718B]">Registrasi pasien dan pengukuran TTV awal</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modalInputPasien').classList.add('hidden')"
                        class="w-8 h-8 rounded-[12px] bg-[#FAFAFD] border border-[#E4E7F4] text-[#69718B] hover:text-[#122056] hover:bg-[#EEEEFD] flex items-center justify-center transition-all cursor-pointer text-sm">
                    ✕
                </button>
            </div>

            <!-- ID Info Badge -->
            <div class="p-3.5 rounded-[18px] bg-[#EEEEFD] border border-[#CBD1EA] text-xs text-[#122056] flex items-start gap-2.5 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#5865DC] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>ID Pasien akan digenerate otomatis oleh sistem dalam format <strong class="font-mono font-bold text-[#5865DC]">YYYYMMDD-NNN</strong> secara atomik setelah data berhasil disimpan.</span>
            </div>

            <form method="POST" action="{{ route('perawat.pasien.store') }}" id="formPasienBaru" class="space-y-6">
                @csrf

                {{-- Bagian 1: Data Pasien --}}
                <div>
                    <div class="mb-3.5 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-[8px] bg-[#EEEEFD] text-[#5865DC] flex items-center justify-center text-xs font-bold">1</span>
                        <div>
                            <h3 class="text-sm font-bold text-[#122056]">Data Identitas Pasien</h3>
                            <p class="text-xs text-[#69718B]">Informasi demografis pasien baru.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div class="sm:col-span-2">
                            <label for="modal_nama_pasien" class="block text-xs font-semibold text-[#122056] mb-1">
                                Nama Lengkap <span class="text-[#DC2626]">*</span>
                            </label>
                            <input type="text" id="modal_nama_pasien" name="nama_pasien" required
                                   value="{{ old('nama_pasien') }}"
                                   placeholder="Masukkan nama lengkap pasien"
                                   class="w-full px-3.5 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-medium text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                            @error('nama_pasien')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Umur -->
                        <div>
                            <label for="modal_umur" class="block text-xs font-semibold text-[#122056] mb-1">
                                Umur <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="modal_umur" name="umur" required
                                       value="{{ old('umur') }}"
                                       placeholder="0" min="0" max="120"
                                       class="w-full pl-3.5 pr-14 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">tahun</span>
                            </div>
                            @error('umur')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Jenis Kelamin -->
                        <div>
                            <label for="modal_jenis_kelamin" class="block text-xs font-semibold text-[#122056] mb-1">
                                Jenis Kelamin <span class="text-[#DC2626]">*</span>
                            </label>
                            <select id="modal_jenis_kelamin" name="jenis_kelamin" required
                                    class="w-full px-3.5 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-medium text-[#20294A] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all cursor-pointer">
                                <option value="" disabled {{ old('jenis_kelamin') ? '' : 'selected' }}>Pilih jenis kelamin</option>
                                <option value="Laki-laki" {{ old('jenis_kelamin') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="Perempuan" {{ old('jenis_kelamin') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('jenis_kelamin')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="border-t border-[#E4E7F4]"></div>

                {{-- Bagian 2: Tanda-Tanda Vital (TTV) --}}
                <div>
                    <div class="mb-3.5 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-[8px] bg-[#EEEEFD] text-[#5865DC] flex items-center justify-center text-xs font-bold">2</span>
                        <div>
                            <h3 class="text-sm font-bold text-[#122056]">Tanda-Tanda Vital (TTV)</h3>
                            <p class="text-xs text-[#69718B]">Pengukuran klinis wajib untuk triase.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Tekanan Darah Sistolik -->
                        <div>
                            <label for="modal_td_sistolik" class="block text-xs font-semibold text-[#122056] mb-1">
                                TD Sistolik <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="modal_td_sistolik" name="tekanan_darah_sistolik" required
                                       value="{{ old('tekanan_darah_sistolik') }}"
                                       placeholder="120" min="50" max="300"
                                       class="w-full pl-3.5 pr-14 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">mmHg</span>
                            </div>
                            @error('tekanan_darah_sistolik')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tekanan Darah Diastolik -->
                        <div>
                            <label for="modal_td_diastolik" class="block text-xs font-semibold text-[#122056] mb-1">
                                TD Diastolik <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="modal_td_diastolik" name="tekanan_darah_diastolik" required
                                       value="{{ old('tekanan_darah_diastolik') }}"
                                       placeholder="80" min="20" max="200"
                                       class="w-full pl-3.5 pr-14 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">mmHg</span>
                            </div>
                            @error('tekanan_darah_diastolik')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Denyut Nadi -->
                        <div>
                            <label for="modal_nadi" class="block text-xs font-semibold text-[#122056] mb-1">
                                Denyut Nadi <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="modal_nadi" name="nadi" required
                                       value="{{ old('nadi') }}"
                                       placeholder="78" min="30" max="250"
                                       class="w-full pl-3.5 pr-16 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">x/menit</span>
                            </div>
                            @error('nadi')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Suhu Tubuh -->
                        <div>
                            <label for="modal_suhu" class="block text-xs font-semibold text-[#122056] mb-1">
                                Suhu Tubuh <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" step="0.1" id="modal_suhu" name="suhu_tubuh" required
                                       value="{{ old('suhu_tubuh') }}"
                                       placeholder="36.5" min="30" max="45"
                                       class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">°C</span>
                            </div>
                            @error('suhu_tubuh')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                            <!-- Warning suhu tinggi -->
                            <p id="modalSuhuWarning" class="text-xs text-[#DC2626] font-semibold mt-1.5 items-center gap-1.5 hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#DC2626] flex-shrink-0 inline" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Peringatan: Suhu di atas rentang normal (&gt;37.5°C)
                            </p>
                        </div>

                        <!-- Laju Pernapasan -->
                        <div>
                            <label for="modal_rr" class="block text-xs font-semibold text-[#122056] mb-1">
                                Laju Pernapasan <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="modal_rr" name="laju_pernapasan" required
                                       value="{{ old('laju_pernapasan') }}"
                                       placeholder="18" min="5" max="60"
                                       class="w-full pl-3.5 pr-16 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">x/menit</span>
                            </div>
                            @error('laju_pernapasan')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Saturasi Oksigen -->
                        <div>
                            <label for="modal_spo2" class="block text-xs font-semibold text-[#122056] mb-1">
                                SpO2 <span class="text-[#DC2626]">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="modal_spo2" name="saturasi_oksigen" required
                                       value="{{ old('saturasi_oksigen') }}"
                                       placeholder="98" min="50" max="100"
                                       class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">%</span>
                            </div>
                            @error('saturasi_oksigen')
                                <p class="text-[11px] text-[#DC2626] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Berat Badan (Optional) -->
                        <div>
                            <label for="modal_bb" class="block text-xs font-semibold text-[#122056] mb-1">Berat Badan</label>
                            <div class="relative">
                                <input type="number" step="0.1" id="modal_bb" name="berat_badan"
                                       value="{{ old('berat_badan') }}"
                                       placeholder="68" min="1" max="300"
                                       class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">kg</span>
                            </div>
                        </div>

                        <!-- Tinggi Badan (Optional) -->
                        <div>
                            <label for="modal_tb" class="block text-xs font-semibold text-[#122056] mb-1">Tinggi Badan</label>
                            <div class="relative">
                                <input type="number" step="0.1" id="modal_tb" name="tinggi_badan"
                                       value="{{ old('tinggi_badan') }}"
                                       placeholder="170" min="30" max="250"
                                       class="w-full pl-3.5 pr-12 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm font-semibold text-[#122056] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-[#69718B] font-semibold pointer-events-none">cm</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Catatan (Optional) -->
                <div>
                    <label for="modal_catatan" class="block text-xs font-semibold text-[#122056] mb-1">Catatan Awal</label>
                    <textarea id="modal_catatan" name="catatan" rows="2"
                              placeholder="Keluhan utama, riwayat singkat, atau observasi visual pasien (opsional)..."
                              class="w-full px-3.5 py-2.5 bg-[#FAFAFD] border border-[#E4E7F4] rounded-[14px] text-sm text-[#20294A] placeholder-[#69718B]/70 focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                    >{{ old('catatan') }}</textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-between pt-2 border-t border-[#E4E7F4]">
                    <button type="button" onclick="document.getElementById('modalInputPasien').classList.add('hidden')"
                            class="py-2.5 px-5 text-sm font-semibold text-[#69718B] hover:text-[#122056] bg-[#FFFFFF] border border-[#E4E7F4] hover:border-[#CBD1EA] rounded-[14px] transition-all cursor-pointer shadow-xs">
                        Batal
                    </button>
                    <button type="submit" id="btnSimpanPasienBaru"
                            class="inline-flex items-center gap-2 py-2.5 px-6 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-semibold text-sm rounded-[14px] transition-all cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.25)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)] focus:ring-3 focus:ring-[#5865DC]/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Pasien
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========== MODAL: Konfirmasi Hapus / Pembatalan Registrasi Pasien ========== --}}
<div id="modalHapusPasien" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalHapusTitle">
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-[#122056]/50 backdrop-blur-xs transition-opacity" onclick="closeModalHapusPasien()"></div>

    <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative w-full max-w-md bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_20px_50px_rgba(18,32,86,0.2)] p-6 space-y-5 animate-scaleIn">
            
            <!-- Modal Header / Icon -->
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-[14px] bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] flex items-center justify-center flex-shrink-0 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="flex-grow">
                    <h2 id="modalHapusTitle" class="text-base font-bold text-[#122056]">Hapus Registrasi Pasien?</h2>
                    <p class="text-xs text-[#69718B] mt-1 leading-relaxed">
                        Apakah Anda yakin ingin menghapus registrasi pasien berikut?
                    </p>
                </div>
                <button type="button" onclick="closeModalHapusPasien()"
                        class="w-8 h-8 rounded-[12px] bg-[#FAFAFD] border border-[#E4E7F4] text-[#69718B] hover:text-[#122056] hover:bg-[#EEEEFD] flex items-center justify-center transition-all cursor-pointer text-sm"
                        aria-label="Tutup dialog">
                    ✕
                </button>
            </div>

            <!-- Detail Pasien Dinamis -->
            <div class="p-4 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[#69718B]">Nama Pasien:</span>
                    <span id="hapusNamaPasien" class="font-bold text-[#122056] text-right">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#69718B]">ID Pasien:</span>
                    <span id="hapusIdPasien" class="font-mono font-bold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2 py-0.5 rounded-[6px]">-</span>
                </div>
            </div>

            <div class="flex items-center gap-2 text-[11px] text-[#69718B]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#D97706] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Pastikan data pasien sudah benar sebelum melanjutkan.</span>
            </div>

            <!-- Form Aksi Hapus dengan CSRF & Method DELETE -->
            <form id="formHapusPasien" method="POST" action="" onsubmit="handleHapusSubmit(event)">
                @csrf
                @method('DELETE')
                <input type="hidden" name="alasan" value="Pasien tidak hadir / pembatalan registrasi oleh perawat">

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" id="btnBatalHapus" onclick="closeModalHapusPasien()"
                            class="px-4 py-2 rounded-[14px] text-xs font-semibold bg-[#FAFAFD] hover:bg-[#EEEEFD] text-[#20294A] border border-[#CBD1EA] transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btnKonfirmasiHapus"
                            class="px-4 py-2 rounded-[14px] text-xs font-semibold bg-[#DC2626] hover:bg-[#B91C1C] active:bg-[#991B1B] text-[#FFFFFF] transition-all flex items-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(220,38,38,0.25)] hover:shadow-[0_4px_16px_rgba(220,38,38,0.35)]">
                        <svg id="iconTrashHapus" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <svg id="spinnerHapus" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="labelHapus">Ya, Hapus Registrasi</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Real-time suhu warning for antrean form
        const suhuInput = document.getElementById('suhu_tubuh');
        const suhuWarning = document.getElementById('suhuWarning');

        if (suhuInput && suhuWarning) {
            function checkSuhu() {
                const val = parseFloat(suhuInput.value);
                if (!isNaN(val) && val > 37.5) {
                    suhuWarning.classList.remove('hidden');
                } else {
                    suhuWarning.classList.add('hidden');
                }
            }
            suhuInput.addEventListener('input', checkSuhu);
            suhuInput.addEventListener('change', checkSuhu);
        }

        // Real-time suhu warning for modal form
        const modalSuhu = document.getElementById('modal_suhu');
        const modalSuhuWarning = document.getElementById('modalSuhuWarning');

        if (modalSuhu && modalSuhuWarning) {
            function checkModalSuhu() {
                const val = parseFloat(modalSuhu.value);
                if (!isNaN(val) && val > 37.5) {
                    modalSuhuWarning.classList.remove('hidden');
                    modalSuhuWarning.classList.add('flex');
                } else {
                    modalSuhuWarning.classList.add('hidden');
                    modalSuhuWarning.classList.remove('flex');
                }
            }
            modalSuhu.addEventListener('input', checkModalSuhu);
            modalSuhu.addEventListener('change', checkModalSuhu);
        }

        // Prevent double submission on both forms
        ['formTTV', 'formPasienBaru'].forEach(function(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn.disabled) {
                        e.preventDefault();
                        return;
                    }
                    btn.disabled = true;
                    btn.innerHTML = '<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Menyimpan...</span>';
                });
            }
        });

        // Dynamic day filter based on selected month/year
        const filterBulan = document.getElementById('filterBulan');
        const filterTahun = document.getElementById('filterTahun');
        const filterTanggal = document.getElementById('filterTanggal');

        if (filterBulan && filterTahun && filterTanggal) {
            function updateDayOptions() {
                const month = parseInt(filterBulan.value);
                const year = parseInt(filterTahun.value) || new Date().getFullYear();
                const currentDay = parseInt(filterTanggal.value);

                if (!month) return;

                const daysInMonth = new Date(year, month, 0).getDate();
                const currentOptions = filterTanggal.options.length - 1; // minus the "Semua" option

                // Remove extra days
                while (filterTanggal.options.length > daysInMonth + 1) {
                    filterTanggal.remove(filterTanggal.options.length - 1);
                }

                // Add missing days
                for (let d = currentOptions + 1; d <= daysInMonth; d++) {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.text = d;
                    filterTanggal.add(opt);
                }

                // Reset selection if current day is invalid
                if (currentDay > daysInMonth) {
                    filterTanggal.value = '';
                }
            }

            filterBulan.addEventListener('change', updateDayOptions);
            filterTahun.addEventListener('change', updateDayOptions);
        }

        // Open modal if there are validation errors for the patient form
        @if ($errors->hasAny(['nama_pasien', 'umur', 'jenis_kelamin', 'tekanan_darah_sistolik', 'tekanan_darah_diastolik', 'saturasi_oksigen']))
            document.getElementById('modalInputPasien').classList.remove('hidden');
        @endif
    });

    // Modal Hapus Registrasi Pasien Handlers
    window.openModalHapusPasien = function(id, nama, noRekamMedis) {
        const modal = document.getElementById('modalHapusPasien');
        const form = document.getElementById('formHapusPasien');
        const namaEl = document.getElementById('hapusNamaPasien');
        const idEl = document.getElementById('hapusIdPasien');
        const btnConfirm = document.getElementById('btnKonfirmasiHapus');
        const btnCancel = document.getElementById('btnBatalHapus');
        const iconTrash = document.getElementById('iconTrashHapus');
        const spinner = document.getElementById('spinnerHapus');
        const label = document.getElementById('labelHapus');

        if (!modal || !form) return;

        namaEl.textContent = nama;
        idEl.textContent = noRekamMedis;
        form.action = "{{ url('/perawat/pasien') }}/" + id;

        // Reset submit button state
        btnConfirm.disabled = false;
        btnCancel.disabled = false;
        if (iconTrash) iconTrash.classList.remove('hidden');
        if (spinner) spinner.classList.add('hidden');
        if (label) label.textContent = 'Ya, Hapus Registrasi';

        modal.classList.remove('hidden');
        btnCancel.focus();
    };

    window.closeModalHapusPasien = function() {
        const modal = document.getElementById('modalHapusPasien');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    window.handleHapusSubmit = function(event) {
        const btnConfirm = document.getElementById('btnKonfirmasiHapus');
        const btnCancel = document.getElementById('btnBatalHapus');
        const iconTrash = document.getElementById('iconTrashHapus');
        const spinner = document.getElementById('spinnerHapus');
        const label = document.getElementById('labelHapus');

        if (btnConfirm.disabled) {
            event.preventDefault();
            return;
        }

        btnConfirm.disabled = true;
        btnCancel.disabled = true;
        if (iconTrash) iconTrash.classList.add('hidden');
        if (spinner) spinner.classList.remove('hidden');
        if (label) label.textContent = 'Memproses...';
    };

    // Keyboard navigation: Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalHapus = document.getElementById('modalHapusPasien');
            if (modalHapus && !modalHapus.classList.contains('hidden')) {
                closeModalHapusPasien();
            }
            const modalInput = document.getElementById('modalInputPasien');
            if (modalInput && !modalInput.classList.contains('hidden')) {
                modalInput.classList.add('hidden');
            }
        }
    });
</script>
@endsection

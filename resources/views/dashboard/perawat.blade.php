@extends('layouts.app')

@section('title', 'Nurse Station - Input Pemeriksaan Awal & TTV - MedScribe AI')

@section('content')
<div class="space-y-5">

    <!-- Top Status & Breadcrumb (Sesuai Mockup Halaman 2) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/80 pb-3">
        <div class="flex items-center gap-2 text-xs font-bold tracking-wider uppercase">
            <span class="text-sky-600">PEMERIKSAAN AWAL</span>
            <span class="text-slate-300">·</span>
            @if (session('status'))
                <span class="text-emerald-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    PEMERIKSAAN BERHASIL DISIMPAN
                </span>
            @elseif ($pasienTerpilih && $pasienTerpilih->status === 'Siap Periksa')
                <span class="text-emerald-600">TTV SUDAH TERVERIFIKASI</span>
            @else
                <span class="text-amber-600">MENUNGGU INPUT TTV</span>
            @endif
        </div>
        <div class="text-xs font-mono text-slate-400">
            /pemeriksaan-awal/{{ $idKunjunganCode ?? 'RSUA-20260919-001' }}
        </div>
    </div>

    <!-- Alert Notifikasi Berhasil (Sesuai Mockup Gambar 3.1) -->
    @if (session('status'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between gap-3 shadow-xs transition-all">
            <div class="flex items-center gap-2.5 font-medium">
                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-sm p-1">✕</button>
        </div>
    @endif

    <!-- Alert Kesalahan Validasi -->
    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-rose-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Mohon periksa kembali isian formulir:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-600 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Layout 2 Kolom (Sesuai Mockup Gambar 3.1 & Dokumen SRS) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Kolom Kiri: Daftar Antrean Pasien (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between">
            <div>
                <!-- Header Antrean -->
                <div class="flex items-center justify-between mb-3.5">
                    <h2 class="text-base font-bold text-slate-900">Daftar Antrean</h2>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-100 text-xs font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-sky-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        {{ $daftarAntrean->count() }} PASIEN
                    </span>
                </div>

                <!-- Kolom Pencarian Pasien -->
                <form method="GET" action="{{ route('perawat.dashboard') }}" class="mb-4">
                    @if (request('antrean_id'))
                        <input type="hidden" name="antrean_id" value="{{ request('antrean_id') }}">
                    @endif
                    <label for="cariPasien" class="block text-xs font-semibold text-slate-700 mb-1.5">Cari pasien</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="cariPasien"
                            name="cari"
                            value="{{ request('cari') }}"
                            placeholder="Nama atau No. RM"
                            class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all text-slate-800 placeholder-slate-400"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        @if (request('cari'))
                            <a href="{{ route('perawat.dashboard', ['antrean_id' => request('antrean_id')]) }}" class="absolute right-2.5 top-2.5 text-xs text-slate-400 hover:text-slate-600">✕</a>
                        @endif
                    </div>
                </form>

                <!-- Daftar Pasien Antrean -->
                <div class="space-y-2.5 max-h-[560px] overflow-y-auto pr-1">
                    @forelse ($daftarAntrean as $antrean)
                        @php
                            $isSelected = $pasienTerpilih && ($pasienTerpilih->id_antrean === $antrean->id_antrean);
                            
                            // Hitung usia dari tanggal lahir
                            $usia = '34 th';
                            if ($antrean->pasien && $antrean->pasien->tanggal_lahir) {
                                $usia = \Carbon\Carbon::parse($antrean->pasien->tanggal_lahir)->age . ' th';
                            }
                            $jk = ($antrean->pasien && $antrean->pasien->jenis_kelamin === 'Perempuan') ? 'P' : 'L';
                            
                            // Estimasi jam antrean
                            $jam = sprintf('%02d:%02d WIB', 8 + intdiv(30 + ($antrean->id_antrean - 1) * 5, 60), (30 + ($antrean->id_antrean - 1) * 5) % 60);
                        @endphp

                        <a
                            href="{{ route('perawat.dashboard', ['antrean_id' => $antrean->id_antrean, 'cari' => request('cari')]) }}"
                            class="block p-3.5 rounded-xl border transition-all text-left {{ $isSelected ? 'border-2 border-sky-500 bg-sky-50/50 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/60' }}"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold {{ $isSelected ? 'text-sky-700' : 'text-slate-700' }}">
                                    A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="text-[11px] text-slate-400 font-medium">{{ $jam }}</span>
                            </div>

                            <div class="font-bold text-slate-900 text-sm mt-1.5">
                                {{ $antrean->pasien->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}
                            </div>

                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $usia }} · {{ $jk }}
                            </div>

                            <div class="mt-2.5">
                                @if ($antrean->status === 'Siap Periksa')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        SIAP DIPANGGIL DOKTER
                                    </span>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        MENUNGGU TTV
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider bg-slate-100 text-slate-600 border border-slate-200 uppercase">
                                        {{ strtoupper($antrean->status) }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="py-8 text-center text-xs text-slate-400">
                            Tidak ada pasien yang cocok dengan pencarian.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Footer Antrean -->
            <div class="mt-4 pt-3.5 border-t border-slate-100 text-[11px] text-slate-400 text-center">
                Antrean diperbarui setelah data pemeriksaan disimpan.
            </div>
        </div>

        <!-- Kolom Kanan: Formulir Pemeriksaan Awal & TTV (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-7 space-y-6">

            @if ($pasienTerpilih)
                <!-- Header Formulir -->
                <div class="flex items-center gap-2.5 text-slate-900">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">Formulir Pemeriksaan Awal & TTV</h1>
                </div>

                <!-- Banner Identitas Pasien Terpilih (Sesuai Mockup) -->
                @php
                    $usiaLengkap = '34 tahun';
                    if ($pasienTerpilih->pasien && $pasienTerpilih->pasien->tanggal_lahir) {
                        $usiaLengkap = \Carbon\Carbon::parse($pasienTerpilih->pasien->tanggal_lahir)->age . ' tahun';
                    }
                    $jkLengkap = ($pasienTerpilih->pasien && $pasienTerpilih->pasien->jenis_kelamin) ? $pasienTerpilih->pasien->jenis_kelamin : 'Laki-laki';
                @endphp
                <div class="p-4 sm:p-5 rounded-xl bg-slate-50/80 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-bold text-slate-900">
                            {{ $pasienTerpilih->pasien->nama_pasien ?? 'Pasien' }}
                        </div>
                        <div class="text-xs text-slate-600 mt-1">
                            {{ $usiaLengkap }} · {{ $jkLengkap }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1 font-mono">
                            No. RM <strong class="text-slate-800 font-sans">{{ $pasienTerpilih->pasien->no_rekam_medis ?? '00-12-345' }}</strong>
                            <span class="mx-2 text-slate-300">|</span>
                            ID <strong class="text-slate-800 font-sans">{{ $idKunjunganCode }}</strong>
                        </div>
                    </div>

                    <div>
                        @if ($pasienTerpilih->status === 'Siap Periksa')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                SIAP DIPANGGIL DOKTER
                            </span>
                        @elseif ($pasienTerpilih->status === 'Menunggu TTV')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold tracking-wider bg-amber-50 text-amber-700 border border-amber-200 uppercase">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                MENUNGGU TTV
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold tracking-wider bg-slate-100 text-slate-700 border border-slate-200 uppercase">
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
                            <h2 class="text-sm font-bold text-slate-900">Tanda-tanda vital</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Semua field wajib diisi. Gunakan satuan yang tertera pada kolom.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <!-- 1. Tekanan Darah -->
                            <div>
                                <label for="tekanan_darah" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Tekanan Darah <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <input
                                        type="text"
                                        id="tekanan_darah"
                                        name="tekanan_darah"
                                        placeholder="120/80"
                                        value="{{ old('tekanan_darah', $pemeriksaanAwal->tekanan_darah ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-14 py-2.5 bg-white border @error('tekanan_darah') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-slate-400 font-medium pointer-events-none">
                                        mmHg
                                    </span>
                                </div>
                                @error('tekanan_darah')
                                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 2. Denyut Nadi -->
                            <div>
                                <label for="nadi" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Denyut Nadi <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <input
                                        type="number"
                                        id="nadi"
                                        name="nadi"
                                        placeholder="78"
                                        min="30"
                                        max="250"
                                        value="{{ old('nadi', $pemeriksaanAwal->nadi ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-16 py-2.5 bg-white border @error('nadi') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-slate-400 font-medium pointer-events-none">
                                        x/menit
                                    </span>
                                </div>
                                @error('nadi')
                                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 3. Suhu Tubuh -->
                            <div>
                                <label for="suhu_tubuh" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Suhu Tubuh <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
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
                                        class="w-full pl-3.5 pr-12 py-2.5 bg-white border @error('suhu_tubuh') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-slate-400 font-medium pointer-events-none">
                                        °C
                                    </span>
                                </div>
                                @error('suhu_tubuh')
                                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                @enderror

                                <!-- Indikator Real-time Peringatan Suhu Tubuh Di Atas Rentang Normal -->
                                @php
                                    $suhuCurrent = (float) old('suhu_tubuh', $pemeriksaanAwal->suhu_tubuh ?? 0);
                                @endphp
                                <p id="suhuWarning" class="text-xs text-amber-600 font-semibold mt-1.5 flex items-center gap-1.5 {{ $suhuCurrent > 37.5 ? '' : 'hidden' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Di atas rentang normal (&gt;37.5°C)</span>
                                </p>
                            </div>

                            <!-- 4. Laju Pernapasan -->
                            <div>
                                <label for="laju_pernapasan" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Laju Pernapasan <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <input
                                        type="number"
                                        id="laju_pernapasan"
                                        name="laju_pernapasan"
                                        placeholder="18"
                                        min="5"
                                        max="60"
                                        value="{{ old('laju_pernapasan', $pemeriksaanAwal->laju_pernapasan ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-16 py-2.5 bg-white border @error('laju_pernapasan') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-slate-400 font-medium pointer-events-none">
                                        x/menit
                                    </span>
                                </div>
                                @error('laju_pernapasan')
                                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 5. Berat Badan -->
                            <div>
                                <label for="berat_badan" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Berat Badan <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <input
                                        type="number"
                                        step="0.1"
                                        id="berat_badan"
                                        name="berat_badan"
                                        placeholder="68"
                                        min="1"
                                        max="300"
                                        value="{{ old('berat_badan', $pemeriksaanAwal->berat_badan ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-12 py-2.5 bg-white border @error('berat_badan') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-slate-400 font-medium pointer-events-none">
                                        kg
                                    </span>
                                </div>
                                @error('berat_badan')
                                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 6. Tinggi Badan -->
                            <div>
                                <label for="tinggi_badan" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Tinggi Badan <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative rounded-xl shadow-xs">
                                    <input
                                        type="number"
                                        step="0.1"
                                        id="tinggi_badan"
                                        name="tinggi_badan"
                                        placeholder="170"
                                        min="30"
                                        max="250"
                                        value="{{ old('tinggi_badan', $pemeriksaanAwal->tinggi_badan ?? '') }}"
                                        required
                                        class="w-full pl-3.5 pr-12 py-2.5 bg-white border @error('tinggi_badan') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                    >
                                    <span class="absolute inset-y-0 right-3.5 flex items-center text-xs text-slate-400 font-medium pointer-events-none">
                                        cm
                                    </span>
                                </div>
                                @error('tinggi_badan')
                                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <!-- 7. Catatan Awal Perawat -->
                    <div>
                        <label for="catatan" class="block text-xs font-semibold text-slate-700 mb-1">
                            Catatan Awal <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="catatan"
                            name="catatan"
                            rows="3"
                            placeholder="Tuliskan keluhan utama awal, riwayat singkat, atau observasi visual pasien..."
                            required
                            class="w-full px-3.5 py-2.5 bg-white border @error('catatan') border-rose-400 ring-1 ring-rose-400 @else border-slate-300 @enderror rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
                        >{{ old('catatan', $pemeriksaanAwal->catatan ?? '') }}</textarea>
                        @error('catatan')
                            <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kotak Informasi Integrasi Objective (Sesuai Mockup) -->
                    <div class="p-3.5 rounded-xl bg-sky-50/80 border border-sky-200 text-xs text-sky-800 flex items-start gap-2.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <span>Data TTV akan otomatis masuk ke bagian <strong>Objective</strong> rekam medis dokter.</span>
                    </div>

                    <!-- Tombol Aksi Simpan -->
                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            id="btnSimpanPemeriksaan"
                            class="inline-flex items-center gap-2 py-2.5 px-6 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-sky-600/25 hover:shadow-sky-600/35 transition-all cursor-pointer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            <span>Simpan data pemeriksaan</span>
                        </button>
                    </div>

                </form>
            @else
                <div class="py-16 text-center text-slate-400 space-y-2">
                    <p class="text-sm font-medium">Tidak ada pasien yang dipilih.</p>
                    <p class="text-xs">Silakan pilih salah satu pasien dari daftar antrean di sebelah kiri.</p>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>
@endsection

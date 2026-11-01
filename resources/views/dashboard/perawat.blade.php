@extends('layouts.app')

@section('title', 'Nurse Station - Input Pemeriksaan Awal & TTV - MedScribe AI')

@section('content')
@php
    // Centang di daftar antrean = TTV pasien sudah diisi perawat (status apa pun setelah "Menunggu TTV").
    $ttvBelum = ['Menunggu TTV', 'Dibatalkan'];
    $ttvLengkapCount = $daftarAntrean->whereNotIn('status', $ttvBelum)->count();
@endphp
<div class="space-y-6">

    {{-- Judul halaman + status pemeriksaan + navigasi tab --}}
    <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-5 rise">
        <div>
            <p class="eyebrow flex items-center gap-2">
                Pemeriksaan Awal
                <span aria-hidden="true">·</span>
                @if (session('saved_pemeriksaan'))
                    <span class="status status-ok">Pemeriksaan Berhasil Disimpan</span>
                @elseif ($pasienTerpilih && $pasienTerpilih->status === 'Siap Periksa')
                    <span class="status status-active">TTV Terverifikasi</span>
                @else
                    <span class="status status-warn">Menunggu Input TTV</span>
                @endif
            </p>
            <h1 class="mt-2 text-[2.2rem] sm:text-[2.9rem] leading-[1.04] font-semibold tracking-[-0.045em] text-medical-navy">
                Nurse <span class="text-royal-indigo">Station</span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <nav aria-label="Tampilan Nurse Station">
                <div class="pill-nav">
                    <a href="{{ route('perawat.dashboard', ['tab' => 'antrean']) }}" @if ($activeTab === 'antrean') aria-current="page" @endif>Antrean Aktif</a>
                    <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}" @if ($activeTab === 'pasien') aria-current="page" @endif>Daftar Pasien</a>
                </div>
            </nav>
            <button type="button" onclick="openModalInputPasien()" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Input Pasien
            </button>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
            <div>
                <p class="font-semibold">Mohon periksa kembali isian formulir:</p>
                <ul class="mt-1 list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($activeTab === 'antrean')
    {{-- ========== TAB ANTREAN ========== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Daftar antrean: kartu navy dengan lingkaran centang per pasien --}}
        <section class="card-navy spotlight lg:col-span-4 flex flex-col rise" style="--i: 1" aria-labelledby="judulDaftarAntrean">
            <div class="relative p-5 pb-4">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="judulDaftarAntrean" class="text-base font-semibold">Daftar Antrean</h2>
                    <p class="text-[2rem] leading-none font-semibold tracking-tight num" title="Pasien dengan TTV lengkap dari total antrean">
                        {{ $ttvLengkapCount }}<span class="text-[#A5ADD8]">/{{ $daftarAntrean->count() }}</span>
                    </p>
                </div>
                <p class="mt-1 text-xs text-[#A5ADD8]">{{ $daftarAntrean->count() }} Pasien · {{ $ttvLengkapCount }} TTV sudah diisi</p>

                {{-- Keterangan arti lingkaran di sisi kanan setiap pasien --}}
                <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-[#CBD1EA]">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded-full bg-royal-indigo flex items-center justify-center" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        TTV sudah diisi
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded-full border-2 border-white/50" aria-hidden="true"></span>
                        Menunggu TTV
                    </span>
                </p>

                <form method="GET" action="{{ route('perawat.dashboard') }}" class="mt-4" role="search">
                    <input type="hidden" name="tab" value="antrean">
                    @if (request('antrean_id'))
                        <input type="hidden" name="antrean_id" value="{{ request('antrean_id') }}">
                    @endif
                    <label for="cariPasien" class="sr-only">Cari pasien</label>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#A5ADD8] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="search" id="cariPasien" name="cari" value="{{ request('cari') }}" placeholder="Nama atau No. RM"
                               class="w-full min-h-11 pl-10 pr-10 rounded-full bg-white/[0.08] border border-white/15 text-sm text-white placeholder-[#A5ADD8] transition-colors hover:bg-white/[0.12] focus:outline-none focus:bg-white/[0.14] focus:border-[#A5ADD8]">
                        @if (request('cari'))
                            <a href="{{ route('perawat.dashboard', array_filter(['tab' => 'antrean', 'antrean_id' => request('antrean_id')])) }}"
                               class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full flex items-center justify-center text-[#A5ADD8] hover:text-white hover:bg-white/10" aria-label="Hapus pencarian">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <ul class="relative px-3 pb-3 space-y-1.5 max-h-80 lg:max-h-[560px] overflow-y-auto [scrollbar-color:rgba(255,255,255,0.2)_transparent]">
                @forelse ($daftarAntrean as $antrean)
                    @php
                        $isSelected = $pasienTerpilih && ($pasienTerpilih->id_antrean === $antrean->id_antrean);
                        $p = $antrean->pasien;
                        $usia = $p?->umur ?: ($p?->tanggal_lahir ? \Carbon\Carbon::parse($p->tanggal_lahir)->age : null);
                        $jk = $p?->jenis_kelamin === 'Perempuan' ? 'P' : ($p?->jenis_kelamin ? 'L' : null);
                        $siap = $antrean->status === 'Siap Periksa';
                        $ttvLengkap = !in_array($antrean->status, $ttvBelum);
                    @endphp
                    <li>
                        <a href="{{ route('perawat.dashboard', array_filter(['antrean_id' => $antrean->id_antrean, 'cari' => request('cari'), 'tab' => 'antrean'])) }}"
                           @if ($isSelected) aria-current="true" @endif
                           class="group flex items-center gap-3 p-3 rounded-[18px] border transition-all duration-200 {{ $isSelected ? 'bg-white text-medical-navy border-white shadow-[0_10px_24px_-12px_rgba(0,0,0,0.5)]' : 'border-transparent hover:bg-white/[0.08] hover:border-white/10' }}">
                            <span class="avatar w-9 h-9 font-mono text-[11px] {{ $isSelected ? 'bg-pale-lavender text-royal-indigo-hover' : 'bg-white/10 text-white' }}" aria-hidden="true">
                                {{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-semibold truncate">{{ $p->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}</span>
                                <span class="block text-[11px] mt-0.5 {{ $isSelected ? 'text-text-secondary' : 'text-[#A5ADD8]' }}">
                                    A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                                    · {{ $usia ? $usia . ' th' : '-' }}{{ $jk ? ' · ' . $jk : '' }}
                                    · {{ $siap ? 'Siap dipanggil dokter' : $antrean->status }}
                                </span>
                            </span>
                            {{-- Lingkaran terisi = TTV sudah diisi, kosong = menunggu TTV (keterangan ada di atas daftar) --}}
                            <span class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 transition-colors {{ $ttvLengkap ? 'bg-royal-indigo border-royal-indigo text-white' : ($isSelected ? 'border-border-hover' : 'border-white/40 group-hover:border-white/70') }}"
                                  title="{{ $ttvLengkap ? 'TTV sudah diisi' : 'Menunggu TTV' }}">
                                <span class="sr-only">{{ $ttvLengkap ? 'TTV sudah diisi' : 'Menunggu TTV' }}</span>
                                @if ($ttvLengkap)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @endif
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-3 py-10 text-center text-xs text-[#A5ADD8]">Tidak ada pasien yang cocok dengan pencarian.</li>
                @endforelse
            </ul>

            <p class="relative mt-auto px-5 py-4 border-t border-white/10 text-[11px] text-[#A5ADD8] text-center">Antrean diperbarui otomatis setelah data disimpan.</p>
        </section>

        {{-- Formulir pemeriksaan awal --}}
        <section class="lg:col-span-8 space-y-5 rise" style="--i: 2" aria-labelledby="judulFormTTV">
            @if ($pasienTerpilih)
                @php
                    $pt = $pasienTerpilih->pasien;
                    $usiaLengkap = $pt?->umur ?: ($pt?->tanggal_lahir ? \Carbon\Carbon::parse($pt->tanggal_lahir)->age : null);
                    $rsPasien = $pasienTerpilih->hospital->hospital_name ?? ($user->hospital->hospital_name ?? '-');
                    $suhuCurrent = (float) old('suhu_tubuh', $pemeriksaanAwal->suhu_tubuh ?? 0);
                @endphp

                {{-- Kartu identitas pasien --}}
                <div class="card-lavender spotlight p-5 sm:p-6">
                    <div class="relative flex flex-col xl:flex-row xl:items-center gap-5">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                        <span class="avatar w-16 h-16 sm:w-20 sm:h-20 text-2xl sm:text-3xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                            {{ strtoupper(substr($pt->nama_pasien ?? 'P', 0, 1)) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <h2 id="judulFormTTV" class="text-xs font-medium text-royal-indigo-hover">Formulir Pemeriksaan Awal &amp; TTV</h2>
                            <p class="mt-0.5 text-2xl font-semibold tracking-tight text-medical-navy">{{ $pt->nama_pasien ?? 'Pasien' }}</p>
                            <p class="text-sm text-text-body/80">{{ $usiaLengkap ? $usiaLengkap . ' tahun' : '-' }} · {{ $pt?->jenis_kelamin ?? '-' }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="tag-id bg-white">No. RM {{ $pt->no_rekam_medis ?? '-' }}</span>
                                <span class="tag-id tag-id-muted bg-white/80">{{ $idKunjunganCode }}</span>
                            </div>
                        </div>
                        </div>
                        <div class="flex flex-wrap xl:flex-col xl:items-end gap-2">
                            @include('partials.queue-status', [
                                'status' => $pasienTerpilih->status,
                                'statusText' => $pasienTerpilih->status === 'Siap Periksa' ? 'Siap Dipanggil Dokter' : null,
                            ])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/80 border border-border-hover text-[11px] text-medical-navy">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                {{ $rsPasien }} · Terkunci ke Faskes Pasien
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('perawat.pemeriksaan.store') }}" id="formTTV" class="card p-5 sm:p-7 space-y-6" data-submit-loading>
                    @csrf
                    <input type="hidden" name="id_antrean" value="{{ $pasienTerpilih->id_antrean }}">
                    <input type="hidden" name="cari_filter" value="{{ request('cari') }}">

                    <fieldset>
                        <legend class="text-lg font-semibold text-medical-navy">Tanda-tanda vital</legend>
                        <p class="mt-0.5 mb-5 text-xs text-text-secondary">Semua field bertanda * wajib diisi. Masukkan angka sesuai satuan standar klinis.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-4">
                            @include('partials.vital-input', ['id' => 'tekanan_darah', 'name' => 'tekanan_darah', 'label' => 'Tekanan Darah', 'unit' => 'mmHg', 'type' => 'text', 'placeholder' => '120/80', 'value' => old('tekanan_darah', $pemeriksaanAwal->tekanan_darah ?? '')])
                            @include('partials.vital-input', ['id' => 'nadi', 'name' => 'nadi', 'label' => 'Denyut Nadi', 'unit' => 'x/menit', 'placeholder' => '78', 'min' => 30, 'max' => 250, 'value' => old('nadi', $pemeriksaanAwal->nadi ?? '')])
                            <div>
                                @include('partials.vital-input', ['id' => 'suhu_tubuh', 'name' => 'suhu_tubuh', 'label' => 'Suhu Tubuh', 'unit' => '°C', 'placeholder' => '36.5', 'step' => '0.1', 'min' => 30, 'max' => 45, 'value' => old('suhu_tubuh', $pemeriksaanAwal->suhu_tubuh ?? '')])
                                <p id="suhuWarning" class="field-error flex items-center gap-1.5 {{ $suhuCurrent > 37.5 ? '' : 'hidden' }}" aria-live="polite">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    Peringatan klinis: Suhu di atas rentang normal (&gt;37.5°C)
                                </p>
                            </div>
                            @include('partials.vital-input', ['id' => 'laju_pernapasan', 'name' => 'laju_pernapasan', 'label' => 'Laju Pernapasan', 'unit' => 'x/menit', 'placeholder' => '18', 'min' => 5, 'max' => 60, 'value' => old('laju_pernapasan', $pemeriksaanAwal->laju_pernapasan ?? '')])
                            @include('partials.vital-input', ['id' => 'berat_badan', 'name' => 'berat_badan', 'label' => 'Berat Badan', 'unit' => 'kg', 'placeholder' => '68', 'step' => '0.1', 'min' => 1, 'max' => 300, 'value' => old('berat_badan', $pemeriksaanAwal->berat_badan ?? '')])
                            @include('partials.vital-input', ['id' => 'tinggi_badan', 'name' => 'tinggi_badan', 'label' => 'Tinggi Badan', 'unit' => 'cm', 'placeholder' => '170', 'step' => '0.1', 'min' => 30, 'max' => 250, 'value' => old('tinggi_badan', $pemeriksaanAwal->tinggi_badan ?? '')])
                        </div>
                    </fieldset>

                    <div>
                        <label for="catatan" class="field-label">Catatan Awal Perawat <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="catatan" name="catatan" rows="3" required
                                  placeholder="Tuliskan keluhan utama awal, riwayat singkat, atau observasi visual pasien..."
                                  class="field leading-relaxed @error('catatan') is-invalid @enderror">{{ old('catatan', $pemeriksaanAwal->catatan ?? '') }}</textarea>
                        @error('catatan')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-4 pt-5 border-t border-border-subtle">
                        <p class="flex items-start gap-2 text-xs text-text-secondary max-w-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-royal-indigo flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Data tanda-tanda vital di atas akan tersimpan dan terintegrasi secara langsung ke berkas rekam medis dokter.
                        </p>
                        @include('partials.submit-button', ['id' => 'btnSimpanPemeriksaan', 'label' => 'Simpan data pemeriksaan', 'loading' => 'Menyimpan...', 'class' => 'w-full sm:w-auto px-6'])
                    </div>
                </form>
            @else
                <div class="card px-6 py-20 text-center">
                    <span class="mx-auto avatar w-14 h-14 bg-pale-lavender text-royal-indigo" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </span>
                    <h2 id="judulFormTTV" class="mt-4 text-base font-semibold text-medical-navy">Tidak ada pasien yang dipilih</h2>
                    <p class="mt-1 text-xs text-text-secondary">Silakan pilih salah satu pasien dari daftar antrean untuk membuka Formulir Pemeriksaan Awal &amp; TTV.</p>
                </div>
            @endif
        </section>
    </div>

    @else
    {{-- ========== TAB DAFTAR PASIEN ========== --}}
    @php
        $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $adaFilter = $searchPasien || $filterTahun || $filterBulan || $filterTanggal;
    @endphp
    <section class="card overflow-hidden rise" style="--i: 1" aria-labelledby="judulDaftarPasien">
        <div class="p-5 sm:p-6 space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
                <div>
                    <h2 id="judulDaftarPasien" class="text-lg font-semibold text-medical-navy">Daftar Pasien Terdaftar</h2>
                    <p class="text-xs text-text-secondary mt-0.5">Pencarian dan filter berdasarkan tanggal registrasi pasien.</p>
                </div>

                <form method="GET" action="{{ route('perawat.dashboard') }}" id="formFilterPasien" class="grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-3" role="search">
                    <input type="hidden" name="tab" value="pasien">

                    <div class="col-span-2 sm:w-60">
                        <label for="cariPasienList" class="field-label">Cari pasien</label>
                        <div class="field-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input type="search" id="cariPasienList" name="cari_pasien" value="{{ $searchPasien }}" placeholder="Cari nama atau ID pasien..." class="field">
                        </div>
                    </div>

                    <div class="sm:w-28">
                        <label for="filterTahun" class="field-label">Tahun</label>
                        <select id="filterTahun" name="tahun" onchange="this.form.submit()" class="field">
                            <option value="">Semua</option>
                            @foreach ($tahunTersedia as $thn)
                                <option value="{{ $thn }}" {{ $filterTahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-36">
                        <label for="filterBulan" class="field-label">Bulan</label>
                        <select id="filterBulan" name="bulan" onchange="this.form.submit()" class="field">
                            <option value="">Semua</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $filterBulan == $i ? 'selected' : '' }}>{{ $namaBulan[$i-1] }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="sm:w-28">
                        <label for="filterTanggal" class="field-label">Tanggal</label>
                        <select id="filterTanggal" name="tanggal" onchange="this.form.submit()" class="field">
                            <option value="">Semua</option>
                            @for ($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}" {{ $filterTanggal == $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-navy">Cari</button>
                        @if ($adaFilter)
                            <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}" class="btn btn-soft">Hapus Filter</a>
                        @endif
                    </div>
                </form>
            </div>

            @if ($adaFilter)
                <div class="flex flex-wrap items-center gap-2 text-xs text-text-secondary">
                    <span>Filter aktif:</span>
                    @if ($searchPasien)<span class="status status-active">"{{ $searchPasien }}"</span>@endif
                    @if ($filterTahun)<span class="status status-active">Tahun {{ $filterTahun }}</span>@endif
                    @if ($filterBulan)<span class="status status-active">{{ $namaBulan[$filterBulan - 1] ?? '' }}</span>@endif
                    @if ($filterTanggal)<span class="status status-active">Tanggal {{ $filterTanggal }}</span>@endif
                    <span aria-hidden="true">·</span>
                    <span><span class="font-semibold text-medical-navy num">{{ $daftarPasien->total() }}</span> hasil ditemukan</span>
                </div>
            @endif
        </div>

        <div class="relative overflow-x-auto border-t border-border-subtle">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID Pasien</th>
                        <th scope="col">Nama Lengkap</th>
                        <th scope="col">Umur</th>
                        <th scope="col" class="hidden lg:table-cell">Jenis Kelamin</th>
                        <th scope="col" class="hidden xl:table-cell">Tgl. Registrasi</th>
                        <th scope="col">TTV Terakhir</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarPasien as $pasien)
                        @php
                            $latestKunjungan = $pasien->kunjungan()->with('pemeriksaanAwal')->orderBy('id_kunjungan', 'desc')->first();
                            $ttvDate = $latestKunjungan?->pemeriksaanAwal?->created_at;
                            $umurPasien = $pasien->umur ?: ($pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->age : null);
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap"><span class="tag-id">{{ $pasien->no_rekam_medis }}</span></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('perawat.pasien.show', $pasien->id_pasien) }}" class="flex items-center gap-3 group">
                                    <span class="avatar w-8 h-8 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:text-white" aria-hidden="true">{{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}</span>
                                    <span class="font-semibold text-medical-navy group-hover:text-royal-indigo-hover transition-colors">{{ $pasien->nama_pasien }}</span>
                                </a>
                            </td>
                            <td class="text-text-secondary whitespace-nowrap num">{{ $umurPasien ? $umurPasien . ' tahun' : '-' }}</td>
                            <td class="whitespace-nowrap hidden lg:table-cell">
                                @if ($pasien->jenis_kelamin === 'Laki-laki')
                                    <span class="status status-active">Laki-laki</span>
                                @elseif ($pasien->jenis_kelamin === 'Perempuan')
                                    <span class="status status-danger">Perempuan</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-text-secondary whitespace-nowrap num hidden xl:table-cell">
                                {{ $pasien->created_at ? $pasien->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB' : '-' }}
                            </td>
                            <td class="whitespace-nowrap num">
                                @if ($ttvDate)
                                    <span class="font-medium text-medical-navy">{{ $ttvDate->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                                @else
                                    <span class="text-text-secondary">Belum ada</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('perawat.pasien.show', $pasien->id_pasien) }}" class="btn btn-secondary btn-sm" title="Lihat Detail & Riwayat Pasien">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Detail<span class="sr-only"> {{ $pasien->nama_pasien }}</span>
                                    </a>
                                    <button type="button" onclick="openModalHapusPasien({{ $pasien->id_pasien }}, '{{ addslashes($pasien->nama_pasien) }}', '{{ $pasien->no_rekam_medis }}')"
                                            class="btn btn-danger-quiet btn-sm" title="Hapus / Batalkan Registrasi Pasien">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        <span class="hidden sm:inline">Hapus</span><span class="sr-only"> registrasi {{ $pasien->nama_pasien }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="!py-14 text-center">
                                <span class="mx-auto avatar w-12 h-12 bg-pale-lavender text-royal-indigo" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-medical-navy">Tidak ada pasien ditemukan</p>
                                <p class="mt-1 text-xs text-text-secondary">Coba ubah kata kunci pencarian atau filter tanggal.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftarPasien->hasPages())
            <div class="px-5 sm:px-6 py-4 border-t border-border-subtle">
                {{ $daftarPasien->links('pagination.medscribe') }}
            </div>
        @endif
    </section>
    @endif
</div>

{{-- ========== DIALOG: Input Pasien Baru + TTV ========== --}}
<div id="modalInputPasien" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalInputTitle">
    <div class="dialog-backdrop" onclick="closeModalInputPasien()"></div>

    <div class="fixed inset-0 overflow-y-auto pointer-events-none">
        <div class="min-h-full flex items-start justify-center px-4 py-8 sm:py-12">
            <div class="dialog w-full max-w-2xl pointer-events-auto animate-dialog">
                <div class="flex items-start justify-between gap-4 p-6 sm:px-8">
                    <div class="flex items-center gap-3">
                        <span class="avatar w-11 h-11 bg-gradient-to-br from-medical-navy to-royal-indigo text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        </span>
                        <div>
                            <h2 id="modalInputTitle" class="text-lg font-semibold text-medical-navy">Input Pasien Baru</h2>
                            <p class="text-xs text-text-secondary">Registrasi pasien dan pengukuran TTV awal</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeModalInputPasien()" class="btn btn-ghost btn-icon -mr-2" aria-label="Tutup dialog">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="mx-6 sm:mx-8 alert alert-info">
                    <svg xmlns="http://www.w3.org/2000/svg" class="text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>ID Pasien akan digenerate otomatis oleh sistem dalam format <strong class="font-mono text-royal-indigo-hover">YYYYMMDD-NNN</strong> secara atomik setelah data berhasil disimpan.</span>
                </div>

                <form method="POST" action="{{ route('perawat.pasien.store') }}" id="formPasienBaru" class="p-6 sm:px-8 space-y-6" data-submit-loading>
                    @csrf

                    <fieldset>
                        <legend class="flex items-center gap-2.5 text-sm font-semibold text-medical-navy">
                            <span class="avatar w-7 h-7 bg-medical-navy text-white text-xs" aria-hidden="true">1</span>
                            Data Identitas Pasien
                        </legend>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="modal_nama_pasien" class="field-label">Nama Lengkap <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" id="modal_nama_pasien" name="nama_pasien" required autocomplete="off"
                                       value="{{ old('nama_pasien') }}" placeholder="Masukkan nama lengkap pasien"
                                       class="field @error('nama_pasien') is-invalid @enderror">
                                @error('nama_pasien')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            @include('partials.vital-input', ['id' => 'modal_umur', 'name' => 'umur', 'label' => 'Umur', 'unit' => 'tahun', 'placeholder' => '0', 'min' => 0, 'max' => 120, 'value' => old('umur')])

                            <div>
                                <label for="modal_jenis_kelamin" class="field-label">Jenis Kelamin <span class="req" aria-hidden="true">*</span></label>
                                <select id="modal_jenis_kelamin" name="jenis_kelamin" required class="field @error('jenis_kelamin') is-invalid @enderror">
                                    <option value="" disabled {{ old('jenis_kelamin') ? '' : 'selected' }}>Pilih jenis kelamin</option>
                                    <option value="Laki-laki" {{ old('jenis_kelamin') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ old('jenis_kelamin') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('jenis_kelamin')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                @if ($user->hospital_id)
                                    <label for="modal_hospital_nama" class="field-label">Rumah Sakit <span class="req" aria-hidden="true">*</span></label>
                                    <input type="hidden" name="hospital_id" value="{{ $user->hospital_id }}">
                                    <div class="field-unit">
                                        <input type="text" id="modal_hospital_nama" readonly value="{{ $user->hospital->hospital_name ?? 'Rumah Sakit Terdaftar' }}" class="field">
                                        <span class="unit !text-royal-indigo-hover">Faskes Anda</span>
                                    </div>
                                    <p class="field-hint">Sesuai penugasan perawat, pasien didaftarkan pada unit rumah sakit Anda.</p>
                                @else
                                    <label for="modal_hospital_id" class="field-label">Rumah Sakit <span class="req" aria-hidden="true">*</span></label>
                                    <select id="modal_hospital_id" name="hospital_id" required class="field @error('hospital_id') is-invalid @enderror">
                                        <option value="" disabled selected>Pilih Rumah Sakit (Kota Surabaya)...</option>
                                        @foreach ($activeHospitals ?? [] as $h)
                                            <option value="{{ $h->id }}" {{ (string) old('hospital_id') === (string) $h->id ? 'selected' : '' }}>{{ $h->hospital_name }} ({{ $h->hospital_type ?? 'RSU' }})</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('hospital_id')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>

                    <div class="pt-6 border-t border-border-subtle">
                        <fieldset>
                            <legend class="flex items-center gap-2.5 text-sm font-semibold text-medical-navy">
                                <span class="avatar w-7 h-7 bg-medical-navy text-white text-xs" aria-hidden="true">2</span>
                                Tanda-Tanda Vital (TTV)
                            </legend>
                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @include('partials.vital-input', ['id' => 'modal_td_sistolik', 'name' => 'tekanan_darah_sistolik', 'label' => 'TD Sistolik', 'unit' => 'mmHg', 'placeholder' => '120', 'min' => 50, 'max' => 300, 'value' => old('tekanan_darah_sistolik')])
                                @include('partials.vital-input', ['id' => 'modal_td_diastolik', 'name' => 'tekanan_darah_diastolik', 'label' => 'TD Diastolik', 'unit' => 'mmHg', 'placeholder' => '80', 'min' => 20, 'max' => 200, 'value' => old('tekanan_darah_diastolik')])
                                @include('partials.vital-input', ['id' => 'modal_nadi', 'name' => 'nadi', 'label' => 'Denyut Nadi', 'unit' => 'x/menit', 'placeholder' => '78', 'min' => 30, 'max' => 250, 'value' => old('nadi')])
                                <div>
                                    @include('partials.vital-input', ['id' => 'modal_suhu', 'name' => 'suhu_tubuh', 'label' => 'Suhu Tubuh', 'unit' => '°C', 'placeholder' => '36.5', 'step' => '0.1', 'min' => 30, 'max' => 45, 'value' => old('suhu_tubuh')])
                                    <p id="modalSuhuWarning" class="field-error items-center gap-1.5 hidden" aria-live="polite">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        Peringatan: Suhu di atas rentang normal (&gt;37.5°C)
                                    </p>
                                </div>
                                @include('partials.vital-input', ['id' => 'modal_rr', 'name' => 'laju_pernapasan', 'label' => 'Laju Pernapasan', 'unit' => 'x/menit', 'placeholder' => '18', 'min' => 5, 'max' => 60, 'value' => old('laju_pernapasan')])
                                @include('partials.vital-input', ['id' => 'modal_spo2', 'name' => 'saturasi_oksigen', 'label' => 'SpO2', 'unit' => '%', 'placeholder' => '98', 'min' => 50, 'max' => 100, 'value' => old('saturasi_oksigen')])
                                @include('partials.vital-input', ['id' => 'modal_bb', 'name' => 'berat_badan', 'label' => 'Berat Badan', 'unit' => 'kg', 'placeholder' => '68', 'step' => '0.1', 'min' => 1, 'max' => 300, 'required' => false, 'value' => old('berat_badan')])
                                @include('partials.vital-input', ['id' => 'modal_tb', 'name' => 'tinggi_badan', 'label' => 'Tinggi Badan', 'unit' => 'cm', 'placeholder' => '170', 'step' => '0.1', 'min' => 30, 'max' => 250, 'required' => false, 'value' => old('tinggi_badan')])
                            </div>
                        </fieldset>
                    </div>

                    <div>
                        <label for="modal_catatan" class="field-label">Catatan Awal</label>
                        <textarea id="modal_catatan" name="catatan" rows="2" placeholder="Keluhan utama, riwayat singkat, atau observasi visual pasien (opsional)..." class="field leading-relaxed">{{ old('catatan') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-5 border-t border-border-subtle">
                        <button type="button" onclick="closeModalInputPasien()" class="btn btn-secondary">Batal</button>
                        @include('partials.submit-button', ['id' => 'btnSimpanPasienBaru', 'label' => 'Simpan Pasien', 'loading' => 'Menyimpan...', 'class' => 'px-6'])
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('partials.modal-hapus-pasien')
@endsection

@section('scripts')
<script>
    window.openModalInputPasien = function () {
        const modal = document.getElementById('modalInputPasien');
        window.__inputReturnFocus = document.activeElement;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        document.getElementById('modal_nama_pasien')?.focus();
    };

    window.closeModalInputPasien = function () {
        const modal = document.getElementById('modalInputPasien');
        if (!modal || modal.classList.contains('hidden')) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        window.__inputReturnFocus?.focus?.();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModalInputPasien();
    });

    document.addEventListener('DOMContentLoaded', function () {
        function watchSuhu(inputId, warningId) {
            const input = document.getElementById(inputId);
            const warning = document.getElementById(warningId);
            if (!input || !warning) return;
            const check = function () {
                const val = parseFloat(input.value);
                const high = !isNaN(val) && val > 37.5;
                warning.classList.toggle('hidden', !high);
                warning.classList.toggle('flex', high);
            };
            input.addEventListener('input', check);
            input.addEventListener('change', check);
        }
        watchSuhu('suhu_tubuh', 'suhuWarning');
        watchSuhu('modal_suhu', 'modalSuhuWarning');

        // Jumlah pilihan tanggal mengikuti bulan dan tahun yang dipilih.
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
                const currentOptions = filterTanggal.options.length - 1;

                while (filterTanggal.options.length > daysInMonth + 1) {
                    filterTanggal.remove(filterTanggal.options.length - 1);
                }
                for (let d = currentOptions + 1; d <= daysInMonth; d++) {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.text = d;
                    filterTanggal.add(opt);
                }
                if (currentDay > daysInMonth) {
                    filterTanggal.value = '';
                }
            }

            filterBulan.addEventListener('change', updateDayOptions);
            filterTahun.addEventListener('change', updateDayOptions);
        }

        @if ($errors->hasAny(['nama_pasien', 'umur', 'jenis_kelamin', 'tekanan_darah_sistolik', 'tekanan_darah_diastolik', 'saturasi_oksigen']))
            openModalInputPasien();
        @endif
    });
</script>
@endsection

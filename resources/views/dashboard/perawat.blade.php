@extends('layouts.app')

@section('title', 'Nurse Station - Input Pemeriksaan Awal & TTV - MedScribe AI')

@section('content')
@php
    // Centang di daftar antrean = TTV pasien sudah diisi perawat (status apa pun setelah "Menunggu TTV").
    $ttvBelum = ['Menunggu TTV', 'Dibatalkan'];
    $ttvLengkapCount = $daftarAntrean->whereNotIn('status', $ttvBelum)->count();
@endphp
<div class="space-y-6">

    {{-- Judul halaman + navigasi tab. Status pasien terpilih ada di kartu identitas, hasil simpan di notifikasi. --}}
    <x-page-header :eyebrow="'Nurse Station · ' . \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y')"
                   title="Pemeriksaan Awal & TTV"
                   subtitle="Pilih pasien dari antrean, lalu isi tanda-tanda vitalnya sebelum diperiksa dokter.">
        <nav aria-label="Tampilan Nurse Station" class="pill-nav-scroll">
            <div class="pill-nav">
                <a href="{{ route('perawat.dashboard', ['tab' => 'antrean']) }}" @if ($activeTab === 'antrean') aria-current="page" @endif>Antrean Aktif</a>
                <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}" @if ($activeTab === 'pasien') aria-current="page" @endif>Daftar Pasien</a>
            </div>
        </nav>
        <button type="button" data-dialog-open="modalInputPasien" class="btn btn-primary">
            <x-icon name="plus" class="w-4 h-4" aria-hidden="true" />
            Input Pasien
        </button>
    </x-page-header>

    @include('partials.form-errors')

    @if ($activeTab === 'antrean')
    {{-- ========== TAB ANTREAN ========== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Daftar antrean: kartu navy dengan lingkaran centang per pasien --}}
        <section class="card-navy spotlight lg:col-span-4 flex flex-col rise" style="--i: 1" aria-labelledby="judulDaftarAntrean">
            <div class="relative p-5 pb-4">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="judulDaftarAntrean" class="text-base font-semibold">Daftar Antrean</h2>
                    <p class="text-[2rem] leading-none font-semibold tracking-tight num" title="Pasien dengan TTV lengkap dari total antrean">
                        {{ $ttvLengkapCount }}<span class="text-on-navy-muted">/{{ $daftarAntrean->count() }}</span>
                    </p>
                </div>
                <p class="mt-1 text-xs text-on-navy-muted">{{ $daftarAntrean->count() }} Pasien · {{ $ttvLengkapCount }} TTV sudah diisi</p>

                {{-- Keterangan arti lingkaran di sisi kanan setiap pasien --}}
                <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-on-navy-soft">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded-full bg-royal-indigo flex items-center justify-center" aria-hidden="true">
                            <x-icon name="check" class="w-2.5 h-2.5 text-white" />
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
                    <div class="field-icon">
                        <x-icon name="search" aria-hidden="true" />
                        <input type="search" id="cariPasien" name="cari" value="{{ request('cari') }}" placeholder="Nama atau No. RM"
                               class="field field-on-navy {{ request('cari') ? 'pr-12' : '' }}">
                        @if (request('cari'))
                            <a href="{{ route('perawat.dashboard', array_filter(['tab' => 'antrean', 'antrean_id' => request('antrean_id')])) }}"
                               class="absolute right-0 top-1/2 -translate-y-1/2 w-11 h-11 rounded-(--radius-field) flex items-center justify-center text-on-navy-muted transition-colors hover:text-white hover:bg-white/10" aria-label="Hapus pencarian">
                                <x-icon name="x" class="w-4 h-4" aria-hidden="true" />
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
                        $usia = $p?->usia;
                        $jk = $p?->jenis_kelamin === 'Perempuan' ? 'P' : ($p?->jenis_kelamin ? 'L' : null);
                        $siap = $antrean->status === 'Siap Periksa';
                        $ttvLengkap = !in_array($antrean->status, $ttvBelum);
                    @endphp
                    <li>
                        <a href="{{ route('perawat.dashboard', array_filter(['antrean_id' => $antrean->id_antrean, 'cari' => request('cari'), 'tab' => 'antrean'])) }}"
                           @if ($isSelected) aria-current="true" @endif
                           class="group flex items-center gap-3 p-3 rounded-(--radius-panel) border transition-all duration-200 {{ $isSelected ? 'bg-white text-medical-navy border-white shadow-[0_10px_24px_-12px_rgba(0,0,0,0.5)]' : 'border-transparent hover:bg-white/[0.08] hover:border-white/10' }}">
                            <span class="avatar w-9 h-9 font-mono text-[11px] {{ $isSelected ? 'bg-pale-lavender text-royal-indigo-hover' : 'bg-white/10 text-white' }}" aria-hidden="true">
                                {{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-semibold truncate">{{ $p->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}</span>
                                <span class="block text-[11px] mt-0.5 {{ $isSelected ? 'text-text-secondary' : 'text-on-navy-muted' }}">
                                    A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                                    · {{ $usia !== null ? $usia . ' th' : '-' }}{{ $jk ? ' · ' . $jk : '' }}
                                    · {{ $siap ? 'Siap dipanggil dokter' : $antrean->status }}
                                </span>
                            </span>
                            {{-- Lingkaran terisi = TTV sudah diisi, kosong = menunggu TTV (keterangan ada di atas daftar) --}}
                            <span class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 transition-colors {{ $ttvLengkap ? 'bg-royal-indigo border-royal-indigo text-white' : ($isSelected ? 'border-border-hover' : 'border-white/40 group-hover:border-white/70') }}"
                                  title="{{ $ttvLengkap ? 'TTV sudah diisi' : 'Menunggu TTV' }}">
                                <span class="sr-only">{{ $ttvLengkap ? 'TTV sudah diisi' : 'Menunggu TTV' }}</span>
                                @if ($ttvLengkap)
                                    <x-icon name="check" class="w-3.5 h-3.5" />
                                @endif
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-3 py-10 text-center text-xs text-on-navy-muted">{{ request('cari') ? 'Tidak ada pasien yang cocok dengan pencarian.' : 'Antrean masih kosong.' }}</li>
                @endforelse
            </ul>

            <p class="relative mt-auto px-5 py-4 border-t border-white/10 text-[11px] text-on-navy-muted text-center">Antrean diperbarui otomatis setelah data disimpan.</p>
        </section>

        {{-- Formulir pemeriksaan awal --}}
        <section class="lg:col-span-8 space-y-5 rise" style="--i: 2" aria-labelledby="judulFormTTV">
            @if ($pasienTerpilih)
                @php
                    $pt = $pasienTerpilih->pasien;
                    $usiaLengkap = $pt?->usia_label;
                    $rsPasien = $pasienTerpilih->hospital->hospital_name ?? ($user->hospital->hospital_name ?? '-');
                @endphp

                {{-- Kartu identitas pasien --}}
                <div class="card-lavender spotlight p-5 sm:p-6">
                    <div class="relative flex flex-col xl:flex-row xl:items-center gap-5">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                        <span class="avatar w-16 h-16 sm:w-20 sm:h-20 text-2xl sm:text-3xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                            {{ strtoupper(substr($pt->nama_pasien ?? 'P', 0, 1)) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-royal-indigo-hover">Formulir Pemeriksaan Awal &amp; TTV</p>
                            <h2 id="judulFormTTV" class="mt-0.5 text-2xl font-semibold tracking-tight text-medical-navy">{{ $pt->nama_pasien ?? 'Pasien' }}</h2>
                            <p class="text-sm text-text-body/80">{{ $usiaLengkap ?? '-' }} · {{ $pt?->jenis_kelamin ?? '-' }}</p>
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
                            <span class="chip bg-white">
                                <x-icon name="building-2" class="w-3.5 h-3.5 text-royal-indigo" aria-hidden="true" />
                                {{ $rsPasien }}
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('perawat.pemeriksaan.store') }}" id="formTTV" class="card p-5 sm:p-7 space-y-6" data-submit-loading>
                    @csrf
                    <input type="hidden" name="id_antrean" value="{{ $pasienTerpilih->id_antrean }}">
                    <input type="hidden" name="cari_filter" value="{{ request('cari') }}">

                    <fieldset>
                        <legend class="section-title">Tanda-tanda vital</legend>
                        <p class="section-subtitle mb-5">Isian bertanda * wajib diisi. Berat dan tinggi badan boleh dikosongkan kalau pasien belum bisa ditimbang.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            @include('partials.vital-input', ['id' => 'tekanan_darah', 'name' => 'tekanan_darah', 'label' => 'Tekanan Darah', 'unit' => 'mmHg', 'type' => 'text', 'placeholder' => '120/80', 'value' => old('tekanan_darah', $pemeriksaanAwal->tekanan_darah ?? '')])
                            @include('partials.vital-input', ['id' => 'nadi', 'name' => 'nadi', 'label' => 'Denyut Nadi', 'unit' => 'x/menit', 'placeholder' => '78', 'min' => 30, 'max' => 250, 'value' => old('nadi', $pemeriksaanAwal->nadi ?? '')])
                            @include('partials.vital-input', ['id' => 'suhu_tubuh', 'name' => 'suhu_tubuh', 'label' => 'Suhu Tubuh', 'unit' => '°C', 'placeholder' => '36.5', 'step' => '0.1', 'min' => 30, 'max' => 45, 'value' => old('suhu_tubuh', $pemeriksaanAwal->suhu_tubuh ?? '')])
                            @include('partials.vital-input', ['id' => 'laju_pernapasan', 'name' => 'laju_pernapasan', 'label' => 'Laju Pernapasan', 'unit' => 'x/menit', 'placeholder' => '18', 'min' => 5, 'max' => 60, 'value' => old('laju_pernapasan', $pemeriksaanAwal->laju_pernapasan ?? '')])
                            @include('partials.vital-input', ['id' => 'saturasi_oksigen', 'name' => 'saturasi_oksigen', 'label' => 'SpO2', 'unit' => '%', 'placeholder' => '98', 'min' => 50, 'max' => 100, 'value' => old('saturasi_oksigen', $pemeriksaanAwal->saturasi_oksigen ?? '')])
                            @include('partials.vital-input', ['id' => 'berat_badan', 'name' => 'berat_badan', 'label' => 'Berat Badan', 'unit' => 'kg', 'placeholder' => '68', 'step' => '0.1', 'min' => 1, 'max' => 300, 'required' => false, 'value' => old('berat_badan', $pemeriksaanAwal->berat_badan ?? '')])
                            @include('partials.vital-input', ['id' => 'tinggi_badan', 'name' => 'tinggi_badan', 'label' => 'Tinggi Badan', 'unit' => 'cm', 'placeholder' => '170', 'step' => '0.1', 'min' => 30, 'max' => 250, 'required' => false, 'value' => old('tinggi_badan', $pemeriksaanAwal->tinggi_badan ?? '')])
                        </div>
                    </fieldset>

                    <div>
                        <label for="catatan" class="field-label">Catatan perawat</label>
                        <textarea id="catatan" name="catatan" rows="3"
                                  placeholder="Keluhan utama, riwayat singkat, atau observasi visual. Kosongkan kalau pasien belum bisa ditanya."
                                  class="field leading-relaxed @error('catatan') is-invalid @enderror">{{ old('catatan', $pemeriksaanAwal->catatan ?? '') }}</textarea>
                        @error('catatan')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-4 pt-5 border-t border-border-subtle">
                        <p class="flex items-start gap-2 text-xs text-text-secondary max-w-md">
                            <x-icon name="info" class="w-4 h-4 text-royal-indigo flex-shrink-0" aria-hidden="true" />
                            Setelah disimpan, tanda-tanda vital langsung masuk ke rekam medis yang dibuka dokter.
                        </p>
                        @include('partials.submit-button', ['id' => 'btnSimpanPemeriksaan', 'label' => 'Simpan data pemeriksaan', 'loading' => 'Menyimpan...', 'class' => 'w-full sm:w-auto px-6'])
                    </div>
                </form>
            @else
                <div class="card py-10">
                    <h2 id="judulFormTTV" class="sr-only">Formulir Pemeriksaan Awal &amp; TTV</h2>
                    <x-empty-state title="Belum ada pasien yang dipilih"
                                   hint="Pilih pasien dari daftar antrean untuk mengisi tanda-tanda vitalnya."
                                   icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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
    <section class="card spotlight overflow-hidden rise" style="--i: 1" aria-labelledby="judulDaftarPasien">
        <div class="p-5 sm:p-6 space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
                <div>
                    <h2 id="judulDaftarPasien" class="section-title">Daftar Pasien Terdaftar</h2>
                    <p class="section-subtitle">Cari pasien atau saring berdasarkan tanggal registrasi.</p>
                </div>

                <form method="GET" action="{{ route('perawat.dashboard') }}" id="formFilterPasien" class="grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-3" role="search">
                    <input type="hidden" name="tab" value="pasien">

                    <div class="col-span-2 sm:w-60">
                        <label for="cariPasienList" class="field-label">Cari pasien</label>
                        <div class="field-icon">
                            <x-icon name="search" aria-hidden="true" />
                            <input type="search" id="cariPasienList" name="cari_pasien" value="{{ $searchPasien }}" placeholder="Nama atau No. RM" class="field">
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
                            <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}" class="btn btn-soft">Hapus filter</a>
                        @endif
                    </div>
                </form>
            </div>

            @if ($adaFilter)
                <div class="flex flex-wrap items-center gap-2 text-xs text-text-secondary">
                    <span>Filter aktif:</span>
                    @if ($searchPasien)<span class="chip chip-accent">"{{ $searchPasien }}"</span>@endif
                    @if ($filterTahun)<span class="chip chip-accent">Tahun {{ $filterTahun }}</span>@endif
                    @if ($filterBulan)<span class="chip chip-accent">{{ $namaBulan[$filterBulan - 1] ?? '' }}</span>@endif
                    @if ($filterTanggal)<span class="chip chip-accent">Tanggal {{ $filterTanggal }}</span>@endif
                    <span aria-hidden="true">·</span>
                    <span><span class="font-semibold text-medical-navy num">{{ $daftarPasien->total() }}</span> hasil ditemukan</span>
                </div>
            @endif
        </div>

        <div class="relative overflow-x-auto border-t border-border-subtle">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">No. RM</th>
                        <th scope="col">Nama Lengkap</th>
                        <th scope="col">Umur</th>
                        <th scope="col" class="hidden lg:table-cell">Jenis Kelamin</th>
                        <th scope="col" class="hidden xl:table-cell">Tgl. Registrasi</th>
                        <th scope="col">TTV Terakhir</th>
                        <th scope="col" class="text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarPasien as $pasien)
                        @php
                            $latestKunjungan = $pasien->kunjungan()->with('pemeriksaanAwal')->orderBy('id_kunjungan', 'desc')->first();
                            $ttvDate = $latestKunjungan?->pemeriksaanAwal?->created_at;
                            $umurPasien = $pasien->usia_label;
                        @endphp
                        <tr class="group">
                            <td class="whitespace-nowrap"><span class="tag-id">{{ $pasien->no_rekam_medis }}</span></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('perawat.pasien.show', $pasien->id_pasien) }}" class="flex items-center gap-3">
                                    <span class="avatar w-8 h-8 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:text-white" aria-hidden="true">{{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}</span>
                                    <span class="font-semibold text-medical-navy group-hover:text-royal-indigo-hover transition-colors">{{ $pasien->nama_pasien }}</span>
                                </a>
                            </td>
                            <td class="text-text-secondary whitespace-nowrap num">{{ $umurPasien ?? '-' }}</td>
                            <td class="text-text-secondary whitespace-nowrap hidden lg:table-cell">
                                {{ $pasien->jenis_kelamin ?: '-' }}
                            </td>
                            <td class="text-text-secondary whitespace-nowrap num hidden xl:table-cell">
                                {{ $pasien->created_at ? $pasien->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH:mm') . ' WIB' : '-' }}
                            </td>
                            <td class="whitespace-nowrap num">
                                @if ($ttvDate)
                                    <span class="font-medium text-medical-navy">{{ $ttvDate->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB</span>
                                @else
                                    <span class="text-text-secondary">Belum ada</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('perawat.pasien.show', $pasien->id_pasien) }}" class="btn btn-secondary btn-sm" title="Lihat Detail & Riwayat Pasien">
                                        <x-icon name="eye" class="w-3.5 h-3.5" aria-hidden="true" />
                                        Detail<span class="sr-only"> {{ $pasien->nama_pasien }}</span>
                                    </a>
                                    <a href="{{ route('perawat.pasien.show', ['id' => $pasien->id_pasien, 'edit' => 1]) }}" class="btn btn-secondary btn-sm" title="Edit identitas pasien">
                                        <x-icon name="square-pen" class="w-3.5 h-3.5" aria-hidden="true" />
                                        <span class="hidden xl:inline">Edit</span><span class="sr-only xl:hidden">Edit identitas</span><span class="sr-only"> {{ $pasien->nama_pasien }}</span>
                                    </a>
                                    <button type="button" onclick="openModalHapusPasien({{ $pasien->id_pasien }}, '{{ addslashes($pasien->nama_pasien) }}', '{{ $pasien->no_rekam_medis }}')"
                                            class="btn btn-danger-quiet btn-sm" title="Hapus / Batalkan Registrasi Pasien">
                                        <x-icon name="trash-2" class="w-3.5 h-3.5" aria-hidden="true" />
                                        <span class="hidden xl:inline">Hapus registrasi</span><span class="sr-only xl:hidden">Hapus registrasi</span><span class="sr-only"> {{ $pasien->nama_pasien }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="!p-0">
                                <x-empty-state :title="$adaFilter ? 'Tidak ada pasien yang cocok' : 'Belum ada pasien terdaftar'"
                                               :hint="$adaFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Pasien baru dari tombol Input Pasien akan tampil di sini.'"
                                               icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftarPasien->hasPages())
            <div class="px-5 sm:px-6 py-4 border-t border-border-subtle">
                {{ $daftarPasien->links(null, ['noun' => 'pasien']) }}
            </div>
        @endif
    </section>
    @endif
</div>

{{-- ========== DIALOG: Input Pasien Baru (data diri saja; TTV diisi di form antrean) ========== --}}
<div id="modalInputPasien" data-dialog class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalInputTitle">
    <div class="dialog-backdrop"></div>

    <div class="fixed inset-0 overflow-y-auto pointer-events-none">
        <div class="min-h-full flex items-start justify-center px-4 py-8 sm:py-12">
            <div class="dialog w-full max-w-2xl pointer-events-auto animate-dialog">
                <div class="flex items-start justify-between gap-4 p-6 sm:px-8">
                    <div class="flex items-center gap-3">
                        <span class="avatar w-11 h-11 bg-pale-lavender text-royal-indigo" aria-hidden="true">
                            <x-icon name="user-plus" class="w-5 h-5" />
                        </span>
                        <div>
                            <h2 id="modalInputTitle" class="section-title">Input Pasien Baru</h2>
                            <p class="section-subtitle">Isi data diri pasien. Tanda-tanda vital diisi setelah ini.</p>
                        </div>
                    </div>
                    <button type="button" data-dialog-close class="btn btn-ghost btn-icon btn-sm -mr-2" aria-label="Tutup dialog">
                        <x-icon name="x" class="w-4 h-4" aria-hidden="true" />
                    </button>
                </div>

                <div class="mx-6 sm:mx-8 alert alert-info">
                    <x-icon name="info" aria-hidden="true" />
                    <span class="text-text-body">Hanya nama yang wajib, jadi pasien darurat tetap bisa didaftarkan. Lengkapi data lain kalau identitasnya sudah ada. No. RM dibuat otomatis dengan format <span class="tag-id">YYYYMMDD-NNN</span>.</span>
                </div>

                <form method="POST" action="{{ route('perawat.pasien.store') }}" id="formPasienBaru" class="p-6 sm:px-8 space-y-6" data-submit-loading>
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="modal_nama_pasien" class="field-label">Nama lengkap <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="modal_nama_pasien" name="nama_pasien" required autocomplete="off" data-dialog-focus
                                   value="{{ old('nama_pasien') }}" placeholder="Sesuai kartu identitas"
                                   class="field @error('nama_pasien') is-invalid @enderror">
                            @error('nama_pasien')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="modal_tanggal_lahir" class="field-label">Tanggal lahir</label>
                            <input type="date" id="modal_tanggal_lahir" name="tanggal_lahir" max="{{ now('Asia/Jakarta')->toDateString() }}"
                                   value="{{ old('tanggal_lahir') }}"
                                   class="field @error('tanggal_lahir') is-invalid @enderror">
                            @error('tanggal_lahir')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            @include('partials.vital-input', ['id' => 'modal_umur', 'name' => 'umur', 'label' => 'Umur', 'unit' => 'tahun', 'placeholder' => '0', 'min' => 0, 'max' => 130, 'required' => false, 'value' => old('umur')])
                            <p class="field-hint">Terisi dari tanggal lahir. Isi perkiraan kalau tanggal lahir tidak diketahui.</p>
                        </div>

                        <div>
                            <label for="modal_jenis_kelamin" class="field-label">Jenis kelamin</label>
                            <select id="modal_jenis_kelamin" name="jenis_kelamin" class="field @error('jenis_kelamin') is-invalid @enderror">
                                <option value="">Belum diketahui</option>
                                <option value="Laki-laki" @selected(old('jenis_kelamin') === 'Laki-laki')>Laki-laki</option>
                                <option value="Perempuan" @selected(old('jenis_kelamin') === 'Perempuan')>Perempuan</option>
                            </select>
                            @error('jenis_kelamin')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="modal_no_hp" class="field-label">No. HP</label>
                            <input type="tel" id="modal_no_hp" name="no_hp" inputmode="tel" autocomplete="off" maxlength="20"
                                   value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"
                                   class="field num @error('no_hp') is-invalid @enderror">
                            @error('no_hp')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="modal_nik" class="field-label">NIK</label>
                            <input type="text" id="modal_nik" name="nik" inputmode="numeric" autocomplete="off" maxlength="16"
                                   value="{{ old('nik') }}" placeholder="16 digit"
                                   class="field num @error('nik') is-invalid @enderror">
                            @error('nik')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="modal_no_bpjs" class="field-label">No. BPJS</label>
                            <input type="text" id="modal_no_bpjs" name="no_bpjs" inputmode="numeric" autocomplete="off" maxlength="13"
                                   value="{{ old('no_bpjs') }}" placeholder="13 digit"
                                   class="field num @error('no_bpjs') is-invalid @enderror">
                            @error('no_bpjs')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            @if ($user->hospital_id)
                                <label for="modal_hospital_nama" class="field-label">Rumah sakit</label>
                                <input type="hidden" name="hospital_id" value="{{ $user->hospital_id }}">
                                <div class="field-unit">
                                    <input type="text" id="modal_hospital_nama" readonly value="{{ $user->hospital->hospital_name ?? 'Rumah Sakit Terdaftar' }}" class="field">
                                    <span class="unit !text-royal-indigo-hover">Faskes Anda</span>
                                </div>
                            @else
                                <label for="modal_hospital_id" class="field-label">Rumah sakit <span class="req" aria-hidden="true">*</span></label>
                                <select id="modal_hospital_id" name="hospital_id" required class="field @error('hospital_id') is-invalid @enderror">
                                    <option value="" disabled selected>Pilih rumah sakit</option>
                                    @foreach ($activeHospitals ?? [] as $h)
                                        <option value="{{ $h->id }}" {{ (string) old('hospital_id') === (string) $h->id ? 'selected' : '' }}>{{ $h->hospital_name }} ({{ $h->hospital_type ?? 'RSU' }})</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('hospital_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-5 border-t border-border-subtle">
                        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
                        @include('partials.submit-button', ['id' => 'btnSimpanPasienBaru', 'label' => 'Simpan dan isi TTV', 'loading' => 'Menyimpan...', 'class' => 'px-6'])
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
    document.addEventListener('DOMContentLoaded', function () {
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

        @if ($errors->hasAny(['nama_pasien', 'tanggal_lahir', 'umur', 'jenis_kelamin', 'nik', 'no_bpjs', 'no_hp', 'hospital_id']))
            window.openDialog?.('modalInputPasien');
        @endif

        const tglLahir = document.getElementById('modal_tanggal_lahir');
        const umur = document.getElementById('modal_umur');
        tglLahir?.addEventListener('change', function () {
            if (!this.value) return;
            const lahir = new Date(this.value + 'T00:00:00');
            const kini = new Date();
            let usia = kini.getFullYear() - lahir.getFullYear();
            const belumUlangTahun = kini.getMonth() < lahir.getMonth()
                || (kini.getMonth() === lahir.getMonth() && kini.getDate() < lahir.getDate());
            if (belumUlangTahun) usia--;
            if (usia >= 0) umur.value = usia;
        });

        // Spasi atau tanda hubung yang ikut tersalin dibuang dari NIK dan BPJS.
        ['modal_nik', 'modal_no_bpjs'].forEach(function (id) {
            document.getElementById(id)?.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    });
</script>
@endsection

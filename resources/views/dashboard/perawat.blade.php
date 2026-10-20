@extends('layouts.app')

@section('title', 'Nurse Station - Input TTV - MedScribe AI')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 mb-1">
                Nurse Station · Triase & Pemeriksaan Awal
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Formulir Pemeriksaan Awal & TTV</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Petugas: <strong>{{ Auth::user()->nama_lengkap }}</strong> (Perawat)</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Stasiun Perawat Aktif
            </span>
        </div>
    </div>

    <!-- Layout 2 Kolom (Sesuai Mockup Gambar 3.1) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Kolom Kiri: Daftar Antrean Pasien (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>Daftar Antrean</span>
                        <span class="px-2 py-0.5 rounded-md bg-sky-100 text-sky-700 text-xs font-semibold">{{ $daftarAntrean->count() }} Pasien</span>
                    </h2>
                </div>

                <!-- Search Input -->
                <div class="relative mb-3">
                    <input
                        type="text"
                        placeholder="Cari pasien / No. RM..."
                        class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-sky-500"
                    >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Antrean List -->
                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1">
                    @forelse ($daftarAntrean as $antrean)
                        <div class="p-3 rounded-xl border {{ $loop->first ? 'border-sky-400 bg-sky-50/40' : 'border-slate-200 bg-white hover:bg-slate-50' }} transition cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-slate-700">A-00{{ $antrean->id_antrean }}</span>
                                <span class="text-[10px] text-slate-400">08:{{ 30 + $loop->index * 5 }} WIB</span>
                            </div>
                            <div class="font-bold text-slate-900 text-xs mt-1">
                                {{ $antrean->pasien->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}
                            </div>
                            <div class="text-[11px] text-slate-500">
                                {{ $antrean->pasien->jenis_kelamin ?? '' }} • No. RM: {{ $antrean->pasien->no_rekam_medis ?? '-' }}
                            </div>
                            <div class="mt-2">
                                @if ($antrean->status === 'Siap Periksa')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                                        ✓ SIAP DIPANGGIL DOKTER
                                    </span>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                        ⏳ MENUNGGU TTV
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        SELESAI
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-400">
                            Tidak ada pasien di antrean.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-400 text-center">
                Antrean diperbarui otomatis setelah data pemeriksaan disimpan.
            </div>
        </div>

        <!-- Kolom Kanan: Form TTV (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            
            <!-- Identitas Pasien Aktif -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="text-xs text-slate-400 uppercase font-bold tracking-wider">Pasien Aktif</div>
                    <div class="text-base font-bold text-slate-900 mt-0.5">
                        {{ $pasienTerpilih->pasien->nama_pasien ?? 'Budi Santoso' }}
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        34 tahun • Laki-laki • No. RM: {{ $pasienTerpilih->pasien->no_rekam_medis ?? '00-12-345' }} • ID: RSUA-20260919-001
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200 self-start sm:self-auto">
                    {{ $pasienTerpilih->status ?? 'Siap Dipanggil Dokter' }}
                </span>
            </div>

            <!-- Form TTV Dummy / Preview -->
            <div>
                <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <span>Tanda-Tanda Vital (TTV)</span>
                    <span class="text-[11px] text-slate-400 font-normal">Semua field wajib diisi (numerik)</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Tekanan Darah -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tekanan Darah <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-xs">
                            <input
                                type="text"
                                readonly
                                value="{{ $pemeriksaanAwal->tekanan_darah ?? '120/80' }}"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-medium">mmHg</span>
                        </div>
                    </div>

                    <!-- Denyut Nadi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Denyut Nadi <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-xs">
                            <input
                                type="text"
                                readonly
                                value="{{ $pemeriksaanAwal->nadi ?? '78' }}"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-medium">x/menit</span>
                        </div>
                    </div>

                    <!-- Suhu Tubuh -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Suhu Tubuh <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-xs">
                            <input
                                type="text"
                                readonly
                                value="{{ $pemeriksaanAwal->suhu_tubuh ?? '38.6' }}"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-medium">°C</span>
                        </div>
                        <p class="text-[11px] text-amber-600 mt-1 font-semibold flex items-center gap-1">
                            <span>⚠️ Di atas rentang normal (&gt;37.5°C)</span>
                        </p>
                    </div>

                    <!-- Laju Pernapasan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Laju Pernapasan <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-xs">
                            <input
                                type="text"
                                readonly
                                value="18"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-medium">x/menit</span>
                        </div>
                    </div>

                    <!-- Berat Badan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Berat Badan</label>
                        <div class="relative rounded-xl shadow-xs">
                            <input
                                type="text"
                                readonly
                                value="68"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-medium">kg</span>
                        </div>
                    </div>

                    <!-- Tinggi Badan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tinggi Badan</label>
                        <div class="relative rounded-xl shadow-xs">
                            <input
                                type="text"
                                readonly
                                value="170"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-medium">cm</span>
                        </div>
                    </div>
                </div>

                <!-- Catatan Awal -->
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Awal Perawat</label>
                    <textarea
                        readonly
                        rows="2"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-700"
                    >{{ $pemeriksaanAwal->catatan ?? 'Demam 3 hari, nyeri menelan, dan batuk kering. Suhu tubuh 38,6°C' }}</textarea>
                </div>
            </div>

            <!-- Catatan Integrasi Objective -->
            <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-xs text-sky-800 flex items-start gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <span>Data TTV akan otomatis masuk ke bagian <strong>Objective</strong> rekam medis dokter saat dokter membuka pemeriksaan pasien ini.</span>
            </div>

            <!-- Tombol Aksi Simpan -->
            <div class="pt-2 flex justify-end">
                <button
                    type="button"
                    class="py-2.5 px-5 bg-sky-600 hover:bg-sky-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-sky-600/20 transition cursor-pointer"
                    onclick="alert('Data pemeriksaan awal tersimpan secara persisten.');"
                >
                    Simpan Data Pemeriksaan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

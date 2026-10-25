@extends('layouts.app')

@section('title', 'Dashboard Antrean Dokter - MedScribe AI')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Dashboard Antrean Dokter</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">19 September 2026 · Poli Penyakit Dalam · dr. Rina Anggraini, Sp.PD</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                Antrean Hari Ini
            </span>
        </div>
    </div>

    <!-- 4 Summary Cards (Sesuai Mockup Halaman 4) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Antrean -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Total Antrean
                </div>
                <div class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalAntrean }}</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-lg">
                👥
            </div>
        </div>

        <!-- Siap Periksa -->
        <div class="bg-white p-5 rounded-2xl border border-sky-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-sky-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Siap Periksa
                </div>
                <div class="text-3xl font-extrabold text-sky-700 mt-2">{{ $siapPeriksa }}</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-lg">
                🩺
            </div>
        </div>

        <!-- Menunggu TTV -->
        <div class="bg-white p-5 rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Menunggu TTV
                </div>
                <div class="text-3xl font-extrabold text-amber-700 mt-2">{{ $menungguTTV }}</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                ⏳
            </div>
        </div>

        <!-- Selesai -->
        <div class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Selesai
                </div>
                <div class="text-3xl font-extrabold text-emerald-700 mt-2">{{ $selesai }}</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                ✅
            </div>
        </div>
    </div>

    <!-- Antrean Pasien Table Card (Sesuai Mockup Gambar 3.2) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Antrean Pasien Poliklinik</h2>
                <p class="text-xs text-slate-500 mt-0.5">Prioritas: Pasien dengan status <strong>Siap Periksa</strong></p>
            </div>

            <!-- Filter Status & Search -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dokter.dashboard') }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ !request('status') ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }} transition">Semua status</a>
                <a href="{{ route('dokter.dashboard', ['status' => 'Siap Periksa']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ request('status') === 'Siap Periksa' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }} transition">Siap periksa</a>
                <a href="{{ route('dokter.dashboard', ['status' => 'Menunggu TTV']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ request('status') === 'Menunggu TTV' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }} transition">Menunggu TTV</a>
                <a href="{{ route('dokter.dashboard', ['status' => 'Selesai']) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ request('status') === 'Selesai' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }} transition">Selesai</a>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                        <th class="py-3 px-5">Nama Pasien</th>
                        <th class="py-3 px-5">No. Antrean</th>
                        <th class="py-3 px-5">Status</th>
                        <th class="py-3 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarAntrean as $antrean)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-5">
                                <div class="font-bold text-slate-900">{{ $antrean->pasien->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    No. RM: {{ $antrean->pasien->no_rekam_medis ?? '-' }} • {{ $antrean->pasien->jenis_kelamin ?? '' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-5 font-mono font-bold text-slate-700">
                                A-00{{ $antrean->id_antrean }}
                            </td>
                            <td class="py-3.5 px-5">
                                @if ($antrean->status === 'Siap Periksa')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                        Siap Periksa
                                    </span>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Menunggu TTV
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Selesai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                @if ($antrean->status === 'Siap Periksa')
                                    @php($kunjungan = \App\Models\Kunjungan::where('id_antrean', $antrean->id_antrean)->first())
                                    <a
                                        href="{{ $kunjungan ? route('dokter.interview.show', $kunjungan) : '#' }}"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer"
                                    >
                                        <span>→ Mulai Wawancara</span>
                                    </a>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <button
                                        type="button"
                                        disabled
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-400 font-medium text-xs cursor-not-allowed border border-slate-200"
                                        title="Menunggu perawat menyelesaikan TTV"
                                    >
                                        <span>→ Buka (Terkunci)</span>
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-semibold text-xs transition cursor-pointer"
                                        onclick="alert('Rekam medis pasien ini telah selesai dan terkunci.');"
                                    >
                                        <span>✓ Lihat Berkas</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada data antrean yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer Note Sesuai Mockup Gambar 3.2 -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><strong>Aturan Akses Medis:</strong> Pasien berstatus "Menunggu TTV" memiliki tombol Buka nonaktif karena memerlukan pemeriksaan tanda vital oleh perawat terlebih dahulu.</span>
        </div>
    </div>
</div>
@endsection

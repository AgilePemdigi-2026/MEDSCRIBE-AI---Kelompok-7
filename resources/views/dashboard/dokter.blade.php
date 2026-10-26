@extends('layouts.app')

@section('title', 'Dashboard Antrean Dokter - MedScribe AI')

@section('content')
<div class="space-y-6">

    <!-- Header Section (Modern Medical Header) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#FFFFFF] p-6 rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)]">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#5865DC] animate-pulse"></span>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#122056]">Dashboard Antrean Dokter</h1>
            </div>
            <p class="text-xs sm:text-sm text-[#69718B] mt-1 font-normal">{{ date('d F Y') }} · Poli Penyakit Dalam · {{ Auth::user()->nama_lengkap ?? 'dr. Rina Anggraini, Sp.PD' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#5865DC]"></span>
                Antrean Aktif Hari Ini
            </span>
        </div>
    </div>

    <!-- 4 Summary Metrics (Elevated Medical Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Antrean -->
        <div class="bg-[#FFFFFF] p-5 rounded-[20px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] flex items-center justify-between transition-all hover:border-[#CBD1EA] hover:-translate-y-0.5 duration-200">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Total Antrean
                </div>
                <div class="text-3xl font-bold text-[#122056] mt-2 tracking-tight">{{ $totalAntrean }}</div>
            </div>
            <div class="w-11 h-11 rounded-[14px] bg-[#EEEEFD] border border-[#CBD1EA] flex items-center justify-center text-[#5865DC]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
        </div>

        <!-- Siap Periksa -->
        <div class="bg-[#FFFFFF] p-5 rounded-[20px] border border-[#5865DC] shadow-[0_4px_18px_rgba(88,101,220,0.12)] flex items-center justify-between transition-all hover:-translate-y-0.5 duration-200">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-[#5865DC]">
                    <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                    Siap Periksa
                </div>
                <div class="text-3xl font-bold text-[#122056] mt-2 tracking-tight">{{ $siapPeriksa }}</div>
            </div>
            <div class="w-11 h-11 rounded-[14px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center shadow-[0_2px_8px_rgba(88,101,220,0.25)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Menunggu TTV -->
        <div class="bg-[#FFFFFF] p-5 rounded-[20px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] flex items-center justify-between transition-all hover:border-[#CBD1EA] hover:-translate-y-0.5 duration-200">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-[#D97706]">
                    <span class="w-2 h-2 rounded-full bg-[#F59E0B]"></span>
                    Menunggu TTV
                </div>
                <div class="text-3xl font-bold text-[#122056] mt-2 tracking-tight">{{ $menungguTTV }}</div>
            </div>
            <div class="w-11 h-11 rounded-[14px] bg-[#FFFBEB] border border-[#FDE68A] flex items-center justify-center text-[#D97706]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Selesai -->
        <div class="bg-[#FFFFFF] p-5 rounded-[20px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] flex items-center justify-between transition-all hover:border-[#CBD1EA] hover:-translate-y-0.5 duration-200">
            <div>
                <div class="flex items-center gap-1.5 text-xs font-semibold text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#059669]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Selesai
                </div>
                <div class="text-3xl font-bold text-[#122056] mt-2 tracking-tight">{{ $selesai }}</div>
            </div>
            <div class="w-11 h-11 rounded-[14px] bg-[#ECFDF5] border border-[#A7F3D0] flex items-center justify-center text-[#059669]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Antrean Pasien Table Card -->
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-[#E4E7F4] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-[#122056]">Antrean Pasien Poliklinik</h2>
                <p class="text-xs text-[#69718B] mt-0.5">Prioritas pemanggilan: Pasien dengan status <strong class="text-[#059669]">Siap Periksa</strong></p>
            </div>

            <!-- Filter Status Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <a
                    href="{{ route('dokter.dashboard') }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all {{ !request('status') ? 'bg-[#5865DC] text-[#FFFFFF] shadow-sm' : 'bg-[#EEEEFD] text-[#122056] hover:bg-[#e2e2fb]' }}"
                >
                    Semua status
                </a>
                <a
                    href="{{ route('dokter.dashboard', ['status' => 'Siap Periksa']) }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all {{ request('status') === 'Siap Periksa' ? 'bg-[#5865DC] text-[#FFFFFF] shadow-sm' : 'bg-[#EEEEFD] text-[#122056] hover:bg-[#e2e2fb]' }}"
                >
                    Siap periksa
                </a>
                <a
                    href="{{ route('dokter.dashboard', ['status' => 'Menunggu TTV']) }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all {{ request('status') === 'Menunggu TTV' ? 'bg-[#5865DC] text-[#FFFFFF] shadow-sm' : 'bg-[#EEEEFD] text-[#122056] hover:bg-[#e2e2fb]' }}"
                >
                    Menunggu TTV
                </a>
                <a
                    href="{{ route('dokter.dashboard', ['status' => 'Selesai']) }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all {{ request('status') === 'Selesai' ? 'bg-[#5865DC] text-[#FFFFFF] shadow-sm' : 'bg-[#EEEEFD] text-[#122056] hover:bg-[#e2e2fb]' }}"
                >
                    Selesai
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-[#FAFAFD] border-b border-[#E4E7F4] text-[#69718B] font-semibold uppercase text-[11px] tracking-wider">
                        <th class="py-3 px-5">Nama Pasien</th>
                        <th class="py-3 px-5">No. Antrean</th>
                        <th class="py-3 px-5">Status</th>
                        <th class="py-3 px-5 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E4E7F4]">
                    @forelse ($daftarAntrean as $antrean)
                        <tr class="hover:bg-[#FAFAFD] transition-colors">
                            <td class="py-4 px-5">
                                <div class="font-semibold text-[#122056] text-sm">{{ $antrean->pasien->nama_pasien ?? 'Pasien #' . $antrean->id_pasien }}</div>
                                <div class="text-[11px] text-[#69718B] mt-0.5 font-mono">
                                    RM: <span class="font-semibold text-[#5865DC]">{{ $antrean->pasien->no_rekam_medis ?? '-' }}</span> · <span class="font-sans">{{ $antrean->pasien->jenis_kelamin ?? '' }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-5">
                                <span class="font-mono text-xs font-bold text-[#122056] bg-[#EEEEFD] px-2.5 py-1 rounded-[8px] border border-[#CBD1EA] inline-block">
                                    A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                @if ($antrean->status === 'Siap Periksa')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                                        Siap Periksa
                                    </span>
                                @elseif ($antrean->status === 'Sedang Diperiksa' || $antrean->status === 'Draf SOAP Tersedia')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#5865DC] animate-pulse"></span>
                                        {{ $antrean->status }}
                                    </span>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
                                        Menunggu TTV
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FAFAFD] text-[#69718B] border border-[#CBD1EA]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#69718B]"></span>
                                        {{ $antrean->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right">
                                @if (in_array($antrean->status, ['Siap Periksa', 'Sedang Diperiksa', 'Draf SOAP Tersedia', 'SOAP Terverifikasi']))
                                    <a
                                        href="{{ route('dokter.rekam-medis', $antrean->id_antrean) }}"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[14px] bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-medium text-xs transition-all cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]"
                                    >
                                        <span>Buka Rekam Medis</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @elseif ($antrean->status === 'Menunggu TTV')
                                    <button
                                        type="button"
                                        disabled
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-[14px] bg-[#FAFAFD] text-[#69718B] font-medium text-xs cursor-not-allowed border border-[#E4E7F4]"
                                        title="Menunggu perawat menyelesaikan TTV"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#69718B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span>Menunggu TTV</span>
                                    </button>
                                @else
                                    <a
                                        href="{{ route('dokter.rekam-medis', $antrean->id_antrean) }}"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-[14px] bg-[#FFFFFF] hover:bg-[#FAFAFD] text-[#122056] border border-[#E4E7F4] hover:border-[#CBD1EA] font-medium text-xs transition-all cursor-pointer shadow-xs"
                                        title="Lihat berkas rekam medis yang telah selesai"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Lihat Berkas</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-[#69718B] text-xs">
                                Tidak ada data antrean yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Clinical Guidelines Footer -->
        <div class="p-4 bg-[#FAFAFD] border-t border-[#E4E7F4] text-xs text-[#69718B] flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#5865DC] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><strong class="text-[#122056]">Protokol Klinis:</strong> Pasien berstatus "Menunggu TTV" terkunci secara sistematis dan memerlukan verifikasi tanda vital oleh perawat sebelum dokter dapat memulai pemeriksaan.</span>
        </div>
    </div>
</div>
@endsection

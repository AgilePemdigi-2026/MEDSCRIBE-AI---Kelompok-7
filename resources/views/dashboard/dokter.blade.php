@extends('layouts.app')

@section('title', 'Dashboard Antrean Dokter - MedScribe AI')

@section('content')
@php
    $persen = fn ($n) => $totalAntrean > 0 ? round($n / $totalAntrean * 100) : 0;
    $filters = [
        '' => 'Semua',
        'Siap Periksa' => 'Siap Periksa',
        'Menunggu TTV' => 'Menunggu TTV',
        'Selesai' => 'Selesai',
    ];
    // Segmen donat ringkasan (pola 21st.dev "Donut Chart With Center Stats").
    // "Dalam pemeriksaan" = sisa antrean yang sedang ditangani dokter, agar cincin genap 100%.
    $dalamPemeriksaan = max(0, $totalAntrean - $siapPeriksa - $menungguTTV - $selesai);
    $segmen = [
        ['Siap Periksa', $siapPeriksa, '#5865DC'],
        ['Dalam pemeriksaan', $dalamPemeriksaan, '#A5ADD8'],
        ['Menunggu TTV', $menungguTTV, '#F59E0B'],
        ['Selesai', $selesai, '#10B981'],
    ];
    $keliling = 2 * M_PI * 52;
    $mulai = 0;
    foreach ($segmen as $i => [$label, $n, $warna]) {
        $panjang = $totalAntrean > 0 ? $n / $totalAntrean * $keliling : 0;
        // Celah tipis antar segmen supaya batasnya terbaca.
        $segmen[$i][] = $panjang > 4 ? $panjang - 3 : $panjang;
        $segmen[$i][] = $mulai / $keliling * 360;
        $mulai += $panjang;
    }
@endphp

<div class="space-y-6">

    {{-- Sapaan: satu baris penuh, ukuran huruf mengikuti lebar layar agar nama panjang tidak terpotong --}}
    <section class="rise">
        <p class="eyebrow">Dashboard Antrean Dokter · {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        <h1 class="mt-2 text-[clamp(1.75rem,2.6vw,2.75rem)] leading-[1.1] font-semibold tracking-[-0.04em] text-medical-navy">
            Selamat datang, <span class="text-royal-indigo">{{ Auth::user()->nama_lengkap }}</span>
        </h1>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Ringkasan antrean: total, jumlah per status beserta proporsinya, dan protokol klinis --}}
        <aside class="card-navy spotlight lg:col-span-4 lg:col-start-9 lg:row-start-1 lg:sticky lg:top-28 rise" style="--i: 1" aria-labelledby="judulRingkasan">
            <div class="relative p-6 pb-5">
                <h2 id="judulRingkasan" class="text-base font-semibold">Ringkasan Antrean Hari Ini</h2>

                {{-- Donat: tiap segmen = proporsi status dari total antrean --}}
                <figure class="mt-6 flex flex-col sm:flex-row lg:flex-col 2xl:flex-row items-center gap-6">
                    <div class="relative w-40 h-40 flex-shrink-0">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120" role="img"
                             aria-label="Komposisi antrean: {{ collect($segmen)->map(fn ($s) => $s[0] . ' ' . $s[1])->join(', ') }}">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="12" />
                            @foreach ($segmen as $i => [$label, $n, $warna, $panjang, $sudut])
                                @if ($n > 0)
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $warna }}" stroke-width="12" stroke-linecap="butt"
                                            class="donut-seg" style="--len: {{ round($panjang, 2) }}; --c: {{ round($keliling, 2) }}; --d: {{ $i * 120 }}ms"
                                            stroke-dasharray="{{ round($panjang, 2) }} {{ round($keliling, 2) }}"
                                            transform="rotate({{ round($sudut, 2) }} 60 60)" />
                                @endif
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-[2.5rem] leading-none font-semibold tracking-[-0.05em] num" data-ticker="{{ $totalAntrean }}">{{ $totalAntrean }}</span>
                            <span class="mt-1 text-[11px] text-on-navy-muted">Total Antrean</span>
                        </div>
                    </div>

                    <figcaption class="w-full">
                        <dl class="space-y-1">
                            @foreach ($segmen as [$label, $n, $warna])
                                <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-(--radius-field) transition-colors hover:bg-white/[0.07]">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $warna }}" aria-hidden="true"></span>
                                    <dt class="flex-1 text-[13px] text-white/90">{{ $label }}</dt>
                                    <dd class="text-right whitespace-nowrap">
                                        <span class="text-sm font-semibold num" data-ticker="{{ $n }}">{{ $n }}</span>
                                        <span class="ml-1 text-[11px] text-on-navy-muted num">{{ $persen($n) }}%</span>
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </figcaption>
                </figure>
            </div>

            {{-- Sorotan siap periksa: tindakan utama dokter --}}
            <div class="relative mx-4 p-4 rounded-(--radius-panel) bg-royal-indigo border border-white/15 flex items-center gap-3">
                <span class="avatar w-10 h-10 bg-white text-royal-indigo" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
                <p class="flex-1 text-sm leading-snug">
                    <span class="font-semibold num">{{ $siapPeriksa }} pasien</span> siap dipanggil sekarang
                </p>
                @if ($siapPeriksa > 0)
                    <a href="{{ route('dokter.dashboard', ['status' => 'Siap Periksa']) }}" class="btn btn-secondary btn-sm">Lihat</a>
                @endif
            </div>

            <div class="relative m-4 mt-3 p-4 rounded-(--radius-panel) bg-white/[0.05] border border-white/10 flex gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0 text-on-navy-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                <p class="text-xs leading-relaxed text-on-navy-soft">
                    Pasien berstatus <span class="font-semibold text-white">Menunggu TTV</span> baru bisa diperiksa setelah perawat mengisi tanda-tanda vitalnya.
                </p>
            </div>
        </aside>

        {{-- Antrean pasien --}}
        <section class="card spotlight lg:col-span-8 lg:col-start-1 lg:row-start-1 overflow-hidden rise" style="--i: 2" aria-labelledby="judulAntrean">
            <div class="relative p-5 sm:p-6 flex flex-col gap-4">
                <div>
                    <h2 id="judulAntrean" class="section-title">Antrean Pasien Poliklinik</h2>
                    <p class="section-subtitle">Panggil lebih dulu pasien berstatus <span class="font-semibold text-success-text">Siap Periksa</span>.</p>
                </div>

                <nav aria-label="Saring status" class="pill-nav-scroll">
                    <div class="pill-nav">
                        @foreach ($filters as $value => $text)
                            @php $aktif = (string) request('status', '') === (string) $value; @endphp
                            <a href="{{ route('dokter.dashboard', $value !== '' ? ['status' => $value] : []) }}" @if ($aktif) aria-current="page" @endif>{{ $text }}</a>
                        @endforeach
                    </div>
                </nav>
            </div>

            <div class="relative overflow-x-auto border-t border-border-subtle">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Nama Pasien</th>
                            <th scope="col">No. Antrean</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarAntrean as $antrean)
                            @php
                                $namaPasien = $antrean->pasien->nama_pasien ?? 'Pasien #' . $antrean->id_pasien;
                            @endphp
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3 min-w-[13rem]">
                                        <span class="avatar w-9 h-9 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:border-royal-indigo group-hover:text-white" aria-hidden="true">{{ strtoupper(substr($namaPasien, 0, 1)) }}</span>
                                        <div>
                                            <div class="font-semibold text-medical-navy text-sm transition-colors group-hover:text-royal-indigo-hover">{{ $namaPasien }}</div>
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-text-secondary">
                                                <span class="tag-id"><span class="sr-only">No. RM </span>{{ $antrean->pasien->no_rekam_medis ?? '-' }}</span>
                                                @if ($antrean->pasien?->jenis_kelamin)<span>{{ $antrean->pasien->jenis_kelamin }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="tag-id tag-id-muted">A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</span></td>
                                <td>@include('partials.queue-status', ['status' => $antrean->status])</td>
                                <td class="text-right whitespace-nowrap">
                                    @if (in_array($antrean->status, ['Siap Periksa', 'Sedang Diperiksa', 'Draf SOAP Tersedia', 'SOAP Terverifikasi']))
                                        <a href="{{ route('dokter.rekam-medis', $antrean->id_antrean) }}" class="btn btn-primary btn-sm group">
                                            Buka Rekam Medis
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    @elseif ($antrean->status === 'Menunggu TTV')
                                        <button type="button" disabled class="btn btn-secondary btn-sm" title="Menunggu perawat menyelesaikan TTV">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            Menunggu TTV
                                        </button>
                                    @else
                                        <a href="{{ route('dokter.rekam-medis', $antrean->id_antrean) }}" class="btn btn-secondary btn-sm" title="Lihat berkas rekam medis yang telah selesai">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            Lihat Berkas
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="!p-0">
                                    <x-empty-state :title="request('status') ? 'Tidak ada pasien dengan status ini' : 'Antrean masih kosong'"
                                                   :hint="request('status') ? 'Pilih status lain atau tampilkan semua antrean.' : 'Pasien yang didaftarkan perawat akan muncul di sini.'"
                                                   icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                        @if (request('status'))
                                            <a href="{{ route('dokter.dashboard') }}" class="btn btn-soft">Tampilkan semua</a>
                                        @endif
                                    </x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>
@endsection

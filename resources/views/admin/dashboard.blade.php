@extends('layouts.admin')

@section('title', 'Dashboard Admin - MedScribe AI')

@section('content')
@php
    $maxQueue = max(1, max($chartQueues));
    $kpi = [
        ['label' => 'Dokter Aktif', 'value' => $totalDokterAktif, 'hint' => 'Bertugas di poliklinik'],
        ['label' => 'Perawat Aktif', 'value' => $totalPerawatAktif, 'hint' => 'Bertugas di nurse station'],
        ['label' => 'Menunggu Verifikasi', 'value' => $totalPending, 'hint' => $totalPending > 0 ? 'Menunggu persetujuan admin' : 'Tidak ada pendaftaran baru'],
        ['label' => 'Antrean Pasien Hari Ini', 'value' => $totalAntreanHariIni, 'hint' => 'Tanpa antrean yang dibatalkan'],
        ['label' => 'Staf Nonaktif', 'value' => $totalInactive, 'hint' => 'Tidak lagi bertugas'],
    ];
@endphp

<div class="space-y-6">

    <x-page-header
        :eyebrow="'Pusat Kendali Administrasi Faskes · ' . \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y')"
        :title="$hospital->hospital_name"
        subtitle="Kelola status tenaga medis, verifikasi pendaftaran staf klinis, dan pantau antrean rumah sakit.">
        <a href="{{ route('admin.pending') }}" class="btn btn-primary">
            Verifikasi staf
            @if ($totalPending > 0)
                <span class="num">({{ $totalPending }})</span>
            @endif
        </a>
    </x-page-header>

    @include('partials.form-errors')

    {{-- Ringkasan angka --}}
    <section class="grid grid-cols-2 lg:grid-cols-5 gap-4 rise" style="--i: 1" aria-label="Ringkasan">
        {{-- Semua kartu memakai efek hover yang sama (card-hover); kartu ini hanya ringkasan, bukan tautan. --}}
        @foreach ($kpi as $item)
            <div @class(['card card-hover p-5 flex flex-col', 'col-span-2 lg:col-span-1' => $loop->last])>
                <span class="eyebrow">{{ $item['label'] }}</span>
                <span class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-3xl font-semibold tracking-tight text-medical-navy num">{{ $item['value'] }}</span>
                    @if ($item['label'] === 'Menunggu Verifikasi' && $item['value'] > 0)
                        <span class="status status-warn">Perlu ditinjau</span>
                    @endif
                </span>
                <span class="mt-auto pt-2 text-xs text-text-secondary">{{ $item['hint'] }}</span>
            </div>
        @endforeach
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <div class="lg:col-span-8 space-y-6">

            {{-- Antrean 7 hari terakhir --}}
            <section class="card p-5 sm:p-6 rise" style="--i: 2" aria-labelledby="judulGrafikAntrean">
                <h2 id="judulGrafikAntrean" class="section-title">Antrean Pasien 7 Hari Terakhir</h2>
                <p class="section-subtitle">Jumlah antrean per hari di {{ $hospital->hospital_name }}, tanpa antrean yang dibatalkan.</p>

                <figure class="mt-6" aria-label="Antrean per hari: {{ collect($chartDates)->zip($chartQueues)->map(fn ($p) => $p[0] . ' ' . $p[1])->join(', ') }}">
                    <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-40 border-b border-border-subtle" aria-hidden="true">
                        @foreach ($chartQueues as $idx => $cnt)
                            <div class="flex flex-col items-center justify-end gap-1.5 h-full">
                                <span class="text-xs font-semibold text-medical-navy num">{{ $cnt }}</span>
                                <span @class(['w-full max-w-9 rounded-t-lg', 'bg-royal-indigo' => $loop->last, 'bg-pale-lavender border border-b-0 border-border-hover' => ! $loop->last])
                                      style="height: {{ max(4, round($cnt / $maxQueue * 100)) }}%"></span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 grid grid-cols-7 gap-2 sm:gap-4 text-center text-xs text-text-secondary" aria-hidden="true">
                        @foreach ($chartDates as $idx => $tanggal)
                            <span @class(['font-semibold text-medical-navy' => $loop->last])>{{ $loop->last ? 'Hari ini' : $tanggal }}</span>
                        @endforeach
                    </div>
                </figure>
            </section>

            {{-- Dokter aktif --}}
            <section class="card spotlight overflow-hidden rise" style="--i: 3" aria-labelledby="judulDokterAktif">
                <div class="p-5 sm:p-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="judulDokterAktif" class="section-title">Daftar Dokter Poliklinik</h2>
                        <p class="section-subtitle">Dokter aktif dan jumlah kunjungannya hari ini.</p>
                    </div>
                    <a href="{{ route('admin.doctors') }}" class="btn btn-secondary btn-sm shrink-0">Lihat semua ({{ $totalDokterAktif }})</a>
                </div>

                @if ($ringkasanDokter->isEmpty())
                    <x-empty-state class="border-t border-border-subtle" title="Belum ada dokter aktif"
                                   hint="Dokter yang pendaftarannya disetujui akan muncul di sini."
                                   icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                @else
                    <div class="overflow-x-auto border-t border-border-subtle">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Nama Dokter</th>
                                    <th scope="col" class="hidden md:table-cell">Email</th>
                                    <th scope="col" class="text-right">Kunjungan Hari Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ringkasanDokter as $doc)
                                    <tr class="group">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <span class="avatar w-9 h-9 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:border-royal-indigo group-hover:text-white" aria-hidden="true">{{ $doc->inisial }}</span>
                                                <span class="font-semibold text-medical-navy transition-colors group-hover:text-royal-indigo-hover">{{ $doc->nama_lengkap }}</span>
                                            </div>
                                        </td>
                                        <td class="hidden md:table-cell text-text-secondary">{{ $doc->email }}</td>
                                        <td class="text-right num font-semibold text-medical-navy">{{ $doc->kunjungan_dokter_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="lg:col-span-4 space-y-6">

            {{-- Pendaftaran baru --}}
            <section class="card p-5 rise" style="--i: 2" aria-labelledby="judulPendaftaranBaru">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="judulPendaftaranBaru" class="section-title">Pendaftaran Baru</h2>
                    <a href="{{ route('admin.pending') }}" class="link text-sm shrink-0">Lihat semua ({{ $totalPending }})</a>
                </div>

                @if ($permohonanTerbaru->isEmpty())
                    <x-empty-state class="!py-8" title="Tidak ada pendaftaran baru"
                                   hint="Semua pendaftaran dokter dan perawat sudah diproses."
                                   icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($permohonanTerbaru as $p)
                            <li class="panel">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-medical-navy truncate">{{ $p->nama_lengkap }}</p>
                                        <p class="text-xs text-text-secondary truncate">{{ $p->email }}</p>
                                    </div>
                                    <span class="chip">{{ $p->peran_label }}</span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <button type="button" class="btn btn-primary btn-sm"
                                            data-dialog-open="dlgApproveStaff" data-staff-url="{{ route('admin.staff.approve', $p) }}"
                                            data-staff-name="{{ $p->nama_lengkap }}" data-staff-role="{{ $p->peran_label }}">Setujui</button>
                                    <button type="button" class="btn btn-danger-quiet btn-sm"
                                            data-dialog-open="dlgRejectStaff" data-staff-url="{{ route('admin.staff.reject', $p) }}"
                                            data-staff-name="{{ $p->nama_lengkap }}" data-staff-role="{{ $p->peran_label }}">Tolak</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Aktivitas terbaru --}}
            <section class="card p-5 rise" style="--i: 3" aria-labelledby="judulAktivitasTerkini">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="judulAktivitasTerkini" class="section-title">Aktivitas Terkini</h2>
                    <a href="{{ route('admin.activities') }}" class="link text-sm shrink-0">Riwayat lengkap</a>
                </div>

                @if ($aktivitasTerbaru->isEmpty())
                    <x-empty-state class="!py-8" title="Belum ada aktivitas"
                                   hint="Persetujuan, penolakan, dan penonaktifan staf akan tercatat di sini."
                                   icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                    <ul class="mt-4 divide-y divide-border-subtle">
                        @foreach ($aktivitasTerbaru as $log)
                            <li class="py-3 first:pt-0 last:pb-0">
                                <span class="status {{ $log->action_badge_class }}">{{ $log->action_label }}</span>
                                <p class="mt-1.5 text-sm font-semibold text-medical-navy">{{ $log->target_name ?? 'Staf' }}</p>
                                <p class="text-xs text-text-secondary">
                                    {{ $log->created_at?->locale('id')->diffForHumans() }} · oleh {{ $log->admin->nama_lengkap ?? 'Admin' }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div>

@include('admin.partials.staff-dialogs')
@endsection

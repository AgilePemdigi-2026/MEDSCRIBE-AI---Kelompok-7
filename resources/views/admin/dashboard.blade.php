@extends('layouts.admin')

@section('title', 'Dashboard Admin - MedScribe AI')

@section('content')
<div class="space-y-7 rise">

    {{-- Header Sapaan Admin --}}
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-border-subtle">
        <div>
            <p class="text-xs font-semibold text-royal-indigo tracking-wider uppercase">Pusat Kendali Administrasi Faskes</p>
            <h1 class="mt-1 text-[clamp(1.75rem,2.4vw,2.5rem)] leading-tight font-semibold tracking-[-0.035em] text-medical-navy">
                {{ $hospital->hospital_name }}
            </h1>
            <p class="mt-1 text-xs text-text-secondary">
                Kelola status tenaga medis, verifikasi registrasi staf klinis, dan pantau operasional rumah sakit.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
            <a href="{{ route('admin.pending') }}" class="btn btn-primary text-xs py-2 px-3.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Verifikasi Staf
                @if ($totalPending > 0)
                    <span class="ml-1.5 px-1.5 py-0.2 rounded-full text-[10px] bg-white text-royal-indigo font-bold">
                        {{ $totalPending }}
                    </span>
                @endif
            </a>
        </div>
    </section>

    {{-- Ringkasan 5 KPI Cards Sesuai Spesifikasi --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- KPI 1: Dokter Aktif --}}
        <div class="card p-5 space-y-2 hover:border-border-hover transition-all duration-200">
            <div class="flex items-center justify-between text-xs text-text-secondary">
                <span class="font-medium">Dokter Aktif</span>
                <span class="w-8 h-8 rounded-full bg-indigo-50 text-royal-indigo flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold tracking-tight text-medical-navy num">{{ $totalDokterAktif }}</p>
            <p class="text-[11px] text-text-secondary flex items-center gap-1">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Praktik & RME Aktif
            </p>
        </div>

        {{-- KPI 2: Perawat Aktif --}}
        <div class="card p-5 space-y-2 hover:border-border-hover transition-all duration-200">
            <div class="flex items-center justify-between text-xs text-text-secondary">
                <span class="font-medium">Perawat Aktif</span>
                <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold tracking-tight text-medical-navy num">{{ $totalPerawatAktif }}</p>
            <p class="text-[11px] text-text-secondary flex items-center gap-1">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Nurse Station Aktif
            </p>
        </div>

        {{-- KPI 3: Menunggu Verifikasi --}}
        <div class="card p-5 space-y-2 {{ $totalPending > 0 ? 'bg-amber-50/50 border-amber-300 ring-2 ring-amber-400/20' : '' }} hover:border-border-hover transition-all duration-200">
            <div class="flex items-center justify-between text-xs text-text-secondary">
                <span class="font-medium {{ $totalPending > 0 ? 'text-amber-900 font-semibold' : '' }}">Menunggu Verifikasi</span>
                <span class="w-8 h-8 rounded-full {{ $totalPending > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold tracking-tight {{ $totalPending > 0 ? 'text-amber-800' : 'text-medical-navy' }} num">{{ $totalPending }}</p>
            <p class="text-[11px] {{ $totalPending > 0 ? 'text-amber-800 font-medium' : 'text-text-secondary' }}">
                {{ $totalPending > 0 ? 'Perlu tindakan admin' : 'Tidak ada antrean pending' }}
            </p>
        </div>

        {{-- KPI 4: Antrean Hari Ini --}}
        <div class="card p-5 space-y-2 hover:border-border-hover transition-all duration-200">
            <div class="flex items-center justify-between text-xs text-text-secondary">
                <span class="font-medium">Antrean Pasien Hari Ini</span>
                <span class="w-8 h-8 rounded-full bg-royal-indigo/10 text-royal-indigo flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold tracking-tight text-medical-navy num">{{ $totalAntreanHariIni }}</p>
            <p class="text-[11px] text-text-secondary flex items-center gap-1">
                Data aktual rumah sakit
            </p>
        </div>

        {{-- KPI 5: Staf Nonaktif --}}
        <div class="card p-5 space-y-2 hover:border-border-hover transition-all duration-200">
            <div class="flex items-center justify-between text-xs text-text-secondary">
                <span class="font-medium">Staf Nonaktif</span>
                <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold tracking-tight text-medical-navy num">{{ $totalInactive }}</p>
            <p class="text-[11px] text-text-secondary">
                Mutasi / Selesai tugas
            </p>
        </div>
    </section>

    {{-- Baris 2: Visualisasi Statistik & Permohonan Verifikasi Cepat --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Kolom Kiri (8 Kolom): Visualisasi & Daftar Dokter --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Visualisasi Antrean 7 Hari Terakhir --}}
            <div class="card p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-medical-navy">Aktivitas Kunjungan & Antrean Pasien</h2>
                        <p class="text-xs text-text-secondary">Pergerakan jumlah antrean pasien 7 hari terakhir di {{ $hospital->hospital_name }}</p>
                    </div>
                    <span class="tag-id bg-pale-lavender text-royal-indigo font-medium text-[11px]">7 Hari Terakhir</span>
                </div>

                {{-- Bar Chart Visual Sederhana & Ringan --}}
                @php
                    $maxQueue = max(1, max($chartQueues));
                @endphp
                <div class="pt-4 pb-2">
                    <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-36 border-b border-border-subtle pb-2">
                        @foreach ($chartQueues as $idx => $cnt)
                            @php
                                $heightPercent = round(($cnt / $maxQueue) * 100);
                            @endphp
                            <div class="flex flex-col items-center gap-1.5 h-full justify-end group">
                                <span class="text-[10px] font-semibold text-medical-navy opacity-80 group-hover:opacity-100">{{ $cnt }}</span>
                                <div class="w-full max-w-[36px] rounded-t-lg bg-royal-indigo/20 group-hover:bg-royal-indigo transition-all duration-200 relative overflow-hidden"
                                     style="height: {{ max(6, $heightPercent) }}%">
                                    <div class="absolute inset-0 bg-gradient-to-t from-royal-indigo to-royal-indigo-hover opacity-75"></div>
                                </div>
                                <span class="text-[10px] text-text-secondary whitespace-nowrap">{{ $chartDates[$idx] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Ringkasan Dokter Aktif --}}
            <div class="card p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                    <div>
                        <h2 class="text-base font-semibold text-medical-navy">Daftar Dokter Poliklinik</h2>
                        <p class="text-xs text-text-secondary">Dokter dengan status aktif bertugas di rumah sakit ini</p>
                    </div>
                    <a href="{{ route('admin.doctors') }}" class="btn btn-secondary btn-sm text-xs">
                        Lihat Semua ({{ $totalDokterAktif }})
                    </a>
                </div>

                @if ($ringkasanDokter->isEmpty())
                    <div class="py-8 text-center">
                        <p class="text-xs text-text-secondary">Belum ada dokter aktif yang terdaftar di rumah sakit ini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-text-secondary border-b border-border-subtle">
                                    <th class="pb-2.5 font-medium">Nama Dokter</th>
                                    <th class="pb-2.5 font-medium">Email</th>
                                    <th class="pb-2.5 font-medium text-center">Kunjungan Hari Ini</th>
                                    <th class="pb-2.5 font-medium text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle/50">
                                @foreach ($ringkasanDokter as $doc)
                                    <tr class="hover:bg-soft-offwhite/50 transition-colors">
                                        <td class="py-3 font-semibold text-medical-navy flex items-center gap-2.5">
                                            <span class="avatar w-7 h-7 bg-pale-lavender text-royal-indigo text-[10px]">
                                                {{ strtoupper(substr($doc->nama_lengkap, 0, 2)) }}
                                            </span>
                                            <span>{{ $doc->nama_lengkap }}</span>
                                        </td>
                                        <td class="py-3 text-text-secondary">{{ $doc->email }}</td>
                                        <td class="py-3 text-center">
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-royal-indigo">
                                                {{ $doc->kunjungan_dokter_count }} kunjungan
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        {{-- Kolom Kanan (4 Kolom): Widget Verifikasi & Log Aktivitas Terbaru --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Widget Permohonan Registrasi Menunggu Persetujuan --}}
            <div class="card p-5 space-y-4 border-amber-200/80 bg-white">
                <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <h2 class="text-sm font-semibold text-medical-navy">Pendaftaran Baru</h2>
                    </div>
                    <a href="{{ route('admin.pending') }}" class="text-[11px] font-medium text-royal-indigo hover:underline">
                        Lihat Semua ({{ $totalPending }})
                    </a>
                </div>

                @if ($permohonanTerbaru->isEmpty())
                    <div class="py-6 text-center text-xs text-text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-emerald-500 mx-auto mb-2 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Semua pendaftaran telah terverifikasi. Tidak ada permohonan tertunda.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($permohonanTerbaru as $p)
                            <div class="p-3 rounded-[16px] bg-soft-offwhite border border-border-subtle flex flex-col gap-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-semibold text-medical-navy leading-snug">{{ $p->nama_lengkap }}</p>
                                        <p class="text-[11px] text-text-secondary">{{ $p->email }}</p>
                                    </div>
                                    <span class="tag-id uppercase text-[10px] {{ $p->role === 'dokter' ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $p->role }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 pt-1 border-t border-border-subtle/60">
                                    <form method="POST" action="{{ route('admin.staff.approve', $p) }}" class="flex-1 m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm w-full justify-center text-[11px] py-1 bg-emerald-600 hover:bg-emerald-700">
                                            Terima
                                        </button>
                                    </form>
                                    <button type="button" onclick="openModalReject({{ $p->id_pengguna }}, '{{ addslashes($p->nama_lengkap) }}')"
                                            class="btn btn-secondary btn-sm flex-1 justify-center text-[11px] py-1 text-danger-solid hover:bg-danger-bg">
                                        Tolak
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Widget Riwayat Aktivitas Terbaru --}}
            <div class="card p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                    <h2 class="text-sm font-semibold text-medical-navy">Aktivitas Terkini</h2>
                    <a href="{{ route('admin.activities') }}" class="text-[11px] font-medium text-royal-indigo hover:underline">
                        Audit Log
                    </a>
                </div>

                @if ($aktivitasTerbaru->isEmpty())
                    <p class="text-xs text-text-secondary text-center py-4">Belum ada aktivitas administratif tercatat.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($aktivitasTerbaru as $log)
                            <div class="flex items-start gap-2.5 text-xs pb-2.5 border-b border-border-subtle/50 last:border-0 last:pb-0">
                                <span class="w-2 h-2 rounded-full bg-royal-indigo mt-1.5 flex-shrink-0"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-medical-navy leading-snug">
                                        {{ $log->action_label }}: <span class="font-semibold">{{ $log->target_name ?? 'Staf' }}</span>
                                    </p>
                                    @if ($log->reason)
                                        <p class="text-[11px] text-text-secondary truncate mt-0.5 italic">"{{ $log->reason }}"</p>
                                    @endif
                                    <p class="text-[10px] text-text-secondary/80 mt-1">
                                        {{ $log->created_at->diffForHumans() }} · oleh {{ $log->admin->nama_lengkap ?? 'Admin' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>

</div>

{{-- Modal Konfirmasi Tolak Pendaftaran --}}
<div id="modalRejectStaff" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalRejectTitle">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModalReject()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="dialog w-full max-w-md p-6 pointer-events-auto animate-dialog bg-white rounded-[24px] shadow-2xl border border-border-subtle">
            <div class="flex items-center gap-3 pb-3 border-b border-border-subtle">
                <span class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
                <div>
                    <h2 id="modalRejectTitle" class="text-base font-semibold text-medical-navy">Tolak Permohonan Registrasi</h2>
                    <p id="modalRejectSubtitle" class="text-xs text-text-secondary"></p>
                </div>
            </div>

            <form id="formRejectStaff" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="reject_reason" class="field-label text-xs">Alasan Penolakan <span class="text-danger-solid">*</span></label>
                    <textarea id="reject_reason" name="reason" rows="3" required minlength="5" maxlength="500"
                              class="field text-xs leading-relaxed"
                              placeholder="Tuliskan alasan penolakan secara jelas. Alasan ini akan ditampilkan kepada calon tenaga medis saat login..."></textarea>
                    <p class="text-[11px] text-text-secondary mt-1">Alasan penolakan wajib diisi untuk transparansi proses seleksi.</p>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button type="button" onclick="closeModalReject()" class="btn btn-secondary justify-center text-xs">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-danger justify-center text-xs">
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openModalReject(userId, userName) {
        const modal = document.getElementById('modalRejectStaff');
        const form = document.getElementById('formRejectStaff');
        const subtitle = document.getElementById('modalRejectSubtitle');
        if (!modal || !form) return;

        form.action = `/admin/verifikasi/${userId}/tolak`;
        subtitle.textContent = `Pendaftar: ${userName}`;
        document.getElementById('reject_reason').value = '';

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('reject_reason')?.focus(), 50);
    }

    function closeModalReject() {
        const modal = document.getElementById('modalRejectStaff');
        if (!modal || modal.classList.contains('hidden')) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>
@endsection


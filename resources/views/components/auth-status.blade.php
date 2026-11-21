{{--
    Halaman status akun untuk tenaga medis yang belum boleh masuk dashboard
    (menunggu verifikasi, ditolak, atau dinonaktifkan). Dipakai di layout auth.

    Props:
      user, hospital   model Pengguna dan Hospital
      tone             warn | danger | muted (warna ikon, pesan, dan badge status)
      icon             path SVG 24x24 (atribut d)
      title, subtitle  judul dan kalimat pembuka
      statusLabel      teks badge status di ringkasan akun
      reasonLabel      label alasan (opsional); reason = isinya
      reload           true untuk menampilkan tombol "Muat ulang status"

    Slot: $slot  isi pesan utama di dalam alert
--}}
@props([
    'user',
    'hospital' => null,
    'tone' => 'warn',
    'icon',
    'title',
    'subtitle',
    'statusLabel',
    'reasonLabel' => null,
    'reason' => null,
    'reload' => false,
])

@php
    $tones = [
        'warn' => ['avatar' => 'bg-warning-bg text-warning-text border-warning-border', 'alert' => 'alert-warn', 'status' => 'status-warn'],
        'danger' => ['avatar' => 'bg-danger-bg text-danger-text border-danger-border', 'alert' => 'alert-danger', 'status' => 'status-danger'],
        'muted' => ['avatar' => 'bg-soft-offwhite text-text-secondary border-border-hover', 'alert' => 'alert-info', 'status' => 'status-muted'],
    ];
    $t = $tones[$tone] ?? $tones['warn'];
@endphp

<span class="avatar w-12 h-12 border {{ $t['avatar'] }}" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
</span>
<h1 class="mt-4 text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">{{ $title }}</h1>
<p class="mt-1.5 text-sm text-text-secondary">{{ $subtitle }}</p>

<div class="mt-7 space-y-5">
    {{-- Ucapan "selamat datang" dari proses login tidak relevan di sini, jadi hanya info, peringatan, dan error. --}}
    @include('partials.flash', ['dismissible' => false, 'only' => ['info', 'warning', 'error']])
    @include('partials.form-errors')

    <div class="alert {{ $t['alert'] }}" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <div class="flex-1 min-w-0 text-text-body space-y-3">
            <p>{{ $slot }}</p>
            @if ($reasonLabel)
                <div class="rounded-(--radius-field) bg-white border border-border-subtle px-3.5 py-3">
                    <p class="font-semibold text-medical-navy">{{ $reasonLabel }}</p>
                    <p class="mt-0.5">{{ $reason ?: 'Admin tidak mencantumkan alasan.' }}</p>
                </div>
            @endif
        </div>
    </div>

    <dl class="panel divide-y divide-border-subtle text-sm">
        <div class="flex items-center justify-between gap-4 pb-2.5">
            <dt class="text-text-secondary">Nama</dt>
            <dd class="font-semibold text-medical-navy text-right">{{ $user->nama_lengkap }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2.5">
            <dt class="text-text-secondary">Alamat Email</dt>
            <dd class="font-semibold text-medical-navy text-right break-all">{{ $user->email }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2.5">
            <dt class="text-text-secondary">Peran</dt>
            <dd class="font-semibold text-medical-navy">{{ $user->peran_label }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2.5">
            <dt class="text-text-secondary">Rumah Sakit</dt>
            <dd class="font-semibold text-medical-navy text-right">{{ $hospital?->hospital_name ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 pt-2.5">
            <dt class="text-text-secondary">Status</dt>
            <dd><span class="status {{ $t['status'] }}">{{ $statusLabel }}</span></dd>
        </div>
    </dl>

    <div class="grid gap-2.5">
        @if ($reload)
            <a href="{{ route('dashboard') }}" class="btn btn-primary w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                Muat ulang status
            </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-danger-quiet w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Keluar
            </button>
        </form>
    </div>
</div>

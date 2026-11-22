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

    Slot: $slot     isi pesan utama di dalam alert
          $actions  tombol tambahan di atas tombol "Keluar" (opsional)
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
    <x-icon :path="$icon" class="w-5 h-5" />
</span>
<h1 class="mt-4 text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">{{ $title }}</h1>
<p class="mt-1.5 text-sm text-text-secondary">{{ $subtitle }}</p>

<div class="mt-7 space-y-5">
    {{-- Ucapan "selamat datang" dari proses login tidak relevan di sini, jadi hanya info, peringatan, dan error. --}}
    @include('partials.flash', ['dismissible' => false, 'only' => ['info', 'warning', 'error']])
    @include('partials.form-errors')

    <div class="alert {{ $t['alert'] }}" role="status">
        <x-icon name="info" aria-hidden="true" />
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
                <x-icon name="refresh-cw" class="w-4 h-4" aria-hidden="true" />
                Muat ulang status
            </a>
        @endif
        {{ $actions ?? '' }}
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-danger-quiet w-full">
                <x-icon name="log-out" class="w-4 h-4" aria-hidden="true" />
                Keluar
            </button>
        </form>
    </div>
</div>

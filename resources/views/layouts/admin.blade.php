{{--
    Layout Panel Admin Rumah Sakit. Memakai layout utama (header, notifikasi, footer, dialog keluar)
    dan hanya menambahkan navigasi menu admin di bawah header.
--}}
@extends('layouts.app')

@section('subnav')
    @php
        $pendingBadgeCount = \App\Models\Pengguna::where('hospital_id', Auth::user()?->hospital_id)
            ->where('approval_status', 'pending')
            ->count();
        $menuAdmin = [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.doctors', 'label' => 'Dokter'],
            ['route' => 'admin.nurses', 'label' => 'Perawat'],
            ['route' => 'admin.pending', 'label' => 'Verifikasi Pendaftaran', 'count' => $pendingBadgeCount],
            ['route' => 'admin.inactive', 'label' => 'Tenaga Medis Nonaktif'],
            ['route' => 'admin.activities', 'label' => 'Riwayat Aktivitas'],
            ['route' => 'admin.hospital.info', 'label' => 'Profil RS'],
        ];
    @endphp
    <nav aria-label="Menu admin" class="pill-nav-scroll">
        <div class="pill-nav">
            @foreach ($menuAdmin as $menu)
                <a href="{{ route($menu['route']) }}" @if (request()->routeIs($menu['route'])) aria-current="page" @endif>
                    {{ $menu['label'] }}
                    @if (! empty($menu['count']))
                        <span class="pill-count">{{ $menu['count'] }}<span class="sr-only"> menunggu</span></span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
@endsection

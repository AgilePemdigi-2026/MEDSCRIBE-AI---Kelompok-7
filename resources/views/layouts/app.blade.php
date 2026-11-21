<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MedScribe AI - Sistem Rekam Medis Elektronik Terintegrasi')</title>
</head>
<body class="min-h-dvh flex flex-col app-backdrop text-text-body antialiased">

    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] btn btn-primary">Lewati ke konten utama</a>

    <header class="sticky top-0 z-40 px-3 sm:px-6 lg:px-8 pt-3">
        <div class="max-w-[1280px] mx-auto flex items-center justify-between gap-3 rounded-full bg-white/90 backdrop-blur-md border border-border-subtle pl-3 pr-2 py-2 shadow-[0_8px_30px_-12px_rgba(18,32,86,0.18)]">

            <a href="{{ route('dashboard') }}" class="rounded-full pr-2 flex items-center gap-2.5" aria-label="MedScribe AI, kembali ke dashboard">
                @include('partials.brand')
                @if (Auth::user()?->isAdmin())
                    <span class="chip chip-accent hidden sm:inline-flex">Admin RS</span>
                @endif
            </a>

            <div class="hidden lg:flex items-center gap-2 px-4 min-h-10 rounded-full bg-soft-offwhite border border-border-subtle text-xs text-text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y') }} · {{ Auth::user()?->hospital?->hospital_name ?? 'RS Universitas Airlangga' }}</span>
            </div>

            @auth
            @php
                $initials = Auth::user()->inisial;
                $roleLabel = Auth::user()->peran_label;
            @endphp
            <div class="flex items-center gap-1.5">
                <div class="flex items-center gap-2.5 rounded-full bg-soft-offwhite border border-border-subtle pl-1 pr-1 md:pr-4 py-1">
                    <span class="avatar w-9 h-9 bg-medical-navy text-white text-xs" aria-hidden="true">{{ $initials ?: 'U' }}</span>
                    <span class="hidden md:block leading-tight whitespace-nowrap">
                        <span class="block text-[13px] font-semibold text-medical-navy">{{ Auth::user()->nama_lengkap }}</span>
                        <span class="block text-[11px] text-text-secondary">{{ $roleLabel }}</span>
                    </span>
                </div>

                <button type="button" data-dialog-open="modalLogout" title="Keluar dari Sistem"
                        class="btn btn-icon btn-secondary hover:!border-danger-border hover:!text-danger-text hover:!bg-danger-bg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="sr-only">Keluar</span>
                </button>
            </div>
            @endauth
        </div>

        @hasSection('subnav')
            <div class="max-w-[1280px] mx-auto mt-3">
                @yield('subnav')
            </div>
        @endif
    </header>

    @if (session()->hasAny(['status', 'info', 'warning', 'error']))
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-5 space-y-3" aria-live="polite">
        @include('partials.flash')
    </div>
    @endif

    <main id="konten" class="flex-grow max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-7 pb-16">
        @yield('content')
    </main>

    <footer class="w-full">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-text-secondary border-t border-border-subtle">
            <span class="font-semibold text-medical-navy">MedScribe AI</span>
            @auth
                <span>Sesi aktif: <span class="font-semibold text-medical-navy">{{ Auth::user()->nama_lengkap }}</span> ({{ $roleLabel }})</span>
            @endauth
        </div>
    </footer>

    @auth
    <x-dialog id="modalLogout" tone="danger" title="Konfirmasi"
              description="Apakah Anda yakin ingin keluar dari sistem MedScribe AI?"
              icon="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
        <x-slot:actions>
            <button type="button" id="btnBatalLogout" data-dialog-close data-dialog-focus class="btn btn-secondary">Batal</button>
            <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="m-0">
                @csrf
                <button type="submit" id="btnKonfirmasiLogout" class="btn btn-danger w-full">Keluar</button>
            </form>
        </x-slot:actions>
    </x-dialog>
    @endauth

    @yield('scripts')
    @stack('scripts')
</body>
</html>

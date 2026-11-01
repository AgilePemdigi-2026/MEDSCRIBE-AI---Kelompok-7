<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MedScribe AI - Sistem RME Cerdas')</title>
</head>
<body class="min-h-dvh flex flex-col app-backdrop text-text-body antialiased">

    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] btn btn-primary">Lewati ke konten utama</a>

    <header class="sticky top-0 z-40 px-3 sm:px-6 lg:px-8 pt-3">
        <div class="max-w-[1280px] mx-auto flex items-center justify-between gap-3 rounded-full bg-white/90 backdrop-blur-md border border-border-subtle pl-3 pr-2 py-2 shadow-[0_8px_30px_-12px_rgba(18,32,86,0.18)]">

            <a href="{{ route('dashboard') }}" class="rounded-full pr-2" aria-label="MedScribe AI, kembali ke dashboard">
                @include('partials.brand')
            </a>

            <div class="hidden lg:flex items-center gap-2 px-4 min-h-10 rounded-full bg-soft-offwhite border border-border-subtle text-xs text-text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y') }} · {{ Auth::user()?->hospital?->hospital_name ?? 'RS Universitas Airlangga' }}</span>
            </div>

            @auth
            @php
                $names = explode(' ', Auth::user()->nama_lengkap);
                $initials = '';
                foreach (array_slice($names, 0, 2) as $n) {
                    $initials .= strtoupper(substr($n, 0, 1));
                }
                $roleLabel = Auth::user()->isDokter() ? 'Dokter' : (Auth::user()->isPerawat() ? 'Perawat' : ucfirst(Auth::user()->role));
            @endphp
            <div class="flex items-center gap-1.5">
                <div class="flex items-center gap-2.5 rounded-full bg-soft-offwhite border border-border-subtle pl-1 pr-1 md:pr-4 py-1">
                    <span class="avatar w-9 h-9 bg-medical-navy text-white text-xs" aria-hidden="true">{{ $initials ?: 'U' }}</span>
                    <span class="hidden md:block leading-tight whitespace-nowrap">
                        <span class="block text-[13px] font-semibold text-medical-navy">{{ Auth::user()->nama_lengkap }}</span>
                        <span class="block text-[11px] text-text-secondary">{{ $roleLabel }}</span>
                    </span>
                </div>

                <button type="button" onclick="openModalLogout()" title="Keluar dari Sistem"
                        class="btn btn-icon btn-secondary hover:!border-danger-border hover:!text-danger-text hover:!bg-danger-bg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="sr-only">Keluar</span>
                </button>
            </div>
            @endauth
        </div>
    </header>

    @if (session('status') || session('error') || session('warning'))
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-5 space-y-3" role="status" aria-live="polite">
        @if (session('status'))
            <div id="flashNotificationStatus" class="alert alert-ok items-center rise">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                <div class="flex-1 flex flex-wrap items-center gap-2 text-text-body">
                    <span class="font-medium">{{ session('status') }}</span>
                    @if (session('new_patient_id'))
                        <span class="tag-id">{{ session('new_patient_id') }}</span>
                    @endif
                </div>
                <button type="button" onclick="document.getElementById('flashNotificationStatus')?.remove()" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div id="flashNotificationError" class="alert alert-danger items-center rise">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                <span class="flex-1 font-medium text-text-body">{{ session('error') }}</span>
                <button type="button" onclick="document.getElementById('flashNotificationError')?.remove()" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif

        @if (session('warning'))
            <div id="flashNotificationWarning" class="alert alert-warn items-center rise">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="flex-1 font-medium text-text-body">{{ session('warning') }}</span>
                <button type="button" onclick="document.getElementById('flashNotificationWarning')?.remove()" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif
    </div>
    @endif

    <main id="konten" class="flex-grow max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-7 pb-16">
        @yield('content')
    </main>

    <footer class="w-full">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-text-secondary border-t border-border-subtle">
            <span class="font-semibold text-medical-navy">MedScribe AI</span>
            @auth
                <span>Sesi aktif: <span class="font-semibold text-medical-navy">{{ Auth::user()->nama_lengkap }}</span> ({{ ucfirst(Auth::user()->role) }})</span>
            @endauth
        </div>
    </footer>

    @auth
    <div id="modalLogout" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalLogoutTitle">
        <div class="dialog-backdrop" onclick="closeModalLogout()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="dialog w-full max-w-sm p-6 pointer-events-auto animate-dialog text-center">
                <span class="mx-auto avatar w-12 h-12 bg-danger-bg text-danger-solid border border-danger-border" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                </span>
                <h2 id="modalLogoutTitle" class="mt-4 text-lg font-semibold text-medical-navy">Konfirmasi</h2>
                <p class="mt-1.5 text-sm text-text-secondary leading-relaxed">Apakah Anda yakin ingin keluar dari sistem MedScribe AI?</p>
                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button type="button" id="btnBatalLogout" onclick="closeModalLogout()" class="btn btn-secondary">Batal</button>
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="m-0">
                        @csrf
                        <button type="submit" id="btnKonfirmasiLogout" class="btn btn-danger w-full">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModalLogout() {
            const modal = document.getElementById('modalLogout');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.getElementById('btnBatalLogout')?.focus();
        }

        function closeModalLogout() {
            const modal = document.getElementById('modalLogout');
            if (!modal || modal.classList.contains('hidden')) return;
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModalLogout();
        });
    </script>
    @endauth

    @yield('scripts')
</body>
</html>

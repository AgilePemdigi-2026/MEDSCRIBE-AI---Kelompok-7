<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Rumah Sakit - MedScribe AI')</title>
</head>
<body class="min-h-dvh flex flex-col app-backdrop text-text-body antialiased">

    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] btn btn-primary">Lewati ke konten utama</a>

    {{-- Header Utama Admin --}}
    <header class="sticky top-0 z-40 px-3 sm:px-6 lg:px-8 pt-3">
        <div class="max-w-[1280px] mx-auto flex items-center justify-between gap-3 rounded-full bg-white/95 backdrop-blur-md border border-border-subtle pl-3 pr-2 py-2 shadow-[0_8px_30px_-12px_rgba(18,32,86,0.18)]">

            <a href="{{ route('admin.dashboard') }}" class="rounded-full pr-2 flex items-center gap-2.5" aria-label="MedScribe AI Admin Dashboard">
                @include('partials.brand')
                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-royal-indigo/10 text-royal-indigo uppercase tracking-wider border border-royal-indigo/20">
                    Admin RS
                </span>
            </a>

            <div class="hidden md:flex items-center gap-2 px-4 min-h-10 rounded-full bg-soft-offwhite border border-border-subtle text-xs text-text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-royal-indigo flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="font-medium text-medical-navy truncate max-w-[280px]">{{ Auth::user()?->hospital?->hospital_name ?? 'Rumah Sakit' }}</span>
                <span>· {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y') }}</span>
            </div>

            @auth
            @php
                $names = explode(' ', Auth::user()->nama_lengkap);
                $initials = '';
                foreach (array_slice($names, 0, 2) as $n) {
                    $initials .= strtoupper(substr($n, 0, 1));
                }
            @endphp
            <div class="flex items-center gap-1.5">
                <div class="flex items-center gap-2.5 rounded-full bg-soft-offwhite border border-border-subtle pl-1 pr-1 md:pr-4 py-1">
                    <span class="avatar w-9 h-9 bg-medical-navy text-white text-xs font-semibold" aria-hidden="true">{{ $initials ?: 'AD' }}</span>
                    <span class="hidden md:block leading-tight whitespace-nowrap text-left">
                        <span class="block text-[13px] font-semibold text-medical-navy">{{ Auth::user()->nama_lengkap }}</span>
                        <span class="block text-[11px] text-text-secondary">Administrator RS</span>
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

        {{-- Navigasi Tab / Menu Admin --}}
        @php
            $pendingBadgeCount = \App\Models\Pengguna::where('hospital_id', Auth::user()?->hospital_id)
                ->where('approval_status', 'pending')
                ->count();
        @endphp
        <nav class="max-w-[1280px] mx-auto mt-3 px-2 overflow-x-auto no-scrollbar" aria-label="Navigasi Menu Admin">
            <div class="inline-flex items-center gap-1.5 p-1.5 rounded-full bg-white/80 backdrop-blur-md border border-border-subtle shadow-sm min-w-full sm:min-w-0">
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('admin.doctors') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.doctors*') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Dokter
                </a>

                <a href="{{ route('admin.nurses') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.nurses*') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Perawat
                </a>

                <a href="{{ route('admin.pending') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.pending*') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Verifikasi Pendaftaran
                    @if ($pendingBadgeCount > 0)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-500 text-white min-w-4">
                            {{ $pendingBadgeCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.inactive') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.inactive*') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Tenaga Medis Nonaktif
                </a>

                <a href="{{ route('admin.activities') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.activities*') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Aktivitas
                </a>

                <a href="{{ route('admin.hospital.info') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium transition-all {{ request()->routeIs('admin.hospital.info*') ? 'bg-medical-navy text-white shadow-sm' : 'text-text-secondary hover:text-medical-navy hover:bg-soft-offwhite' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Profil RS
                </a>
            </div>
        </nav>
    </header>

    {{-- Flash Notifications --}}
    @if (session('status') || session('error') || session('warning') || session('info'))
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-4 space-y-3" role="status" aria-live="polite">
        @if (session('status'))
            <div id="flashNotificationStatus" class="alert alert-ok items-center rise">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" class="w-5 h-5 flex-shrink-0" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                <span class="flex-1 font-medium text-xs sm:text-sm text-text-body">{{ session('status') }}</span>
                <button type="button" onclick="document.getElementById('flashNotificationStatus')?.remove()" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div id="flashNotificationError" class="alert alert-danger items-center rise">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" class="w-5 h-5 flex-shrink-0" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span class="flex-1 font-medium text-xs sm:text-sm">{{ session('error') }}</span>
                <button type="button" onclick="document.getElementById('flashNotificationError')?.remove()" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif

        @if (session('warning'))
            <div id="flashNotificationWarning" class="alert alert-warn items-center rise">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" class="w-5 h-5 flex-shrink-0" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="flex-1 font-medium text-xs sm:text-sm">{{ session('warning') }}</span>
                <button type="button" onclick="document.getElementById('flashNotificationWarning')?.remove()" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        @endif
    </div>
    @endif

    {{-- Konten Utama --}}
    <main id="konten" class="flex-grow max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full pt-6 pb-16">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="w-full mt-auto">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-text-secondary border-t border-border-subtle">
            <span class="font-semibold text-medical-navy">MedScribe AI · Panel Administrasi Rumah Sakit</span>
            <span>Faskes: <span class="font-semibold text-medical-navy">{{ Auth::user()?->hospital?->hospital_name ?? '-' }}</span> (Kode: {{ Auth::user()?->hospital?->hospital_code ?? '-' }})</span>
        </div>
    </footer>

    {{-- Modal Dialog Konfirmasi Logout --}}
    <div id="modalLogout" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalLogoutTitle">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModalLogout()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="dialog w-full max-w-sm p-6 pointer-events-auto animate-dialog text-center bg-white rounded-[24px] shadow-2xl border border-border-subtle">
                <span class="mx-auto avatar w-12 h-12 bg-danger-bg text-danger-solid border border-danger-border flex items-center justify-center rounded-full mb-3" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </span>
                <h2 id="modalLogoutTitle" class="text-lg font-semibold text-medical-navy">Konfirmasi Keluar</h2>
                <p class="mt-1.5 text-xs text-text-secondary leading-relaxed">Apakah Anda yakin ingin keluar dari Panel Admin Rumah Sakit?</p>
                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button type="button" onclick="closeModalLogout()" class="btn btn-secondary justify-center">Batal</button>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger w-full justify-center">Keluar</button>
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
    @yield('scripts')
</body>
</html>


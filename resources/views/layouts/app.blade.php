<!DOCTYPE html>
<html lang="id" class="h-full bg-[#FAFAFD]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MedScribe AI - Sistem RME Cerdas')</title>
    
    <!-- Typography: Geist & Inter Fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Geist', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-medical-mesh text-[#20294A] flex flex-col justify-between antialiased selection:bg-[#5865DC] selection:text-[#FFFFFF]">

    <!-- Top Navigation Bar (Frosted Medical Navbar) -->
    <header class="w-full frosted-navbar sticky top-0 z-40 transition-all shadow-[0_2px_12px_rgba(18,32,86,0.03)]">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <!-- Logo & Brand Header -->
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-[12px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center transition-transform group-hover:scale-[1.04] shadow-[0_2px_10px_rgba(88,101,220,0.28)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-semibold tracking-tight text-[#122056]">MedScribe <span class="font-semibold text-[#5865DC]">AI</span></span>
                        <span class="hidden sm:inline-block px-2.5 py-0.5 text-[11px] font-medium tracking-wide text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] rounded-full">RME Kemenkes</span>
                    </div>
                </a>

                <div class="hidden md:block h-4 w-px bg-[#E4E7F4] mx-2"></div>

                <!-- Timestamp & Location Metadata -->
                <div class="hidden md:flex items-center gap-1.5 text-xs text-[#69718B] font-normal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ date('d F Y') }} · RS Universitas Airlangga</span>
                </div>
            </div>

            <!-- User Profile & Action Area -->
            @auth
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- User Information -->
                <div class="flex items-center gap-3 text-right">
                    <div class="hidden sm:block">
                        <div class="text-[13px] font-semibold text-[#122056] leading-tight">
                            {{ Auth::user()->nama_lengkap }}
                        </div>
                        <div class="text-[11px] text-[#69718B] flex items-center justify-end gap-1.5 mt-0.5">
                            @if (Auth::user()->isDokter())
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#5865DC]"></span>
                                <span class="text-[#5865DC] font-medium">Dokter · Poli Penyakit Dalam</span>
                            @elseif (Auth::user()->isPerawat())
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#5865DC]"></span>
                                <span class="text-[#5865DC] font-medium">Perawat · Nurse Station</span>
                            @else
                                <span class="capitalize text-[#69718B]">{{ ucfirst(Auth::user()->role) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Initials Avatar -->
                    @php
                        $names = explode(' ', Auth::user()->nama_lengkap);
                        $initials = '';
                        foreach (array_slice($names, 0, 2) as $n) {
                            $initials .= strtoupper(substr($n, 0, 1));
                        }
                    @endphp
                    <div class="w-9 h-9 rounded-[12px] bg-gradient-to-br from-[#EEEEFD] to-[#E4E7F4] border border-[#CBD1EA] text-[#122056] flex items-center justify-center font-semibold text-xs select-none shadow-xs">
                        {{ $initials ?: 'U' }}
                    </div>
                </div>

                <div class="h-5 w-px bg-[#E4E7F4]"></div>

                <!-- Tombol Logout (Memicu Modal Konfirmasi) -->
                <button
                    type="button"
                    onclick="openModalLogout()"
                    title="Keluar dari Sistem"
                    class="px-3 py-1.5 rounded-[14px] text-[#69718B] hover:text-[#DC2626] bg-[#FFFFFF] hover:bg-[#FEF2F2] border border-[#E4E7F4] hover:border-[#FECACA] text-xs font-medium flex items-center gap-1.5 transition-all shadow-xs cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </div>
            @endauth
        </div>
    </header>

    <!-- Flash Notifications Container -->
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full mt-5">
        @if (session('status'))
            <div id="flashNotificationStatus" class="p-4 rounded-[18px] bg-[#FFFFFF] border border-[#A7F3D0] text-[#059669] text-xs flex items-center justify-between gap-3 shadow-[0_4px_18px_rgba(18,32,86,0.04)] mb-4">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="w-5 h-5 rounded-full bg-[#ECFDF5] border border-[#A7F3D0] text-[#059669] flex items-center justify-center text-[11px] font-bold flex-shrink-0">✓</span>
                    <span class="font-medium text-[#20294A]">{{ session('status') }}</span>
                    @if (session('new_patient_id'))
                        <span class="font-mono font-bold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2.5 py-0.5 rounded-[8px]">{{ session('new_patient_id') }}</span>
                    @endif
                </div>
                <button type="button" onclick="document.getElementById('flashNotificationStatus')?.remove()" class="text-[#69718B] hover:text-[#20294A] p-1 text-sm leading-none cursor-pointer flex-shrink-0" aria-label="Tutup notifikasi">✕</button>
            </div>
        @endif

        @if (session('error'))
            <div id="flashNotificationError" class="p-4 rounded-[18px] bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] text-xs flex items-center justify-between gap-3 shadow-[0_4px_18px_rgba(18,32,86,0.04)] mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] flex items-center justify-center text-[11px] font-bold flex-shrink-0">!</span>
                    <span class="font-medium text-[#20294A]">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="document.getElementById('flashNotificationError')?.remove()" class="text-[#69718B] hover:text-[#20294A] p-1 text-sm leading-none cursor-pointer flex-shrink-0" aria-label="Tutup notifikasi">✕</button>
            </div>
        @endif

        @if (session('warning'))
            <div id="flashNotificationWarning" class="p-4 rounded-[18px] bg-[#FFFFFF] border border-[#FDE68A] text-[#D97706] text-xs flex items-center justify-between gap-3 shadow-[0_4px_18px_rgba(18,32,86,0.04)] mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A] flex items-center justify-center text-[11px] font-bold flex-shrink-0">!</span>
                    <span class="font-medium text-[#20294A]">{{ session('warning') }}</span>
                </div>
                <button type="button" onclick="document.getElementById('flashNotificationWarning')?.remove()" class="text-[#69718B] hover:text-[#20294A] p-1 text-sm leading-none cursor-pointer flex-shrink-0" aria-label="Tutup notifikasi">✕</button>
            </div>
        @endif
    </div>

    <!-- Main Content Canvas -->
    <main class="flex-grow max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 w-full py-4 pb-14">
        @yield('content')
    </main>

    <!-- Medical Footer -->
    <footer class="w-full py-5 px-6 text-xs text-[#69718B] border-t border-[#E4E7F4] bg-[#FAFAFD]">
        <div class="max-w-[1280px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-[#122056]">MedScribe AI</span>
                <span class="text-[#CBD1EA]">|</span>
                <span>Kelompok 7 · S1 Sistem Informasi Universitas Airlangga</span>
            </div>
            <div class="text-[11px] text-[#69718B]">
                @auth
                    Sesi aktif: <span class="font-semibold text-[#122056]">{{ Auth::user()->nama_lengkap }}</span> ({{ ucfirst(Auth::user()->role) }})
                @else
                    Sistem RME Terenkripsi Standar Kemenkes
                @endauth
            </div>
        </div>
    </footer>

    @auth
    <!-- Modal Konfirmasi Logout (Struktur Klasik MedScribe AI) -->
    <div id="modalLogout" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalLogoutTitle">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-[#122056]/40 backdrop-blur-xs transition-opacity" onclick="closeModalLogout()"></div>

        <!-- Centered Dialog Container -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-[#FFFFFF] rounded-[20px] border border-[#E4E7F4] shadow-[0_14px_40px_rgba(18,32,86,0.12)] overflow-hidden">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E7F4] bg-[#FFFFFF]">
                    <h3 id="modalLogoutTitle" class="text-sm sm:text-base font-bold text-[#122056]">
                        Konfirmasi
                    </h3>
                    <button
                        type="button"
                        onclick="closeModalLogout()"
                        class="w-7 h-7 rounded-[10px] text-[#69718B] hover:text-[#122056] hover:bg-[#FAFAFD] flex items-center justify-center transition-colors cursor-pointer text-sm font-semibold"
                        aria-label="Tutup"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 text-sm text-[#20294A] bg-[#FFFFFF] leading-relaxed">
                    Apakah Anda yakin ingin keluar dari sistem MedScribe AI?
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-[#E4E7F4] bg-[#FAFAFD]">
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="inline m-0">
                        @csrf
                        <button
                            type="submit"
                            id="btnKonfirmasiLogout"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[14px] bg-[#DC2626] hover:bg-[#B91C1C] active:bg-[#991B1B] text-[#FFFFFF] font-medium text-xs sm:text-sm transition-all cursor-pointer shadow-xs"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                    <button
                        type="button"
                        onclick="closeModalLogout()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[14px] bg-[#FFFFFF] hover:bg-[#FAFAFD] text-[#69718B] hover:text-[#122056] border border-[#E4E7F4] hover:border-[#CBD1EA] font-medium text-xs sm:text-sm transition-all cursor-pointer shadow-xs"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#69718B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Batal</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Kontrol Modal Logout -->
    <script>
        function openModalLogout() {
            const modal = document.getElementById('modalLogout');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModalLogout() {
            const modal = document.getElementById('modalLogout');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModalLogout();
            }
        });
    </script>
    @endauth

    @yield('scripts')
</body>
</html>

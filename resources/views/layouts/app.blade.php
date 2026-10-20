<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MedScribe AI - Sistem RME Cerdas')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between antialiased">

    <!-- Top Navigation Bar MedScribe AI (Sesuai Mockup) -->
    <header class="w-full bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <!-- Logo & Brand -->
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-cyan-500 flex items-center justify-center text-white shadow-md shadow-sky-600/20 group-hover:scale-105 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-slate-900">MedScribe <span class="text-sky-600">AI</span></span>
                    </div>
                </a>

                <div class="hidden md:block h-5 w-px bg-slate-200 mx-1"></div>

                <!-- Timestamp / Lokasi -->
                <div class="hidden md:flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ date('d F Y') }} · RS Universitas Airlangga</span>
                </div>
            </div>

            <!-- User Profile & Logout -->
            @auth
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- User Info -->
                <div class="flex items-center gap-3 text-right">
                    <div class="hidden sm:block">
                        <div class="text-xs font-bold text-slate-900 leading-tight">
                            {{ Auth::user()->nama_lengkap }}
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center justify-end gap-1.5 mt-0.5">
                            @if (Auth::user()->isDokter())
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                <span>Dokter · Poli Penyakit Dalam</span>
                            @elseif (Auth::user()->isPerawat())
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Perawat · Nurse Station</span>
                            @else
                                <span>{{ ucfirst(Auth::user()->role) }}</span>
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
                        $avatarBg = Auth::user()->isDokter() ? 'bg-sky-100 text-sky-700 border-sky-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200';
                    @endphp
                    <div class="w-10 h-10 rounded-xl {{ $avatarBg }} border flex items-center justify-center font-bold text-sm shadow-xs select-none">
                        {{ $initials ?: 'U' }}
                    </div>
                </div>

                <div class="h-6 w-px bg-slate-200"></div>

                <!-- Tombol Logout -->
                <form method="POST" action="{{ route('logout') }}" id="logoutForm" class="inline">
                    @csrf
                    <button
                        type="submit"
                        title="Keluar dari Sistem"
                        class="p-2 sm:px-3 sm:py-2 rounded-xl text-slate-600 hover:text-rose-600 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                        onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem MedScribe AI?');"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if (session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between gap-3 shadow-xs mb-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
            </div>
        @endif

        @if (session('warning'))
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center justify-between gap-3 shadow-xs mb-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('warning') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700">✕</button>
            </div>
        @endif
    </div>

    <!-- Main Body -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-4 pb-12">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="w-full py-4 px-6 text-center text-xs text-slate-500 border-t border-slate-200 bg-white">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>© 2026 <strong>MedScribe AI</strong> — Kelompok 7 (Kelas I4) S1 Sistem Informasi UNAIR.</p>
            <p class="text-[11px] text-slate-400">Sesi terotentikasi sebagai <span class="font-semibold text-slate-700">{{ Auth::user()->nama_lengkap ?? '' }} ({{ ucfirst(Auth::user()->role ?? '') }})</span></p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>

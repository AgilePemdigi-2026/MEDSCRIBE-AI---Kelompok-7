<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'MedScribe AI - Autentikasi')</title>
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
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-sky-50/40 to-blue-50/30 text-slate-800 flex flex-col justify-between antialiased">

    <!-- Header Sederhana -->
    <header class="w-full py-4 px-6 sm:px-12 flex items-center justify-between border-b border-slate-200/60 bg-white/70 backdrop-blur-md sticky top-0 z-20">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-cyan-500 flex items-center justify-center text-white shadow-md shadow-sky-600/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div>
                <span class="text-xl font-bold tracking-tight text-slate-900">MedScribe <span class="text-sky-600">AI</span></span>
                <span class="hidden sm:inline-block ml-2 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-sky-100 text-sky-700 rounded-md border border-sky-200">RME Cerdas</span>
            </div>
        </div>

        <div class="text-xs text-slate-500 hidden sm:flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Server MySQL Aktif</span>
            <span class="text-slate-300">•</span>
            <span>v1.0 Sprint 1</span>
        </div>
    </header>

    <!-- Konten Utama Halaman Autentikasi -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-md my-auto">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-4 px-6 text-center text-xs text-slate-500 border-t border-slate-200/60 bg-white/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>© 2026 <strong>MedScribe AI</strong> — Kelompok 7 (Kelas I4) S1 Sistem Informasi Universitas Airlangga.</p>
            <p class="text-[11px] text-slate-400">Keamanan Sesi Terenkripsi & Sesuai Standar RME Kemenkes</p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>

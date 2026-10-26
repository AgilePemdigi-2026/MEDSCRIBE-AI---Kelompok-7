<!DOCTYPE html>
<html lang="id" class="h-full bg-[#FAFAFD]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'MedScribe AI - Autentikasi')</title>

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

    <!-- Header Sederhana Frosted Medical Navbar -->
    <header class="w-full py-4 px-6 sm:px-12 flex items-center justify-between border-b border-[#E4E7F4] frosted-navbar sticky top-0 z-30 shadow-[0_2px_12px_rgba(18,32,86,0.03)]">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-[12px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center shadow-[0_2px_10px_rgba(88,101,220,0.28)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-base font-semibold tracking-tight text-[#122056]">MedScribe <span class="font-semibold text-[#5865DC]">AI</span></span>
                <span class="hidden sm:inline-block px-2.5 py-0.5 text-[11px] font-medium tracking-wide text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] rounded-full">RME Cerdas</span>
            </div>
        </div>

        <div class="text-xs text-[#69718B] hidden sm:flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
            <span>Server Aktif</span>
            <span class="text-[#CBD1EA]">·</span>
            <span>v1.0 Produksi</span>
        </div>
    </header>

    <!-- Konten Utama Halaman Autentikasi -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-[460px] my-auto">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-5 px-6 text-center text-xs text-[#69718B] border-t border-[#E4E7F4] bg-[#FAFAFD]">
        <div class="max-w-[1280px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-[#122056]">MedScribe AI</span>
                <span class="text-[#CBD1EA]">|</span>
                <span>Kelompok 7 · S1 Sistem Informasi Universitas Airlangga</span>
            </div>
            <p class="text-[11px] text-[#69718B]">Keamanan Sesi Terenkripsi & Sesuai Standar RME Kemenkes</p>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>

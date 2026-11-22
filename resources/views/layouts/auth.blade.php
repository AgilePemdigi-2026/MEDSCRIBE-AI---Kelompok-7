<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>@yield('title', 'MedScribe AI - Autentikasi')</title>
</head>
<body class="min-h-dvh app-backdrop text-text-body antialiased">

    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] btn btn-primary">Lewati ke formulir</a>

    <div class="min-h-dvh flex flex-col p-3 sm:p-5">
        <div class="flex-grow grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] gap-4">

            @php
                $logoUrl = asset("images/logo-medscribe.png");
            @endphp

            {{-- Panel navy: identitas sistem dan inti isometrik yang menjelaskan alur rekam medis saat di-hover. --}}
            <aside class="hidden lg:flex card-navy flex-col gap-8 p-8 xl:p-10 rise">
                <span class="relative inline-flex items-center gap-2.5">
                    {{-- Ubin terang di belakang logo agar huruf M navy tetap terbaca di atas panel navy --}}
                    <span class="w-12 h-12 rounded-[14px] bg-white flex items-center justify-center shadow-[0_8px_20px_-8px_rgba(18,32,86,0.35)]">
                        <img src="{{ $logoUrl }}" srcset="{{ $logoUrl }} 1x, {{ str_replace(".png", "@2x.png", $logoUrl) }} 2x"
                             width="40" height="40" alt="" class="w-10 h-10">
                    </span>
                    <span class="text-lg font-semibold tracking-tight">MedScribe <span class="text-on-navy-muted">AI</span></span>
                </span>

                <div class="relative flex-grow flex flex-col justify-center gap-8">
                    @include("partials.hero.inti", ["ukuran" => "360px"])
                    <div class="text-center">
                        <p class="text-[clamp(1.8rem,2.4vw,2.4rem)] leading-[1.1] font-semibold tracking-[-0.035em]">
                            Sistem Rekam Medis<br>Elektronik <span class="text-on-navy-muted">Terintegrasi</span>
                        </p>
                        <p class="mt-3 mx-auto max-w-sm text-sm text-on-navy-soft leading-relaxed">
                            Satu tempat bagi perawat dan dokter untuk mencatat pemeriksaan pasien, dari tanda vital sampai dokumen SOAP.
                        </p>
                    </div>
                </div>
            </aside>

            <div class="card flex flex-col">
                <header class="lg:hidden px-5 pt-5">
                    @include('partials.brand')
                </header>

                <main id="konten" class="flex-grow flex items-start sm:items-center justify-center px-5 sm:px-10 py-8 sm:py-12">
                    <div class="w-full max-w-[420px] rise" style="--i: 1">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    @yield('scripts')
</body>
</html>

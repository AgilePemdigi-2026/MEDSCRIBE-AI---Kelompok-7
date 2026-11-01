<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    {{-- Caveat: tulisan tangan untuk keterangan foto polaroid --}}
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&display=swap" rel="stylesheet">
    <title>@yield('title', 'MedScribe AI - Autentikasi')</title>
</head>
<body class="min-h-dvh app-backdrop text-text-body antialiased">

    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] btn btn-primary">Lewati ke formulir</a>

    <div class="min-h-dvh flex flex-col p-3 sm:p-5">
        <div class="flex-grow grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] gap-4">

            {{-- Panel navy: identitas sistem dan cuplikan fitur untuk dokter & perawat. --}}
            <aside class="hidden lg:flex card-navy spotlight flex-col justify-between gap-6 p-8 xl:p-10 rise">
                <div class="relative">
                    <span class="inline-flex items-center gap-2.5">
                        {{-- Ubin terang di belakang logo agar huruf M navy tetap terbaca di atas panel navy --}}
                        <span class="w-12 h-12 rounded-[14px] bg-white flex items-center justify-center shadow-[0_8px_20px_-8px_rgba(0,0,0,0.5)]">
                            <img src="{{ asset('images/logo-medscribe.png') }}" srcset="{{ asset('images/logo-medscribe.png') }} 1x, {{ asset('images/logo-medscribe@2x.png') }} 2x"
                                 width="40" height="40" alt="" class="w-10 h-10">
                        </span>
                        <span class="text-lg font-semibold tracking-tight">MedScribe <span class="text-[#A5ADD8]">AI</span></span>
                    </span>
                </div>

                <div class="relative">
                    <p class="text-[clamp(1.9rem,2.6vw,2.6rem)] leading-[1.08] font-semibold tracking-[-0.035em]">
                        Sistem Rekam Medis<br>Elektronik <span class="text-[#A5ADD8]">Terintegrasi</span>
                    </p>

                    <div class="mt-6">
                        @include('partials.polaroid-carousel')
                    </div>
                </div>

                <span aria-hidden="true"></span>
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

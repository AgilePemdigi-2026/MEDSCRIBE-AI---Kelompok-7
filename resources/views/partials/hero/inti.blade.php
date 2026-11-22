{{--
    Inti isometrik bertema kesehatan (diadaptasi dari 21st.dev "Syntra Hero" oleh clevision, sisi kanan).
    Diam: lima lapisan bertumpuk rapat dengan ikon kesehatan mengapung. Saat di-hover/fokus/ketuk:
    lapisan terbuka ke atas dan tiap lapisan menampilkan penjelasan tahap rekam medis.
    Parameter: ukuran (CSS length, tinggi panggung).
--}}
@php
    // Tahap dari bawah (lapisan 0) ke atas (lapisan 4). Sisi kartu bergantian kanan-kiri seperti referensi.
    $tahap = [
        ['01', 'Pendaftaran pasien', 'Data diri dicatat perawat', 'kanan'],
        ['02', 'Tanda-tanda vital', 'Tekanan darah, nadi, suhu, SpO2', 'kiri'],
        ['03', 'Wawancara klinis', 'Percakapan direkam jadi transkrip', 'kanan'],
        ['04', 'Draf SOAP', 'Disusun AI, ditinjau dokter', 'kiri'],
        ['05', 'Rekam medis final', 'Dikunci dan siap dicetak', 'kanan'],
    ];

    // Ikon garis gaya Lucide (stroke 1.75) yang mengapung saat tumpukan tertutup.
    $chip = [
        ['16%', '22%', '0s', false, '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>'],
        ['84%', '28%', '1.2s', true, '<path d="M11 2v2"/><path d="M5 2v2"/><path d="M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1"/><path d="M8 15a6 6 0 0 0 12 0v-3"/><circle cx="20" cy="10" r="2"/>'],
        ['14%', '76%', '2.1s', false, '<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>'],
        ['86%', '78%', '0.6s', false, '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>'],
        ['50%', '5%', '1.7s', false, '<path d="M12 7v10"/><path d="M7 12h10"/><rect x="3" y="3" width="18" height="18" rx="4"/>'],
    ];
@endphp

<div class="hero-core" style="--ukuran: {{ $ukuran ?? '320px' }}" tabindex="0" data-hero-core
     aria-label="Alur rekam medis MedScribe AI, lima tahap. Arahkan kursor atau tekan untuk melihat penjelasan.">

    <div class="hero-core-art" aria-hidden="true">
        <span class="hero-core-ring"></span>
        <span class="hero-core-ring is-inner"></span>

        <div class="hero-core-iso">
            <div class="hero-core-stack">
                @foreach ($tahap as $i => [$nomor])
                    <div class="hero-core-layer" style="--i: {{ $i }}">
                        {{-- Tiga lempeng tipis memberi kesan tebal; makin atas makin terang. --}}
                        <span class="hero-core-slab" style="--z: 0px"></span>
                        <span class="hero-core-slab" style="--z: 4px"></span>
                        <span class="hero-core-face">
                            @if ($i === count($tahap) - 1)
                                {{-- Puncak: papan klip rekam medis dengan tanda tambah --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="8" y="2" width="8" height="4" rx="1"/>
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                    <path d="M9 14h6"/><path d="M12 17v-6"/>
                                </svg>
                            @else
                                <span class="hero-core-num">{{ $nomor }}</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        @foreach ($chip as [$x, $y, $tunda, $aksen, $ikon])
            <span @class(['hero-core-chip', 'is-accent' => $aksen]) style="--x: {{ $x }}; --y: {{ $y }}; --tunda: {{ $tunda }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
            </span>
        @endforeach
    </div>

    {{-- Kartu penjelasan: tampil saat tumpukan terbuka, posisinya mengikuti tinggi tiap lapisan. --}}
    <ol class="hero-core-labels">
        @foreach ($tahap as $i => [$nomor, $judul, $isi, $sisi])
            <li class="hero-core-label is-{{ $sisi }}" style="--i: {{ $i }}">
                <span class="hero-core-label-num">{{ $nomor }}</span>
                <span class="min-w-0">
                    <span class="hero-core-label-title">{{ $judul }}</span>
                    <span class="hero-core-label-text">{{ $isi }}</span>
                </span>
            </li>
        @endforeach
    </ol>

    <p class="hero-core-hint" aria-hidden="true">Arahkan kursor untuk melihat alurnya</p>
</div>

<script>
    // Layar sentuh tidak punya hover: ketukan membuka/menutup tumpukan.
    document.querySelectorAll('[data-hero-core]').forEach(function (core) {
        core.addEventListener('click', function () { core.classList.toggle('is-open'); });
        core.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); core.classList.toggle('is-open'); }
            if (e.key === 'Escape') core.classList.remove('is-open');
        });
    });
</script>

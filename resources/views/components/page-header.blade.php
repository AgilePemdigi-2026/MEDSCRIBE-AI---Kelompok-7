{{--
    Kepala halaman standar: eyebrow, judul (h1), subjudul, dan aksi di kanan.

    Props: title (wajib), eyebrow, subtitle
    Slot:  $slot untuk tombol atau badge di sisi kanan (opsional)
--}}
@props([
    'title',
    'eyebrow' => null,
    'subtitle' => null,
])

<header {{ $attributes->class(['flex flex-col sm:flex-row sm:items-end justify-between gap-4 rise']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 @class(['page-title', 'mt-2' => $eyebrow])>{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{ $slot }}
        </div>
    @endif
</header>

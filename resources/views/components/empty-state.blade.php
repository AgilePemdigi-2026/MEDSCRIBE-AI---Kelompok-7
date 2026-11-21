{{--
    Keadaan kosong standar untuk tabel atau daftar.

    Props: title (wajib), hint, icon (path SVG 24x24, atribut d)
    Slot:  $slot untuk tombol aksi (opsional), misalnya "Hapus filter"
--}}
@props([
    'title',
    'hint' => null,
    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
])

<div {{ $attributes->class(['empty-state']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    <p class="empty-state-title">{{ $title }}</p>
    @if ($hint)
        <p class="empty-state-hint">{{ $hint }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>

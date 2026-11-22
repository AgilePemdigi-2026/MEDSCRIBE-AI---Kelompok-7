{{--
    Ikon Lucide dengan gerak halus saat tombol/tautan induknya di-hover, difokus, atau ditekan.

    Props:
      name   nama ikon Lucide (lihat resources/icons/lucide.php)
      path   cadangan: path SVG gaya heroicons lama; dipetakan ke Lucide bila dikenal
      stroke tebal garis (default 1.75, seragam dengan ikon UI lain)
      static true untuk mematikan gerak (mis. ikon di alert atau label)
    Atribut lain (class, data-*, aria-*) diteruskan ke <svg>. Tanpa aria-label, ikon dianggap dekoratif.
--}}
@props([
    'name' => null,
    'path' => null,
    'stroke' => '1.75',
    'static' => false,
])

@php
    $nama = $name ?? \App\Support\Ikon::namaDariPath($path);
    $ikon = $nama ? \App\Support\Ikon::get($nama) : null;
    $gerak = (! $static && $ikon) ? $ikon['gerak'] : null;
    $dekoratif = ! $attributes->has('aria-label') && ! $attributes->has('aria-hidden');
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->class(['ikon', 'w-4 h-4' => ! str_contains((string) $attributes->get('class'), 'w-')]) }}
     @if ($gerak) data-gerak="{{ $gerak }}" @endif
     @if ($dekoratif) aria-hidden="true" focusable="false" @endif>
    @if ($ikon)
        {!! $ikon['isi'] !!}
    @elseif ($path)
        <path d="{{ $path }}"/>
    @endif
</svg>

{{--
    Dialog konfirmasi standar. Dibuka dengan <button data-dialog-open="{id}"> atau openDialog('{id}').
    Ditutup dengan [data-dialog-close], klik latar, atau Escape (lihat resources/js/app.js).

    Props:
      id          wajib, unik per halaman
      title       judul dialog
      description kalimat penjelas di bawah judul (opsional)
      tone        default | danger | warn | ok (warna ikon)
      icon        path SVG ikon 24x24 (atribut d); default sesuai tone
      size        sm (default) | md, untuk dialog berisi formulir
      static      true agar tidak tertutup lewat latar atau Escape

    Slot:
      $slot       isi tambahan di bawah deskripsi (misalnya formulir alasan), rata kiri
      $actions    tombol aksi; ditata dua kolom. Tombol batal cukup diberi data-dialog-close.
                  Kalau formulir ada di $slot, tombol kirim di sini memakai atribut form="idFormulir".
--}}
@props([
    'id',
    'title',
    'description' => null,
    'tone' => 'default',
    'icon' => null,
    'size' => 'sm',
    'static' => false,
])

@php
    $icons = [
        'danger' => 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
        'warn' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'ok' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'default' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    $toneClass = ['danger' => 'dialog-icon-danger', 'warn' => 'dialog-icon-warn', 'ok' => 'dialog-icon-ok'][$tone] ?? '';
    $iconPath = $icon ?? ($icons[$tone] ?? $icons['default']);
    $width = $size === 'md' ? 'max-w-md' : 'max-w-sm';
@endphp

<div id="{{ $id }}" data-dialog @if ($static) data-dialog-static @endif
     class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title"
     @if ($description) aria-describedby="{{ $id }}-desc" @endif>
    <div class="dialog-backdrop"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div {{ $attributes->class(['dialog w-full p-6 pointer-events-auto animate-dialog', $width]) }}>
            <div class="text-center">
                <span class="dialog-icon {{ $toneClass }}" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                </span>
                <h2 id="{{ $id }}-title" class="dialog-title">{{ $title }}</h2>
                @if ($description)
                    <p id="{{ $id }}-desc" class="dialog-body">{{ $description }}</p>
                @endif
            </div>

            @if (trim($slot) !== '')
                <div class="mt-5 text-left">
                    {{ $slot }}
                </div>
            @endif

            @isset($actions)
                <div class="dialog-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</div>

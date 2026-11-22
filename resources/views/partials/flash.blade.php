{{--
    Notifikasi sesi (flash) yang sama untuk semua layout: status, info, warning, error.
    Parameter opsional: dismissible (default true), only (daftar jenis yang ditampilkan,
    mis. ['info', 'warning', 'error']). Pesan warning yang juga muncul sebagai
    error validasi tidak ditampilkan dua kali.
--}}
@php
    $dismissible = $dismissible ?? true;
    $validationMessages = isset($errors) ? $errors->all() : [];
    $flashes = collect([
        ['key' => 'status', 'class' => 'alert-ok', 'role' => 'status'],
        ['key' => 'info', 'class' => 'alert-info', 'role' => 'status'],
        ['key' => 'warning', 'class' => 'alert-warn', 'role' => 'alert'],
        ['key' => 'error', 'class' => 'alert-danger', 'role' => 'alert'],
    ])->filter(fn ($flash) => filled(session($flash['key'])) && ! in_array(session($flash['key']), $validationMessages, true))
      ->filter(fn ($flash) => ! isset($only) || in_array($flash['key'], $only, true));
@endphp

@foreach ($flashes as $flash)
    <div class="alert {{ $flash['class'] }} items-center rise" role="{{ $flash['role'] }}" data-alert>
        @switch($flash['key'])
            @case('status')
                <x-icon name="check" aria-hidden="true" />
                @break
            @case('info')
                <x-icon name="info" aria-hidden="true" />
                @break
            @case('warning')
                <x-icon name="circle-alert" aria-hidden="true" />
                @break
            @default
                <x-icon name="triangle-alert" aria-hidden="true" />
        @endswitch

        <div class="flex-1 flex flex-wrap items-center gap-2 font-medium text-text-body">
            <span>{{ session($flash['key']) }}</span>
            @if ($flash['key'] === 'status' && session('new_patient_id'))
                <span class="tag-id">{{ session('new_patient_id') }}</span>
            @endif
        </div>

        @if ($dismissible)
            <button type="button" data-alert-dismiss class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                <x-icon name="x" class="w-4 h-4" aria-hidden="true" />
            </button>
        @endif
    </div>
@endforeach

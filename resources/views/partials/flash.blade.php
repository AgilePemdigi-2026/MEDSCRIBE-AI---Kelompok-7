{{--
    Notifikasi sesi (flash) yang sama untuk semua layout: status, info, warning, error.
    Parameter opsional: dismissible (default true). Pesan warning yang juga muncul sebagai
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
    ])->filter(fn ($flash) => filled(session($flash['key'])) && ! in_array(session($flash['key']), $validationMessages, true));
@endphp

@foreach ($flashes as $flash)
    <div class="alert {{ $flash['class'] }} items-center rise" role="{{ $flash['role'] }}" data-alert>
        @switch($flash['key'])
            @case('status')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                @break
            @case('info')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @break
            @case('warning')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @break
            @default
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
        @endswitch

        <div class="flex-1 flex flex-wrap items-center gap-2 font-medium text-text-body">
            <span>{{ session($flash['key']) }}</span>
            @if ($flash['key'] === 'status' && session('new_patient_id'))
                <span class="tag-id">{{ session('new_patient_id') }}</span>
            @endif
        </div>

        @if ($dismissible)
            <button type="button" data-alert-dismiss class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup notifikasi">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        @endif
    </div>
@endforeach

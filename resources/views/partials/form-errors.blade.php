{{--
    Ringkasan error validasi formulir. Parameter opsional: bag (nama error bag), title.
    Satu pesan tampil sebagai kalimat; beberapa pesan tampil sebagai daftar.
--}}
@php
    $bagErrors = isset($errors) ? $errors->getBag($bag ?? 'default') : null;
@endphp

@if ($bagErrors && $bagErrors->any())
    <div class="alert alert-danger" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
        <div class="flex-1 min-w-0">
            @if ($bagErrors->count() === 1)
                <p class="font-medium">{{ $bagErrors->first() }}</p>
            @else
                <p class="font-semibold">{{ $title ?? 'Periksa kembali isian berikut:' }}</p>
                <ul class="mt-1 list-disc pl-5 space-y-0.5 text-text-body">
                    @foreach ($bagErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif

{{--
    Ringkasan error validasi formulir. Parameter opsional: bag (nama error bag), title.
    Satu pesan tampil sebagai kalimat; beberapa pesan tampil sebagai daftar.
--}}
@php
    $bagErrors = isset($errors) ? $errors->getBag($bag ?? 'default') : null;
@endphp

@if ($bagErrors && $bagErrors->any())
    <div class="alert alert-danger" role="alert">
        <x-icon name="triangle-alert" aria-hidden="true" />
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

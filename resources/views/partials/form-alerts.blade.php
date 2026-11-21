{{-- Notifikasi untuk halaman autentikasi: flash sesi lalu error validasi, dengan jarak ke formulir. --}}
@if (session()->hasAny(['status', 'info', 'warning', 'error']) || $errors->any())
    <div class="mb-5 space-y-3">
        @include('partials.flash', ['dismissible' => false])
        @include('partials.form-errors')
    </div>
@endif

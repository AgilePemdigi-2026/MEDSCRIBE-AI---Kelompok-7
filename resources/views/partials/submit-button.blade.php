{{-- Tombol kirim utama dengan status memproses. Parameter: label, loading, id, class, variant --}}
<button type="submit" id="{{ $id ?? 'submitBtn' }}" data-loading-text="{{ $loading }}" class="btn {{ $variant ?? 'btn-primary' }} {{ $class ?? 'w-full' }}">
    <span data-spinner class="spinner hidden" aria-hidden="true"></span>
    <span data-label>{{ $label }}</span>
</button>

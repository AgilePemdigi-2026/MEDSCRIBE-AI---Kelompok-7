{{-- Tombol kirim utama dengan status memproses. Parameter: label, loading, id, class, variant --}}
<button type="submit" id="{{ $id ?? 'submitBtn' }}" data-loading-text="{{ $loading }}" class="btn {{ $variant ?? 'btn-primary' }} {{ $class ?? 'w-full' }}">
    <svg data-spinner class="hidden animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    <span data-label>{{ $label }}</span>
</button>

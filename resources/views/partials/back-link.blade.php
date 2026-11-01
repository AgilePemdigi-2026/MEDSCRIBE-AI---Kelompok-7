{{-- Tombol kembali yang seragam di semua halaman. Parameter: href, label --}}
<a href="{{ $href }}" class="btn btn-secondary btn-sm group !pl-1.5 !pr-4 font-semibold">
    <span class="avatar w-8 h-8 sm:w-7 sm:h-7 bg-pale-lavender text-royal-indigo transition-colors group-hover:bg-royal-indigo group-hover:text-white" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
    </span>
    {{ $label }}
</a>

{{-- Tombol kembali yang seragam di semua halaman. Parameter: href, label --}}
<a href="{{ $href }}" class="btn btn-secondary btn-sm group !pl-1.5 !pr-4 font-semibold">
    <span class="avatar w-8 h-8 sm:w-7 sm:h-7 bg-pale-lavender text-royal-indigo transition-colors group-hover:bg-royal-indigo group-hover:text-white" aria-hidden="true">
        <x-icon name="arrow-left" class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" />
    </span>
    {{ $label }}
</a>

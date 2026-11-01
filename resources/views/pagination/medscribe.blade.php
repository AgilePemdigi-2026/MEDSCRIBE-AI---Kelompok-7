@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <p class="text-text-secondary">
            Menampilkan <span class="num font-semibold text-medical-navy">{{ $paginator->firstItem() }}</span>
            sampai <span class="num font-semibold text-medical-navy">{{ $paginator->lastItem() }}</span>
            dari <span class="num font-semibold text-medical-navy">{{ $paginator->total() }}</span> pasien
        </p>

        <ul class="flex flex-wrap items-center gap-1 p-1 rounded-full bg-white border border-border-subtle">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-ghost btn-sm" aria-disabled="true">Sebelumnya</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-ghost btn-sm">Sebelumnya</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-text-secondary" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="btn btn-navy btn-sm min-w-11 sm:min-w-9 num">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-ghost btn-sm min-w-11 sm:min-w-9 num" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-ghost btn-sm">Berikutnya</a>
                @else
                    <span class="btn btn-ghost btn-sm" aria-disabled="true">Berikutnya</span>
                @endif
            </li>
        </ul>
    </nav>
@endif

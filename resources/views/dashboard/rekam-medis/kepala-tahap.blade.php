{{--
    Kepala tiap tahap rekam medis.
    Parameter: nomor, judul, keterangan, keadaan (done|active|locked|soon), statusLabel, statusClass, idJudul
--}}
<header class="flex items-start gap-4 p-5 sm:p-6">
    @if ($keadaan === 'done')
        <span class="avatar w-10 h-10 bg-success-bg text-success-text border border-success-border" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
        </span>
    @elseif ($keadaan === 'active')
        <span class="avatar w-10 h-10 bg-royal-indigo text-white text-sm ring-4 ring-royal-indigo/15 shadow-[0_8px_18px_-8px_rgba(88,101,220,0.8)]" aria-hidden="true">{{ $nomor }}</span>
    @elseif ($keadaan === 'locked')
        <span class="avatar w-10 h-10 bg-soft-offwhite text-text-secondary border border-border-subtle" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
        </span>
    @else
        <span class="avatar w-10 h-10 bg-soft-offwhite text-text-secondary text-sm border border-dashed border-border-hover" aria-hidden="true">{{ $nomor }}</span>
    @endif

    <div class="flex-1 min-w-0">
        <h2 id="{{ $idJudul }}" class="text-base sm:text-lg font-semibold text-medical-navy leading-tight">{{ $judul }}</h2>
        <p class="mt-1 text-xs text-text-secondary">{{ $keterangan }}</p>
    </div>

    <span class="status {{ $statusClass }} self-start">{{ $statusLabel }}</span>
</header>

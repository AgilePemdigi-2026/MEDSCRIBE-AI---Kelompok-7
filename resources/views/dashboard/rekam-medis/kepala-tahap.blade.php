{{--
    Kepala tiap tahap rekam medis.
    Parameter: nomor, judul, keterangan, keadaan (done|active|locked|soon), statusLabel, statusClass, idJudul
--}}
<header class="flex items-start gap-4 p-5 sm:p-6">
    @if ($keadaan === 'done')
        <span class="avatar w-10 h-10 bg-success-bg text-success-text border border-success-border" aria-hidden="true">
            <x-icon name="check" class="w-4 h-4" />
        </span>
    @elseif ($keadaan === 'active')
        <span class="avatar w-10 h-10 bg-royal-indigo text-white text-sm ring-4 ring-royal-indigo/15 shadow-[0_8px_18px_-8px_rgba(88,101,220,0.8)]" aria-hidden="true">{{ $nomor }}</span>
    @elseif ($keadaan === 'locked')
        <span class="avatar w-10 h-10 bg-soft-offwhite text-text-secondary border border-border-subtle" aria-hidden="true">
            <x-icon name="lock" class="w-4 h-4" />
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

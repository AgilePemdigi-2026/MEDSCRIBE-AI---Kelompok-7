{{-- Label status antrean. Parameter: status, statusText (opsional, menimpa teks status) --}}
@php $text = $statusText ?? $status; @endphp
@if ($status === 'Siap Periksa')
    <span class="status status-ok">{{ $text }}</span>
@elseif ($status === 'Menunggu TTV')
    <span class="status status-warn">{{ $text }}</span>
@elseif (in_array($status, ['Sedang Diperiksa', 'Draf SOAP Tersedia']))
    <span class="status status-active">{{ $text }}</span>
@else
    <span class="status status-muted">{{ $text }}</span>
@endif

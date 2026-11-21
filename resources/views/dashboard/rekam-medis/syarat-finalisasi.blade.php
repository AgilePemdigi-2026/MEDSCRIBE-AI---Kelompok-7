{{--
    Daftar syarat kelayakan finalisasi. Dipakai sebelum dan sesudah finalisasi.
    Memakai variabel dari rekam-medis.blade.php: $soapSelesai, $ttvSelesai, $wawancaraSelesai,
    $tindakLanjutSelesai, $tindakLanjut.
--}}
@php
    $daftarSyarat = [
        [$soapSelesai, 'Draf SOAP', 'Sudah diverifikasi dokter', 'Belum diverifikasi atau ada bagian yang kosong'],
        [$ttvSelesai, 'TTV & Catatan Perawat', 'Sudah diisi perawat', 'Belum lengkap diisi perawat'],
        [$wawancaraSelesai, 'Transkrip wawancara', 'Sudah diverifikasi', 'Belum diverifikasi'],
        [$tindakLanjutSelesai, 'Tindak lanjut medis', $tindakLanjut?->status ?: 'Tersimpan', 'Belum disimpan'],
    ];
@endphp

<ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" aria-label="Syarat finalisasi">
    @foreach ($daftarSyarat as [$terpenuhi, $judulSyarat, $teksYa, $teksTidak])
        <li @class([
            'flex items-center gap-3 p-3.5 rounded-(--radius-field) border text-sm',
            'bg-success-bg border-success-border' => $terpenuhi,
            'bg-warning-bg border-warning-border' => ! $terpenuhi,
        ])>
            <span @class([
                'avatar w-7 h-7 border',
                'bg-white text-success-text border-success-border' => $terpenuhi,
                'bg-white text-warning-text border-warning-border' => ! $terpenuhi,
            ]) aria-hidden="true">
                @if ($terpenuhi)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" /></svg>
                @endif
            </span>
            <span class="min-w-0">
                <span class="block font-semibold text-medical-navy">{{ $judulSyarat }}</span>
                <span class="block text-xs {{ $terpenuhi ? 'text-success-text' : 'text-warning-text' }}">
                    <span class="sr-only">{{ $terpenuhi ? 'Terpenuhi:' : 'Belum terpenuhi:' }}</span>
                    {{ $terpenuhi ? $teksYa : $teksTidak }}
                </span>
            </span>
        </li>
    @endforeach
</ul>

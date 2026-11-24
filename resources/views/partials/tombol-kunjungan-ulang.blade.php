{{--
    Tombol kunjungan ulang pasien lama: membuat antrean + kunjungan baru tanpa No. RM baru.
    Kalau pasien masih punya antrean berjalan, tombol diganti tautan ke antrean itu.

    Variabel: $pasien  (dengan relasi antreanAktif)
              $class   kelas tambahan tombol (opsional, untuk versi berlabel)
              $ringkas true di tabel Daftar Pasien: tombol ikon bulat, keterangan lewat tooltip dan sr-only
--}}
@php
    $antreanBerjalan = $pasien->antreanAktif;
    $ringkas = $ringkas ?? false;
    $kelasTombol = $ringkas ? 'btn-icon w-11 h-11' : ($class ?? '');
    $kelasIkon = $ringkas ? 'w-4 h-4' : 'w-3.5 h-3.5';
@endphp

@if ($antreanBerjalan)
    <a href="{{ route('perawat.dashboard', ['antrean_id' => $antreanBerjalan->id_antrean]) }}"
       class="btn btn-soft {{ $kelasTombol }}" title="Masih di antrean ({{ $antreanBerjalan->status }}), buka antrean">
        <x-icon name="clock" class="{{ $kelasIkon }}" aria-hidden="true" />
        @if ($ringkas)
            <span class="sr-only">{{ $pasien->nama_pasien }} masih di antrean, status {{ $antreanBerjalan->status }}</span>
        @else
            Di antrean<span class="sr-only"> · status {{ $antreanBerjalan->status }}</span>
        @endif
    </a>
@else
    <form method="POST" action="{{ route('perawat.pasien.kunjungan', $pasien->id_pasien) }}" class="contents" data-submit-loading>
        @csrf
        <button type="submit" @unless ($ringkas) data-loading-text="Mendaftarkan..." @endunless
                class="btn btn-primary {{ $kelasTombol }}" title="Daftarkan kunjungan (tanpa No. RM baru)">
            <x-icon name="plus" class="{{ $kelasIkon }}" aria-hidden="true" />
            @if ($ringkas)
                <span class="sr-only">Daftarkan kunjungan {{ $pasien->nama_pasien }}</span>
            @else
                Daftarkan kunjungan<span class="sr-only"> {{ $pasien->nama_pasien }}</span>
            @endif
        </button>
    </form>
@endif

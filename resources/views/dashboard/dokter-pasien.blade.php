@extends('layouts.app')

@section('title', 'Daftar Pasien - MedScribe AI')

@section('content')
<div class="space-y-6">

    <x-page-header eyebrow="Dokter Poliklinik"
                   title="Daftar Pasien"
                   subtitle="Cari pasien rumah sakit Anda untuk melihat riwayat kunjungan dan rekam medisnya.">
        @include('partials.dokter-nav')
    </x-page-header>

    <section class="card spotlight overflow-hidden rise" style="--i: 1" aria-labelledby="judulDaftarPasienDokter">
        <div class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-end justify-between gap-5">
            <div>
                <h2 id="judulDaftarPasienDokter" class="section-title">Pasien terdaftar</h2>
                <p class="section-subtitle">
                    <span class="num">{{ $daftarPasien->total() }}</span> pasien{{ $cari !== '' ? ' cocok dengan "' . $cari . '"' : '' }}
                </p>
            </div>

            <form method="GET" action="{{ route('dokter.pasien.index') }}" class="flex flex-wrap items-end gap-2" role="search">
                <div class="w-full sm:w-72">
                    <label for="cariPasienDokter" class="field-label">Cari pasien</label>
                    <div class="field-icon">
                        <x-icon name="search" aria-hidden="true" />
                        <input type="search" id="cariPasienDokter" name="cari" value="{{ $cari }}" placeholder="Nama, No. RM, atau NIK" class="field">
                    </div>
                </div>
                <button type="submit" class="btn btn-navy">Cari</button>
                @if ($cari !== '')
                    <a href="{{ route('dokter.pasien.index') }}" class="btn btn-soft">Hapus</a>
                @endif
            </form>
        </div>

        <div class="relative overflow-x-auto border-t border-border-subtle">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">No. RM</th>
                        <th scope="col">Nama Lengkap</th>
                        <th scope="col">Umur</th>
                        <th scope="col" class="hidden lg:table-cell">Jenis Kelamin</th>
                        <th scope="col">Kunjungan Terakhir</th>
                        <th scope="col" class="hidden md:table-cell">Jumlah Kunjungan</th>
                        <th scope="col" class="text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarPasien as $pasien)
                        <tr class="group">
                            <td class="whitespace-nowrap"><span class="tag-id">{{ $pasien->no_rekam_medis }}</span></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('dokter.pasien.show', $pasien->id_pasien) }}" class="flex items-center gap-3">
                                    <span class="avatar w-8 h-8 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:text-white" aria-hidden="true">{{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}</span>
                                    <span class="font-semibold text-medical-navy group-hover:text-royal-indigo-hover transition-colors">{{ $pasien->nama_pasien }}</span>
                                </a>
                            </td>
                            <td class="text-text-secondary whitespace-nowrap num">{{ $pasien->usia_label ?? '-' }}</td>
                            <td class="text-text-secondary whitespace-nowrap hidden lg:table-cell">{{ $pasien->jenis_kelamin ?: '-' }}</td>
                            <td class="whitespace-nowrap num">
                                @if ($pasien->kunjungan_terakhir)
                                    <span class="font-medium text-medical-navy">{{ \Carbon\Carbon::parse($pasien->kunjungan_terakhir)->locale('id')->isoFormat('D MMM Y') }}</span>
                                @else
                                    <span class="text-text-secondary">Belum ada</span>
                                @endif
                            </td>
                            <td class="text-text-secondary whitespace-nowrap num hidden md:table-cell">{{ $pasien->kunjungan_count }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('dokter.pasien.show', $pasien->id_pasien) }}" class="btn btn-secondary btn-sm">
                                    <x-icon name="history" class="w-3.5 h-3.5" aria-hidden="true" />
                                    Riwayat<span class="sr-only"> {{ $pasien->nama_pasien }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="!p-0">
                                <x-empty-state :title="$cari !== '' ? 'Tidak ada pasien yang cocok' : 'Belum ada pasien terdaftar'"
                                               :hint="$cari !== '' ? 'Coba kata kunci lain.' : 'Pasien yang didaftarkan perawat akan tampil di sini.'"
                                               icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftarPasien->hasPages())
            <div class="px-5 sm:px-6 py-4 border-t border-border-subtle">
                {{ $daftarPasien->links(null, ['noun' => 'pasien']) }}
            </div>
        @endif
    </section>
</div>
@endsection

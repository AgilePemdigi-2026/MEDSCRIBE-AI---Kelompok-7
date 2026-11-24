@extends('layouts.admin')

@section('title', 'Mutasi Tenaga Medis - MedScribe AI')

@section('content')
@php
    $rsId = (int) $hospital->id;
    $tanggal = fn ($t) => $t ? $t->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH:mm') : '-';
    // Kalimat arah dari sudut pandang RS yang sedang membuka halaman.
    $arahKalimat = function ($m) use ($rsId) {
        $keluar = (int) $m->hospital_asal_id === $rsId;
        return $keluar
            ? 'Pindah ke ' . $m->rsTujuan->hospital_name
            : 'Pindah dari ' . $m->rsAsal->hospital_name;
    };
@endphp

<div class="space-y-6">
    <x-page-header :eyebrow="'Tenaga medis · ' . $hospital->hospital_name"
                   title="Mutasi Tenaga Medis"
                   subtitle="Pindah penugasan dokter dan perawat antar rumah sakit. Setiap mutasi butuh persetujuan admin kedua rumah sakit.">
        <nav aria-label="Tampilan mutasi" class="pill-nav-scroll">
            <div class="pill-nav">
                <a href="{{ route('admin.mutasi', ['tab' => 'masuk']) }}" @if ($tab === 'masuk') aria-current="page" @endif>
                    Perlu keputusan
                    @if ($masuk->isNotEmpty())<span class="pill-count">{{ $masuk->count() }}<span class="sr-only"> menunggu</span></span>@endif
                </a>
                <a href="{{ route('admin.mutasi', ['tab' => 'diajukan']) }}" @if ($tab === 'diajukan') aria-current="page" @endif>Diajukan</a>
                <a href="{{ route('admin.mutasi', ['tab' => 'riwayat']) }}" @if ($tab === 'riwayat') aria-current="page" @endif>Riwayat</a>
            </div>
        </nav>
    </x-page-header>

    @include('partials.form-errors')

    {{-- ========== PERLU KEPUTUSAN ========== --}}
    @if ($tab === 'masuk')
        @forelse ($masuk as $m)
            @php
                $pasienBelumFinal = $belumFinal[$m->id] ?? collect();
            @endphp
            <article class="card p-5 sm:p-6 space-y-4 rise" style="--i: {{ $loop->index + 1 }}">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="avatar w-11 h-11 bg-pale-lavender text-royal-indigo-hover text-sm" aria-hidden="true">{{ $m->staf->inisial }}</span>
                        <div class="min-w-0">
                            <h2 class="font-semibold text-medical-navy">{{ $m->staf->nama_lengkap }}</h2>
                            <p class="text-sm text-text-secondary">{{ $m->staf->peran_label }} · {{ $m->staf->email }}</p>
                        </div>
                    </div>
                    <span class="status status-warn shrink-0">Menunggu keputusan Anda</span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div class="panel">
                        <dt class="text-text-secondary">Perpindahan</dt>
                        <dd class="mt-0.5 font-semibold text-medical-navy">{{ $m->rsAsal->hospital_name }} → {{ $m->rsTujuan->hospital_name }}</dd>
                    </div>
                    <div class="panel">
                        <dt class="text-text-secondary">Diajukan oleh</dt>
                        <dd class="mt-0.5 font-semibold text-medical-navy">{{ $m->pengaju->nama_lengkap }}</dd>
                        <dd class="text-xs text-text-secondary">{{ $m->arah === 'lepas' ? 'Admin rumah sakit asal' : 'Admin rumah sakit tujuan' }} · {{ $tanggal($m->created_at) }}</dd>
                    </div>
                    <div class="panel">
                        <dt class="text-text-secondary">Alasan</dt>
                        <dd class="mt-0.5 text-text-body">{{ $m->alasan }}</dd>
                    </div>
                </dl>

                @if ($pasienBelumFinal->isNotEmpty())
                    <div class="alert alert-warn" role="status">
                        <x-icon name="triangle-alert" static />
                        <div class="flex-1 min-w-0 text-text-body">
                            <p class="font-semibold text-warning-text">Masih menangani {{ $pasienBelumFinal->count() }} rekam medis yang belum final</p>
                            <p class="mt-1">Kalau disetujui, kunjungan ini dilepas dari {{ $m->staf->nama_lengkap }} dan bisa diambil alih dokter lain di {{ $m->rsAsal->hospital_name }}.</p>
                            {{-- Nama pasien hanya untuk admin RS asal; RS lain cukup melihat jumlahnya. --}}
                            @if ((int) $m->hospital_asal_id === $rsId)
                                <ul class="mt-2 list-disc pl-5 space-y-0.5">
                                    @foreach ($pasienBelumFinal as $k)
                                        <li>{{ $k->pasien?->nama_pasien ?? 'Pasien' }} <span class="text-text-secondary">· {{ $k->status }} · {{ \Carbon\Carbon::parse($k->tanggal)->locale('id')->isoFormat('D MMM Y') }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t border-border-subtle">
                    <button type="button" class="btn btn-danger-quiet" data-dialog-open="dlgTolakMutasi"
                            data-staff-url="{{ route('admin.mutasi.tolak', $m) }}" data-staff-name="{{ $m->staf->nama_lengkap }}" data-staff-role="{{ $m->staf->peran_label }}">
                        <x-icon name="x" /> Tolak
                    </button>
                    <button type="button" class="btn btn-primary" data-dialog-open="dlgSetujuiMutasi"
                            data-staff-url="{{ route('admin.mutasi.setujui', $m) }}" data-staff-name="{{ $m->staf->nama_lengkap }}" data-staff-role="{{ $arahKalimat($m) }}">
                        <x-icon name="check" /> Setujui mutasi
                    </button>
                </div>
            </article>
        @empty
            <section class="card">
                <x-empty-state title="Tidak ada permintaan yang menunggu"
                               hint="Permintaan mutasi dari rumah sakit lain yang butuh persetujuan Anda akan muncul di sini."
                               icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </section>
        @endforelse

    {{-- ========== DIAJUKAN ========== --}}
    @elseif ($tab === 'diajukan')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <section class="lg:col-span-7 space-y-4" aria-labelledby="judulDiajukan">
                <h2 id="judulDiajukan" class="section-title">Menunggu keputusan rumah sakit lain</h2>
                @forelse ($diajukan as $m)
                    <article class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <span class="avatar w-10 h-10 bg-pale-lavender text-royal-indigo-hover text-xs" aria-hidden="true">{{ $m->staf->inisial }}</span>
                            <div class="min-w-0">
                                <p class="font-semibold text-medical-navy">{{ $m->staf->nama_lengkap }} <span class="font-normal text-text-secondary">· {{ $m->staf->peran_label }}</span></p>
                                <p class="text-sm text-text-secondary">{{ $arahKalimat($m) }} · diajukan {{ $tanggal($m->created_at) }}</p>
                                <p class="mt-1 text-sm text-text-body">{{ $m->alasan }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.mutasi.batal', $m) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">Batalkan</button>
                        </form>
                    </article>
                @empty
                    <section class="card">
                        <x-empty-state title="Tidak ada permintaan yang sedang berjalan"
                                       hint="Lepas staf dari menu Dokter/Perawat, atau minta tenaga medis dari rumah sakit lain di samping."
                                       icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </section>
                @endforelse
            </section>

            {{-- Meminta tenaga medis dari RS lain: cari lewat email lengkap. --}}
            <section class="lg:col-span-5 card p-5 sm:p-6 space-y-4" aria-labelledby="judulMinta">
                <div>
                    <h2 id="judulMinta" class="section-title">Minta tenaga medis dari rumah sakit lain</h2>
                    <p class="section-subtitle">Masukkan email lengkap dokter atau perawat. Admin rumah sakit asalnya yang akan menyetujui.</p>
                </div>

                <form method="GET" action="{{ route('admin.mutasi') }}" class="flex gap-2" role="search">
                    <input type="hidden" name="tab" value="diajukan">
                    <div class="field-icon flex-1">
                        <x-icon name="at-sign" static />
                        <label for="emailCari" class="sr-only">Email tenaga medis</label>
                        <input type="email" id="emailCari" name="email" required value="{{ $emailCari }}" placeholder="nama@medscribe.id" class="field">
                    </div>
                    <button type="submit" class="btn btn-navy"><x-icon name="search" /> Cari</button>
                </form>

                @if ($pesanCari)
                    <div class="alert alert-info" role="status">
                        <x-icon name="info" static />
                        <span class="text-text-body">{{ $pesanCari }}</span>
                    </div>
                @elseif ($hasilCari)
                    <form method="POST" action="{{ route('admin.mutasi.tarik') }}" class="space-y-4" data-submit-loading>
                        @csrf
                        <input type="hidden" name="email" value="{{ $hasilCari->email }}">
                        <div class="panel flex items-center gap-3">
                            <span class="avatar w-10 h-10 bg-white text-royal-indigo-hover text-xs" aria-hidden="true">{{ $hasilCari->inisial }}</span>
                            <div class="min-w-0 text-sm">
                                <p class="font-semibold text-medical-navy">{{ $hasilCari->nama_lengkap }}</p>
                                <p class="text-text-secondary">{{ $hasilCari->peran_label }} · {{ $hasilCari->hospital?->hospital_name ?? '-' }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="alasanTarik" class="field-label">Alasan permintaan <span class="req" aria-hidden="true">*</span></label>
                            <textarea id="alasanTarik" name="alasan" rows="3" required minlength="5" maxlength="500" class="field"
                                      placeholder="Contoh: Penempatan sesuai surat tugas dinas kesehatan.">{{ old('alasan') }}</textarea>
                        </div>
                        @include('partials.submit-button', ['id' => 'btnTarik', 'label' => 'Kirim permintaan', 'loading' => 'Mengirim...'])
                    </form>
                @endif
            </section>
        </div>

    {{-- ========== RIWAYAT ========== --}}
    @else
        <section class="card overflow-hidden" aria-label="Riwayat mutasi">
            @if ($riwayat->isEmpty())
                <x-empty-state title="Belum ada riwayat mutasi"
                               hint="Mutasi yang disetujui, ditolak, atau dibatalkan akan tercatat di sini."
                               icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Tenaga medis</th>
                                <th scope="col">Perpindahan</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="hidden lg:table-cell">Keterangan</th>
                                <th scope="col" class="hidden md:table-cell">Diputuskan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayat as $m)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-medical-navy">{{ $m->staf->nama_lengkap }}</p>
                                        <p class="text-xs text-text-secondary">{{ $m->staf->peran_label }}</p>
                                    </td>
                                    <td class="text-sm">
                                        {{ $m->rsAsal->hospital_name }} → {{ $m->rsTujuan->hospital_name }}
                                        <p class="text-xs text-text-secondary">{{ (int) $m->hospital_asal_id === $rsId ? 'Keluar' : 'Masuk' }}</p>
                                    </td>
                                    <td><span class="status {{ $m->status_class }}">{{ $m->status_label }}</span></td>
                                    <td class="hidden lg:table-cell text-sm text-text-secondary max-w-xs">
                                        @if ($m->status === 'ditolak')
                                            {{ $m->alasan_penolakan }}
                                        @elseif ($m->status === 'disetujui' && $m->kunjungan_dilepas)
                                            {{ $m->kunjungan_dilepas }} kunjungan belum final dilepas
                                        @else
                                            {{ $m->alasan }}
                                        @endif
                                    </td>
                                    <td class="hidden md:table-cell text-sm text-text-secondary whitespace-nowrap">
                                        {{ $tanggal($m->diputuskan_at) }}
                                        <p class="text-xs">{{ $m->pemutus?->nama_lengkap ?? '-' }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($riwayat->hasPages())
                    <div class="p-4 sm:px-6 border-t border-border-subtle">{{ $riwayat->links(null, ['noun' => 'mutasi']) }}</div>
                @endif
            @endif
        </section>
    @endif
</div>

<x-dialog id="dlgSetujuiMutasi" tone="ok" size="md" title="Setujui mutasi?"
          description="Akun langsung pindah ke rumah sakit tujuan. No. SIP/SIPP dikosongkan agar diisi ulang untuk tempat praktik baru; nomor lama tetap tersimpan di riwayat dan rekam medis yang sudah final tidak berubah.">
    <form method="POST" id="formSetujuiMutasi" data-staff-form data-submit-loading class="panel text-sm">
        @csrf
        <p class="text-text-secondary">Tenaga medis</p>
        <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span></p>
        <p class="text-text-secondary"><span data-staff-fill="role"></span></p>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formSetujuiMutasi" class="btn btn-primary" data-dialog-focus>Setujui</button>
    </x-slot:actions>
</x-dialog>

<x-dialog id="dlgTolakMutasi" tone="danger" size="md" title="Tolak permintaan mutasi?"
          description="Alasan penolakan terlihat oleh admin rumah sakit yang mengajukan.">
    <form method="POST" id="formTolakMutasi" data-staff-form data-submit-loading class="space-y-4">
        @csrf
        <div class="panel text-sm">
            <p class="text-text-secondary">Tenaga medis</p>
            <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span> · <span data-staff-fill="role"></span></p>
        </div>
        <div>
            <label for="alasanTolakMutasi" class="field-label">Alasan penolakan <span class="req" aria-hidden="true">*</span></label>
            <textarea id="alasanTolakMutasi" name="alasan_penolakan" rows="3" required minlength="5" maxlength="500" data-dialog-focus
                      class="field" placeholder="Contoh: Masih dibutuhkan di poliklinik sampai akhir bulan."></textarea>
        </div>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formTolakMutasi" class="btn btn-danger">Tolak</button>
    </x-slot:actions>
</x-dialog>

@include('admin.partials.isi-dialog-staf')
@endsection

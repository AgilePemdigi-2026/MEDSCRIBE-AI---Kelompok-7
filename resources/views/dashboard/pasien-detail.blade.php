@extends('layouts.app')

@section('title', 'Detail Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
    $usia = $pasien->usia_label;
    // Sama dengan kode kunjungan di Nurse Station dan rekam medis dokter.
    $prefixKunjungan = $pasien->hospital?->hospital_code ? 'RS' . $pasien->hospital->hospital_code : 'RSUA';
@endphp
<div class="space-y-6">

    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-text-secondary rise">
        @include('partials.back-link', ['href' => route('perawat.dashboard', ['tab' => 'pasien']), 'label' => 'Daftar Pasien'])
        <span class="text-border-hover" aria-hidden="true">›</span>
        <span class="font-semibold text-medical-navy" aria-current="page">{{ $pasien->nama_pasien }}</span>
    </nav>

    {{-- Mis. penolakan hapus registrasi karena pasien sudah punya riwayat pemeriksaan dokter. --}}
    @include('partials.form-errors')

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Kartu identitas pasien --}}
        <aside class="card-lavender spotlight lg:col-span-4 p-6 lg:sticky lg:top-28 rise" style="--i: 1">
            <div class="relative">
                <span class="avatar w-20 h-20 text-3xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                    {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                </span>
                <h1 class="mt-5 text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">{{ $pasien->nama_pasien }}</h1>
                <span class="mt-2 tag-id bg-white">{{ $pasien->no_rekam_medis }}</span>

                @php
                    $identitas = [
                        ['Tanggal lahir', $pasien->tanggal_lahir?->locale('id')->isoFormat('D MMMM Y'), false],
                        ['Umur', $usia, false],
                        ['Jenis kelamin', $pasien->jenis_kelamin, false],
                        ['No. HP', $pasien->no_hp, true],
                        ['NIK', $pasien->nik, true],
                        ['No. BPJS', $pasien->no_bpjs, true],
                    ];
                @endphp
                <dl class="mt-6 grid grid-cols-2 gap-2">
                    @foreach ($identitas as [$labelIdentitas, $nilaiIdentitas, $angka])
                        <div class="rounded-(--radius-field) bg-white/80 border border-border-hover p-3 min-w-0">
                            <dt class="text-[11px] text-text-secondary">{{ $labelIdentitas }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-medical-navy break-all {{ $angka ? 'num' : '' }}">{{ $nilaiIdentitas ?: '-' }}</dd>
                        </div>
                    @endforeach
                    @if ($pasien->created_at)
                        <div class="col-span-2 rounded-(--radius-field) bg-white/80 border border-border-hover p-3">
                            <dt class="text-[11px] text-text-secondary">Terdaftar</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-medical-navy">{{ $pasien->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 grid gap-2">
                    @include('partials.tombol-kunjungan-ulang', ['pasien' => $pasien, 'class' => 'btn-sm w-full'])
                    <button type="button" data-dialog-open="modalEditPasien" class="btn btn-secondary btn-sm w-full">
                        <x-icon name="square-pen" class="w-3.5 h-3.5" aria-hidden="true" />
                        Edit identitas
                    </button>
                    <button type="button" onclick="openModalHapusPasien({{ $pasien->id_pasien }}, '{{ addslashes($pasien->nama_pasien) }}', '{{ $pasien->no_rekam_medis }}')"
                            class="btn btn-danger-quiet btn-sm w-full" title="Hapus / Batalkan Registrasi Pasien">
                        <x-icon name="trash-2" class="w-3.5 h-3.5" aria-hidden="true" />
                        Hapus registrasi
                    </button>
                </div>
            </div>
        </aside>

        {{-- Riwayat kunjungan sebagai linimasa --}}
        <section class="lg:col-span-8 rise" style="--i: 2" aria-labelledby="judulRiwayat">
            <div class="flex items-end justify-between gap-3 mb-4">
                <div>
                    <h2 id="judulRiwayat" class="section-title">Riwayat kunjungan &amp; TTV</h2>
                    <p class="section-subtitle"><span class="num" data-ticker="{{ $riwayatKunjungan->count() }}">{{ $riwayatKunjungan->count() }}</span> kunjungan tercatat</p>
                </div>
            </div>

            @forelse ($riwayatKunjungan as $kunjungan)
                @php
                    $pa = $kunjungan->pemeriksaanAwal;
                    $tglStr = str_replace('-', '', $kunjungan->tanggal);
                    $kodeKunjungan = $prefixKunjungan . '-' . $tglStr . '-' . str_pad($kunjungan->id_antrean ?? $kunjungan->id_kunjungan, 3, '0', STR_PAD_LEFT);
                @endphp
                <div class="relative pl-8 pb-6 last:pb-0">
                    <span class="absolute left-[11px] top-3 bottom-0 w-px bg-border-hover" aria-hidden="true"></span>
                    <span class="absolute left-0 top-1.5 w-6 h-6 rounded-full bg-white border-2 border-royal-indigo flex items-center justify-center" aria-hidden="true">
                        <span class="w-2 h-2 rounded-full bg-royal-indigo"></span>
                    </span>

                    <article class="card card-hover spotlight overflow-hidden">
                        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-border-subtle">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-sm font-semibold text-medical-navy">{{ \Carbon\Carbon::parse($kunjungan->tanggal)->locale('id')->isoFormat('D MMMM Y') }}</h3>
                                <span class="tag-id">{{ $kodeKunjungan }}</span>
                            </div>
                            @if ($kunjungan->status)
                                <span class="status {{ in_array($kunjungan->status, ['Selesai', 'Final', \App\Models\Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT, \App\Models\Kunjungan::STATUS_SOAP_TERVERIFIKASI]) ? 'status-ok' : 'status-active' }}">{{ $kunjungan->status }}</span>
                            @else
                                <span class="status status-muted">Belum ada status</span>
                            @endif
                        </div>

                        <div class="relative p-5">
                            @if ($pa)
                                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <div class="vital"><dt class="vital-label">Tekanan Darah</dt><dd class="vital-value">{{ $pa->tekanan_darah ?? '-' }}<span class="vital-unit">mmHg</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Nadi</dt><dd class="vital-value">{{ $pa->nadi ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Suhu</dt><dd class="vital-value">{{ $pa->suhu_tubuh ?? '-' }}<span class="vital-unit">°C</span></dd></div>
                                    <div class="vital"><dt class="vital-label">Pernapasan</dt><dd class="vital-value">{{ $pa->laju_pernapasan ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                                    @if ($pa->saturasi_oksigen)
                                        <div class="vital"><dt class="vital-label">SpO2</dt><dd class="vital-value">{{ $pa->saturasi_oksigen }}<span class="vital-unit">%</span></dd></div>
                                    @endif
                                    @if ($pa->berat_badan)
                                        <div class="vital"><dt class="vital-label">Berat Badan</dt><dd class="vital-value">{{ $pa->berat_badan + 0 }}<span class="vital-unit">kg</span></dd></div>
                                    @endif
                                    @if ($pa->tinggi_badan)
                                        <div class="vital"><dt class="vital-label">Tinggi Badan</dt><dd class="vital-value">{{ $pa->tinggi_badan + 0 }}<span class="vital-unit">cm</span></dd></div>
                                    @endif
                                </dl>

                                @if ($pa->catatan)
                                    <div class="mt-4 panel text-sm text-text-body">
                                        <p class="font-semibold text-medical-navy">Catatan perawat</p>
                                        <p class="mt-0.5">{{ $pa->catatan }}</p>
                                    </div>
                                @endif

                                @if ($pa->perawat)
                                    <p class="mt-4 flex items-center gap-2 text-[11px] text-text-secondary">
                                        <span class="avatar w-6 h-6 bg-medical-navy text-white text-[10px]" aria-hidden="true">{{ strtoupper(substr($pa->perawat->nama_lengkap, 0, 1)) }}</span>
                                        Diperiksa oleh: <strong class="text-medical-navy font-semibold">{{ $pa->perawat->nama_lengkap }}</strong>
                                        @if ($pa->created_at)
                                            <span aria-hidden="true">·</span> {{ $pa->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH:mm') }} WIB
                                        @endif
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-text-secondary">Tanda-tanda vital untuk kunjungan ini belum diisi.</p>
                            @endif
                        </div>
                    </article>
                </div>
            @empty
                <div class="card">
                    <x-empty-state title="Belum ada riwayat kunjungan"
                                   hint="Kunjungan dan tanda-tanda vital pasien ini akan tercatat di sini."
                                   icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </div>
            @endforelse
        </section>
    </div>
</div>

@php $errEdit = $errors->editPasien; @endphp
{{-- ========== DIALOG: Edit identitas pasien (No. RM dan rumah sakit tetap) ========== --}}
<div id="modalEditPasien" data-dialog class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
    <div class="dialog-backdrop"></div>

    <div class="fixed inset-0 overflow-y-auto pointer-events-none">
        <div class="min-h-full flex items-start justify-center px-4 py-8 sm:py-12">
            <div class="dialog w-full max-w-2xl pointer-events-auto animate-dialog">
                <div class="flex items-start justify-between gap-4 p-6 sm:px-8">
                    <div class="flex items-center gap-3">
                        <span class="avatar w-11 h-11 bg-pale-lavender text-royal-indigo" aria-hidden="true">
                            <x-icon name="square-pen" class="w-5 h-5" />
                        </span>
                        <div>
                            <h2 id="modalEditTitle" class="section-title">Edit identitas pasien</h2>
                            <p class="section-subtitle">No. RM <span class="tag-id">{{ $pasien->no_rekam_medis }}</span> tidak berubah, riwayat kunjungan tetap tersambung.</p>
                        </div>
                    </div>
                    <button type="button" data-dialog-close class="btn btn-ghost btn-icon btn-sm -mr-2" aria-label="Tutup dialog">
                        <x-icon name="x" class="w-4 h-4" aria-hidden="true" />
                    </button>
                </div>

                <form method="POST" action="{{ route('perawat.pasien.update', $pasien->id_pasien) }}" id="formEditPasien" class="p-6 pt-0 sm:px-8 space-y-6" data-submit-loading>
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="edit_nama_pasien" class="field-label">Nama lengkap <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="edit_nama_pasien" name="nama_pasien" required maxlength="100" autocomplete="off" data-dialog-focus
                                   value="{{ old('nama_pasien', $pasien->nama_pasien) }}" placeholder="Sesuai kartu identitas"
                                   class="field {{ $errEdit->has('nama_pasien') ? 'is-invalid' : '' }}">
                            @if ($errEdit->has('nama_pasien'))<p class="field-error">{{ $errEdit->first('nama_pasien') }}</p>@endif
                        </div>

                        <div>
                            <label for="edit_tanggal_lahir" class="field-label">Tanggal lahir</label>
                            <input type="date" id="edit_tanggal_lahir" name="tanggal_lahir" max="{{ now('Asia/Jakarta')->toDateString() }}"
                                   value="{{ old('tanggal_lahir', $pasien->tanggal_lahir?->toDateString()) }}"
                                   class="field {{ $errEdit->has('tanggal_lahir') ? 'is-invalid' : '' }}">
                            @if ($errEdit->has('tanggal_lahir'))<p class="field-error">{{ $errEdit->first('tanggal_lahir') }}</p>@endif
                        </div>

                        <div>
                            <label for="edit_umur" class="field-label">Umur</label>
                            <div class="field-unit">
                                <input type="number" id="edit_umur" name="umur" min="0" max="130" placeholder="0"
                                       value="{{ old('umur', $pasien->umur) }}"
                                       class="field field-num {{ $errEdit->has('umur') ? 'is-invalid' : '' }}">
                                <span class="unit">tahun</span>
                            </div>
                            @if ($errEdit->has('umur'))<p class="field-error">{{ $errEdit->first('umur') }}</p>@endif
                            <p class="field-hint">Terisi dari tanggal lahir. Isi perkiraan kalau tanggal lahir tidak diketahui.</p>
                        </div>

                        <div>
                            <label for="edit_jenis_kelamin" class="field-label">Jenis kelamin</label>
                            @php $jkLama = old('jenis_kelamin', $pasien->jenis_kelamin); @endphp
                            <select id="edit_jenis_kelamin" name="jenis_kelamin" class="field {{ $errEdit->has('jenis_kelamin') ? 'is-invalid' : '' }}">
                                <option value="">Belum diketahui</option>
                                <option value="Laki-laki" @selected($jkLama === 'Laki-laki')>Laki-laki</option>
                                <option value="Perempuan" @selected($jkLama === 'Perempuan')>Perempuan</option>
                            </select>
                            @if ($errEdit->has('jenis_kelamin'))<p class="field-error">{{ $errEdit->first('jenis_kelamin') }}</p>@endif
                        </div>

                        <div>
                            <label for="edit_no_hp" class="field-label">No. HP</label>
                            <input type="tel" id="edit_no_hp" name="no_hp" inputmode="tel" autocomplete="off" maxlength="20"
                                   value="{{ old('no_hp', $pasien->no_hp) }}" placeholder="08xxxxxxxxxx"
                                   class="field num {{ $errEdit->has('no_hp') ? 'is-invalid' : '' }}">
                            @if ($errEdit->has('no_hp'))<p class="field-error">{{ $errEdit->first('no_hp') }}</p>@endif
                        </div>

                        <div>
                            <label for="edit_nik" class="field-label">NIK</label>
                            <input type="text" id="edit_nik" name="nik" inputmode="numeric" autocomplete="off" maxlength="16"
                                   value="{{ old('nik', $pasien->nik) }}" placeholder="16 digit"
                                   class="field num {{ $errEdit->has('nik') ? 'is-invalid' : '' }}">
                            @if ($errEdit->has('nik'))<p class="field-error">{{ $errEdit->first('nik') }}</p>@endif
                        </div>

                        <div>
                            <label for="edit_no_bpjs" class="field-label">No. BPJS</label>
                            <input type="text" id="edit_no_bpjs" name="no_bpjs" inputmode="numeric" autocomplete="off" maxlength="13"
                                   value="{{ old('no_bpjs', $pasien->no_bpjs) }}" placeholder="13 digit"
                                   class="field num {{ $errEdit->has('no_bpjs') ? 'is-invalid' : '' }}">
                            @if ($errEdit->has('no_bpjs'))<p class="field-error">{{ $errEdit->first('no_bpjs') }}</p>@endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-5 border-t border-border-subtle">
                        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
                        @include('partials.submit-button', ['id' => 'btnSimpanEditPasien', 'label' => 'Simpan perubahan', 'loading' => 'Menyimpan...', 'class' => 'px-6'])
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('partials.modal-hapus-pasien')
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Buka otomatis saat validasi gagal, atau dari tombol "Edit" di Daftar Pasien (?edit=1).
        @if ($errEdit->any() || request()->boolean('edit'))
            window.openDialog?.('modalEditPasien');
        @endif

        const tglLahir = document.getElementById('edit_tanggal_lahir');
        const umur = document.getElementById('edit_umur');
        tglLahir?.addEventListener('change', function () {
            if (!this.value) return;
            const lahir = new Date(this.value + 'T00:00:00');
            const kini = new Date();
            let usia = kini.getFullYear() - lahir.getFullYear();
            const belumUlangTahun = kini.getMonth() < lahir.getMonth()
                || (kini.getMonth() === lahir.getMonth() && kini.getDate() < lahir.getDate());
            if (belumUlangTahun) usia--;
            if (usia >= 0) umur.value = usia;
        });

        // Spasi atau tanda hubung yang ikut tersalin dibuang dari NIK dan BPJS.
        ['edit_nik', 'edit_no_bpjs'].forEach(function (id) {
            document.getElementById(id)?.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    });
</script>
@endsection

@php
    $isFinalized = $kunjungan->isFinalized();
    $draf = $soap['draf'] ?? null;

    // Evaluasi 4 syarat kelayakan riil dari database (Section 2.4)
    $soapSelesai = $kunjungan->isSoapVerified()
        && $draf
        && filled(trim($draf->subjective_text ?? ''))
        && filled(trim($draf->objective_text ?? ''))
        && filled(trim($draf->assessment_text ?? ''))
        && filled(trim($draf->plan_text ?? ''));

    $ttvSelesai = $pemeriksaanAwal !== null
        && filled($pemeriksaanAwal->tekanan_darah)
        && $pemeriksaanAwal->nadi
        && $pemeriksaanAwal->suhu_tubuh;

    $wawancaraSelesai = $wawancara !== null
        && filled(trim($wawancara->transkrip ?? ''))
        && $wawancara->transcript_reviewed_at !== null;

    $tindakLanjutSelesai = $tindakLanjut !== null;

    $semuaSyaratTerpenuhi = $soapSelesai && $ttvSelesai && $wawancaraSelesai && $tindakLanjutSelesai;

    [$statusFinalisasiLabel, $statusFinalisasiClass] = match (true) {
        $isFinalized => ['Final (Terkunci)', 'status-ok'],
        $semuaSyaratTerpenuhi => ['Siap Difinalisasi', 'status-warn'],
        $tahapFinalisasi === 'active' => ['Menunggu Kelengkapan', 'status-warn'],
        default => ['Terkunci', 'status-muted'],
    };

    $keteranganFinalisasi = match (true) {
        $isFinalized => 'Rekam medis ini telah disetujui dan difinalisasi secara permanen.',
        $semuaSyaratTerpenuhi => 'Seluruh data klinis lengkap. Berikan konfirmasi persetujuan klinis dan finalisasi rekam medis.',
        $tahapFinalisasi === 'active' => 'Lengkapi dan verifikasi seluruh komponen klinis sebelum memfinalisasi rekam medis.',
        default => 'Terbuka setelah draf SOAP diverifikasi oleh dokter pemeriksa.',
    };

    $dokterPemeriksa = $kunjungan->dokter ?? $user;
    $finalizedDoctor = $finalisasi?->finalizedBy ?? $dokterPemeriksa;
    $waktuFinal = $finalisasi?->finalized_at
        ? \Carbon\Carbon::parse($finalisasi->finalized_at)->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB'
        : null;
@endphp

@include('dashboard.rekam-medis.kepala-tahap', [
    'nomor' => 4,
    'judul' => 'Setujui & Finalisasi Rekam Medis',
    'keterangan' => $keteranganFinalisasi,
    'keadaan' => $tahapFinalisasi,
    'statusLabel' => $statusFinalisasiLabel,
    'statusClass' => $statusFinalisasiClass,
    'idJudul' => 'judulFinalisasi',
])

@if ($isFinalized)
    {{-- Tampilan saat Rekam Medis telah Berstatus FINAL (Locked Read-Only) --}}
    <div class="border-t border-border-subtle p-5 sm:p-6 space-y-6">

        {{-- Banner Status Final & Penguncian --}}
        <div class="rounded-[22px] bg-gradient-to-br from-emerald-50 to-teal-50/60 border border-emerald-200/90 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <span class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-emerald-600/20" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-emerald-950">Rekam Medis Telah Difinalisasi</h3>
                            <span class="tag-id bg-emerald-600 text-white font-mono text-[11px] px-2.5 py-0.5">FINAL</span>
                        </div>
                        <p class="text-xs text-emerald-800 mt-1">
                            Disetujui dan difinalisasi oleh <strong class="font-semibold">{{ $finalizedDoctor->nama_lengkap }}</strong>
                            @if ($waktuFinal) pada {{ $waktuFinal }} @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-center">
                    <span class="status status-ok text-xs">Terkunci (Read-Only)</span>
                </div>
            </div>

            @if ($finalisasi?->catatan_finalisasi)
                <div class="mt-4 pt-3.5 border-t border-emerald-200/70 text-xs text-emerald-900">
                    <span class="font-semibold block mb-0.5 text-emerald-950">Catatan Finalisasi Dokter:</span>
                    <p class="italic text-emerald-800 leading-relaxed">{{ $finalisasi->catatan_finalisasi }}</p>
                </div>
            @endif
        </div>

        {{-- Ringkasan Komprehensif Rekam Medis Terfinalisasi --}}
        <div class="space-y-5">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Arsip Rekam Medis Lengkap</h4>

            {{-- Identitas Pasien & Dokter --}}
            <div class="rounded-[18px] bg-soft-offwhite border border-border-subtle p-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-text-secondary block">Pasien:</span>
                    <span class="font-semibold text-medical-navy text-sm">{{ $pasien->nama_pasien }}</span>
                    <span class="text-text-secondary block mt-0.5">No. RM: <span class="font-mono">{{ $pasien->no_rekam_medis }}</span> · {{ $umur ? $umur . ' th' : '-' }} ({{ $pasien->jenis_kelamin }})</span>
                </div>
                <div>
                    <span class="text-text-secondary block">Dokter Pemeriksa:</span>
                    <span class="font-semibold text-medical-navy text-sm">{{ $dokterPemeriksa->nama_lengkap }}</span>
                    <span class="text-text-secondary block mt-0.5">ID Kunjungan: <span class="font-mono">{{ $idKunjunganCode }}</span></span>
                </div>
            </div>

            {{-- Ringkasan SOAP Final --}}
            @if ($draf)
                <div class="rounded-[18px] border border-border-subtle bg-white p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                        <span class="font-semibold text-sm text-medical-navy">Catatan SOAP Terverifikasi</span>
                        @if ($draf->kode_icd10)
                            <span class="tag-id bg-pale-lavender text-royal-indigo font-mono text-xs">ICD-10: {{ $draf->kode_icd10 }}</span>
                        @endif
                    </div>
                    <dl class="space-y-2.5 text-xs">
                        <div>
                            <dt class="font-semibold text-royal-indigo mb-0.5">Subjective (S):</dt>
                            <dd class="text-text-body pl-3 border-l-2 border-border-subtle whitespace-pre-line leading-relaxed">{{ $draf->subjective_text ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-royal-indigo mb-0.5">Objective (O):</dt>
                            <dd class="text-text-body pl-3 border-l-2 border-border-subtle whitespace-pre-line leading-relaxed">{{ $draf->objective_text ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-royal-indigo mb-0.5">Assessment (A):</dt>
                            <dd class="text-text-body pl-3 border-l-2 border-border-subtle whitespace-pre-line leading-relaxed">{{ $draf->assessment_text ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-royal-indigo mb-0.5">Plan (P):</dt>
                            <dd class="text-text-body pl-3 border-l-2 border-border-subtle whitespace-pre-line leading-relaxed">{{ $draf->plan_text ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            {{-- Tindak Lanjut Medis --}}
            @if ($tindakLanjut)
                <div class="rounded-[18px] border border-border-subtle bg-white p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                        <span class="font-semibold text-sm text-medical-navy">Tindak Lanjut Medis</span>
                        <span class="tag-id text-xs">{{ $tindakLanjut->status ?: 'Tersimpan' }}</span>
                    </div>

                    @if ($tindakLanjut->eResep->isNotEmpty())
                        <div>
                            <span class="text-xs font-semibold text-text-secondary block mb-1.5">Resep Obat (E-Resep):</span>
                            <ul class="space-y-1.5">
                                @foreach ($tindakLanjut->eResep as $resep)
                                    <li class="text-xs p-2.5 rounded-[12px] bg-soft-offwhite border border-border-subtle flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold text-medical-navy">{{ $resep->nama_obat }}</span>
                                        <span class="text-text-secondary">{{ collect([$resep->dosis, $resep->bentuk_sediaan, $resep->aturan_konsumsi, $resep->jumlah ? $resep->jumlah . ' item' : null])->filter()->join(' · ') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($tindakLanjut->rujukanLab)
                        <div class="pt-2">
                            <span class="text-xs font-semibold text-text-secondary block mb-1.5">Rujukan Laboratorium:</span>
                            <p class="text-xs text-text-secondary mb-2">{{ $tindakLanjut->rujukanLab->catatan ?: 'Tidak ada catatan laboratorium tambahan.' }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($tindakLanjut->rujukanLab->parameter as $param)
                                    <span class="tag-id tag-id-muted text-xs">{{ $param->nama_pemeriksaan }} @if ($param->kode_loinc) ({{ $param->kode_loinc }}) @endif</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($tindakLanjut->jenis_tindak_lanjut === \App\Models\TindakLanjut::JENIS_TANPA_TINDAKAN)
                        <p class="text-xs text-text-secondary italic">Selesai tanpa resep maupun rujukan laboratorium.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

@elseif ($tahapFinalisasi === 'active')
    {{-- Tampilan Pra-Finalisasi: Dokter Memeriksa Kelengkapan dan Menyetujui --}}
    <div class="border-t border-border-subtle p-5 sm:p-6 space-y-6">

        {{-- Indikator Kelayakan Pra-Finalisasi (4 Syarat Riil dari Database) --}}
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-text-secondary mb-3">Pemeriksaan Kelayakan Pra-Finalisasi</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                {{-- Syarat A: Draf SOAP --}}
                <div class="flex items-center gap-3 p-3.5 rounded-[16px] {{ $soapSelesai ? 'bg-emerald-50/60 border border-emerald-200' : 'bg-amber-50/60 border border-amber-200' }} text-xs">
                    <span class="w-6 h-6 rounded-full {{ $soapSelesai ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }} flex items-center justify-center flex-shrink-0">
                        @if ($soapSelesai)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" /></svg>
                        @endif
                    </span>
                    <div>
                        <span class="font-semibold {{ $soapSelesai ? 'text-emerald-950' : 'text-amber-950' }} block">Draf SOAP Terverifikasi</span>
                        <span class="{{ $soapSelesai ? 'text-emerald-800' : 'text-amber-800' }}">{{ $soapSelesai ? 'SOAP telah disetujui dokter' : 'Belum diverifikasi / bagian SOAP belum lengkap' }}</span>
                    </div>
                </div>

                {{-- Syarat B: TTV & Catatan Perawat --}}
                <div class="flex items-center gap-3 p-3.5 rounded-[16px] {{ $ttvSelesai ? 'bg-emerald-50/60 border border-emerald-200' : 'bg-amber-50/60 border border-amber-200' }} text-xs">
                    <span class="w-6 h-6 rounded-full {{ $ttvSelesai ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }} flex items-center justify-center flex-shrink-0">
                        @if ($ttvSelesai)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" /></svg>
                        @endif
                    </span>
                    <div>
                        <span class="font-semibold {{ $ttvSelesai ? 'text-emerald-950' : 'text-amber-950' }} block">TTV & Catatan Perawat</span>
                        <span class="{{ $ttvSelesai ? 'text-emerald-800' : 'text-amber-800' }}">{{ $ttvSelesai ? 'Telah diisi lengkap oleh perawat' : 'Data TTV belum lengkap diisi perawat' }}</span>
                    </div>
                </div>

                {{-- Syarat C: Transkripsi Wawancara --}}
                <div class="flex items-center gap-3 p-3.5 rounded-[16px] {{ $wawancaraSelesai ? 'bg-emerald-50/60 border border-emerald-200' : 'bg-amber-50/60 border border-amber-200' }} text-xs">
                    <span class="w-6 h-6 rounded-full {{ $wawancaraSelesai ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }} flex items-center justify-center flex-shrink-0">
                        @if ($wawancaraSelesai)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" /></svg>
                        @endif
                    </span>
                    <div>
                        <span class="font-semibold {{ $wawancaraSelesai ? 'text-emerald-950' : 'text-amber-950' }} block">Transkripsi Wawancara</span>
                        <span class="{{ $wawancaraSelesai ? 'text-emerald-800' : 'text-amber-800' }}">{{ $wawancaraSelesai ? 'Transkrip telah diverifikasi' : 'Transkrip belum diverifikasi' }}</span>
                    </div>
                </div>

                {{-- Syarat D: Tindak Lanjut Medis --}}
                <div class="flex items-center gap-3 p-3.5 rounded-[16px] {{ $tindakLanjutSelesai ? 'bg-emerald-50/60 border border-emerald-200' : 'bg-amber-50/60 border border-amber-200' }} text-xs">
                    <span class="w-6 h-6 rounded-full {{ $tindakLanjutSelesai ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }} flex items-center justify-center flex-shrink-0">
                        @if ($tindakLanjutSelesai)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" /></svg>
                        @endif
                    </span>
                    <div>
                        <span class="font-semibold {{ $tindakLanjutSelesai ? 'text-emerald-950' : 'text-amber-950' }} block">Tindak Lanjut Medis</span>
                        <span class="{{ $tindakLanjutSelesai ? 'text-emerald-800' : 'text-amber-800' }}">{{ $tindakLanjutSelesai ? ($tindakLanjut->status ?: 'Tersimpan') : 'Belum disimpan (resep/rujukan/tanpa tindakan)' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Persetujuan & Finalisasi --}}
        <form id="form-finalisasi" method="POST" action="{{ route('dokter.rekam-medis.finalisasi', $kunjungan) }}" class="space-y-5">
            @csrf

            {{-- Catatan Finalisasi Opsional --}}
            <div>
                <label for="catatan_finalisasi" class="field-label">Catatan Tambahan Finalisasi Dokter (Opsional)</label>
                <textarea id="catatan_finalisasi" name="catatan_finalisasi" rows="2" class="field leading-relaxed" placeholder="Tambahkan catatan khusus dokter penanggung jawab rekam medis jika diperlukan...">{{ old('catatan_finalisasi') }}</textarea>
                @error('catatan_finalisasi')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            {{-- Checkbox Konfirmasi Persetujuan Wajib --}}
            <div class="p-4 rounded-[18px] bg-pale-lavender/50 border border-royal-indigo/20 space-y-2">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="konfirmasi_persetujuan" name="konfirmasi_persetujuan" value="1" required
                           class="mt-1 h-4 w-4 rounded border-royal-indigo text-royal-indigo focus:ring-royal-indigo/30 @error('konfirmasi_persetujuan') is-invalid @enderror">
                    <span class="text-xs text-medical-navy leading-relaxed">
                        <strong>Pernyataan Tanggung Jawab Klinis:</strong> Saya mengonfirmasi bahwa saya telah memeriksa seluruh isi rekam medis ini, menyetujui kebenaran diagnosis, catatan SOAP, dan instruksi terapi, serta memahami bahwa <strong>tindakan finalisasi bersifat permanen dan mengunci data rekam medis</strong>.
                    </span>
                </label>
                @error('konfirmasi_persetujuan')
                    <p class="field-error ml-7">{{ $message }}</p>
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <a href="#soap" class="btn btn-secondary min-h-11 px-5 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span>Kembali ke SOAP</span>
                </a>
                <button type="button" id="btn-finalisasi-action" onclick="bukaModalFinalisasi()"
                        class="btn min-h-11 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-[14px] shadow-sm flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span>Setujui & Finalisasi Rekam Medis</span>
                </button>
            </div>
        </form>

        {{-- Modal Dialog Konfirmasi Finalisasi (Format Standar Desain MedScribe AI) --}}
        <div id="modalFinalisasi" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalFinalisasiTitle">
            <div class="dialog-backdrop" onclick="tutupModalFinalisasi()"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div class="dialog w-full max-w-md p-6 pointer-events-auto animate-dialog text-center space-y-4">
                    <span class="mx-auto avatar w-12 h-12 bg-emerald-100 text-emerald-700 border border-emerald-200" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </span>

                    <div class="space-y-2">
                        <h2 id="modalFinalisasiTitle" class="text-lg font-bold text-medical-navy">Finalisasi Rekam Medis?</h2>
                        <p class="text-xs text-text-secondary leading-relaxed">
                            Apakah Anda yakin telah memeriksa dan menyetujui seluruh isi rekam medis untuk <strong>{{ $pasien->nama_pasien }}</strong> (RM: {{ $pasien->no_rekam_medis }})?
                        </p>
                        <div class="p-3.5 rounded-[14px] bg-amber-50 border border-amber-200 text-left text-xs text-amber-900 space-y-1">
                            <span class="font-semibold block text-amber-950">Peringatan Penguncian:</span>
                            <p class="text-amber-800">Setelah finalisasi, data tidak dapat diubah melalui proses pengeditan biasa. Status rekam medis akan menjadi <strong>Final</strong> dan seluruh formulir terkunci secara permanen.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button type="button" onclick="tutupModalFinalisasi()" class="btn btn-secondary min-h-11">
                            Batal
                        </button>
                        <button type="button" id="btn-konfirmasi-finalisasi" onclick="eksekusiFinalisasi()"
                                class="btn bg-emerald-600 hover:bg-emerald-700 text-white font-semibold min-h-11 rounded-[14px]">
                            <span id="btn-konfirmasi-label">Ya, Setujui & Finalisasi</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Script Inline Mandiri (Bebas dari masalah konflik Blade section) --}}
    <script>
    const finalisasiState = {
        semuaSyaratTerpenuhi: @json($semuaSyaratTerpenuhi),
        soapSelesai: @json($soapSelesai),
        ttvSelesai: @json($ttvSelesai),
        wawancaraSelesai: @json($wawancaraSelesai),
        tindakLanjutSelesai: @json($tindakLanjutSelesai),
    };

    function bukaModalFinalisasi() {
        if (!finalisasiState.semuaSyaratTerpenuhi) {
            let pesan = 'Mohon lengkapi persyaratan sebelum finalisasi:';
            if (!finalisasiState.soapSelesai) pesan += '\n- Draf SOAP belum diverifikasi lengkap';
            if (!finalisasiState.ttvSelesai) pesan += '\n- Data TTV perawat belum lengkap';
            if (!finalisasiState.wawancaraSelesai) pesan += '\n- Transkripsi wawancara belum diverifikasi';
            if (!finalisasiState.tindakLanjutSelesai) pesan += '\n- Tindak lanjut medis belum disimpan';
            alert(pesan);
            return;
        }

        const checkbox = document.getElementById('konfirmasi_persetujuan');
        if (!checkbox || !checkbox.checked) {
            alert('Anda wajib mencentang pernyataan persetujuan klinis sebelum memfinalisasi rekam medis.');
            checkbox?.focus();
            return;
        }

        const modal = document.getElementById('modalFinalisasi');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function tutupModalFinalisasi() {
        const modal = document.getElementById('modalFinalisasi');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function eksekusiFinalisasi() {
        const form = document.getElementById('form-finalisasi');
        const btn = document.getElementById('btn-konfirmasi-finalisasi');
        const label = document.getElementById('btn-konfirmasi-label');

        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        }
        if (label) {
            label.textContent = 'Memproses Finalisasi...';
        }

        if (form) {
            form.submit();
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            tutupModalFinalisasi();
        }
    });
    </script>

@else
    {{-- Tampilan saat Tahap Finalisasi Terkunci (SOAP belum diverifikasi) --}}
    <div class="border-t border-border-subtle p-5 sm:p-6">
        <div class="alert alert-warn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="flex-1">Langkah 4 (Setujui & Finalisasi Rekam Medis) akan terbuka setelah draf SOAP diverifikasi oleh dokter pemeriksa.</span>
        </div>
    </div>
@endif

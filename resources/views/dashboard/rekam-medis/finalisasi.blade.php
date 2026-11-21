@php
    $isFinalized = $kunjungan->isFinalized();
    $draf = $soap['draf'] ?? null;

    // Syarat kelayakan ($soapSelesai, $ttvSelesai, ...) dan status tahap dihitung di rekam-medis.blade.php.

    $keteranganFinalisasi = match (true) {
        $isFinalized => 'Rekam medis ini sudah disetujui dan tidak bisa diubah lagi.',
        $semuaSyaratTerpenuhi => 'Semua data klinis lengkap. Centang pernyataan persetujuan, lalu finalisasi rekam medis.',
        $tahapFinalisasi === 'active' => 'Lengkapi syarat di bawah sebelum memfinalisasi rekam medis.',
        default => 'Terbuka setelah draf SOAP diverifikasi.',
    };

    $dokterPemeriksa = $kunjungan->dokter ?? $user;
    $finalizedDoctor = $finalisasi?->finalizedBy ?? $dokterPemeriksa;
    $waktuFinal = $finalisasi?->finalized_at
        ? \Carbon\Carbon::parse($finalisasi->finalized_at)->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB'
        : null;

    // Nama bagian SOAP sama dengan form SOAP dan pratinjau.
    $bagianSoapFinal = [
        ['subjective_text', 'Subjektif'],
        ['objective_text', 'Objektif'],
        ['assessment_text', 'Asesmen'],
        ['plan_text', 'Rencana'],
    ];
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
    {{-- Rekam medis final: hanya baca --}}
    <div class="border-t border-border-subtle p-5 sm:p-6 space-y-6">

        <div class="alert alert-ok" role="status">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            <div class="flex-1 text-text-body">
                <p class="font-semibold text-medical-navy">Rekam medis sudah final</p>
                <p class="mt-0.5">
                    Disetujui oleh <span class="font-semibold text-medical-navy">{{ $finalizedDoctor->nama_lengkap }}</span>@if ($waktuFinal) pada {{ $waktuFinal }}@endif.
                </p>
                @if ($finalisasi?->catatan_finalisasi)
                    <p class="mt-2"><span class="font-semibold text-medical-navy">Catatan dokter:</span> {{ $finalisasi->catatan_finalisasi }}</p>
                @endif
            </div>
        </div>

        <section class="space-y-3" aria-labelledby="judulSyaratFinal">
            <h3 id="judulSyaratFinal" class="text-sm font-semibold text-medical-navy">Syarat finalisasi</h3>
            @include('dashboard.rekam-medis.syarat-finalisasi')
        </section>

        <section class="space-y-4" aria-labelledby="judulArsip">
            <h3 id="judulArsip" class="text-sm font-semibold text-medical-navy">Ringkasan rekam medis</h3>

            <dl class="panel grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs text-text-secondary">Pasien</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $pasien->nama_pasien }}</dd>
                    <dd class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-text-secondary">
                        <span class="tag-id"><span class="sr-only">No. RM </span>{{ $pasien->no_rekam_medis }}</span>
                        <span>{{ $umur ? $umur . ' tahun' : 'Umur belum diisi' }} · {{ $pasien->jenis_kelamin ?? '-' }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-text-secondary">Dokter pemeriksa</dt>
                    <dd class="mt-0.5 font-semibold text-medical-navy">{{ $dokterPemeriksa->nama_lengkap }}</dd>
                    <dd class="mt-1"><span class="tag-id tag-id-muted"><span class="sr-only">Kode kunjungan </span>{{ $idKunjunganCode }}</span></dd>
                </div>
            </dl>

            @if ($draf)
                <div class="rounded-(--radius-panel) border border-border-subtle bg-white p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-border-subtle">
                        <p class="text-sm font-semibold text-medical-navy">Catatan SOAP</p>
                        @if ($draf->kode_icd10)
                            <span class="chip chip-accent">
                                <span class="font-mono font-semibold">ICD-10 {{ $draf->kode_icd10 }}</span>
                                @if (filled($icd10LabelResmi ?? null))
                                    <span class="whitespace-normal">{{ $icd10LabelResmi }}</span>
                                @endif
                            </span>
                        @endif
                    </div>
                    <dl class="space-y-3 text-sm">
                        @foreach ($bagianSoapFinal as [$fieldSoap, $namaSoap])
                            <div>
                                <dt class="text-xs font-semibold text-royal-indigo-hover">{{ $namaSoap }}</dt>
                                <dd class="mt-0.5 pl-3 border-l-2 border-border-subtle text-text-body whitespace-pre-line leading-relaxed">{{ $draf->$fieldSoap ?: '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($tindakLanjut)
                <div class="rounded-(--radius-panel) border border-border-subtle bg-white p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-border-subtle">
                        <p class="text-sm font-semibold text-medical-navy">Tindak lanjut medis</p>
                        <span class="status status-ok">{{ $tindakLanjut->status ?: 'Tersimpan' }}</span>
                    </div>

                    @if ($tindakLanjut->eResep->isNotEmpty())
                        <div>
                            <p class="text-xs font-semibold text-text-secondary mb-1.5">E-Resep</p>
                            <ul class="space-y-1.5">
                                @foreach ($tindakLanjut->eResep as $resep)
                                    <li class="text-sm p-2.5 rounded-(--radius-field) bg-soft-offwhite border border-border-subtle flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold text-medical-navy">{{ $resep->nama_obat }}</span>
                                        <span class="text-xs text-text-secondary">{{ collect([$resep->dosis, $resep->bentuk_sediaan, $resep->aturan_konsumsi, $resep->jumlah ? $resep->jumlah . ' item' : null])->filter()->join(' · ') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($tindakLanjut->rujukanLab)
                        <div>
                            <p class="text-xs font-semibold text-text-secondary mb-1.5">Rujukan Lab</p>
                            <p class="text-sm text-text-body mb-2">{{ $tindakLanjut->rujukanLab->catatan ?: 'Tidak ada catatan tambahan.' }}</p>
                            <ul class="flex flex-wrap gap-1.5">
                                @foreach ($tindakLanjut->rujukanLab->parameter as $param)
                                    <li class="chip">
                                        {{ $param->nama_pemeriksaan }}
                                        @if ($param->kode_loinc)<span class="tag-id">{{ $param->kode_loinc }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($tindakLanjut->jenis_tindak_lanjut === \App\Models\TindakLanjut::JENIS_TANPA_TINDAKAN)
                        <p class="text-sm text-text-secondary">Tanpa tindak lanjut: pasien selesai tanpa e-resep dan tanpa rujukan lab.</p>
                    @endif
                </div>
            @endif
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-border-subtle">
            @include('partials.back-link', ['href' => '#soap', 'label' => 'Kembali ke SOAP'])
            <a href="{{ route('dokter.soap.preview', $kunjungan) }}" id="btn-preview-soap" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <span>Pratinjau SOAP (PDF)</span>
            </a>
        </div>
    </div>

@elseif ($tahapFinalisasi === 'active')
    {{-- Sebelum finalisasi: periksa syarat, centang persetujuan, konfirmasi --}}
    <div class="border-t border-border-subtle p-5 sm:p-6 space-y-6">

        <section class="space-y-3" aria-labelledby="judulSyarat">
            <h3 id="judulSyarat" class="text-sm font-semibold text-medical-navy">Syarat finalisasi</h3>
            @include('dashboard.rekam-medis.syarat-finalisasi')
        </section>

        @unless ($semuaSyaratTerpenuhi)
            <div id="finalisasi-syarat-belum" class="alert alert-warn" role="status">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="flex-1 text-text-body">Finalisasi bisa dilakukan setelah semua syarat di atas terpenuhi.</span>
            </div>
        @endunless

        <form id="form-finalisasi" method="POST" action="{{ route('dokter.rekam-medis.finalisasi', $kunjungan) }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="catatan_finalisasi" class="field-label">Catatan finalisasi (opsional)</label>
                <textarea id="catatan_finalisasi" name="catatan_finalisasi" rows="2" class="field leading-relaxed" placeholder="Catatan khusus dari dokter penanggung jawab, bila ada">{{ old('catatan_finalisasi') }}</textarea>
                @error('catatan_finalisasi')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="panel">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="konfirmasi_persetujuan" name="konfirmasi_persetujuan" value="1" required
                           aria-describedby="konfirmasi-error"
                           class="checkbox mt-0.5 @error('konfirmasi_persetujuan') is-invalid @enderror"
                           @disabled(! $semuaSyaratTerpenuhi)>
                    <span class="text-sm text-text-body leading-relaxed">
                        <span class="font-semibold text-medical-navy">Saya bertanggung jawab atas isi rekam medis ini.</span>
                        Saya sudah memeriksa diagnosis, catatan SOAP, dan instruksi terapi, dan memahami bahwa setelah finalisasi data tidak bisa diubah lagi.
                    </span>
                </label>
                <p id="konfirmasi-error" class="field-error ml-8 {{ $errors->has('konfirmasi_persetujuan') ? '' : 'hidden' }}">
                    {{ $errors->first('konfirmasi_persetujuan') ?: 'Centang pernyataan ini sebelum memfinalisasi.' }}
                </p>
            </div>

            <div id="finalisasi-error" class="alert alert-danger" role="alert" hidden>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                <p class="flex-1 font-medium" data-error-text></p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3 pt-2">
                @include('partials.back-link', ['href' => '#soap', 'label' => 'Kembali ke SOAP'])
                <button type="button" id="btn-finalisasi-action" class="btn btn-primary" @disabled(! $semuaSyaratTerpenuhi)
                        @unless ($semuaSyaratTerpenuhi) aria-describedby="finalisasi-syarat-belum" @endunless>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span>Setujui &amp; Finalisasi Rekam Medis</span>
                </button>
            </div>
        </form>

        <x-dialog id="modalFinalisasi" tone="warn" size="md" title="Finalisasi rekam medis?"
                  icon="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
            <p class="text-sm text-text-body text-center">
                Rekam medis <span class="font-semibold text-medical-navy">{{ $pasien->nama_pasien }}</span>
                (<span class="font-mono">{{ $pasien->no_rekam_medis }}</span>) akan menjadi final.
                Setelah itu wawancara, SOAP, dan tindak lanjut tidak bisa diubah lagi.
            </p>
            <x-slot:actions>
                <button type="button" class="btn btn-secondary" data-dialog-close data-dialog-focus>Batal</button>
                <button type="button" id="btn-konfirmasi-finalisasi" class="btn btn-primary">
                    <span class="spinner hidden" aria-hidden="true" data-spinner></span>
                    <span id="btn-konfirmasi-label">Finalisasi</span>
                </button>
            </x-slot:actions>
        </x-dialog>
    </div>

    <script>
    (function () {
        const form = document.getElementById('form-finalisasi');
        const tombolUtama = document.getElementById('btn-finalisasi-action');
        const tombolKonfirmasi = document.getElementById('btn-konfirmasi-finalisasi');
        const centang = document.getElementById('konfirmasi_persetujuan');
        const errorCentang = document.getElementById('konfirmasi-error');
        const errorFinal = document.getElementById('finalisasi-error');
        if (!form || !tombolUtama || !tombolKonfirmasi) return;

        const tampilkanErrorFinal = (pesan) => {
            errorFinal.querySelector('[data-error-text]').textContent = pesan;
            errorFinal.hidden = false;
            errorFinal.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        centang?.addEventListener('change', () => {
            if (centang.checked) {
                errorCentang.classList.add('hidden');
                centang.classList.remove('is-invalid');
            }
        });

        // Persetujuan belum dicentang: pesan di bawah kotak centang, bukan dialog.
        tombolUtama.addEventListener('click', () => {
            errorFinal.hidden = true;
            if (!centang?.checked) {
                errorCentang.classList.remove('hidden');
                centang?.classList.add('is-invalid');
                centang?.focus();
                return;
            }
            window.openDialog?.('modalFinalisasi', tombolUtama);
        });

        const setMemproses = (aktif) => {
            tombolKonfirmasi.disabled = aktif;
            tombolUtama.disabled = aktif;
            tombolKonfirmasi.querySelector('[data-spinner]')?.classList.toggle('hidden', !aktif);
            document.getElementById('btn-konfirmasi-label').textContent = aktif ? 'Memfinalisasi…' : 'Finalisasi';
        };

        tombolKonfirmasi.addEventListener('click', async () => {
            if (tombolKonfirmasi.disabled) return;
            setMemproses(true);

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token'),
                    },
                    body: formData,
                });
                const result = await response.json().catch(() => ({}));

                if (response.ok && result.success) {
                    window.location.href = result.redirect_url || (window.location.pathname + '#finalisasi');
                    window.location.reload();
                    return;
                }

                window.closeDialog?.('modalFinalisasi');
                setMemproses(false);
                tampilkanErrorFinal(result.message || 'Rekam medis gagal difinalisasi. Periksa kembali kelengkapan data klinis.');
            } catch (error) {
                // Kendala jaringan atau skrip: kirim formulir dengan cara biasa.
                form.submit();
            }
        });
    })();
    </script>

@else
    {{-- Tahap finalisasi belum terbuka --}}
    <div class="border-t border-border-subtle p-5 sm:p-6">
        <div class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            <span class="flex-1 text-text-body">Finalisasi terbuka setelah draf SOAP diverifikasi.</span>
        </div>
    </div>
@endif

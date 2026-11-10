@php
    [$statusLabel, $statusClass] = match ($tahapTindakLanjut) {
        'done' => ['Tersimpan', 'status-ok'],
        'active' => ['Siap diisi', 'status-warn'],
        'locked' => ['Terkunci', 'status-muted'],
        default => ['Belum tersedia', 'status-muted'],
    };

    $keterangan = match ($tahapTindakLanjut) {
        'done' => 'Rencana tindak lanjut pasien sudah tersimpan pada rekam medis.',
        'active' => 'SOAP sudah terverifikasi. Tentukan resep, rujukan lab, atau selesaikan tanpa tindakan lanjut.',
        default => 'Terbuka setelah draf SOAP diverifikasi oleh dokter.',
    };

    $jenisLabel = $tindakLanjut
        ? match ($tindakLanjut->jenis_tindak_lanjut) {
            \App\Models\TindakLanjut::JENIS_RESEP => 'Resep Obat',
            \App\Models\TindakLanjut::JENIS_RESEP_RUJUKAN => 'Resep Obat + Rujukan Lab',
            \App\Models\TindakLanjut::JENIS_RUJUKAN => 'Rujukan Lab',
            \App\Models\TindakLanjut::JENIS_TANPA_TINDAKAN => 'Selesai Tanpa Tindakan Lanjut',
            default => $tindakLanjut->jenis_tindak_lanjut,
        }
        : null;

    $opsiLama = old('opsi', []);
    $opsiLama = is_array($opsiLama) ? $opsiLama : [];

    $resepLama = old('resep');
    if (! is_array($resepLama) || $resepLama === []) {
        $resepLama = [[
            'nama_obat' => '',
            'dosis' => '',
            'bentuk_sediaan' => '',
            'jumlah' => '',
            'aturan_konsumsi' => '',
        ]];
    }
    $resepLama = array_values($resepLama);

    $parameterLoinc = $parameterLoinc ?? collect();
    $kodeLoincTerpilih = '';
    if (filled(old('id_parameter_loinc'))) {
        $kodeLoincTerpilih = (string) optional(
            $parameterLoinc->firstWhere('id_parameter_loinc', (int) old('id_parameter_loinc'))
        )->kode_loinc;
    }
@endphp

@include('dashboard.rekam-medis.kepala-tahap', [
    'nomor' => 3,
    'judul' => 'Kelola Tindak Lanjut Medis',
    'keterangan' => $keterangan,
    'keadaan' => $tahapTindakLanjut,
    'statusLabel' => $statusLabel,
    'statusClass' => $statusClass,
    'idJudul' => 'judulTindakLanjut',
])

@if ($tahapTindakLanjut === 'active')
    <div class="border-t border-border-subtle p-5 sm:p-6 space-y-5">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                <div class="space-y-0.5">
                    <p class="font-semibold">Lengkapi field wajib sebelum validasi.</p>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form id="tindak-lanjut-form" method="POST" action="{{ route('dokter.tindak-lanjut.store', $kunjungan) }}" class="space-y-5" data-submit-loading>
            @csrf

            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-medical-navy">Pilihan tindak lanjut</legend>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="group relative flex cursor-pointer items-start gap-3 rounded-[18px] border border-border-subtle bg-white p-4 transition hover:border-border-hover has-[:checked]:border-royal-indigo has-[:checked]:bg-pale-lavender has-[:checked]:ring-2 has-[:checked]:ring-royal-indigo/15 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60">
                        <input type="checkbox" name="opsi[]" value="resep" class="mt-0.5 h-4 w-4 rounded accent-royal-indigo" data-opsi-resep @checked(in_array('resep', $opsiLama, true)) @if (in_array('tanpa_tindakan', $opsiLama, true)) disabled @endif>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-sm font-semibold text-medical-navy">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Buat E-Resep
                            </span>
                            <span class="mt-1 block text-xs leading-relaxed text-text-secondary">Terbitkan e-resep obat untuk pasien.</span>
                        </span>
                    </label>

                    <label class="group relative flex cursor-pointer items-start gap-3 rounded-[18px] border border-border-subtle bg-white p-4 transition hover:border-border-hover has-[:checked]:border-royal-indigo has-[:checked]:bg-pale-lavender has-[:checked]:ring-2 has-[:checked]:ring-royal-indigo/15 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60">
                        <input type="checkbox" name="opsi[]" value="rujukan" class="mt-0.5 h-4 w-4 rounded accent-royal-indigo" data-opsi-rujukan @checked(in_array('rujukan', $opsiLama, true)) @if (in_array('tanpa_tindakan', $opsiLama, true)) disabled @endif>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-sm font-semibold text-medical-navy">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                Buat Surat Rujukan Lab
                            </span>
                            <span class="mt-1 block text-xs leading-relaxed text-text-secondary">Rujuk pemeriksaan laboratorium dengan kode LOINC.</span>
                        </span>
                    </label>

                    <label class="group relative flex cursor-pointer items-start gap-3 rounded-[18px] border border-border-subtle bg-white p-4 transition hover:border-border-hover has-[:checked]:border-royal-indigo has-[:checked]:bg-pale-lavender has-[:checked]:ring-2 has-[:checked]:ring-royal-indigo/15 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60">
                        <input type="checkbox" name="opsi[]" value="tanpa_tindakan" class="mt-0.5 h-4 w-4 rounded accent-royal-indigo" data-opsi-tanpa @checked(in_array('tanpa_tindakan', $opsiLama, true))>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-sm font-semibold text-medical-navy">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Tanpa Tindak Lanjut
                            </span>
                            <span class="mt-1 block text-xs leading-relaxed text-text-secondary">Tandai pasien selesai tanpa resep atau rujukan.</span>
                        </span>
                    </label>
                </div>

                <p id="tindak-lanjut-client-error" class="field-error hidden" role="alert"></p>
                @error('opsi')<p class="field-error">{{ $message }}</p>@enderror
                @error('opsi.*')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>

            <div id="form-resep" class="rounded-[20px] border border-border-subtle bg-soft-offwhite p-4 sm:p-5 space-y-4" hidden>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-medical-navy">E-Resep</h3>
                        <p class="mt-0.5 text-xs text-text-secondary">Setiap obat dapat diisi terpisah.</p>
                    </div>
                    <button type="button" id="tambah-obat" class="btn btn-secondary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Tambah Obat
                    </button>
                </div>

                @error('resep')<p class="field-error">{{ $message }}</p>@enderror
                @error('resep.*')<p class="field-error">{{ $message }}</p>@enderror

                <div id="daftar-obat" class="space-y-4">
                    @foreach ($resepLama as $index => $obat)
                        @include('dashboard.rekam-medis.resep-item', ['index' => $index, 'obat' => $obat])
                    @endforeach
                </div>

                <template id="template-obat">
                    @include('dashboard.rekam-medis.resep-item', ['index' => '__INDEX__', 'obat' => []])
                </template>
            </div>

            <div id="form-rujukan" class="rounded-[20px] border border-border-subtle bg-soft-offwhite p-4 sm:p-5 space-y-4" hidden>
                <div>
                    <h3 class="text-sm font-semibold text-medical-navy">E-Rujukan Lab</h3>
                    <p class="mt-0.5 text-xs text-text-secondary">Pilih parameter pemeriksaan dari katalog LOINC.</p>
                </div>

                @if ($parameterLoinc->isEmpty())
                    <div class="alert alert-warn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="flex-1">Katalog parameter LOINC belum tersedia. Hubungi administrator sebelum membuat rujukan lab.</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="id_parameter_loinc" class="field-label">Parameter Pemeriksaan Laboratorium <span class="req">*</span></label>
                        <select id="id_parameter_loinc" name="id_parameter_loinc" class="field @error('id_parameter_loinc') is-invalid @enderror" data-error-key="id_parameter_loinc" data-required-rujukan>
                            <option value="">Pilih parameter pemeriksaan</option>
                            @foreach ($parameterLoinc as $parameter)
                                <option value="{{ $parameter->id_parameter_loinc }}" data-kode-loinc="{{ $parameter->kode_loinc }}" @selected((string) old('id_parameter_loinc') === (string) $parameter->id_parameter_loinc)>
                                    {{ $parameter->nama_pemeriksaan }}@if ($parameter->kategori) · {{ $parameter->kategori }}@endif
                                </option>
                            @endforeach
                        </select>
                        @error('id_parameter_loinc')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="kode_loinc_tampil" class="field-label">Kode LOINC</label>
                        <input id="kode_loinc_tampil" type="text" class="field font-mono" value="{{ $kodeLoincTerpilih }}" readonly placeholder="Terisi otomatis">
                    </div>
                </div>

                <div>
                    <label for="catatan_rujukan" class="field-label">Catatan Rujukan</label>
                    <textarea id="catatan_rujukan" name="catatan_rujukan" rows="3" class="field @error('catatan_rujukan') is-invalid @enderror" data-error-key="catatan_rujukan" placeholder="Contoh: Periksa darah lengkap untuk evaluasi infeksi.">{{ old('catatan_rujukan') }}</textarea>
                    @error('catatan_rujukan')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="info-tanpa-tindakan" class="alert alert-info" hidden>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="flex-1">Pasien akan ditandai selesai tanpa resep obat dan tanpa rujukan laboratorium.</span>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-1">
                <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">
                    <svg xmlns="http://www.w3.org/2000/svg" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true" data-spinner><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".25" /><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" /></svg>
                    <span data-label>Validasi &amp; Simpan</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const form = document.getElementById('tindak-lanjut-form');
            if (!form) return;

            const opsiResep = form.querySelector('[data-opsi-resep]');
            const opsiRujukan = form.querySelector('[data-opsi-rujukan]');
            const opsiTanpa = form.querySelector('[data-opsi-tanpa]');
            const formResep = document.getElementById('form-resep');
            const formRujukan = document.getElementById('form-rujukan');
            const infoTanpa = document.getElementById('info-tanpa-tindakan');
            const daftarObat = document.getElementById('daftar-obat');
            const templateObat = document.getElementById('template-obat');
            const tambahObat = document.getElementById('tambah-obat');
            const parameterSelect = document.getElementById('id_parameter_loinc');
            const kodeLoinc = document.getElementById('kode_loinc_tampil');
            const clientError = document.getElementById('tindak-lanjut-client-error');
            const submitButton = form.querySelector('button[type="submit"]');
            let indexObat = {{ count($resepLama) }};

            const setSectionEnabled = (section, enabled) => {
                section.querySelectorAll('input, select, textarea, button').forEach((field) => {
                    field.disabled = !enabled;
                    if (field.matches('[data-required-resep], [data-required-rujukan]')) {
                        field.required = enabled;
                    }
                });
            };

            const updateRemoveButtons = () => {
                const aktif = opsiResep.checked && !opsiTanpa.checked;
                const items = [...daftarObat.querySelectorAll('[data-resep-item]')];
                items.forEach((item) => {
                    const hapus = item.querySelector('[data-hapus-obat]');
                    if (hapus) hapus.disabled = !aktif || items.length <= 1;
                });
            };

            const syncKodeLoinc = () => {
                const option = parameterSelect?.selectedOptions?.[0];
                if (kodeLoinc) kodeLoinc.value = option?.dataset?.kodeLoinc || '';
            };

            const syncSelection = () => {
                if (opsiTanpa.checked) {
                    opsiResep.checked = false;
                    opsiRujukan.checked = false;
                }

                opsiResep.disabled = opsiTanpa.checked;
                opsiRujukan.disabled = opsiTanpa.checked;

                const resepAktif = opsiResep.checked && !opsiTanpa.checked;
                const rujukanAktif = opsiRujukan.checked && !opsiTanpa.checked;

                formResep.hidden = !resepAktif;
                formRujukan.hidden = !rujukanAktif;
                infoTanpa.hidden = !opsiTanpa.checked;

                setSectionEnabled(formResep, resepAktif);
                setSectionEnabled(formRujukan, rujukanAktif);
                updateRemoveButtons();
            };

            const clearServerErrors = () => {
                form.querySelectorAll('[data-server-error]').forEach((error) => error.remove());
                form.querySelectorAll('.is-invalid').forEach((field) => {
                    field.classList.remove('is-invalid');
                    field.removeAttribute('aria-invalid');
                });
                clientError.classList.add('hidden');
                clientError.textContent = '';
            };

            const showClientError = (message) => {
                clientError.textContent = message;
                clientError.classList.remove('hidden');
            };

            const showServerErrors = (data) => {
                showClientError(data.message || 'Lengkapi field wajib sebelum validasi.');
                const errors = data.errors || {};

                Object.entries(errors).forEach(([key, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : String(messages);
                    const field = form.querySelector(`[data-error-key="${key}"]`);

                    if (field) {
                        field.classList.add('is-invalid');
                        field.setAttribute('aria-invalid', 'true');
                        const error = document.createElement('p');
                        error.className = 'field-error';
                        error.dataset.serverError = 'true';
                        error.textContent = message;
                        field.insertAdjacentElement('afterend', error);
                    } else if (key === 'opsi' || key === 'opsi.0') {
                        showClientError(message);
                    }
                });

                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus({ preventScroll: true });
                }
            };

            const setLoading = (loading) => {
                if (!submitButton) return;
                submitButton.disabled = loading;
                submitButton.setAttribute('aria-busy', loading ? 'true' : 'false');
                submitButton.querySelector('[data-spinner]')?.classList.toggle('hidden', !loading);
                const label = submitButton.querySelector('[data-label]');
                if (label) {
                    if (loading) {
                        label.dataset.originalLabel = label.textContent;
                        label.textContent = submitButton.dataset.loadingText || 'Menyimpan...';
                    } else if (label.dataset.originalLabel) {
                        label.textContent = label.dataset.originalLabel;
                    }
                }
            };

            [opsiResep, opsiRujukan, opsiTanpa].forEach((checkbox) => {
                checkbox.addEventListener('change', syncSelection);
            });

            tambahObat.addEventListener('click', () => {
                const html = templateObat.innerHTML.replaceAll('__INDEX__', String(indexObat++));
                daftarObat.insertAdjacentHTML('beforeend', html);
                setSectionEnabled(formResep, true);
                updateRemoveButtons();
            });

            daftarObat.addEventListener('click', (event) => {
                const hapus = event.target.closest('[data-hapus-obat]');
                if (!hapus) return;

                const items = daftarObat.querySelectorAll('[data-resep-item]');
                if (items.length <= 1) return;

                hapus.closest('[data-resep-item]')?.remove();
                updateRemoveButtons();
            });

            parameterSelect?.addEventListener('change', syncKodeLoinc);

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                clearServerErrors();

                const pilihan = [opsiResep, opsiRujukan, opsiTanpa].filter((checkbox) => checkbox.checked && !checkbox.disabled);
                if (pilihan.length === 0) {
                    showClientError('Pilih minimal satu jenis tindak lanjut.');
                    clientError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (!form.reportValidity()) return;

                setLoading(true);

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token') || '',
                        },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (response.ok) {
                        const targetUrl = data.redirect || (window.location.pathname + '#finalisasi');
                        window.location.href = targetUrl;
                        window.location.reload();
                        return;
                    }

                    showServerErrors(data);
                } catch (error) {
                    showClientError('Gagal menghubungi server. Periksa koneksi lalu coba lagi.');
                } finally {
                    setLoading(false);
                }
            });

            syncSelection();
            syncKodeLoinc();
        })();
    </script>
@elseif ($tahapTindakLanjut === 'done' && $tindakLanjut)
    <div class="border-t border-border-subtle p-5 sm:p-6 space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3 rounded-[18px] bg-soft-offwhite border border-border-subtle p-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-secondary">Jenis tindak lanjut</p>
                <p class="mt-1 text-sm font-semibold text-medical-navy">{{ $jenisLabel }}</p>
            </div>
            <span class="status status-ok">{{ $tindakLanjut->status ?: 'Tersimpan' }}</span>
        </div>

        @if ($tindakLanjut->eResep->isNotEmpty())
            <div>
                <h3 class="text-sm font-semibold text-medical-navy">E-Resep</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($tindakLanjut->eResep as $resep)
                        <li class="rounded-[16px] border border-border-subtle bg-white p-3">
                            <p class="text-sm font-semibold text-medical-navy">{{ $resep->nama_obat }}</p>
                            <p class="mt-1 text-xs text-text-secondary">
                                {{ collect([$resep->dosis, $resep->bentuk_sediaan, $resep->aturan_konsumsi, $resep->jumlah ? $resep->jumlah . ' item' : null])->filter()->join(' · ') ?: 'Detail obat belum diisi' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($tindakLanjut->rujukanLab)
            <div>
                <h3 class="text-sm font-semibold text-medical-navy">Rujukan Laboratorium</h3>
                <p class="mt-1 text-xs text-text-secondary">{{ $tindakLanjut->rujukanLab->catatan ?: 'Tidak ada catatan tambahan.' }}</p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($tindakLanjut->rujukanLab->parameter as $parameter)
                        <li class="tag-id tag-id-muted">
                            {{ $parameter->nama_pemeriksaan }}
                            @if ($parameter->kode_loinc)
                                <span class="font-mono">{{ $parameter->kode_loinc }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@elseif ($tahapTindakLanjut === 'locked')
    <div class="border-t border-border-subtle p-5 sm:p-6">
        <div class="alert alert-warn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="flex-1">Verifikasi draf SOAP untuk membuka tindak lanjut medis.</span>
        </div>
    </div>
@endif

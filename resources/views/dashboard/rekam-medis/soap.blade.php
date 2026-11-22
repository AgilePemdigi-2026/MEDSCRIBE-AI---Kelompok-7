@php
    $isFinalized = $kunjungan->isFinalized();
    // $statusSoap dan $kelasStatusSoap dihitung di rekam-medis.blade.php.
    $pakaiOpenRouter = config('medscribe.soap_driver') === 'openrouter';
    $bagianSoap = [
        // Nama bagian sama dengan pratinjau dan finalisasi: Subjektif, Objektif, Asesmen, Rencana.
        ['subjective_text', 'S', 'Subjektif', 'Keluhan dan riwayat dari pasien'],
        ['objective_text', 'O', 'Objektif', 'Hasil pemeriksaan dan TTV'],
        ['assessment_text', 'A', 'Asesmen', 'Penilaian awal klinis, belum diagnosis final'],
        ['plan_text', 'P', 'Rencana', 'Terapi, obat, dan pemeriksaan penunjang'],
    ];
@endphp

@include('dashboard.rekam-medis.kepala-tahap', [
    'nomor' => 2,
    'judul' => 'Tinjau dan Edit Draf SOAP',
    'keterangan' => $tahapSoap === 'locked'
        ? 'Terbuka setelah transkrip wawancara ditinjau dan diverifikasi.'
        : ($isFinalized ? 'Rekam medis sudah final. Draf SOAP tidak bisa diubah lagi.' : 'Draf disusun AI dari transkrip dan TTV. Baca dan koreksi sebelum diverifikasi.'),
    'keadaan' => $tahapSoap,
    'statusLabel' => $statusSoap,
    'statusClass' => $kelasStatusSoap,
    'idJudul' => 'judulSoap',
])

@if ($soap)
@php
    $draf = $soap['draf'];
    $isProcessing = ($draf->generation_status ?? '') === 'processing';
@endphp
<div class="border-t border-border-subtle p-5 sm:p-6 space-y-5">

    @if ($isFinalized)
        <div class="alert alert-ok" role="status">
            <x-icon name="lock" aria-hidden="true" />
            <div class="flex-1 text-text-body">
                <p class="font-semibold text-medical-navy">Rekam medis sudah final</p>
                <p class="mt-0.5">Draf SOAP sudah disetujui dan tidak bisa diubah lagi.</p>
            </div>
        </div>
    @endif

    @if ($isProcessing)
        <div id="soap-processing-banner" class="panel flex flex-col items-center text-center gap-3 !py-6" role="status">
            <span class="spinner !w-8 !h-8 text-royal-indigo" aria-hidden="true"></span>
            <div>
                <h3 class="text-sm font-semibold text-medical-navy">AI sedang menyusun draf SOAP</h3>
                <p class="text-xs text-text-secondary mt-1 max-w-md">Menganalisis transkrip wawancara dan TTV. Halaman diperbarui otomatis setelah selesai.</p>
            </div>
        </div>
    @endif

    @if ($soap['generationError'] && ! $isProcessing)
        <div class="alert alert-danger">
            <x-icon name="triangle-alert" aria-hidden="true" />
            <span class="flex-1">{{ $soap['generationError'] }}</span>
        </div>
    @endif

    @php
        // Item dari AI bisa berupa teks atau objek; objek dijadikan kalimat, bukan JSON.
        $keKalimat = fn ($item) => is_array($item)
            ? collect($item)->flatten()->filter(fn ($v) => is_scalar($v) && filled($v))->implode(' — ')
            : $item;
        $tampilCatatan = ! $isFinalized && ! $isProcessing;
        $drafBerubah = $tampilCatatan && $draf->needs_review && ! $soap['generationError'];
        $kelompokCatatan = $tampilCatatan ? array_filter([
            'Belum ditanyakan di wawancara' => collect($draf->missing_information ?? [])->map($keKalimat)->filter()->values()->all(),
            'Catatan dari AI' => collect($draf->review_warnings ?? [])->map($keKalimat)->filter()->values()->all(),
        ]) : [];
    @endphp

    {{-- Satu kotak untuk semua hal yang perlu dicek dokter, supaya tidak menumpuk tiga alert kuning. --}}
    @if ($drafBerubah || $kelompokCatatan)
        <div class="alert alert-warn" role="status">
            <x-icon name="circle-alert" aria-hidden="true" />
            <div class="flex-1 min-w-0 text-text-body">
                <p class="font-semibold text-warning-text">Periksa dulu sebelum memverifikasi</p>

                @if ($drafBerubah)
                    <p class="mt-1">Transkrip atau TTV berubah setelah draf ini dibuat. Isi draf tidak ikut berubah, jadi tinjau ulang bagian yang terdampak.</p>
                @endif

                @foreach ($kelompokCatatan as $judulKelompok => $daftar)
                    <div class="mt-3">
                        <p class="text-xs font-semibold text-text-secondary">{{ $judulKelompok }}</p>
                        <ul class="mt-1 list-disc pl-5 space-y-1">
                            @foreach ($daftar as $kalimat)
                                <li>{{ $kalimat }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @include('partials.form-errors')

    <form id="soap-form" method="POST" action="{{ route('dokter.soap.save', $kunjungan) }}" class="space-y-5" data-submit-loading>
        @csrf
        @method('PUT')

        <div class="space-y-4">
            @foreach ($bagianSoap as [$field, $huruf, $nama, $petunjuk])
                <div>
                    <label for="{{ $field }}" class="flex items-center gap-2.5 mb-2">
                        <span class="avatar w-7 h-7 bg-pale-lavender text-royal-indigo text-xs font-bold" aria-hidden="true">{{ $huruf }}</span>
                        <span class="text-sm font-semibold text-medical-navy">{{ $nama }}</span>
                        <span class="text-[11px] text-text-secondary">{{ $petunjuk }}</span>
                    </label>
                    <textarea id="{{ $field }}" name="{{ $field }}" rows="4" required
                              @disabled($isFinalized)
                              class="field leading-relaxed @error($field) is-invalid @enderror">{{ old($field, $draf->$field) }}</textarea>
                    @error($field)<p class="field-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>

        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <label for="kode_icd10" class="field-label mb-0">Kode ICD-10 (Diagnosis Utama)</label>
                @if (filled($kodeIc10 ?? null))
                    <span id="icd10-sumber"
                          data-sumber-icd10="{{ ($icd10Ic10 ?? false) ? 'ai' : 'manual' }}"
                          class="status {{ ($icd10Ic10 ?? false) ? 'status-warn' : 'status-ok' }}">
                        <span data-teks-sumber>{{ ($icd10Ic10 ?? false) ? 'Diisi AI, perlu dikonfirmasi' : 'Diisi dokter' }}</span>
                    </span>
                @endif
            </div>

            <div class="relative" id="icd10-pencarian">
                <input type="text" id="kode_icd10" name="kode_icd10" autocomplete="off"
                       value="{{ old('kode_icd10', $draf->kode_icd10) }}"
                       placeholder="Contoh: J02.9"
                       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="icd10-hasil"
                       class="field font-mono @error('kode_icd10') is-invalid @enderror"
                       @disabled($isFinalized)>
                <div id="icd10-hasil" role="listbox"
                     class="hidden absolute left-0 right-0 top-full mt-1 z-30 max-h-64 overflow-y-auto rounded-(--radius-field) border border-border-subtle bg-white shadow-lg py-1"></div>
            </div>

            <p id="icd10-label" class="field-hint">{{ $icd10Label ?? '' }}</p>
            @error('kode_icd10')<p class="field-error">{{ $message }}</p>@enderror

            @if (! empty($saranIcd10))
                <div class="mt-3">
                    <p class="text-xs font-semibold text-medical-navy mb-1.5">Saran AI, pilih untuk memakai kodenya</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($saranIcd10 as $saran)
                            <button type="button"
                                    data-kode-icd10="{{ $saran['code'] }}"
                                    data-label-icd10="{{ $saran['description'] }}"
                                    @disabled($isFinalized)
                                    class="chip min-h-11 px-3.5 bg-white text-left whitespace-normal transition-colors hover:border-royal-indigo hover:bg-pale-lavender disabled:cursor-not-allowed disabled:opacity-60">
                                <span class="font-mono font-semibold text-medical-navy">{{ $saran['code'] }}</span>
                                <span class="text-text-secondary">{{ $saran['description'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Data pasien dilipat --}}
        <details class="group rounded-(--radius-panel) border border-border-subtle" @if ($errors->hasAny(['nama_pasien', 'no_rekam_medis', 'tanggal_lahir', 'jenis_kelamin'])) open @endif>
            <summary class="flex items-center justify-between gap-3 px-4 min-h-12 text-sm font-medium text-medical-navy list-none cursor-pointer">
                <span>Koreksi data identitas pasien</span>
                <x-icon name="chevron-down" class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" aria-hidden="true" />
            </summary>
            <div class="border-t border-border-subtle p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nama_pasien" class="field-label">Nama pasien</label>
                    <input id="nama_pasien" name="nama_pasien" value="{{ old('nama_pasien', $pasien->nama_pasien) }}" required class="field" @disabled($isFinalized)>
                </div>
                <div>
                    <label for="no_rekam_medis" class="field-label">No. RM</label>
                    <input id="no_rekam_medis" name="no_rekam_medis" value="{{ old('no_rekam_medis', $pasien->no_rekam_medis) }}" required class="field font-mono" @disabled($isFinalized)>
                </div>
                <div>
                    <label for="tanggal_lahir" class="field-label">Tanggal lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->format('Y-m-d') : '') }}" class="field" @disabled($isFinalized)>
                </div>
                <div>
                    <label for="jenis_kelamin" class="field-label">Jenis kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="field" @disabled($isFinalized)>
                        <option value="">Belum diisi</option>
                        <option value="Laki-laki" @selected(old('jenis_kelamin', $pasien->jenis_kelamin) === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('jenis_kelamin', $pasien->jenis_kelamin) === 'Perempuan')>Perempuan</option>
                    </select>
                </div>
            </div>
        </details>

        @unless ($isFinalized)
            <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-2">
                @if ($pakaiOpenRouter)
                    <button type="button" id="regenerate-button" class="btn btn-soft sm:mr-auto">
                        <x-icon name="refresh-cw" class="w-4 h-4" aria-hidden="true" data-icon />
                        <span class="spinner hidden" aria-hidden="true" data-spinner></span>
                        <span data-label>Buat Ulang Draf AI</span>
                    </button>
                @else
                    <span class="sm:mr-auto"></span>
                @endif
                <button type="submit" class="btn btn-secondary" data-loading-text="Menyimpan…">
                    <span class="spinner hidden" aria-hidden="true" data-spinner></span>
                    <span data-label>Simpan Koreksi</span>
                </button>
                <button type="button" id="verify-button" class="btn btn-primary">
                    <x-icon name="check" class="w-4 h-4" aria-hidden="true" />
                    <span>{{ $terverifikasi ? 'Verifikasi Ulang' : 'Verifikasi SOAP' }}</span>
                </button>
            </div>

            @if ($pakaiOpenRouter)
                <p class="text-xs text-text-secondary sm:text-right">Draf SOAP disusun AI dari transkrip dan TTV. Audio pasien tidak dikirim ke pihak ketiga.</p>
            @endif
        @endunless
    </form>

    @if ($pakaiOpenRouter && ! $isFinalized)
        <form id="soap-generate-form" method="POST" action="{{ route('dokter.soap.generate', $kunjungan) }}" hidden>
            @csrf
        </form>

        <x-dialog id="modalRegenerateSoap" title="Buat ulang draf SOAP?"
                  description="Isi draf saat ini akan diganti dengan draf baru dari transkrip dan TTV. Koreksi yang belum disimpan akan hilang."
                  icon="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
            <x-slot:actions>
                <button type="button" class="btn btn-secondary" data-dialog-close data-dialog-focus>Batal</button>
                <button type="button" id="btnKonfirmasiRegenerateSoap" onclick="konfirmasiRegenerateSoap()" class="btn btn-primary">Buat ulang</button>
            </x-slot:actions>
        </x-dialog>
    @endif

    @unless ($isFinalized)
        <x-dialog id="modalVerifySoap" tone="ok" title="Verifikasi draf SOAP?"
                  description="Draf yang sedang tampil akan disimpan dan diverifikasi. Setelah itu tindak lanjut dan finalisasi bisa dibuka.">
            <x-slot:actions>
                <button type="button" class="btn btn-secondary" data-dialog-close data-dialog-focus>Batal</button>
                <button type="button" id="btnKonfirmasiVerifySoap" onclick="konfirmasiVerifySoap()" class="btn btn-primary">Verifikasi</button>
            </x-slot:actions>
        </x-dialog>
    @endunless

    <script>
    const bukaModalRegenerateSoap = () => window.openDialog?.('modalRegenerateSoap');
    const tutupModalRegenerateSoap = () => window.closeDialog?.('modalRegenerateSoap');
    const bukaModalVerifySoap = () => window.openDialog?.('modalVerifySoap');
    const tutupModalVerifySoap = () => window.closeDialog?.('modalVerifySoap');

    function konfirmasiRegenerateSoap() {
        tutupModalRegenerateSoap();
        const button = document.getElementById('regenerate-button');
        if (!button) return;
        button.dataset.busy = 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.querySelector('[data-icon]')?.classList.add('hidden');
        button.querySelector('[data-spinner]')?.classList.remove('hidden');
        const label = button.querySelector('[data-label]');
        if (label) label.textContent = 'Menyusun ulang draf…';
        document.getElementById('soap-generate-form')?.submit();
    }

    function konfirmasiVerifySoap() {
        const form = document.getElementById('soap-form');
        if (!form) return;
        const tombol = document.getElementById('btnKonfirmasiVerifySoap');
        if (tombol) {
            tombol.disabled = true;
            tombol.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Memverifikasi…</span>';
        }
        form.action = @json(route('dokter.soap.verify', $kunjungan));
        form.querySelector('input[name="_method"]')?.remove();
        form.submit();
    }

    document.getElementById('regenerate-button')?.addEventListener('click', function (event) {
        event.preventDefault();
        const button = document.getElementById('regenerate-button');
        if (button.dataset.busy === 'true') return;
        bukaModalRegenerateSoap();
    });

    document.getElementById('verify-button')?.addEventListener('click', function () {
        const form = document.getElementById('soap-form');
        if (!form || !form.reportValidity()) return;
        bukaModalVerifySoap();
    });

    // Pencarian kode ICD-10, pilihan saran AI, dan label deskripsi langsung.
    (function () {
        const input = document.getElementById('kode_icd10');
        const panel = document.getElementById('icd10-hasil');
        const label = document.getElementById('icd10-label');
        const badge = document.getElementById('icd10-sumber');
        if (!input || !panel) return;

        const endpoint = @json(route('dokter.icd10.index'));
        const labels = @json(collect($saranIcd10 ?? [])->mapWithKeys(fn ($saran) => [$saran['code'] => $saran['description']])->all());
        const kodeAiUtama = @json($saranIcd10[0]['code'] ?? null);

        let timer = null;
        let controller = null;

        function tutupPanel() {
            panel.classList.add('hidden');
            panel.replaceChildren();
            input.setAttribute('aria-expanded', 'false');
        }

        function isiLabel(teks) {
            if (label) label.textContent = teks || '';
        }

        function perbaruiBadge() {
            if (!badge) return;
            const kode = input.value.trim().toUpperCase();
            const dariAi = kode !== '' && kode === kodeAiUtama;
            badge.dataset.sumberIcd10 = dariAi ? 'ai' : 'manual';
            badge.classList.toggle('status-warn', dariAi);
            badge.classList.toggle('status-ok', !dariAi);
            const teks = badge.querySelector('[data-teks-sumber]');
            if (teks) {
                teks.textContent = dariAi ? 'Diisi AI, perlu dikonfirmasi' : 'Diisi dokter';
            }
        }

        function pilihKode(kode, deskripsi) {
            input.value = kode;
            isiLabel(deskripsi || labels[kode] || '');
            perbaruiBadge();
            tutupPanel();
        }

        function renderHasil(data) {
            panel.replaceChildren();

            if (! data.length) {
                const kosong = document.createElement('p');
                kosong.className = 'px-3 py-2 text-xs text-text-secondary';
                kosong.textContent = 'Kode tidak ada di katalog ICD-10. Periksa penulisannya, misalnya J02.9.';
                panel.appendChild(kosong);
            } else {
                data.forEach((item) => {
                    const tombol = document.createElement('button');
                    tombol.type = 'button';
                    tombol.className = 'w-full min-h-11 text-left px-3 py-2 hover:bg-soft-offwhite focus-visible:bg-soft-offwhite flex flex-col gap-0.5';

                    const kode = document.createElement('span');
                    kode.className = 'font-mono text-xs font-semibold text-medical-navy';
                    kode.textContent = item.kode_icd10;

                    const judul = document.createElement('span');
                    judul.className = 'text-xs text-text-secondary';
                    judul.textContent = item.deskripsi;

                    tombol.appendChild(kode);
                    tombol.appendChild(judul);
                    tombol.addEventListener('click', () => pilihKode(item.kode_icd10, item.deskripsi));
                    panel.appendChild(tombol);
                });
            }

            panel.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        }

        input.addEventListener('input', function () {
            const kode = input.value.trim().toUpperCase();
            isiLabel(labels[kode] || '');
            perbaruiBadge();
            clearTimeout(timer);

            if (kode.length < 2) {
                tutupPanel();
                return;
            }

            timer = setTimeout(async function () {
                if (controller) controller.abort();
                controller = new AbortController();

                try {
                    const res = await fetch(endpoint + '?q=' + encodeURIComponent(kode) + '&limit=8', {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    });
                    if (! res.ok) return;
                    const json = await res.json();
                    renderHasil(json.data || []);
                } catch (e) {
                    // Permintaan dibatalkan atau jaringan gagal: abaikan.
                }
            }, 250);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') tutupPanel();
        });

        document.querySelectorAll('[data-kode-icd10]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                pilihKode(chip.dataset.kodeIcd10, chip.dataset.labelIcd10);
            });
        });

        document.addEventListener('click', function (event) {
            if (! event.target.closest('#icd10-pencarian')) tutupPanel();
        });
    })();

    // Polling jika status draf SOAP masih 'processing'
    @if ($isProcessing)
    (function pollSoapStatus() {
        const pollInterval = setInterval(async () => {
            try {
                const res = await fetch(@json(route('dokter.soap.status', $kunjungan)), {
                    headers: { Accept: 'application/json' }
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.status && data.status !== 'processing') {
                    clearInterval(pollInterval);
                    window.location.hash = 'soap';
                    window.location.reload();
                }
            } catch (e) {
                console.warn('Gagal memeriksa status SOAP:', e);
            }
        }, 2500);
    })();
    @endif
    </script>
</div>
@endif

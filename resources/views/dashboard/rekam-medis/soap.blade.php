@php
    $isFinalized = $kunjungan->isFinalized();
    [$statusSoap, $kelasStatusSoap] = match (true) {
        $isFinalized => ['Final (Terkunci)', 'status-ok'],
        $tahapSoap === 'locked' => ['Terkunci', 'status-muted'],
        $terverifikasi => ['Terverifikasi', 'status-ok'],
        default => ['Belum diverifikasi', 'status-warn'],
    };
    $pakaiOpenRouter = config('medscribe.soap_driver') === 'openrouter';
    $bagianSoap = [
        ['subjective_text', 'S', 'Subjective', 'Keluhan dan riwayat dari pasien'],
        ['objective_text', 'O', 'Objective', 'Hasil pemeriksaan dan TTV'],
        ['assessment_text', 'A', 'Assessment', 'Penilaian awal klinis, belum diagnosis final'],
        ['plan_text', 'P', 'Plan', 'Rencana terapi, obat, dan pemeriksaan penunjang'],
    ];
@endphp

@include('dashboard.rekam-medis.kepala-tahap', [
    'nomor' => 2,
    'judul' => 'Review dan Edit Draf SOAP',
    'keterangan' => $tahapSoap === 'locked'
        ? 'Terbuka setelah transkrip wawancara ditinjau dan diverifikasi.'
        : ($isFinalized ? 'Draf SOAP telah difinalisasi secara permanen.' : 'Draf disusun AI dari transkrip dan TTV. Baca dan koreksi sebelum diverifikasi.'),
    'keadaan' => $tahapSoap,
    'statusLabel' => $statusSoap,
    'statusClass' => $kelasStatusSoap,
    'idJudul' => 'judulSoap',
])

@if ($soap)
@php($draf = $soap['draf'])
@php($isProcessing = ($draf->generation_status ?? '') === 'processing')
<div class="border-t border-border-subtle p-5 sm:p-6 space-y-5">

    @if ($isFinalized)
        <div class="flex items-center gap-3 p-4 rounded-[18px] bg-emerald-50/70 border border-emerald-200 text-sm text-emerald-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            <div class="flex-1">
                <p class="font-semibold">Draf SOAP Telah Terfinalisasi</p>
                <p class="text-xs text-emerald-700 mt-0.5">Draf SOAP ini telah disetujui dan dikunci permanen. Pengubahan isi data medis tidak lagi diperkenankan.</p>
            </div>
        </div>
    @endif

    @if ($isProcessing)
        <div id="soap-processing-banner" class="flex flex-col items-center text-center gap-3 py-6 rounded-[20px] bg-soft-offwhite border border-border-subtle p-6">
            <span class="w-10 h-10 rounded-full border-4 border-pale-lavender border-t-royal-indigo animate-spin" aria-hidden="true"></span>
            <div>
                <h3 class="text-sm font-semibold text-medical-navy">AI Sedang Menyusun Draf SOAP</h3>
                <p class="text-xs text-text-secondary mt-1 max-w-md">Menganalisis transkripsi wawancara klinis dan data TTV nurse station via OpenRouter... Halaman akan diperbarui otomatis saat selesai.</p>
            </div>
        </div>
    @endif

    @if ($soap['generationError'] && ! $isProcessing)
        <div class="alert alert-danger">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
            <span class="flex-1">{{ $soap['generationError'] }}</span>
        </div>
    @endif

    @if ($draf->needs_review && ! $soap['generationError'] && ! $isFinalized && ! $isProcessing)
        <div class="alert alert-warn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="flex-1">Transkrip atau TTV berubah setelah draf ini dibuat. Isi draf tidak otomatis berubah, tinjau ulang sebelum memverifikasi.</span>
        </div>
    @endif

    @if ($draf->missing_information && ! $isFinalized && ! $isProcessing)
        <div class="alert alert-warn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div class="flex-1">
                <p class="font-semibold">Informasi yang belum lengkap pada wawancara</p>
                <ul class="mt-1 list-disc list-inside">
                    @foreach ($draf->missing_information as $item)
                        <li>{{ is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
            <ul class="flex-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form id="soap-form" method="POST" action="{{ route('dokter.soap.save', $kunjungan) }}" class="space-y-5">
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
                              class="field leading-relaxed @error($field) is-invalid @enderror {{ $isFinalized ? 'bg-soft-offwhite cursor-not-allowed opacity-90' : '' }}">{{ old($field, $draf->$field) }}</textarea>
                    @error($field)<p class="field-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>

        <div>
            <label for="kode_icd10" class="field-label">Kode ICD-10 (Diagnosis Utama)</label>
            <select id="kode_icd10" name="kode_icd10" class="field {{ $isFinalized ? 'bg-soft-offwhite cursor-not-allowed' : '' }}" @disabled($isFinalized)>
                <option value="">Belum dipilih</option>
                @foreach (\App\Services\SoapDraftPreparer::ICD10_OPTIONS as $code => $description)
                    <option value="{{ $code }}" @selected(old('kode_icd10', $draf->kode_icd10) === $code)>{{ $code }} · {{ $description }}</option>
                @endforeach
            </select>
        </div>

        {{-- Data pasien dilipat --}}
        <details class="group rounded-[18px] border border-border-subtle" @if ($errors->hasAny(['nama_pasien', 'no_rekam_medis', 'tanggal_lahir', 'jenis_kelamin'])) open @endif>
            <summary class="flex items-center justify-between gap-3 px-4 min-h-12 text-sm font-medium text-medical-navy list-none cursor-pointer">
                <span>Koreksi data identitas pasien</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-text-secondary transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </summary>
            <div class="border-t border-border-subtle p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nama_pasien" class="field-label">Nama pasien</label>
                    <input id="nama_pasien" name="nama_pasien" value="{{ old('nama_pasien', $pasien->nama_pasien) }}" required class="field" @disabled($isFinalized)>
                </div>
                <div>
                    <label for="no_rekam_medis" class="field-label">Nomor rekam medis</label>
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
                    <button type="submit" form="soap-generate-form" id="regenerate-button" class="btn btn-ai sm:mr-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ai-spark w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-icon><path d="M12 2.5l1.9 5.6 5.6 1.9-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.9L12 2.5z" /><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z" opacity=".6" /></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="hidden w-4 h-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24" aria-hidden="true" data-spinner><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".25" /><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" /></svg>
                        <span data-label>Buat Ulang Draf AI</span>
                    </button>
                @else
                    <span class="sm:mr-auto"></span>
                @endif
                <button type="submit" class="btn btn-secondary min-h-11">Simpan Koreksi</button>
                <button type="button" id="verify-button" class="btn btn-primary min-h-11 px-6 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span>{{ $terverifikasi ? 'Verifikasi Ulang' : 'Verifikasi SOAP' }}</span>
                </button>
            </div>

            @if ($pakaiOpenRouter)
                <p class="text-[11px] text-text-secondary sm:text-right">Draf SOAP digenerate secara otomatis menggunakan model AI terkonfigurasi. Audio pasien tidak pernah diunggah ke pihak ketiga.</p>
            @endif
        @endunless
    </form>

    @if ($pakaiOpenRouter && ! $isFinalized)
        <form id="soap-generate-form" method="POST" action="{{ route('dokter.soap.generate', $kunjungan) }}" hidden>
            @csrf
        </form>
    @endif
    <script>
    document.getElementById('soap-generate-form')?.addEventListener('submit', function (event) {
        const button = document.getElementById('regenerate-button');
        if (button.dataset.busy === 'true' || !confirm('Buat ulang draf SOAP dengan AI? Seluruh isi draf saat ini akan diperbarui dari transkrip dan TTV.')) {
            event.preventDefault();
            return;
        }
        button.dataset.busy = 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.querySelector('[data-icon]').classList.add('hidden');
        button.querySelector('[data-spinner]').classList.remove('hidden');
        button.querySelector('[data-label]').textContent = 'Menyusun ulang draf...';
    });

    document.getElementById('verify-button')?.addEventListener('click', function () {
        const form = document.getElementById('soap-form');
        if (!form.reportValidity()) return;
        if (!confirm('Verifikasi draf SOAP dengan data yang sedang ditampilkan? Anda akan dapat melanjutkan ke tindak lanjut dan finalisasi.')) return;
        form.action = @json(route('dokter.soap.verify', $kunjungan));
        form.querySelector('input[name="_method"]')?.remove();
        form.submit();
    });

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

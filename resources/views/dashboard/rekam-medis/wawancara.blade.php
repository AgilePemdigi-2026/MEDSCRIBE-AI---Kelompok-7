@php
    $mode = $workflowState;
    $segments = $wawancara?->transcript_segments ?? [];
    $peranSpeaker = ['doctor' => 'Dokter', 'patient' => 'Pasien'];
    $isFinalized = $kunjungan->isFinalized();

    [$keteranganWawancara, $statusWawancara, $kelasStatusWawancara] = match (true) {
        $isFinalized => ['Rekam medis telah difinalisasi. Data wawancara klinis terkunci.', 'Final (Terkunci)', 'status-ok'],
        $mode === 'ttv' => ['Terbuka setelah perawat mengisi TTV.', 'Menunggu TTV', 'status-muted'],
        $mode === 'interview' => ['Mulai wawancara dan rekam percakapan klinis dengan pasien.', 'Belum dimulai', 'status-active'],
        $mode === 'recording' => ['Rekam percakapan klinis dengan transkripsi langsung (real-time).', 'Sesi berlangsung', 'status-active'],
        $mode === 'transcript' => ['Periksa teks dan peran pembicara sebelum verifikasi dan draf SOAP.', 'Perlu ditinjau', 'status-warn'],
        default => ['Transkrip sudah ditinjau dan diverifikasi untuk draf SOAP.', 'Selesai', 'status-ok'],
    };
@endphp

@include('dashboard.rekam-medis.kepala-tahap', [
    'nomor' => 1,
    'judul' => 'Rekam Wawancara Klinis',
    'keterangan' => $keteranganWawancara,
    'keadaan' => $tahapWawancara,
    'statusLabel' => $statusWawancara,
    'statusClass' => $kelasStatusWawancara,
    'idJudul' => 'judulWawancara',
])

@if ($mode !== 'ttv')
<div class="border-t border-border-subtle p-5 sm:p-6 space-y-5">

    @if ($isFinalized)
        <div class="flex items-center gap-3 p-4 rounded-[18px] bg-emerald-50/70 border border-emerald-200 text-sm text-emerald-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            <div class="flex-1">
                <p class="font-semibold">Rekam Medis Telah Difinalisasi</p>
                <p class="text-xs text-emerald-700 mt-0.5">Seluruh rekaman audio dan transkrip wawancara telah dikunci permanen untuk menjaga integritas data klinis.</p>
            </div>
        </div>
    @endif

    {{-- Mode 1: Interview Belum Dimulai --}}
    @if ($mode === 'interview' && ! $isFinalized)
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
            <p class="text-sm text-text-body max-w-md">Mikrofon dan transkripsi langsung (real-time) akan aktif setelah Anda menekan tombol Mulai Wawancara.</p>
            <form method="POST" action="{{ route('dokter.interview.start', $kunjungan) }}" data-submit-loading>
                @csrf
                <button type="submit" class="btn btn-primary min-h-12 px-6" data-loading-text="Memulai...">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                    <span data-label>Mulai Wawancara</span>
                </button>
            </form>
        </div>

    {{-- Mode 2: Sesi Perekaman & Transkripsi Real-Time --}}
    @elseif ($mode === 'recording' && ! $isFinalized)
        @php($batasMenit = max(1, intdiv((int) config('medscribe.audio.max_duration_seconds'), 60)))
        <div id="recording-console" class="rounded-[22px] bg-soft-offwhite border border-border-subtle p-5 sm:p-6 space-y-5">
            <div class="flex items-center justify-between gap-3">
                <span id="recording-state" class="status status-active">Siap merekam</span>
                <span class="text-[11px] text-text-secondary">Maks. {{ $batasMenit }} menit</span>
            </div>

            <p id="recording-duration" class="text-center text-[3.25rem] leading-none font-semibold tracking-[-0.05em] text-medical-navy num" aria-label="Durasi rekaman">00:00</p>

            {{-- Visualisasi gelombang suara langsung --}}
            <div class="relative h-20 text-royal-indigo">
                <div id="recording-wave-idle" class="absolute inset-x-0 top-1/2 -translate-y-1/2 border-t-2 border-dotted border-border-hover" aria-hidden="true"></div>
                <canvas id="recording-wave" class="block w-full h-full" aria-hidden="true"></canvas>
            </div>

            {{-- Wadah Transkripsi Langsung (Real-Time Live STT) --}}
            <div id="live-stt-wrapper" class="rounded-[18px] bg-white border border-border-subtle p-4 shadow-sm space-y-3">
                <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-border-subtle text-xs">
                    <span class="font-semibold text-medical-navy flex items-center gap-2">
                        <span id="live-indicator-dot" class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-dot opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-danger-dot"></span>
                        </span>
                        Transkripsi Langsung (Real-Time)
                    </span>
                    <span id="stt-engine-badge" class="tag-id text-[10px] bg-pale-lavender text-royal-indigo font-medium">Web Speech API (id-ID)</span>
                </div>
                <div id="live-stt-body" class="max-h-48 overflow-y-auto space-y-2 text-sm leading-relaxed text-text-body font-normal">
                    <p id="live-stt-placeholder" class="text-xs text-text-secondary italic">Menunggu rekaman dimulai... Tekan Mulai Rekaman untuk memulai wawancara.</p>
                    <div id="live-stt-content" class="space-y-1.5">
                        <span id="live-stt-final" class="text-medical-navy font-normal"></span>
                        <span id="live-stt-interim" class="text-royal-indigo italic opacity-75"></span>
                    </div>
                </div>
            </div>

            {{-- Browser capability notice jika Web Speech API tidak didukung --}}
            <div id="stt-fallback-notice" class="hidden alert alert-warn text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Browser Anda tidak mendukung streaming Web Speech API secara native. Silakan gunakan browser Google Chrome atau Microsoft Edge untuk transkripsi klinis real-time bahasa Indonesia.</span>
            </div>

            {{-- Kontrol Aksi Rekaman & Transkripsi (State Machine Lifecycle) --}}
            <div class="flex flex-wrap items-center justify-center gap-3">
                {{-- State: IDLE --}}
                <button type="button" id="record-button" class="btn btn-primary min-h-12 px-6 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                    <span>Mulai Rekaman</span>
                </button>

                {{-- State: RECORDING --}}
                <button type="button" id="stop-button" hidden class="btn btn-danger min-h-12 px-6 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="5" y="5" width="10" height="10" rx="2" /></svg>
                    <span>Hentikan</span>
                </button>

                {{-- State: PAUSED (Tiga Aksi: Lanjutkan, Ulangi, Proses) --}}
                <div id="paused-actions" hidden class="flex flex-wrap items-center justify-center gap-3">
                    {{-- 1. Lanjutkan --}}
                    <button type="button" id="resume-button" class="btn btn-secondary min-h-12 px-5 font-semibold text-royal-indigo border-royal-indigo/30 hover:bg-pale-lavender/50 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5 text-royal-indigo" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                        </svg>
                        <span>Lanjutkan</span>
                    </button>

                    {{-- 2. Ulangi Rekaman --}}
                    <button type="button" id="retry-button" class="btn btn-secondary min-h-12 px-5 font-semibold text-danger-text border-danger-line/40 hover:bg-danger-bg/50 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Ulangi Rekaman</span>
                    </button>

                    {{-- 3. Proses Rekaman --}}
                    <button type="button" id="process-button" class="btn btn-primary min-h-12 px-7 font-semibold shadow-md shadow-royal-indigo/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Proses Rekaman</span>
                    </button>
                </div>

                {{-- State: PROCESSING Indicator --}}
                <div id="processing-indicator" hidden class="flex items-center gap-3 py-2 px-5 rounded-[14px] bg-pale-lavender text-royal-indigo text-xs font-medium shadow-sm">
                    <svg class="animate-spin h-4 w-4 text-royal-indigo" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="processing-label">Memproses rekaman & draf SOAP...</span>
                </div>
            </div>

            <p id="recording-message" class="text-xs text-text-secondary text-center min-h-4 transition-colors" role="status"></p>
        </div>

        {{-- Modal Konfirmasi Ulangi Rekaman --}}
        <div id="modalUlangiRekaman" class="dialog-backdrop hidden" style="z-index: 9999;" role="dialog" aria-modal="true" aria-labelledby="modalUlangiJudul">
            <div class="dialog max-w-md p-6 bg-white rounded-[22px] shadow-2xl border border-border-subtle space-y-4">
                <div class="flex items-start gap-3.5">
                    <span class="w-10 h-10 rounded-xl bg-danger-bg text-danger-text flex items-center justify-center flex-shrink-0" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </span>
                    <div>
                        <h3 id="modalUlangiJudul" class="text-base font-semibold text-medical-navy">Ulangi Rekaman?</h3>
                        <p class="text-xs text-text-secondary mt-1.5 leading-relaxed">
                            Apakah Anda yakin ingin mengulang rekaman dari awal? Seluruh transkrip dan durasi rekaman saat ini akan dihapus.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button type="button" id="btn-batal-ulangi" class="btn btn-secondary min-h-11">
                        Batal
                    </button>
                    <button type="button" id="btn-konfirmasi-ulangi" class="btn btn-danger min-h-11">
                        Ya, Ulangi Rekaman
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Audio player bila rekaman tersimpan --}}
    @if ($rekamanAudio && ! in_array($mode, ['interview'], true))
        <div class="rounded-[18px] bg-soft-offwhite border border-border-subtle p-3">
            <p class="px-1 pb-2 text-[11px] font-medium text-text-secondary">Rekaman audio wawancara klinis</p>
            <audio controls preload="none" class="w-full h-10" src="{{ route('dokter.rekam-medis.stream-audio', $antrean->id_antrean) }}">Browser tidak mendukung pemutar audio.</audio>
        </div>
    @endif

    {{-- Mode 3: Review Transkrip Wawancara (Step 1 Review) --}}
    @if ($mode === 'transcript' && ! $isFinalized)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-medical-navy">Review Transkrip Wawancara</h3>
                <p class="mt-1 text-xs text-text-secondary">Periksa teks dan peran pembicara (Dokter / Pasien) sebelum memverifikasi dan menyusun draf SOAP.</p>
            </div>
            <span class="tag-id bg-pale-lavender text-royal-indigo font-medium text-xs">Perlu Verifikasi</span>
        </div>

        @if ($segments)
            <div id="transcript-editor" class="space-y-3"></div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3 pt-2">
                <p id="transcript-message" class="text-xs text-text-secondary sm:mr-auto" role="status"></p>
                <button type="button" id="save-transcript" class="btn btn-secondary min-h-11">
                    Simpan Koreksi
                </button>
                <button type="button" id="verify-transcript-button" class="btn btn-primary min-h-11 px-6 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <span>Verifikasi Transkripsi</span>
                </button>
            </div>
        @else
            <div class="space-y-3">
                <label for="manual-transcript-input" class="field-label">Transkrip Wawancara Klinis</label>
                <textarea id="manual-transcript-input" rows="6" class="field leading-relaxed" placeholder="Ketik atau tempel transkrip percakapan dokter dan pasien di sini...">{{ old('transkrip', $wawancara?->transkrip) }}</textarea>
                <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3 pt-2">
                    <p id="transcript-message" class="text-xs text-text-secondary sm:mr-auto" role="status"></p>
                    <button type="button" id="verify-manual-transcript-button" class="btn btn-primary min-h-11 px-6 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        <span>Verifikasi Transkripsi</span>
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- Transkrip yang sudah diverifikasi (SOAP ready / SOAP / Selesai / Final) --}}
    @if (in_array($mode, ['soap-ready', 'soap', 'completed'], true) || $isFinalized)
        <details class="group rounded-[18px] border border-border-subtle" @if ($isFinalized) open @endif>
            <summary class="flex items-center justify-between gap-3 px-4 min-h-12 text-sm font-medium text-medical-navy list-none cursor-pointer">
                <span class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Transkrip yang telah diverifikasi
                </span>
                <span class="flex items-center gap-2 text-xs font-normal text-text-secondary">
                    {{ count($segments) }} bagian
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </span>
            </summary>
            <ol class="border-t border-border-subtle divide-y divide-border-subtle">
                @forelse ($segments as $segment)
                    <li class="px-4 py-3 text-sm">
                        <span class="block text-[11px] font-semibold {{ ($segment['speakerRole'] ?? '') === 'doctor' ? 'text-royal-indigo' : 'text-text-secondary' }}">
                            {{ $peranSpeaker[$segment['speakerRole'] ?? ''] ?? 'Pembicara' }}
                        </span>
                        <span class="text-text-body leading-relaxed">{{ $segment['text'] ?? '' }}</span>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-text-body whitespace-pre-line">{{ $wawancara?->transkrip }}</li>
                @endforelse
            </ol>
        </details>

        @if ($mode === 'soap-ready' && ! $isFinalized)
            <form method="POST" action="{{ route('dokter.interview.finish', $kunjungan) }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3" data-submit-loading>
                @csrf
                <p class="text-xs text-text-secondary">Tutup sesi bila wawancara tidak akan direkam ulang.</p>
                <button type="submit" class="btn btn-secondary btn-sm" data-loading-text="Menutup...">
                    <span data-label>Tutup Sesi Wawancara</span>
                </button>
            </form>
        @endif
    @endif
</div>

{{-- Script Inline Mandiri untuk Wawancara & Real-Time STT --}}
@if (in_array($mode, ['recording', 'transcript'], true) && ! $isFinalized)
<script>
(function () {
    const audioConfig = {
        upload: @json(route('dokter.interview.audio.upload', $kunjungan)),
        process: @json(route('dokter.interview.process', $kunjungan)),
        transcript: @json(route('dokter.interview.transcript.update', $kunjungan)),
        verify: @json(route('dokter.interview.transcript.verify', $kunjungan)),
        maxSeconds: @json(config('medscribe.audio.max_duration_seconds', 3600)),
        maxBytes: @json(config('medscribe.audio.max_size_kb', 102400) * 1024),
    };

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    let recorder = null, timer = null;
    let segments = @json($segments), speakerMap = @json($wawancara?->speaker_map ?? []);
    let mediaStream = null;

    // Persistent State Machine Variables (Phases 2-7)
    // Lifecycle states: 'idle' | 'recording' | 'paused' | 'processing' | 'completed' | 'error'
    let recordingLifecycle = 'idle';
    let accumulatedSeconds = 0;
    let sessionStartTime = 0;
    let isExplicitlyPaused = false;
    let allAudioChunks = [];

    // Web Speech API Variables (Phase 1 Real-Time Native)
    const SpeechRecognitionClass = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let finalTranscript = '';
    let interimTranscript = '';
    let liveSegments = [];

    const byId = (id) => document.getElementById(id);
    const escapeHtml = (value) => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    const message = (text, error = false) => {
        const el = byId('recording-message') || byId('transcript-message');
        if (el) {
            el.textContent = text;
            el.classList.toggle('text-danger-text', error);
            el.classList.toggle('text-text-secondary', !error);
        }
    };

    const RECORDING_STATES = {
        ready: ['status-active', 'Siap merekam'],
        live: ['status-danger is-live', 'Merekam'],
        speaking: ['status-danger is-live', 'Mendengarkan ucapan...'],
        paused: ['status-warn', 'Rekaman Dijeda'],
        processing: ['status-warn', 'Memproses Rekaman...'],
        completed: ['status-ok', 'Selesai'],
        error: ['status-danger', 'Gagal'],
    };

    const setRecordingState = (key) => {
        const el = byId('recording-state');
        if (!el || !RECORDING_STATES[key]) return;
        const [cls, text] = RECORDING_STATES[key];
        el.className = 'status ' + cls;
        el.textContent = text;
    };

    const show = (id, visible) => {
        const el = byId(id);
        if (el) el.hidden = !visible;
    };

    const formatDuration = (seconds) => `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;

    // Sinkronisasi tombol kontrol UI berdasarkan lifecycle state machine
    function updateControlButtons() {
        const recordBtn = byId('record-button');
        const stopBtn = byId('stop-button');
        const pausedActions = byId('paused-actions');
        const processingIndicator = byId('processing-indicator');
        const sttBadge = byId('stt-engine-badge');

        if (recordingLifecycle === 'idle') {
            show('record-button', true);
            show('stop-button', false);
            show('paused-actions', false);
            show('processing-indicator', false);
            if (recordBtn) recordBtn.disabled = false;
            if (sttBadge) sttBadge.textContent = 'Web Speech API (id-ID)';
        } else if (recordingLifecycle === 'recording') {
            show('record-button', false);
            show('stop-button', true);
            show('paused-actions', false);
            show('processing-indicator', false);
            if (stopBtn) stopBtn.disabled = false;
            if (sttBadge) sttBadge.textContent = 'Mendengarkan Langsung';
        } else if (recordingLifecycle === 'paused') {
            show('record-button', false);
            show('stop-button', false);
            show('paused-actions', true);
            show('processing-indicator', false);
            if (sttBadge) sttBadge.textContent = 'Transkrip Tersimpan (Dijeda)';
        } else if (recordingLifecycle === 'processing') {
            show('record-button', false);
            show('stop-button', false);
            show('paused-actions', false);
            show('processing-indicator', true);
            if (sttBadge) sttBadge.textContent = 'Memproses dengan OpenRouter...';
        } else if (recordingLifecycle === 'error') {
            show('record-button', false);
            show('stop-button', false);
            show('paused-actions', true);
            show('processing-indicator', false);
            if (sttBadge) sttBadge.textContent = 'Siap Diproses Ulang';
        }
    }

    // Timer Akumulatif Akurat Lintas Sesi Pause & Resume (Phase 6)
    function updateTimerDisplay() {
        if (sessionStartTime <= 0) return;
        const currentSessionElapsed = (Date.now() - sessionStartTime) / 1000;
        const totalSeconds = accumulatedSeconds + currentSessionElapsed;
        const totalSecondsInt = Math.floor(totalSeconds);
        byId('recording-duration').textContent = formatDuration(totalSecondsInt);

        if (totalSecondsInt >= audioConfig.maxSeconds) {
            pauseRecordingSession();
            message('Batas durasi maksimum tercapai (' + formatDuration(audioConfig.maxSeconds) + '). Rekaman dijeda otomatis.', true);
        }
    }

    // Waveform mikrofon langsung
    const wave = { canvas: byId('recording-wave'), ctx: null, audioCtx: null, analyser: null, bars: [], raf: 0, live: false, width: 0, height: 0 };
    function sizeWave() {
        if (!wave.canvas) return;
        const rect = wave.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        wave.canvas.width = rect.width * dpr;
        wave.canvas.height = rect.height * dpr;
        wave.width = rect.width;
        wave.height = rect.height;
        wave.ctx = wave.canvas.getContext('2d');
        wave.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    function readBars() {
        const data = new Uint8Array(wave.analyser.frequencyBinCount);
        wave.analyser.getByteFrequencyData(data);
        const relevant = data.slice(Math.floor(data.length * 0.05), Math.floor(data.length * 0.4));
        const half = Math.floor(wave.width / 5 / 2);
        const side = Array.from({ length: half }, (_, i) => Math.max(0.06, Math.min(1, relevant[Math.floor(i / half * relevant.length)] / 255 * 1.4)));
        return [...side.slice().reverse(), ...side];
    }
    function drawWave() {
        const { ctx } = wave;
        if (!ctx) return;
        if (wave.live && wave.analyser) wave.bars = readBars();
        else wave.bars = wave.bars.map(v => v * 0.88).filter(v => v > 0.02);
        ctx.clearRect(0, 0, wave.width, wave.height);
        ctx.fillStyle = getComputedStyle(wave.canvas).color;
        wave.bars.forEach((value, i) => {
            const h = Math.max(4, value * wave.height * 0.85);
            ctx.globalAlpha = 0.35 + value * 0.65;
            if (ctx.roundRect) { ctx.beginPath(); ctx.roundRect(i * 5, (wave.height - h) / 2, 3, h, 1.5); ctx.fill(); }
            else ctx.fillRect(i * 5, (wave.height - h) / 2, 3, h);
        });
        ctx.globalAlpha = 1;
        const fade = ctx.createLinearGradient(0, 0, wave.width, 0);
        fade.addColorStop(0, 'rgba(255,255,255,1)'); fade.addColorStop(0.12, 'rgba(255,255,255,0)');
        fade.addColorStop(0.88, 'rgba(255,255,255,0)'); fade.addColorStop(1, 'rgba(255,255,255,1)');
        ctx.globalCompositeOperation = 'destination-out'; ctx.fillStyle = fade; ctx.fillRect(0, 0, wave.width, wave.height); ctx.globalCompositeOperation = 'source-over';
        const idle = byId('recording-wave-idle');
        if (idle) idle.hidden = wave.live || wave.bars.length > 0;
        wave.raf = (wave.live || wave.bars.length) ? requestAnimationFrame(drawWave) : 0;
    }
    function startWave(stream) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!wave.canvas || !AudioContextClass) return;
        try {
            sizeWave();
            wave.audioCtx = new AudioContextClass();
            wave.analyser = wave.audioCtx.createAnalyser();
            wave.analyser.fftSize = 256;
            wave.analyser.smoothingTimeConstant = 0.8;
            wave.audioCtx.createMediaStreamSource(stream).connect(wave.analyser);
            wave.live = true;
            if (!wave.raf) drawWave();
        } catch (error) { wave.live = false; }
    }
    function stopWave() {
        wave.live = false;
        wave.analyser = null;
        if (wave.audioCtx && wave.audioCtx.state !== 'closed') wave.audioCtx.close();
        wave.audioCtx = null;
    }
    window.addEventListener('resize', () => { if (wave.ctx) sizeWave(); });

    // Render Teks Transkripsi Langsung (Real-Time)
    function updateLiveTranscriptDisplay() {
        const placeholder = byId('live-stt-placeholder');
        const finalSpan = byId('live-stt-final');
        const interimSpan = byId('live-stt-interim');
        const scrollBox = byId('live-stt-body');

        const hasFinal = finalTranscript.trim().length > 0;
        const hasInterim = interimTranscript.trim().length > 0;

        if (placeholder) {
            placeholder.classList.toggle('hidden', hasFinal || hasInterim);
        }

        if (finalSpan) {
            finalSpan.textContent = finalTranscript;
        }

        if (interimSpan) {
            interimSpan.textContent = hasInterim ? (hasFinal ? ' ' : '') + interimTranscript : '';
        }

        if (scrollBox && (hasFinal || hasInterim)) {
            scrollBox.scrollTop = scrollBox.scrollHeight;
        }
    }

    // Inisialisasi dan Jalankan Web Speech API dengan Persistent Accumulator (Phase 1 & Phase 7)
    function startSpeechRecognitionSession() {
        if (!SpeechRecognitionClass) {
            const fallback = byId('stt-fallback-notice');
            if (fallback) fallback.classList.remove('hidden');
            return;
        }

        try {
            if (recognition) {
                try { recognition.abort(); } catch (_) {}
                recognition = null;
            }

            recognition = new SpeechRecognitionClass();
            recognition.lang = 'id-ID';
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;

            recognition.onstart = () => {
                if (recordingLifecycle === 'recording') {
                    setRecordingState('live');
                    const ph = byId('live-stt-placeholder');
                    if (ph && !finalTranscript && !interimTranscript) {
                        ph.textContent = 'Sedang mendengarkan percakapan klinis... Suara Anda akan langsung tertulis di sini.';
                    }
                }
            };

            recognition.onspeechstart = () => {
                if (recordingLifecycle === 'recording') {
                    setRecordingState('speaking');
                    const ph = byId('live-stt-placeholder');
                    if (ph) ph.classList.add('hidden');
                }
            };

            recognition.onspeechend = () => {
                if (recordingLifecycle === 'recording') {
                    setRecordingState('live');
                }
            };

            recognition.onresult = (event) => {
                let currentInterim = '';
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    const res = event.results[i];
                    const text = res[0].transcript;
                    if (res.isFinal) {
                        const trimmed = text.trim();
                        if (trimmed) {
                            // Persistent Accumulator: Tambahkan ucapan baru ke transkrip terfinalisasi
                            finalTranscript += (finalTranscript.trim().length > 0 ? ' ' : '') + trimmed;
                            const currentTotalSec = accumulatedSeconds + (sessionStartTime > 0 ? (Date.now() - sessionStartTime) / 1000 : 0);
                            liveSegments.push({
                                id: 'seg-' + Date.now() + '-' + liveSegments.length,
                                startTime: Math.max(0, currentTotalSec - 3),
                                endTime: currentTotalSec,
                                text: trimmed,
                                speakerRole: 'doctor',
                                reviewStatus: 'confirmed'
                            });
                        }
                    } else {
                        currentInterim += text;
                    }
                }
                interimTranscript = currentInterim;
                updateLiveTranscriptDisplay();
            };

            recognition.onerror = (e) => {
                console.warn('SpeechRecognition error:', e.error);
                if (e.error === 'no-speech') {
                    // Timeout hening normal pada Chrome, teruskan
                    return;
                }
                if (e.error === 'network') {
                    message('Gagal terhubung ke server Speech Recognition. Pastikan koneksi internet stabil, nonaktifkan VPN/Adblock, dan gunakan Google Chrome resmi (bukan Brave/Firefox).', true);
                    const ph = byId('live-stt-placeholder');
                    if (ph && !finalTranscript && !interimTranscript) {
                        ph.innerHTML = '<span class="text-amber-700 font-medium">⚠️ Pengenalan suara otomatis terkendala koneksi/browser.</span><br><span class="text-text-secondary text-[11px]">Anda dapat merekam audio, lalu menekan tombol <b>Hentikan</b> dan memasukkan transkrip sebelum menekan <b>Proses Rekaman</b>.</span>';
                    }
                    return;
                }
                if (e.error === 'not-allowed') {
                    message('Izin mikrofon untuk Speech Recognition ditolak oleh browser. Silakan izinkan akses mikrofon pada address bar.', true);
                    return;
                }
                if (e.error === 'audio-capture') {
                    message('Mikrofon tidak dapat diakses atau sedang dipakai aplikasi lain.', true);
                    return;
                }
            };

            recognition.onend = () => {
                // Hanya auto-restart jika rekaman masih aktif dan TIDAK dijeda atau diproses (Phase 3)
                if (recordingLifecycle === 'recording' && !isExplicitlyPaused) {
                    setTimeout(() => {
                        if (recordingLifecycle === 'recording' && !isExplicitlyPaused) {
                            startSpeechRecognitionSession();
                        }
                    }, 150);
                }
            };

            recognition.start();
            const liveWrapper = byId('live-stt-wrapper');
            if (liveWrapper) liveWrapper.classList.remove('hidden');
        } catch (err) {
            console.error('SpeechRecognition start failed:', err);
        }
    }

    // 1. Action: Mulai Rekaman
    const recordBtn = byId('record-button');
    if (recordBtn) {
        recordBtn.onclick = async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                return message('Browser tidak mendukung perekaman mikrofon.', true);
            }

            try {
                recordBtn.disabled = true;

                // Reset state awal
                finalTranscript = '';
                interimTranscript = '';
                liveSegments = [];
                allAudioChunks = [];
                accumulatedSeconds = 0;
                isExplicitlyPaused = false;
                recordingLifecycle = 'recording';

                // Minta izin audio stream
                mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });

                // Jalankan Web Speech API
                startSpeechRecognitionSession();

                // Inisialisasi MediaRecorder
                const supportedMime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/mp4', 'audio/wav'].find(type => window.MediaRecorder && MediaRecorder.isTypeSupported(type));
                if (window.MediaRecorder && supportedMime) {
                    recorder = new MediaRecorder(mediaStream, { mimeType: supportedMime });
                    recorder.ondataavailable = e => e.data && e.data.size && allAudioChunks.push(e.data);
                    recorder.start(1000);
                }

                startWave(mediaStream);
                sessionStartTime = Date.now();
                timer = setInterval(updateTimerDisplay, 250);

                setRecordingState('live');
                updateControlButtons();
                message('Merekam dan transkripsi klinis aktif...');
            } catch (error) {
                recordingLifecycle = 'idle';
                isExplicitlyPaused = true;
                clearInterval(timer);
                timer = null;
                if (recorder?.state === 'recording') recorder.stop();
                if (recognition) { try { recognition.abort(); } catch(_) {} }
                updateControlButtons();
                message(error.message || 'Izin mikrofon ditolak atau perangkat tidak tersedia.', true);
            } finally {
                recordBtn.disabled = false;
            }
        };
    }

    // 2. Action: Hentikan (Berperilaku sebagai Pause - Phase 3)
    function pauseRecordingSession() {
        if (recordingLifecycle !== 'recording') return;
        isExplicitlyPaused = true;
        recordingLifecycle = 'paused';

        clearInterval(timer);
        timer = null;

        if (sessionStartTime > 0) {
            accumulatedSeconds += (Date.now() - sessionStartTime) / 1000;
            sessionStartTime = 0;
        }
        byId('recording-duration').textContent = formatDuration(Math.floor(accumulatedSeconds));

        // Hentikan pengenalan suara
        if (recognition) {
            try { recognition.stop(); } catch (_) {}
        }

        // Hentikan recorder dan kumpulkan chunk terakhir
        if (recorder && recorder.state === 'recording') {
            try {
                recorder.requestData();
                recorder.stop();
            } catch (_) {}
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach(t => t.stop());
            mediaStream = null;
        }
        stopWave();

        // Gabungkan sisa interim transcript jika ada (jangan hilangkan ucapan terakhir!)
        if (interimTranscript.trim()) {
            finalTranscript += (finalTranscript.trim().length > 0 ? ' ' : '') + interimTranscript.trim();
            interimTranscript = '';
        }
        updateLiveTranscriptDisplay();

        setRecordingState('paused');
        updateControlButtons();
        message('Rekaman dijeda. Transkrip dan durasi tersimpan. Pilih Lanjutkan, Ulangi, atau Proses Rekaman.');
    }

    const stopBtn = byId('stop-button');
    if (stopBtn) {
        stopBtn.onclick = () => {
            pauseRecordingSession();
        };
    }

    // 3. Action: Lanjutkan (Melanjutkan rekaman & menyambung transkrip - Phase 4.A)
    async function resumeRecordingSession() {
        if (recordingLifecycle !== 'paused' && recordingLifecycle !== 'error') return;
        isExplicitlyPaused = false;
        recordingLifecycle = 'recording';

        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            startWave(mediaStream);

            const supportedMime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/mp4', 'audio/wav'].find(type => window.MediaRecorder && MediaRecorder.isTypeSupported(type));
            if (window.MediaRecorder && supportedMime) {
                recorder = new MediaRecorder(mediaStream, { mimeType: supportedMime });
                recorder.ondataavailable = e => e.data && e.data.size && allAudioChunks.push(e.data);
                recorder.start(1000);
            }

            startSpeechRecognitionSession();

            sessionStartTime = Date.now();
            timer = setInterval(updateTimerDisplay, 250);

            setRecordingState('live');
            updateControlButtons();
            message('Melanjutkan rekaman dan transkripsi klinis...');
        } catch (err) {
            recordingLifecycle = 'paused';
            updateControlButtons();
            message('Gagal melanjutkan rekaman: ' + (err.message || 'Izin mikrofon tidak tersedia.'), true);
        }
    }

    byId('resume-button')?.addEventListener('click', resumeRecordingSession);

    // 4. Action: Ulangi Rekaman dengan Modal Konfirmasi (Phase 4.B)
    function bukaModalUlangi() {
        const modal = byId('modalUlangiRekaman');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function tutupModalUlangi() {
        const modal = byId('modalUlangiRekaman');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function konfirmasiUlangiRekaman() {
        isExplicitlyPaused = true;
        recordingLifecycle = 'idle';

        clearInterval(timer);
        timer = null;
        sessionStartTime = 0;
        accumulatedSeconds = 0;

        if (recognition) {
            try { recognition.abort(); } catch (_) {}
            recognition = null;
        }
        if (recorder && recorder.state === 'recording') {
            try { recorder.stop(); } catch (_) {}
            recorder = null;
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach(t => t.stop());
            mediaStream = null;
        }
        stopWave();

        finalTranscript = '';
        interimTranscript = '';
        liveSegments = [];
        allAudioChunks = [];

        byId('recording-duration').textContent = '00:00';
        updateLiveTranscriptDisplay();
        setRecordingState('ready');
        updateControlButtons();
        tutupModalUlangi();
        message('Sesi rekaman diulang dari awal. Tekan Mulai Rekaman untuk memulai.');
    }

    byId('retry-button')?.addEventListener('click', bukaModalUlangi);
    byId('btn-batal-ulangi')?.addEventListener('click', tutupModalUlangi);
    byId('btn-konfirmasi-ulangi')?.addEventListener('click', konfirmasiUlangiRekaman);

    // 5. Action: Proses Rekaman (Menyimpan transkrip & memicu OpenRouter SOAP - Phase 5)
    async function processRecordingSession() {
        if (recordingLifecycle === 'recording') {
            pauseRecordingSession();
        }

        // Pastikan interim terakhir digabungkan ke final
        if (interimTranscript.trim()) {
            finalTranscript += (finalTranscript.trim().length > 0 ? ' ' : '') + interimTranscript.trim();
            interimTranscript = '';
        }
        updateLiveTranscriptDisplay();

        const cleanedTranscript = finalTranscript.trim();
        if (!cleanedTranscript) {
            message('Transkrip wawancara belum tersedia. Silakan lakukan rekaman terlebih dahulu.', true);
            const ph = byId('live-stt-placeholder');
            if (ph) ph.textContent = 'Transkrip wawancara belum tersedia. Silakan lakukan rekaman terlebih dahulu.';
            return;
        }

        recordingLifecycle = 'processing';
        setRecordingState('processing');
        updateControlButtons();

        byId('processing-label').textContent = 'Menyiapkan transkrip klinis...';
        message('Menyiapkan transkrip klinis...');

        const p1 = setTimeout(() => {
            if (recordingLifecycle === 'processing') {
                byId('processing-label').textContent = 'Mengolah transkrip dan data TTV melalui OpenRouter...';
                message('Mengolah transkrip dan data TTV melalui OpenRouter...');
            }
        }, 800);

        const p2 = setTimeout(() => {
            if (recordingLifecycle === 'processing') {
                byId('processing-label').textContent = 'Menyimpan draf SOAP...';
                message('Menyimpan draf SOAP...');
            }
        }, 2200);

        let audioBlob = null;
        if (allAudioChunks.length > 0) {
            const mime = allAudioChunks[0]?.type || 'audio/webm';
            audioBlob = new Blob(allAudioChunks, { type: mime });
        }

        if (liveSegments.length === 0) {
            liveSegments.push({
                id: 'seg-' + Date.now(),
                startTime: 0,
                endTime: Math.max(1, accumulatedSeconds),
                text: cleanedTranscript,
                speakerRole: 'doctor',
                reviewStatus: 'confirmed'
            });
        }

        const form = new FormData();
        form.append('realtime_transcript', cleanedTranscript);
        form.append('realtime_segments', JSON.stringify(liveSegments));
        form.append('accumulated_duration', Math.floor(accumulatedSeconds));
        if (audioBlob && audioBlob.size > 0) {
            const ext = audioBlob.type.includes('ogg') ? '.ogg' : (audioBlob.type.includes('mp4') ? '.m4a' : '.webm');
            form.append('audio', audioBlob, `interview-${Date.now()}${ext}`);
        }

        try {
            const res = await fetch(audioConfig.process, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: form
            });

            clearTimeout(p1);
            clearTimeout(p2);

            const data = await res.json();

            if (res.ok && data.success !== false) {
                recordingLifecycle = 'completed';
                setRecordingState('completed');
                message('Draf SOAP berhasil dibuat. Silakan tinjau hasilnya sebelum melanjutkan.');

                setTimeout(() => {
                    window.location.href = data.redirect_url || (window.location.pathname + '#soap');
                    window.location.reload();
                }, 500);
            } else {
                throw new Error(data.message || 'Gagal memproses rekaman dengan AI.');
            }
        } catch (err) {
            clearTimeout(p1);
            clearTimeout(p2);
            recordingLifecycle = 'error';
            setRecordingState('error');
            updateControlButtons();
            message('Gagal memproses rekaman: ' + err.message + ' Anda dapat menekan tombol Proses Rekaman untuk mencoba kembali.', true);
        }
    }

    byId('process-button')?.addEventListener('click', processRecordingSession);

    // Transcript Editor Rendering (Mode Review Transkrip)
    function renderTranscript() {
        const root = byId('transcript-editor');
        if (!root) return;
        root.innerHTML = '';
        segments.forEach((s, i) => {
            const ambiguous = s.reviewStatus === 'ambiguous';
            const row = document.createElement('div');
            row.className = 'rounded-[18px] border p-4 ' + (ambiguous ? 'bg-amber-50/60 border-amber-200' : 'bg-soft-offwhite border-border-subtle');
            row.innerHTML = `<div class="flex items-center justify-between gap-2 text-[11px] text-text-secondary"><span class="num">${Number(s.startTime || 0).toFixed(1)}–${Number(s.endTime || 0).toFixed(1)} dtk</span>${ambiguous ? '<span class="status status-warn">Perlu diperiksa</span>' : ''}</div>`
                + `<label class="sr-only" for="segment-text-${i}">Teks bagian ${i + 1}</label><textarea id="segment-text-${i}" data-index="${i}" rows="2" class="transcript-text field mt-2 leading-relaxed">${escapeHtml(s.text)}</textarea>`
                + `<div class="grid grid-cols-2 gap-2 mt-2"><label class="sr-only" for="segment-speaker-${i}">Pembicara bagian ${i + 1}</label><select id="segment-speaker-${i}" data-speaker="${i}" class="speaker-role field"><option value="unknown">Pembicara tidak dikenal</option><option value="doctor" ${s.speakerRole === 'doctor' ? 'selected' : ''}>Dokter</option><option value="patient" ${s.speakerRole === 'patient' ? 'selected' : ''}>Pasien</option></select>`
                + `<label class="sr-only" for="segment-status-${i}">Status bagian ${i + 1}</label><select id="segment-status-${i}" data-status="${i}" class="review-status field"><option value="confirmed" ${s.reviewStatus === 'confirmed' ? 'selected' : ''}>Terkonfirmasi</option><option value="ambiguous" ${ambiguous ? 'selected' : ''}>Ambigu</option><option value="edited" ${s.reviewStatus === 'edited' ? 'selected' : ''}>Diedit</option></select></div>`;
            root.appendChild(row);
        });
    }

    function syncSegments() {
        document.querySelectorAll('.transcript-text').forEach(el => {
            if (segments[el.dataset.index]) {
                segments[el.dataset.index].text = el.value;
                segments[el.dataset.index].reviewStatus = 'edited';
            }
        });
        document.querySelectorAll('[data-speaker]').forEach(el => {
            if (segments[el.dataset.speaker]) segments[el.dataset.speaker].speakerRole = el.value;
        });
        document.querySelectorAll('[data-status]').forEach(el => {
            if (segments[el.dataset.status]) segments[el.dataset.status].reviewStatus = el.value;
        });
        speakerMap = {};
        segments.forEach(s => {
            if (s.speakerId) speakerMap[s.speakerId] = s.speakerRole;
        });
    }

    // Simpan Koreksi Review Transkrip Sementara
    byId('save-transcript')?.addEventListener('click', async () => {
        syncSegments();
        const button = byId('save-transcript');
        button.disabled = true;
        button.textContent = 'Menyimpan...';
        try {
            const res = await fetch(audioConfig.transcript, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ segments, speakerMap })
            });
            if (res.ok) {
                message('Koreksi transkrip berhasil disimpan.');
                button.disabled = false;
                button.textContent = 'Simpan Koreksi';
            } else {
                button.disabled = false;
                button.textContent = 'Simpan Koreksi';
                message('Koreksi transkrip gagal disimpan.', true);
            }
        } catch (error) {
            button.disabled = false;
            button.textContent = 'Simpan Koreksi';
            message('Koneksi terputus saat menyimpan review.', true);
        }
    });

    // Verifikasi Transkripsi Wawancara (Aksi Utama Tahap 1 -> Otomatis trigger OpenRouter SOAP)
    const verifyTranscriptBtn = byId('verify-transcript-button');
    if (verifyTranscriptBtn) {
        verifyTranscriptBtn.addEventListener('click', async () => {
            syncSegments();
            if (!segments || segments.length === 0) {
                return message('Transkrip tidak boleh kosong sebelum diverifikasi.', true);
            }

            verifyTranscriptBtn.disabled = true;
            const originalContent = verifyTranscriptBtn.innerHTML;
            verifyTranscriptBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memverifikasi & Menyusun SOAP...</span>
            `;
            message('Memverifikasi transkrip wawancara dan menyusun draf SOAP otomatis...');

            try {
                const res = await fetch(audioConfig.verify, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ segments, speakerMap })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    message('Transkrip berhasil diverifikasi! Mengalihkan ke Draf SOAP...');
                    window.location.href = data.redirect_url || (window.location.pathname + '#soap');
                    window.location.reload();
                } else {
                    verifyTranscriptBtn.disabled = false;
                    verifyTranscriptBtn.innerHTML = originalContent;
                    message(data.message || 'Verifikasi transkrip gagal.', true);
                }
            } catch (error) {
                verifyTranscriptBtn.disabled = false;
                verifyTranscriptBtn.innerHTML = originalContent;
                message('Gagal menghubungi server untuk verifikasi transkrip.', true);
            }
        });
    }

    // Verifikasi Manual (Fallback jika tanpa segmen)
    const verifyManualBtn = byId('verify-manual-transcript-button');
    if (verifyManualBtn) {
        verifyManualBtn.addEventListener('click', async () => {
            const textVal = byId('manual-transcript-input')?.value?.trim();
            if (!textVal) {
                return message('Transkrip wawancara wajib diisi.', true);
            }

            verifyManualBtn.disabled = true;
            const originalContent = verifyManualBtn.innerHTML;
            verifyManualBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memverifikasi & Menyusun SOAP...</span>
            `;

            try {
                const res = await fetch(audioConfig.verify, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ transkrip: textVal })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    window.location.href = data.redirect_url || (window.location.pathname + '#soap');
                    window.location.reload();
                } else {
                    verifyManualBtn.disabled = false;
                    verifyManualBtn.innerHTML = originalContent;
                    message(data.message || 'Verifikasi transkrip gagal.', true);
                }
            } catch (error) {
                verifyManualBtn.disabled = false;
                verifyManualBtn.innerHTML = originalContent;
                message('Gagal menghubungi server.', true);
            }
        });
    }

    renderTranscript();
})();
</script>
@endif
@endif

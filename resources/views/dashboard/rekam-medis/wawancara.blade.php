@php
    $mode = $workflowState;
    $segments = $wawancara?->transcript_segments ?? [];
    $peranSpeaker = ['doctor' => 'Dokter', 'patient' => 'Pasien'];
    $isFinalized = $kunjungan->isFinalized();

    // $keteranganWawancara, $statusWawancara, $kelasStatusWawancara dihitung di rekam-medis.blade.php.
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
        <div class="alert alert-ok" role="status">
            <x-icon name="lock" aria-hidden="true" />
            <div class="flex-1 text-text-body">
                <p class="font-semibold text-medical-navy">Rekam medis sudah final</p>
                <p class="mt-0.5">Rekaman audio dan transkrip wawancara tidak bisa diubah lagi.</p>
            </div>
        </div>
    @endif

    {{-- Mode 1: Interview Belum Dimulai --}}
    @if ($mode === 'interview' && ! $isFinalized)
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
            <p class="text-sm text-text-body max-w-md">Mikrofon dan transkrip langsung aktif setelah Anda menekan Mulai Wawancara.</p>
            <form method="POST" action="{{ route('dokter.interview.start', $kunjungan) }}" data-submit-loading>
                @csrf
                <button type="submit" class="btn btn-primary" data-loading-text="Memulai...">
                    <x-icon name="mic" class="w-4 h-4" aria-hidden="true" />
                    <span data-label>Mulai Wawancara</span>
                </button>
            </form>
        </div>

    {{-- Mode 2: Sesi Perekaman & Transkripsi Real-Time --}}
    @elseif ($mode === 'recording' && ! $isFinalized)
        @php($batasMenit = max(1, intdiv((int) config('medscribe.audio.max_duration_seconds'), 60)))
        <div id="recording-console" class="rounded-(--radius-panel) bg-soft-offwhite border border-border-subtle p-5 sm:p-6 space-y-5">
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
            <div id="live-stt-wrapper" class="rounded-(--radius-panel) bg-white border border-border-subtle p-4 space-y-3">
                <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-border-subtle text-xs">
                    <span class="font-semibold text-medical-navy flex items-center gap-2">
                        <span id="live-indicator-dot" class="relative flex h-2.5 w-2.5 invisible" aria-hidden="true">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-dot opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-danger-dot"></span>
                        </span>
                        Transkrip langsung
                    </span>
                    <span id="stt-engine-badge" class="chip chip-accent">Bahasa Indonesia</span>
                </div>
                <div id="live-stt-body" class="max-h-48 overflow-y-auto space-y-2 text-sm leading-relaxed text-text-body font-normal">
                    <p id="live-stt-placeholder" class="text-xs text-text-secondary">Tekan Mulai Rekaman. Ucapan akan tertulis di sini.</p>
                    <div id="live-stt-content" class="space-y-1.5">
                        <span id="live-stt-final" class="text-medical-navy font-normal"></span>
                        <span id="live-stt-interim" class="text-royal-indigo italic opacity-75"></span>
                    </div>
                </div>
            </div>

            {{-- Pemberitahuan bila browser tidak punya Web Speech API --}}
            <div id="stt-fallback-notice" class="hidden alert alert-warn">
                <x-icon name="circle-alert" aria-hidden="true" />
                <span class="text-text-body">Browser ini belum bisa menulis transkrip langsung. Gunakan Google Chrome atau Microsoft Edge.</span>
            </div>

            {{-- Kontrol Aksi Rekaman & Transkripsi (State Machine Lifecycle) --}}
            <div class="flex flex-wrap items-center justify-center gap-3">
                {{-- State: IDLE --}}
                <button type="button" id="record-button" class="btn btn-primary">
                    <x-icon name="mic" class="w-4 h-4" aria-hidden="true" />
                    <span>Mulai Rekaman</span>
                </button>

                {{-- State: RECORDING --}}
                <button type="button" id="stop-button" hidden class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="5" y="5" width="10" height="10" rx="2" /></svg>
                    <span>Hentikan</span>
                </button>

                {{-- State: PAUSED (Tiga Aksi: Lanjutkan, Ulangi, Proses) --}}
                <div id="paused-actions" hidden class="flex flex-wrap items-center justify-center gap-3">
                    {{-- 1. Lanjutkan --}}
                    <button type="button" id="resume-button" class="btn btn-secondary">
                        <x-icon name="play" />
                        <span>Lanjutkan</span>
                    </button>

                    {{-- 2. Ulangi Rekaman --}}
                    <button type="button" id="retry-button" class="btn btn-danger-quiet">
                        <x-icon name="refresh-cw" class="w-4 h-4" aria-hidden="true" />
                        <span>Ulangi Rekaman</span>
                    </button>

                    {{-- 3. Proses Rekaman --}}
                    <button type="button" id="process-button" class="btn btn-primary">
                        <x-icon name="circle-check" class="w-4 h-4" aria-hidden="true" />
                        <span>Proses Rekaman</span>
                    </button>
                </div>

                {{-- State: PROCESSING Indicator --}}
                <div id="processing-indicator" hidden class="flex items-center gap-3 min-h-11 px-5 rounded-(--radius-field) bg-pale-lavender text-royal-indigo-hover text-sm font-medium">
                    <span class="spinner" aria-hidden="true"></span>
                    <span id="processing-label">Memproses rekaman…</span>
                </div>
            </div>

            <p id="recording-message" class="text-xs text-text-secondary text-center min-h-4 transition-colors" role="status"></p>
        </div>

        {{-- Konfirmasi ulangi rekaman --}}
        <x-dialog id="modalUlangiRekaman" tone="danger" title="Ulangi rekaman?"
                  description="Transkrip dan durasi rekaman saat ini akan dihapus, lalu rekaman dimulai dari awal."
                  icon="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
            <x-slot:actions>
                <button type="button" class="btn btn-secondary" data-dialog-close data-dialog-focus>Batal</button>
                <button type="button" id="btn-konfirmasi-ulangi" class="btn btn-danger">Ulangi Rekaman</button>
            </x-slot:actions>
        </x-dialog>
    @endif

    {{-- Audio player bila rekaman tersimpan --}}
    @if ($rekamanAudio && ! in_array($mode, ['interview'], true))
        <div class="panel !p-3">
            <p class="px-1 pb-2 text-xs font-medium text-text-secondary">Rekaman audio wawancara klinis</p>
            <audio controls preload="none" class="w-full h-10" src="{{ route('dokter.rekam-medis.stream-audio', $antrean->id_antrean) }}">Browser tidak mendukung pemutar audio.</audio>
        </div>
    @endif

    {{-- Mode 3: Review Transkrip Wawancara (Step 1 Review) --}}
    @if ($mode === 'transcript' && ! $isFinalized)
        <div>
            <h3 class="text-sm font-semibold text-medical-navy">Tinjau transkrip wawancara</h3>
            <p class="mt-1 text-xs text-text-secondary">Periksa teks dan peran pembicara (dokter atau pasien), lalu verifikasi untuk menyusun draf SOAP.</p>
        </div>

        @if ($segments)
            <div id="transcript-editor" class="space-y-3"></div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3 pt-2">
                <p id="transcript-message" class="text-xs text-text-secondary sm:mr-auto" role="status"></p>
                <button type="button" id="save-transcript" class="btn btn-secondary">
                    <span>Simpan Koreksi</span>
                </button>
                <button type="button" id="verify-transcript-button" class="btn btn-primary">
                    <x-icon name="check" class="w-4 h-4" aria-hidden="true" />
                    <span>Verifikasi Transkripsi</span>
                </button>
            </div>
        @else
            <div class="space-y-3">
                <label for="manual-transcript-input" class="field-label">Transkrip Wawancara Klinis</label>
                <textarea id="manual-transcript-input" rows="6" class="field leading-relaxed" placeholder="Ketik atau tempel transkrip percakapan dokter dan pasien di sini...">{{ old('transkrip', $wawancara?->transkrip) }}</textarea>
                <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3 pt-2">
                    <p id="transcript-message" class="text-xs text-text-secondary sm:mr-auto" role="status"></p>
                    <button type="button" id="verify-manual-transcript-button" class="btn btn-primary">
                        <x-icon name="check" class="w-4 h-4" aria-hidden="true" />
                        <span>Verifikasi Transkripsi</span>
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- Transkrip yang sudah diverifikasi (SOAP ready / SOAP / Selesai / Final) --}}
    @if (in_array($mode, ['soap-ready', 'soap', 'completed'], true) || $isFinalized)
        <details class="group rounded-(--radius-panel) border border-border-subtle" @if ($isFinalized) open @endif>
            <summary class="flex items-center justify-between gap-3 px-4 min-h-12 text-sm font-medium text-medical-navy list-none cursor-pointer">
                <span class="flex items-center gap-2">
                    <x-icon name="circle-check" class="w-4 h-4 text-success-text" aria-hidden="true" />
                    Transkrip yang telah diverifikasi
                </span>
                <span class="flex items-center gap-2 text-xs font-normal text-text-secondary">
                    {{ count($segments) }} bagian
                    <x-icon name="chevron-down" class="w-4 h-4 transition-transform group-open:rotate-180" aria-hidden="true" />
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
    // tone: 'error' | 'ok' | kosong (info). Nilai true tetap dianggap error.
    const message = (text, tone = '') => {
        const el = byId('recording-message') || byId('transcript-message');
        if (!el) return;
        const kind = tone === true ? 'error' : tone;
        el.textContent = text;
        el.classList.toggle('text-danger-text', kind === 'error');
        el.classList.toggle('text-success-text', kind === 'ok');
        el.classList.toggle('text-text-secondary', kind !== 'error' && kind !== 'ok');
    };

    // Pola loading tombol yang sama dengan data-submit-loading: spinner + label sementara.
    const setLoading = (button, label) => {
        button.dataset.original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner" aria-hidden="true"></span><span></span>';
        button.lastElementChild.textContent = label;
    };
    const resetLoading = (button) => {
        button.disabled = false;
        if (button.dataset.original) button.innerHTML = button.dataset.original;
    };

    const RECORDING_STATES = {
        ready: ['status-active', 'Siap merekam'],
        live: ['status-danger is-live', 'Merekam'],
        speaking: ['status-danger is-live', 'Mendengarkan ucapan...'],
        paused: ['status-warn', 'Dijeda'],
        processing: ['status-warn', 'Memproses…'],
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
        // Titik merah hanya menyala saat benar-benar merekam.
        byId('live-indicator-dot')?.classList.toggle('invisible', recordingLifecycle !== 'recording');

        if (recordingLifecycle === 'idle') {
            show('record-button', true);
            show('stop-button', false);
            show('paused-actions', false);
            show('processing-indicator', false);
            if (recordBtn) recordBtn.disabled = false;
            if (sttBadge) sttBadge.textContent = 'Bahasa Indonesia';
        } else if (recordingLifecycle === 'recording') {
            show('record-button', false);
            show('stop-button', true);
            show('paused-actions', false);
            show('processing-indicator', false);
            if (stopBtn) stopBtn.disabled = false;
            if (sttBadge) sttBadge.textContent = 'Mendengarkan';
        } else if (recordingLifecycle === 'paused') {
            show('record-button', false);
            show('stop-button', false);
            show('paused-actions', true);
            show('processing-indicator', false);
            if (sttBadge) sttBadge.textContent = 'Dijeda';
        } else if (recordingLifecycle === 'processing') {
            show('record-button', false);
            show('stop-button', false);
            show('paused-actions', false);
            show('processing-indicator', true);
            if (sttBadge) sttBadge.textContent = 'Memproses';
        } else if (recordingLifecycle === 'error') {
            show('record-button', false);
            show('stop-button', false);
            show('paused-actions', true);
            show('processing-indicator', false);
            if (sttBadge) sttBadge.textContent = 'Siap diproses ulang';
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
                        ph.textContent = 'Mendengarkan… Ucapan akan langsung tertulis di sini.';
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
                    message('Transkrip otomatis terputus. Periksa koneksi internet, lalu coba lagi di Google Chrome atau Microsoft Edge.', 'error');
                    const ph = byId('live-stt-placeholder');
                    if (ph && !finalTranscript && !interimTranscript) {
                        ph.textContent = 'Transkrip otomatis belum bisa berjalan. Rekaman audio tetap berlangsung.';
                        ph.classList.add('text-warning-text');
                    }
                    return;
                }
                if (e.error === 'not-allowed') {
                    message('Akses mikrofon ditolak. Izinkan mikrofon lewat ikon di kolom alamat browser.', 'error');
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
                message('Merekam…');
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
            message('Merekam lagi…');
        } catch (err) {
            recordingLifecycle = 'paused';
            updateControlButtons();
            message('Gagal melanjutkan rekaman: ' + (err.message || 'Izin mikrofon tidak tersedia.'), true);
        }
    }

    byId('resume-button')?.addEventListener('click', resumeRecordingSession);

    // 4. Action: Ulangi Rekaman dengan Modal Konfirmasi (Phase 4.B)
    const bukaModalUlangi = () => window.openDialog?.('modalUlangiRekaman');
    const tutupModalUlangi = () => window.closeDialog?.('modalUlangiRekaman');

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
    byId('btn-konfirmasi-ulangi')?.addEventListener('click', konfirmasiUlangiRekaman);

    // 5. Action: Proses Rekaman (menyimpan transkrip dan menyusun draf SOAP lewat AI - Phase 5)
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

        byId('processing-label').textContent = 'Menyiapkan transkrip…';
        message('Menyiapkan transkrip…');

        const p1 = setTimeout(() => {
            if (recordingLifecycle === 'processing') {
                byId('processing-label').textContent = 'Menyusun draf SOAP dari transkrip dan TTV…';
                message('Menyusun draf SOAP dari transkrip dan TTV…');
            }
        }, 800);

        const p2 = setTimeout(() => {
            if (recordingLifecycle === 'processing') {
                byId('processing-label').textContent = 'Menyimpan draf SOAP…';
                message('Menyimpan draf SOAP…');
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
                message('Draf SOAP berhasil dibuat. Tinjau hasilnya sebelum melanjutkan.', 'ok');

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
            message('Gagal memproses rekaman: ' + err.message + ' Tekan Proses Rekaman untuk mencoba lagi.', 'error');
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
            row.className = 'rounded-(--radius-panel) border p-4 ' + (ambiguous ? 'bg-warning-bg border-warning-border' : 'bg-soft-offwhite border-border-subtle');
            row.innerHTML = `<div class="flex items-center justify-between gap-2 text-[11px] text-text-secondary"><span class="num">${Number(s.startTime || 0).toFixed(1)}–${Number(s.endTime || 0).toFixed(1)} dtk</span>${ambiguous ? '<span class="status status-warn">Perlu ditinjau</span>' : ''}</div>`
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
        setLoading(button, 'Menyimpan…');
        try {
            const res = await fetch(audioConfig.transcript, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ segments, speakerMap })
            });
            message(res.ok ? 'Koreksi transkrip tersimpan.' : 'Koreksi transkrip gagal disimpan.', res.ok ? 'ok' : 'error');
        } catch (error) {
            message('Koneksi terputus saat menyimpan koreksi.', 'error');
        } finally {
            resetLoading(button);
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

            setLoading(verifyTranscriptBtn, 'Menyusun draf SOAP…');
            message('Memverifikasi transkrip dan menyusun draf SOAP…');

            try {
                const res = await fetch(audioConfig.verify, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ segments, speakerMap })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    message('Transkrip terverifikasi. Membuka draf SOAP…', 'ok');
                    window.location.href = data.redirect_url || (window.location.pathname + '#soap');
                    window.location.reload();
                } else {
                    resetLoading(verifyTranscriptBtn);
                    message(data.message || 'Verifikasi transkrip gagal.', 'error');
                }
            } catch (error) {
                resetLoading(verifyTranscriptBtn);
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

            setLoading(verifyManualBtn, 'Menyusun draf SOAP…');

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
                    resetLoading(verifyManualBtn);
                    message(data.message || 'Verifikasi transkrip gagal.', 'error');
                }
            } catch (error) {
                resetLoading(verifyManualBtn);
                message('Gagal menghubungi server.', true);
            }
        });
    }

    renderTranscript();
})();
</script>
@endif
@endif

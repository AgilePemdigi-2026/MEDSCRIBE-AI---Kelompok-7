@php
    $mode = $workflowState;
    $segments = $wawancara?->transcript_segments ?? [];
    $peranSpeaker = ['doctor' => 'Dokter', 'patient' => 'Pasien'];

    [$keteranganWawancara, $statusWawancara, $kelasStatusWawancara] = match ($mode) {
        'ttv' => ['Terbuka setelah perawat mengisi TTV.', 'Menunggu TTV', 'status-muted'],
        'interview' => ['Rekam percakapan dengan pasien untuk dijadikan transkrip.', 'Belum dimulai', 'status-active'],
        'recording' => ['Rekam percakapan, lalu kirim audio untuk ditranskripsi.', 'Sesi berlangsung', 'status-active'],
        'processing' => ['Audio tersimpan dan sedang diubah menjadi teks.', 'Diproses', 'status-warn'],
        'failed' => ['Audio gagal ditranskripsi. Rekaman asli tetap tersimpan.', 'Gagal', 'status-danger'],
        'transcript' => ['Periksa teks dan peran pembicara sebelum membuat SOAP.', 'Perlu ditinjau', 'status-warn'],
        default => ['Transkrip sudah ditinjau dan dipakai untuk draf SOAP.', 'Selesai', 'status-ok'],
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

    @if ($mode === 'interview')
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
            <p class="text-sm text-text-body max-w-md">Mikrofon baru aktif setelah Anda menekan Mulai Rekaman pada langkah berikutnya.</p>
            <form method="POST" action="{{ route('dokter.interview.start', $kunjungan) }}" data-submit-loading>
                @csrf
                <button type="submit" class="btn btn-primary min-h-12 px-6" data-loading-text="Memulai...">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                    <span data-label>Mulai Wawancara</span>
                </button>
            </form>
        </div>

    @elseif ($mode === 'recording')
        @php($batasMenit = max(1, intdiv((int) config('medscribe.audio.max_duration_seconds'), 60)))
        {{-- Konsol perekam; visual gelombang diadaptasi dari 21st.dev "Live Waveform" (ElevenLabs). --}}
        <div id="recording-console" class="rounded-[22px] bg-soft-offwhite border border-border-subtle p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <span id="recording-state" class="status status-active">Siap merekam</span>
                <span class="text-[11px] text-text-secondary">Maks. {{ $batasMenit }} menit</span>
            </div>

            <p id="recording-duration" class="mt-5 text-center text-[3.25rem] leading-none font-semibold tracking-[-0.05em] text-medical-navy num" aria-label="Durasi rekaman">00:00</p>

            <div class="relative mt-5 h-20 text-royal-indigo">
                <div id="recording-wave-idle" class="absolute inset-x-0 top-1/2 -translate-y-1/2 border-t-2 border-dotted border-border-hover" aria-hidden="true"></div>
                <canvas id="recording-wave" class="block w-full h-full" aria-hidden="true"></canvas>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <button type="button" id="record-button" class="btn btn-primary min-h-12 px-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                    Mulai Rekaman
                </button>
                <button type="button" id="stop-button" hidden class="btn btn-danger min-h-12 px-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="5" y="5" width="10" height="10" rx="2" /></svg>
                    Hentikan
                </button>
                <button type="button" id="retry-recording" hidden class="btn btn-secondary min-h-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    Rekam Ulang
                </button>
                <button type="button" id="process-button" hidden class="btn btn-navy min-h-12 px-6">
                    Proses Audio
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </div>

            <p id="recording-message" class="mt-4 text-xs text-text-secondary text-center min-h-4" role="status"></p>
        </div>

    @elseif ($mode === 'processing')
        <div class="flex flex-col items-center text-center gap-3 py-4">
            <span class="w-12 h-12 rounded-full border-4 border-pale-lavender border-t-royal-indigo animate-spin" aria-hidden="true"></span>
            <h3 class="text-base font-semibold text-medical-navy">Transkripsi sedang diproses</h3>
            <p id="processing-message" class="text-xs text-text-secondary max-w-sm" role="status">Halaman akan diperbarui otomatis saat transkrip siap.</p>
            <span id="processing-state" class="tag-id tag-id-muted">{{ strtoupper($wawancara?->processing_status ?? '') }}</span>
        </div>

    @elseif ($mode === 'failed')
        <div class="alert alert-danger">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
            <span class="flex-1">{{ $wawancara?->processing_error ?: 'Layanan transkripsi tidak berhasil memproses audio.' }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" id="retry-process" class="btn btn-danger">Coba Proses Lagi</button>
            <p id="processing-message" class="text-xs text-text-secondary" role="status"></p>
        </div>
    @endif

    @if ($rekamanAudio && ! in_array($mode, ['interview', 'processing'], true))
        <div class="rounded-[18px] bg-soft-offwhite border border-border-subtle p-3">
            <p class="px-1 pb-2 text-[11px] font-medium text-text-secondary">Rekaman audio</p>
            <audio controls preload="none" class="w-full h-10" src="{{ route('dokter.rekam-medis.stream-audio', $antrean->id_antrean) }}">Browser tidak mendukung pemutar audio.</audio>
        </div>
    @endif

    @if ($mode === 'transcript')
        <div>
            <h3 class="text-sm font-semibold text-medical-navy">Review Transkrip Wawancara</h3>
            <p class="mt-1 text-xs text-text-secondary">Perbaiki teks yang salah dengar dan pastikan setiap bagian diberi peran pembicara yang benar.</p>
        </div>
        @if ($segments)
            <div id="transcript-editor" class="space-y-3"></div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-3">
                <p id="transcript-message" class="text-xs text-text-secondary sm:mr-auto" role="status"></p>
                <button type="button" id="save-transcript" class="btn btn-primary">Simpan Review Transkrip</button>
            </div>
        @else
            <p class="alert alert-danger">Transkrip kosong, tidak ada bagian yang bisa ditinjau.</p>
        @endif
    @endif

    @if (in_array($mode, ['soap-ready', 'soap', 'completed'], true))
        <details class="group rounded-[18px] border border-border-subtle">
            <summary class="flex items-center justify-between gap-3 px-4 min-h-12 text-sm font-medium text-medical-navy list-none">
                Transkrip yang sudah ditinjau
                <span class="flex items-center gap-2 text-xs font-normal text-text-secondary">
                    {{ count($segments) }} bagian
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </span>
            </summary>
            <ol class="border-t border-border-subtle divide-y divide-border-subtle">
                @forelse ($segments as $segment)
                    <li class="px-4 py-3 text-sm">
                        <span class="block text-[11px] font-semibold {{ ($segment['speakerRole'] ?? '') === 'doctor' ? 'text-royal-indigo' : 'text-text-secondary' }}">
                            {{ $peranSpeaker[$segment['speakerRole'] ?? ''] ?? 'Pembicara tidak dikenal' }}
                        </span>
                        <span class="text-text-body leading-relaxed">{{ $segment['text'] ?? '' }}</span>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-text-body whitespace-pre-line">{{ $wawancara?->transkrip }}</li>
                @endforelse
            </ol>
        </details>

        @if ($mode === 'soap-ready')
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
@endif

@if (in_array($mode, ['recording', 'processing', 'transcript', 'failed'], true))
@section('scripts')
<script>
const audioConfig = { upload: @json(route('dokter.interview.audio.upload', $kunjungan)), status: @json(route('dokter.interview.audio.status', $kunjungan)), retry: @json(route('dokter.interview.audio.retry', $kunjungan)), transcript: @json(route('dokter.interview.transcript.update', $kunjungan)), maxSeconds: @json(config('medscribe.audio.max_duration_seconds')), maxBytes: @json(config('medscribe.audio.max_size_kb') * 1024), pollInterval: 2500, maxPollAttempts: @json((int) ceil(config('medscribe.stt.timeout') / 2.5)), maxPollDurationMs: @json((int) config('medscribe.stt.timeout') * 1000) };
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
let recorder, chunks = [], blob = null, timer, startedAt, pollTimer, pollController = null, polling = false, pollGeneration = 0, pollAttempts = 0, pollDeadline = 0, uploading = false, segments = @json($segments), speakerMap = @json($wawancara?->speaker_map ?? []);
const byId = (id) => document.getElementById(id);
const escapeHtml = (value) => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
const message = (text, error = false) => { const el = byId('recording-message') || byId('transcript-message') || byId('processing-message'); if (el) { el.textContent = text; el.classList.toggle('text-danger-text', error); el.classList.toggle('text-text-secondary', !error); } };
const RECORDING_STATES = { ready: ['status-active', 'Siap merekam'], live: ['status-danger is-live', 'Merekam'], recorded: ['status-ok', 'Siap diproses'], uploading: ['status-warn', 'Mengunggah'], queued: ['status-warn', 'Diproses'] };
const setRecordingState = (key) => { const el = byId('recording-state'); if (!el) return; const [cls, text] = RECORDING_STATES[key]; el.className = 'status ' + cls; el.textContent = text; };
const show = (id, visible) => { const el = byId(id); if (el) el.hidden = !visible; };
const formatDuration = (seconds) => `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;

// Gelombang suara langsung dari mikrofon (pola 21st.dev "Live Waveform", mode statis bercermin).
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
    // Pudarkan tepi kiri-kanan agar gelombang tidak terpotong tajam.
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
const extensionForMime = (mime) => { const base = (mime || '').split(';', 1)[0].toLowerCase(); return ({ 'audio/webm': '.webm', 'video/webm': '.webm', 'audio/ogg': '.ogg', 'audio/mp4': '.m4a', 'audio/x-m4a': '.m4a', 'audio/wav': '.wav', 'audio/x-wav': '.wav', 'audio/mpeg': '.mp3' })[base] || null; };
const isCurrentPolling = (generation) => polling && generation === pollGeneration;
const stopPolling = () => { polling = false; pollGeneration += 1; if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; } if (pollController) { pollController.abort(); pollController = null; } };
const schedulePoll = (generation, delay = audioConfig.pollInterval) => { if (isCurrentPolling(generation)) pollTimer = setTimeout(() => poll(generation), Math.min(delay, Math.max(0, pollDeadline - Date.now()))); };
async function poll(generation) { if (!isCurrentPolling(generation)) return; if (pollAttempts >= audioConfig.maxPollAttempts || Date.now() >= pollDeadline) { stopPolling(); message('Batas waktu pemeriksaan status transkripsi tercapai. Muat ulang halaman untuk memeriksa kembali.', true); return; } pollAttempts += 1; const controller = new AbortController(); pollController = controller; try { const res = await fetch(audioConfig.status, { headers: { Accept: 'application/json' }, signal: controller.signal }); const data = await res.json().catch(() => ({})); if (!isCurrentPolling(generation)) return; if (!res.ok) throw new Error(data.message || 'Status transkripsi tidak dapat dibaca.'); const status = data.status; const state = byId('processing-state'); if (state) state.textContent = (status || 'unknown').toUpperCase(); if (status === 'completed' || status === 'failed') { stopPolling(); window.location.reload(); return; } if (status === null) { stopPolling(); message('Status transkripsi belum tersedia untuk sesi ini. Muat ulang halaman untuk memeriksa kembali.', true); return; } if (!['queued', 'transcribing'].includes(status)) throw new Error('Status transkripsi tidak dikenali.'); message(status === 'queued' ? 'Audio menunggu giliran diproses.' : 'Whisper sedang memproses audio...'); schedulePoll(generation); } catch (error) { if (error.name === 'AbortError' || !isCurrentPolling(generation)) return; if (pollAttempts >= audioConfig.maxPollAttempts || Date.now() >= pollDeadline) { stopPolling(); message('Status transkripsi belum dapat dipastikan dalam batas waktu yang ditentukan.', true); return; } message('Status belum dapat diperbarui. Mencoba kembali...'); schedulePoll(generation, 5000); } }
const startPolling = () => { if (polling) return; polling = true; pollGeneration += 1; const generation = pollGeneration; pollAttempts = 0; pollDeadline = Date.now() + audioConfig.maxPollDurationMs; poll(generation); };
window.addEventListener('pagehide', stopPolling);
window.addEventListener('beforeunload', stopPolling);
window.addEventListener('pageshow', () => { if (!polling && byId('processing-state')) startPolling(); });

function renderTranscript() {
    const root = byId('transcript-editor');
    if (!root) return;
    root.innerHTML = '';
    segments.forEach((s, i) => {
        const ambiguous = s.reviewStatus === 'ambiguous';
        const row = document.createElement('div');
        row.className = 'rounded-[18px] border p-4 ' + (ambiguous ? 'bg-warning-bg/50 border-warning-border' : 'bg-soft-offwhite border-border-subtle');
        row.innerHTML = `<div class="flex items-center justify-between gap-2 text-[11px] text-text-secondary"><span class="num">${Number(s.startTime).toFixed(1)}–${Number(s.endTime).toFixed(1)} dtk</span>${ambiguous ? '<span class="status status-warn">Perlu diperiksa</span>' : ''}</div>`
            + `<label class="sr-only" for="segment-text-${i}">Teks bagian ${i + 1}</label><textarea id="segment-text-${i}" data-index="${i}" rows="2" class="transcript-text field mt-2 leading-relaxed">${escapeHtml(s.text)}</textarea>`
            + `<div class="grid grid-cols-2 gap-2 mt-2"><label class="sr-only" for="segment-speaker-${i}">Pembicara bagian ${i + 1}</label><select id="segment-speaker-${i}" data-speaker="${i}" class="speaker-role field"><option value="unknown">Pembicara tidak dikenal</option><option value="doctor" ${s.speakerRole === 'doctor' ? 'selected' : ''}>Dokter</option><option value="patient" ${s.speakerRole === 'patient' ? 'selected' : ''}>Pasien</option></select>`
            + `<label class="sr-only" for="segment-status-${i}">Status bagian ${i + 1}</label><select id="segment-status-${i}" data-status="${i}" class="review-status field"><option value="confirmed" ${s.reviewStatus === 'confirmed' ? 'selected' : ''}>Terkonfirmasi</option><option value="ambiguous" ${ambiguous ? 'selected' : ''}>Ambigu</option><option value="edited" ${s.reviewStatus === 'edited' ? 'selected' : ''}>Diedit</option></select></div>`;
        root.appendChild(row);
    });
}
function syncSegments() { document.querySelectorAll('.transcript-text').forEach(el => { segments[el.dataset.index].text = el.value; segments[el.dataset.index].reviewStatus = 'edited'; }); document.querySelectorAll('[data-speaker]').forEach(el => segments[el.dataset.speaker].speakerRole = el.value); document.querySelectorAll('[data-status]').forEach(el => segments[el.dataset.status].reviewStatus = el.value); speakerMap = {}; segments.forEach(s => { if (s.speakerId) speakerMap[s.speakerId] = s.speakerRole; }); }

const recordButton = byId('record-button');
if (recordButton) recordButton.onclick = async () => {
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) return message('Browser tidak mendukung perekaman audio.', true);
    try {
        recordButton.disabled = true;
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const supportedMime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/mp4', 'audio/wav'].find(type => MediaRecorder.isTypeSupported(type));
        if (!supportedMime) { stream.getTracks().forEach(t => t.stop()); throw new Error('Browser tidak menghasilkan format audio yang didukung.'); }
        recorder = new MediaRecorder(stream, { mimeType: supportedMime });
        chunks = [];
        recorder.ondataavailable = e => e.data.size && chunks.push(e.data);
        recorder.onstop = () => {
            stream.getTracks().forEach(t => t.stop());
            stopWave();
            blob = new Blob(chunks, { type: recorder.mimeType || supportedMime });
            show('stop-button', false);
            show('retry-recording', true);
            show('process-button', blob.size > 0);
            setRecordingState('recorded');
            message('Rekaman siap. Tekan Proses Audio untuk mengirimnya.');
        };
        recorder.start();
        startWave(stream);
        startedAt = Date.now();
        timer = setInterval(() => { const seconds = Math.floor((Date.now() - startedAt) / 1000); byId('recording-duration').textContent = formatDuration(seconds); if (seconds >= audioConfig.maxSeconds) byId('stop-button').click(); }, 500);
        show('record-button', false);
        show('stop-button', true);
        byId('stop-button').focus();
        setRecordingState('live');
        message('Rekaman berjalan.');
    } catch (error) {
        // Jangan biarkan mikrofon tetap merekam tanpa tombol Hentikan bila ada langkah yang gagal.
        clearInterval(timer);
        if (recorder?.state === 'recording') recorder.stop();
        message(error.message || 'Izin mikrofon ditolak atau perangkat tidak tersedia.', true);
    } finally {
        recordButton.disabled = false;
    }
};
byId('stop-button')?.addEventListener('click', () => { if (recorder?.state === 'recording') recorder.stop(); clearInterval(timer); stopWave(); });
byId('retry-recording')?.addEventListener('click', () => { blob = null; chunks = []; show('process-button', false); show('retry-recording', false); show('record-button', true); byId('recording-duration').textContent = '00:00'; setRecordingState('ready'); message('Rekaman sebelumnya dibuang.'); });
byId('process-button')?.addEventListener('click', async () => {
    if (!blob || uploading) return;
    if (blob.size > audioConfig.maxBytes) return message('Ukuran audio melebihi batas konfigurasi.', true);
    const extension = extensionForMime(blob.type);
    if (!extension) return message('Format audio tidak didukung oleh aplikasi. Rekam ulang menggunakan browser yang mendukung WebM, OGG, MP4, atau WAV.', true);
    const form = new FormData();
    form.append('audio', blob, `interview-${Date.now()}${extension}`);
    const button = byId('process-button');
    uploading = true; button.disabled = true; byId('retry-recording').disabled = true;
    setRecordingState('uploading');
    message('Mengirim audio ke server...');
    try {
        const res = await fetch(audioConfig.upload, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: form });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || res.status !== 202 || !['queued', 'transcribing'].includes(data.status)) { uploading = false; button.disabled = false; byId('retry-recording').disabled = false; setRecordingState('recorded'); return message(data.message || 'Upload audio gagal.', true); }
        setRecordingState('queued');
        message(data.message || 'Audio tersimpan dan masuk antrean transkripsi.');
        startPolling();
    } catch (error) { uploading = false; button.disabled = false; byId('retry-recording').disabled = false; setRecordingState('recorded'); message('Upload audio gagal karena koneksi ke server terputus.', true); }
});
byId('retry-process')?.addEventListener('click', async () => { const button = byId('retry-process'); button.disabled = true; try { const res = await fetch(audioConfig.retry, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } }); const data = await res.json().catch(() => ({})); if (res.ok && data.status === 'queued') window.location.reload(); else { button.disabled = false; message(data.message || 'Pengulangan pemrosesan gagal.', true); } } catch (error) { button.disabled = false; message('Pengulangan gagal karena koneksi ke server terputus.', true); } });
byId('save-transcript')?.addEventListener('click', async () => { syncSegments(); const button = byId('save-transcript'); button.disabled = true; button.textContent = 'Menyimpan...'; try { const res = await fetch(audioConfig.transcript, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify({ segments, speakerMap }) }); if (res.ok) { window.location.hash = 'soap'; window.location.reload(); } else { button.disabled = false; button.textContent = 'Simpan Review Transkrip'; message('Review transkrip gagal disimpan.', true); } } catch (error) { button.disabled = false; button.textContent = 'Simpan Review Transkrip'; message('Review transkrip gagal karena koneksi ke server terputus.', true); } });
renderTranscript();
@if ($mode === 'processing') startPolling(); @endif
</script>
@endsection
@endif

@extends('layouts.app')

@section('title', 'Wawancara Klinis - MedScribe AI')

@section('content')
@php
    $processingStatus = $wawancara->processing_status;
    $hasTranscript = filled($wawancara->transkrip);
    $isReviewed = $wawancara->transcript_reviewed_at !== null;
    $isVerified = $kunjungan->status === 'SOAP Terverifikasi';
    $mode = $isVerified ? 'completed' : ($wawancara->status === 'Wawancara Selesai' ? 'soap' : ($isReviewed ? 'soap-ready' : ($processingStatus === 'failed' ? 'failed' : ($processingStatus === 'completed' && $hasTranscript ? 'transcript' : (in_array($processingStatus, ['queued', 'transcribing'], true) ? 'processing' : ($wawancara->status === 'Wawancara Berlangsung' ? 'recording' : 'interview'))))));
    $segments = $wawancara->transcript_segments ?? [];
    $rekamMedisUrl = $kunjungan->id_antrean ? route('dokter.rekam-medis', $kunjungan->id_antrean) : route('dokter.dashboard');
    $step = match ($mode) {
        'completed' => 5,
        'soap', 'soap-ready' => 4,
        'transcript', 'failed' => 3,
        default => 2,
    };
@endphp

<div class="space-y-6">
    <div class="bg-[#FFFFFF] p-4 sm:px-6 rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs text-[#69718B]">
                <a href="{{ $rekamMedisUrl }}" class="hover:text-[#5865DC] font-semibold">← Rekam Medis</a>
                <span class="text-[#CBD1EA]">›</span>
                <span class="font-semibold text-[#122056]">{{ $kunjungan->pasien?->nama_pasien }}</span>
                <span class="text-[#CBD1EA]">·</span>
                <span class="font-mono text-[#5865DC]">KJ-{{ str_pad($kunjungan->id_kunjungan, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-1.5 text-[10px] sm:text-[11px] font-semibold">
                @foreach ([1 => 'TTV & Rekam Medis', 2 => 'Wawancara Audio', 3 => 'Review Transkrip', 4 => 'Review SOAP', 5 => 'Penyelesaian'] as $number => $label)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full border {{ $number < $step ? 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0]' : ($number === $step ? 'bg-[#5865DC] text-white border-[#5865DC] shadow-sm' : 'bg-[#FAFAFD] text-[#69718B] border-[#E4E7F4]') }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $number < $step ? 'bg-[#10B981]' : ($number === $step ? 'bg-white' : 'bg-[#CBD1EA]') }}"></span>{{ $number }}. {{ $label }}
                    </span>
                    @if ($number < 5)<span class="text-[#CBD1EA]">›</span>@endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <p class="text-[11px] font-bold tracking-widest text-[#5865DC] uppercase">Langkah {{ $step }} dari 5</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#122056] mt-1">
                {{ match ($mode) {
                    'completed' => 'Kunjungan Selesai',
                    'soap', 'soap-ready' => 'Siap Review SOAP',
                    'transcript' => 'Review Transkrip Wawancara',
                    'failed' => 'Transkripsi Gagal',
                    'processing' => 'Transkrip Sedang Diproses',
                    'recording' => 'Wawancara Audio',
                    default => 'Mulai Wawancara Audio',
                } }}
            </h1>
            <p class="text-sm text-[#69718B] mt-1">
                {{ match ($mode) {
                    'completed' => 'SOAP telah diverifikasi. Kunjungan ini siap ditutup.',
                    'soap', 'soap-ready' => 'Transkrip sudah ditinjau. Lanjutkan ke penyusunan SOAP.',
                    'transcript' => 'Periksa teks dan peran speaker sebelum melanjutkan ke SOAP.',
                    'failed' => 'Audio tidak berhasil diproses. Coba lagi dengan rekaman yang sama.',
                    'processing' => 'Audio sudah tersimpan. Tunggu sampai transkrip siap ditinjau.',
                    'recording' => 'Rekam percakapan dokter-pasien, lalu proses audio menjadi transkrip.',
                    default => 'Mulai sesi audio sebelum melakukan rekaman wawancara klinis.',
                } }}
            </p>
        </div>
        <span class="inline-flex self-start items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold border {{ $mode === 'completed' ? 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0]' : ($mode === 'processing' ? 'bg-[#FFFBEB] text-[#D97706] border-[#FDE68A]' : 'bg-[#EEEEFD] text-[#5865DC] border-[#CBD1EA]') }}">
            <span class="w-2 h-2 rounded-full bg-current {{ $mode === 'processing' ? 'animate-pulse' : '' }}"></span>
            {{ match ($mode) { 'completed' => 'Selesai', 'processing' => 'Diproses', 'failed' => 'Gagal', 'transcript' => 'Perlu Ditinjau', 'soap', 'soap-ready' => 'Siap ke SOAP', default => 'Aktif' } }}
        </span>
    </div>

    @if (session('status'))<div class="rounded-[18px] border border-[#A7F3D0] bg-[#ECFDF5] p-4 text-sm font-medium text-[#059669]">{{ session('status') }}</div>@endif
    @if (session('warning'))<div class="rounded-[18px] border border-[#FDE68A] bg-[#FFFBEB] p-4 text-sm font-medium text-[#D97706]">{{ session('warning') }}</div>@endif
    @if ($errors->any())<div class="rounded-[18px] border border-[#FECACA] bg-[#FEF2F2] p-4 text-sm text-[#DC2626]">{{ $errors->first() }}</div>@endif

    <section class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6">
        <p class="text-[11px] font-bold tracking-widest text-[#5865DC] uppercase">Pasien & kunjungan</p>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-[#122056] mt-1">{{ $kunjungan->pasien?->nama_pasien ?? 'Pasien belum tersedia' }}</h2>
                <p class="text-sm text-[#69718B] mt-1">No. RM {{ $kunjungan->pasien?->no_rekam_medis ?? '-' }} · KJ-{{ str_pad($kunjungan->id_kunjungan, 4, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach ([['TD', $kunjungan->pemeriksaanAwal?->tekanan_darah, 'mmHg'], ['Nadi', $kunjungan->pemeriksaanAwal?->nadi, 'x/menit'], ['Suhu', $kunjungan->pemeriksaanAwal?->suhu_tubuh, '°C'], ['RR', $kunjungan->pemeriksaanAwal?->laju_pernapasan, 'x/menit']] as [$label, $value, $unit])
                    <div class="rounded-xl bg-[#FAFAFD] border border-[#E4E7F4] p-2.5 min-w-[82px]"><span class="text-[11px] text-[#69718B]">{{ $label }}</span><p class="font-bold text-[#20294A] mt-1">{{ filled($value) ? $value.' '.$unit : '-' }}</p></div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($mode === 'interview')
        <section class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-[18px] bg-[#EEEEFD] text-[#5865DC] flex items-center justify-center text-xs font-bold">MIC</div>
            <h2 class="text-xl font-bold text-[#122056] mt-4">Siap memulai wawancara?</h2>
            <p class="max-w-xl mx-auto text-sm text-[#69718B] mt-2">Audio akan diproses secara lokal. Setelah selesai, Anda akan meninjau teks dan speaker sebelum SOAP dibuat.</p>
            <form method="POST" action="{{ route('dokter.interview.start', $kunjungan) }}" class="mt-6">@csrf<button class="inline-flex items-center justify-center rounded-[16px] bg-[#5865DC] hover:bg-[#4854C9] px-6 py-3 text-sm font-bold text-white shadow-[0_4px_16px_rgba(88,101,220,0.28)]">Mulai Wawancara</button></form>
        </section>
    @elseif ($mode === 'processing')
        <section class="bg-[#FFFFFF] rounded-[24px] border border-[#FDE68A] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full border-4 border-[#FDE68A] border-t-[#5865DC] animate-spin"></div>
            <h2 class="text-xl font-bold text-[#122056] mt-5">Transkripsi sedang diproses</h2>
            <p id="processing-message" class="max-w-xl mx-auto text-sm text-[#69718B] mt-2">Audio sudah tersimpan di server. Halaman ini akan diperbarui otomatis saat transkrip tersedia.</p>
            <span id="processing-state" class="inline-flex mt-5 px-3 py-1 rounded-full bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A] text-xs font-bold">{{ strtoupper($processingStatus) }}</span>
        </section>
    @elseif ($mode === 'completed')
        <section class="bg-[#FFFFFF] rounded-[24px] border border-[#A7F3D0] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-[#ECFDF5] text-[#059669] flex items-center justify-center text-2xl">✓</div>
            <h2 class="text-xl font-bold text-[#122056] mt-4">Kunjungan telah selesai</h2>
            <p class="max-w-xl mx-auto text-sm text-[#69718B] mt-2">SOAP sudah diverifikasi oleh dokter. Tidak ada tindakan rekaman atau perubahan transcript yang diperlukan.</p>
            <a href="{{ route('dokter.dashboard') }}" class="inline-flex mt-6 rounded-[16px] bg-[#5865DC] hover:bg-[#4854C9] px-6 py-3 text-sm font-bold text-white">Kembali ke Antrean</a>
        </section>
    @elseif ($mode === 'failed')
        <section class="bg-[#FFFFFF] rounded-[24px] border border-[#FECACA] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-[#FEF2F2] text-[#DC2626] flex items-center justify-center text-2xl">!</div>
            <h2 class="text-xl font-bold text-[#122056] mt-4">Transkripsi gagal diproses</h2>
            <p class="max-w-xl mx-auto text-sm text-[#69718B] mt-2">{{ $wawancara->processing_error ?: 'Service transkripsi tidak berhasil memproses audio.' }} Rekaman asli tetap dipertahankan.</p>
            <button type="button" id="retry-process" class="inline-flex mt-6 rounded-[16px] bg-[#DC2626] hover:bg-[#B91C1C] px-6 py-3 text-sm font-bold text-white">Coba Proses Lagi</button>
        </section>
    @else
        <section class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-5 sm:p-6">
            @if (in_array($mode, ['recording', 'transcript', 'soap-ready', 'soap'], true))
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 pb-4 border-b border-[#E4E7F4]">
                    <div><p class="text-[11px] font-bold tracking-widest text-[#5865DC] uppercase">{{ in_array($mode, ['transcript', 'soap-ready', 'soap'], true) ? 'Review Transkrip' : 'Wawancara Audio' }}</p><h2 class="text-xl font-bold text-[#122056] mt-1">{{ in_array($mode, ['transcript', 'soap-ready', 'soap'], true) ? 'Periksa teks dan speaker' : 'Rekam percakapan klinis' }}</h2></div>
                    @if ($mode === 'recording')<span id="recording-state" class="text-[10px] font-bold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] rounded-lg px-2 py-1">SIAP</span>@endif
                </div>

                @if ($rekamanAudio && $kunjungan->id_antrean)
                    <div class="mt-4 rounded-[18px] bg-[#FAFAFD] border border-[#E4E7F4] p-4">
                        <div class="flex items-center justify-between gap-3 mb-2"><span class="text-xs font-semibold text-[#69718B]">Audio referensi</span><span class="text-[11px] text-[#059669] font-semibold">Audio tersimpan</span></div>
                        <audio controls class="w-full h-10" src="{{ route('dokter.rekam-medis.stream-audio', $kunjungan->id_antrean) }}">Browser tidak mendukung audio.</audio>
                    </div>
                @endif

                @if ($mode === 'recording')
                    <div id="recording-console" class="mt-5 rounded-[22px] bg-[#FAFAFD] border border-[#E4E7F4] p-6 text-center">
                        <div class="font-mono text-3xl font-bold text-[#122056]" id="recording-duration">00:00</div>
                        <p class="text-xs text-[#69718B] mt-1">Durasi wawancara klinis</p>
                        <div class="flex flex-wrap justify-center gap-3 mt-5"><button type="button" id="record-button" class="rounded-[16px] bg-[#5865DC] hover:bg-[#4854C9] px-5 py-3 text-sm font-bold text-white">Mulai Rekaman</button><button type="button" id="stop-button" disabled class="rounded-[16px] bg-[#E4E7F4] px-5 py-3 text-sm font-bold text-[#69718B]">Hentikan</button><button type="button" id="retry-recording" hidden class="rounded-[16px] border border-[#CBD1EA] px-4 py-2.5 text-sm font-bold text-[#5865DC]">Rekam Ulang</button></div>
                        <button type="button" id="process-button" disabled class="mt-4 w-full max-w-md rounded-[16px] bg-[#122056] px-4 py-3 text-sm font-bold text-white disabled:opacity-40">Proses Audio</button>
                        <div id="recording-message" class="text-xs mt-3 text-[#69718B]" role="status"></div>
                    </div>
                @endif

                @if (in_array($mode, ['transcript', 'soap-ready', 'soap'], true))
                    @if ($segments)
                        <div id="transcript-editor" class="mt-5 space-y-3"></div>
                        @if ($mode === 'transcript')<button type="button" id="save-transcript" class="mt-5 w-full rounded-[16px] bg-[#5865DC] hover:bg-[#4854C9] px-4 py-3 text-sm font-bold text-white">Simpan Review Transkrip</button>@endif
                    @else
                        <div class="mt-5 rounded-[18px] border border-[#FECACA] bg-[#FEF2F2] p-4 text-sm text-[#DC2626]">Transkrip kosong atau belum memiliki segmen yang dapat ditinjau.</div>
                    @endif
                    @if ($mode === 'transcript')<p id="transcript-message" class="text-xs text-[#69718B] mt-3" role="status"></p>@endif
                    @if (in_array($mode, ['soap-ready', 'soap'], true))<a href="{{ route('dokter.soap.edit', $kunjungan) }}" class="mt-5 inline-flex w-full justify-center rounded-[16px] bg-[#059669] hover:bg-[#047857] px-4 py-3 text-sm font-bold text-white">Lanjut ke SOAP</a>@endif
                @endif
            @endif
        </section>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $rekamMedisUrl }}" class="inline-flex items-center gap-2 rounded-[14px] bg-[#FFFFFF] hover:bg-[#FAFAFD] border border-[#E4E7F4] px-4 py-2.5 text-xs font-semibold text-[#122056]">← Kembali ke Rekam Medis</a>
        @if ($mode === 'soap-ready')
            <form method="POST" action="{{ route('dokter.interview.finish', $kunjungan) }}">@csrf<button class="rounded-[14px] bg-[#059669] hover:bg-[#047857] px-5 py-2.5 text-xs font-bold text-white">Lanjut ke SOAP</button></form>
        @elseif ($mode === 'soap')
            <a href="{{ route('dokter.soap.edit', $kunjungan) }}" class="rounded-[14px] bg-[#059669] hover:bg-[#047857] px-5 py-2.5 text-xs font-bold text-white">Lanjut ke SOAP</a>
        @endif
    </div>
</div>
@endsection

@section('scripts')
@if (in_array($mode, ['recording', 'processing', 'transcript', 'failed'], true))
<script>
const audioConfig = { upload: @json(route('dokter.interview.audio.upload', $kunjungan)), status: @json(route('dokter.interview.audio.status', $kunjungan)), retry: @json(route('dokter.interview.audio.retry', $kunjungan)), transcript: @json(route('dokter.interview.transcript.update', $kunjungan)), maxSeconds: @json(config('medscribe.audio.max_duration_seconds')), maxBytes: @json(config('medscribe.audio.max_size_kb') * 1024), pollInterval: 2500, maxPollAttempts: @json((int) ceil(config('medscribe.stt.timeout') / 2.5)), maxPollDurationMs: @json((int) config('medscribe.stt.timeout') * 1000) };
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
let recorder, chunks = [], blob = null, timer, startedAt, pollTimer, pollController = null, polling = false, pollGeneration = 0, pollAttempts = 0, pollDeadline = 0, uploading = false, segments = @json($segments), speakerMap = @json($wawancara->speaker_map ?? []);
const message = (text, error = false) => { const el = document.getElementById('recording-message') || document.getElementById('transcript-message') || document.getElementById('processing-message'); if (el) { el.textContent = text; el.className = 'text-xs mt-3 ' + (error ? 'text-[#DC2626]' : 'text-[#69718B]'); } };
const setRecordingState = (text) => { const el = document.getElementById('recording-state'); if (el) el.textContent = text; };
const extensionForMime = (mime) => { const base = (mime || '').split(';', 1)[0].toLowerCase(); return ({ 'audio/webm': '.webm', 'video/webm': '.webm', 'audio/ogg': '.ogg', 'audio/mp4': '.m4a', 'audio/x-m4a': '.m4a', 'audio/wav': '.wav', 'audio/x-wav': '.wav', 'audio/mpeg': '.mp3' })[base] || null; };
const isCurrentPolling = (generation) => polling && generation === pollGeneration;
const stopPolling = () => { polling = false; pollGeneration += 1; if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; } if (pollController) { pollController.abort(); pollController = null; } };
const schedulePoll = (generation, delay = audioConfig.pollInterval) => { if (isCurrentPolling(generation)) pollTimer = setTimeout(() => poll(generation), Math.min(delay, Math.max(0, pollDeadline - Date.now()))); };
async function poll(generation) { if (!isCurrentPolling(generation)) return; if (pollAttempts >= audioConfig.maxPollAttempts || Date.now() >= pollDeadline) { stopPolling(); message('Batas waktu pemeriksaan status transkripsi tercapai. Muat ulang halaman untuk memeriksa kembali.', true); return; } pollAttempts += 1; const controller = new AbortController(); pollController = controller; try { const res = await fetch(audioConfig.status, { headers: { Accept: 'application/json' }, signal: controller.signal }); const data = await res.json().catch(() => ({})); if (!isCurrentPolling(generation)) return; if (!res.ok) throw new Error(data.message || 'Status transkripsi tidak dapat dibaca.'); const status = data.status; const state = document.getElementById('processing-state'); if (state) state.textContent = (status || 'unknown').toUpperCase(); if (status === 'completed' || status === 'failed') { stopPolling(); window.location.reload(); return; } if (status === null) { stopPolling(); message('Status transkripsi belum tersedia untuk sesi ini. Muat ulang halaman untuk memeriksa kembali.', true); return; } if (!['queued', 'transcribing'].includes(status)) throw new Error('Status transkripsi tidak dikenali.'); message(status === 'queued' ? 'Audio masih menunggu queue worker.' : 'Whisper sedang memproses audio...'); schedulePoll(generation); } catch (error) { if (error.name === 'AbortError' || !isCurrentPolling(generation)) return; if (pollAttempts >= audioConfig.maxPollAttempts || Date.now() >= pollDeadline) { stopPolling(); message('Status transkripsi belum dapat dipastikan dalam batas waktu yang ditentukan.', true); return; } message('Status belum dapat diperbarui. Mencoba kembali...'); schedulePoll(generation, 5000); } }
const startPolling = () => { if (polling) return; polling = true; pollGeneration += 1; const generation = pollGeneration; pollAttempts = 0; pollDeadline = Date.now() + audioConfig.maxPollDurationMs; poll(generation); };
window.addEventListener('pagehide', stopPolling);
window.addEventListener('beforeunload', stopPolling);
window.addEventListener('pageshow', () => { if (!polling && document.getElementById('processing-state')) startPolling(); });
function renderTranscript() { const root = document.getElementById('transcript-editor'); if (!root) return; root.innerHTML = ''; segments.forEach((s, i) => { const row = document.createElement('div'); row.className = 'rounded-[16px] border border-[#E4E7F4] bg-[#FAFAFD] p-4'; row.innerHTML = `<div class="flex justify-between text-[11px] text-[#69718B]"><span>${Number(s.startTime).toFixed(1)}s - ${Number(s.endTime).toFixed(1)}s</span><span>${s.reviewStatus === 'ambiguous' ? 'Perlu diperiksa' : 'Tersimpan'}</span></div><textarea data-index="${i}" rows="2" class="transcript-text mt-2 w-full rounded-xl border border-[#CBD1EA] bg-white p-3 text-sm text-[#20294A]">${String(s.text || '').replaceAll('&','&amp;').replaceAll('<','&lt;')}</textarea><div class="grid grid-cols-2 gap-2 mt-2"><select data-speaker="${i}" class="speaker-role rounded-xl border border-[#CBD1EA] bg-white p-2 text-xs"><option value="unknown">Pembicara tidak dikenal</option><option value="doctor" ${s.speakerRole === 'doctor' ? 'selected' : ''}>Dokter</option><option value="patient" ${s.speakerRole === 'patient' ? 'selected' : ''}>Pasien</option></select><select data-status="${i}" class="review-status rounded-xl border border-[#CBD1EA] bg-white p-2 text-xs"><option value="confirmed" ${s.reviewStatus === 'confirmed' ? 'selected' : ''}>Terkonfirmasi</option><option value="ambiguous" ${s.reviewStatus === 'ambiguous' ? 'selected' : ''}>Ambigu</option><option value="edited" ${s.reviewStatus === 'edited' ? 'selected' : ''}>Diedit</option></select></div>`; root.appendChild(row); }); }
function syncSegments() { document.querySelectorAll('.transcript-text').forEach(el => { segments[el.dataset.index].text = el.value; segments[el.dataset.index].reviewStatus = 'edited'; }); document.querySelectorAll('[data-speaker]').forEach(el => segments[el.dataset.speaker].speakerRole = el.value); document.querySelectorAll('[data-status]').forEach(el => segments[el.dataset.status].reviewStatus = el.value); speakerMap = {}; segments.forEach(s => { if (s.speakerId) speakerMap[s.speakerId] = s.speakerRole; }); }
const recordButton = document.getElementById('record-button');
if (recordButton) recordButton.onclick = async () => { if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) return message('Browser tidak mendukung perekaman audio.', true); try { const stream = await navigator.mediaDevices.getUserMedia({ audio: true }); const supportedMime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/mp4', 'audio/wav'].find(type => MediaRecorder.isTypeSupported(type)); if (!supportedMime) { stream.getTracks().forEach(t => t.stop()); throw new Error('Browser tidak menghasilkan format audio yang didukung.'); } recorder = new MediaRecorder(stream, { mimeType: supportedMime }); chunks = []; recorder.ondataavailable = e => e.data.size && chunks.push(e.data); recorder.onstop = () => { stream.getTracks().forEach(t => t.stop()); blob = new Blob(chunks, { type: recorder.mimeType || supportedMime }); document.getElementById('process-button').disabled = !blob.size; document.getElementById('retry-recording').hidden = false; setRecordingState('SIAP DIPROSES'); message('Audio tersedia. Tekan Proses Audio untuk mengirimkannya.'); }; recorder.start(); startedAt = Date.now(); timer = setInterval(() => { const seconds = Math.floor((Date.now() - startedAt) / 1000); document.getElementById('recording-duration').textContent = `${String(Math.floor(seconds / 60)).padStart(2,'0')}:${String(seconds % 60).padStart(2,'0')}`; if (seconds >= audioConfig.maxSeconds) document.getElementById('stop-button').click(); }, 500); recordButton.disabled = true; document.getElementById('stop-button').disabled = false; setRecordingState('MEREKAM'); message('Rekaman aktif.'); } catch (error) { message(error.message || 'Izin mikrofon ditolak atau perangkat tidak tersedia.', true); } };
document.getElementById('stop-button')?.addEventListener('click', () => { if (recorder?.state === 'recording') recorder.stop(); clearInterval(timer); document.getElementById('stop-button').disabled = true; recordButton.disabled = false; });
document.getElementById('retry-recording')?.addEventListener('click', () => { blob = null; chunks = []; document.getElementById('process-button').disabled = true; document.getElementById('retry-recording').hidden = true; setRecordingState('SIAP'); message('Rekaman lokal direset.'); });
document.getElementById('process-button')?.addEventListener('click', async () => { if (!blob || uploading) return; if (blob.size > audioConfig.maxBytes) return message('Ukuran audio melebihi batas konfigurasi.', true); const extension = extensionForMime(blob.type); if (!extension) return message('Format audio tidak didukung oleh aplikasi. Rekam ulang menggunakan browser yang mendukung WebM, OGG, MP4, atau WAV.', true); const form = new FormData(); form.append('audio', blob, `interview-${Date.now()}${extension}`); const button = document.getElementById('process-button'); uploading = true; button.disabled = true; setRecordingState('MENGUNGGAH'); message('Mengirim audio ke server...'); try { const res = await fetch(audioConfig.upload, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: form }); const data = await res.json().catch(() => ({})); if (!res.ok || res.status !== 202 || !['queued', 'transcribing'].includes(data.status)) { uploading = false; button.disabled = false; return message(data.message || 'Upload audio gagal.', true); } setRecordingState('DIPROSES'); message(data.message || 'Audio tersimpan dan masuk antrean transkripsi.'); startPolling(); } catch (error) { uploading = false; button.disabled = false; message('Upload audio gagal karena koneksi ke server terputus.', true); } });
document.getElementById('retry-process')?.addEventListener('click', async () => { const button = document.getElementById('retry-process'); button.disabled = true; try { const res = await fetch(audioConfig.retry, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } }); const data = await res.json().catch(() => ({})); if (res.ok && data.status === 'queued') window.location.reload(); else { button.disabled = false; message(data.message || 'Pengulangan pemrosesan gagal.', true); } } catch (error) { button.disabled = false; message('Pengulangan gagal karena koneksi ke server terputus.', true); } });
document.getElementById('save-transcript')?.addEventListener('click', async () => { syncSegments(); const button = document.getElementById('save-transcript'); button.disabled = true; button.textContent = 'Menyimpan...'; try { const res = await fetch(audioConfig.transcript, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify({ segments, speakerMap }) }); if (res.ok) window.location.reload(); else { button.disabled = false; button.textContent = 'Simpan Review Transkrip'; message('Review transcript gagal disimpan.', true); } } catch (error) { button.disabled = false; button.textContent = 'Simpan Review Transkrip'; message('Review transcript gagal karena koneksi ke server terputus.', true); } });
renderTranscript();
@if ($mode === 'processing') startPolling(); @endif
</script>
@endif
@endsection

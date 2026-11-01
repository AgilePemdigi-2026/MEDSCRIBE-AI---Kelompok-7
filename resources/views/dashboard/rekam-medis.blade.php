@extends('layouts.app')

@section('title', 'Rekam Medis Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
@php
    $demam = $pemeriksaanAwal && $pemeriksaanAwal->suhu_tubuh && $pemeriksaanAwal->suhu_tubuh > 37.5;
@endphp
<div class="space-y-6">

    {{-- Breadcrumb + tahapan klinis --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rise">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-text-secondary">
            @include('partials.back-link', ['href' => route('dokter.dashboard'), 'label' => 'Dashboard Dokter'])
            <span class="text-border-hover" aria-hidden="true">›</span>
            <span class="font-semibold text-medical-navy" aria-current="page">{{ $pasien->nama_pasien }}</span>
            <span class="tag-id">{{ $idKunjunganCode }}</span>
        </nav>

        <ol class="pill-nav self-start md:self-auto text-xs" aria-label="Tahapan pemeriksaan">
            <li class="inline-flex items-center gap-1.5 min-h-9 px-3.5 rounded-full bg-pale-lavender text-royal-indigo-hover font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                1. TTV &amp; Anamnesis
            </li>
            <li class="inline-flex items-center gap-1.5 min-h-9 px-3.5 rounded-full font-medium {{ $rekamanAudio ? 'bg-success-bg text-success-text' : 'bg-medical-navy text-white' }}" @if (!$rekamanAudio) aria-current="step" @endif>
                @if ($rekamanAudio)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                @endif
                2. Rekaman Klinis
            </li>
            <li class="inline-flex items-center min-h-9 px-3.5 rounded-full text-text-secondary font-medium">3. Selesai</li>
        </ol>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Kolom kiri: identitas pasien + TTV --}}
        <div class="lg:col-span-5 space-y-6">
            <section class="card-lavender spotlight p-6 rise" style="--i: 1" aria-label="Identitas pasien">
                <div class="relative flex items-start gap-4">
                    <span class="avatar w-16 h-16 text-2xl bg-white text-royal-indigo shadow-[0_10px_24px_-10px_rgba(88,101,220,0.6)]" aria-hidden="true">
                        {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">{{ $pasien->nama_pasien }}</h1>
                        <p class="text-sm text-text-body/80">
                            @if ($umur)
                                {{ $umur }} tahun
                                @if ($pasien->tanggal_lahir)
                                    ({{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->format('d/m/Y') }})
                                @endif
                            @else
                                -
                            @endif
                            · {{ $pasien->jenis_kelamin ?? '-' }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="tag-id bg-white">RM: {{ $pasien->no_rekam_medis }}</span>
                            <span class="tag-id tag-id-muted bg-white/80">Antrean A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                </div>
                <dl class="relative mt-5 grid grid-cols-2 gap-2">
                    <div class="rounded-[16px] bg-white/80 border border-border-hover p-3">
                        <dt class="text-[11px] text-text-secondary">Tgl Kunjungan</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-medical-navy">{{ \Carbon\Carbon::parse($antrean->tanggal_kunjungan)->format('d F Y') }}</dd>
                    </div>
                    <div class="rounded-[16px] bg-white/80 border border-border-hover p-3">
                        <dt class="text-[11px] text-text-secondary">Dokter Pemeriksa</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-medical-navy truncate">{{ $user->nama_lengkap }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card p-5 sm:p-6 space-y-4 rise" style="--i: 2" aria-labelledby="judulTTV">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="judulTTV" class="text-base font-semibold text-medical-navy">TTV &amp; Catatan Perawat</h2>
                        <p class="text-xs text-text-secondary mt-0.5">Pemeriksaan tanda-tanda vital awal Nurse Station</p>
                    </div>
                    @if ($pemeriksaanAwal)
                        <span class="status status-ok">Terverifikasi oleh Perawat</span>
                    @else
                        <span class="status status-warn">Belum Ada Data TTV</span>
                    @endif
                </div>

                @if ($pemeriksaanAwal)
                    @if ($pemeriksaanAwal->perawat)
                        <p class="flex items-center gap-2 text-[11px] text-text-secondary">
                            <span class="avatar w-6 h-6 bg-medical-navy text-white text-[10px]" aria-hidden="true">{{ strtoupper(substr($pemeriksaanAwal->perawat->nama_lengkap, 0, 1)) }}</span>
                            {{ $pemeriksaanAwal->perawat->nama_lengkap }}
                            @if ($pemeriksaanAwal->created_at)
                                · {{ $pemeriksaanAwal->created_at->timezone('Asia/Jakarta')->format('H:i') }} WIB
                            @endif
                        </p>
                    @endif

                    <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-2.5">
                        <div class="vital"><dt class="vital-label">Tekanan Darah</dt><dd class="vital-value">{{ $pemeriksaanAwal->tekanan_darah ?? '-' }}<span class="vital-unit">mmHg</span></dd></div>
                        <div class="vital"><dt class="vital-label">Denyut Nadi</dt><dd class="vital-value">{{ $pemeriksaanAwal->nadi ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                        <div class="vital {{ $demam ? 'is-flagged' : '' }}"><dt class="vital-label">Suhu Tubuh</dt><dd class="vital-value">{{ $pemeriksaanAwal->suhu_tubuh ?? '-' }}<span class="vital-unit">°C</span></dd></div>
                        <div class="vital"><dt class="vital-label">Pernapasan (RR)</dt><dd class="vital-value">{{ $pemeriksaanAwal->laju_pernapasan ?? '-' }}<span class="vital-unit">x/menit</span></dd></div>
                        <div class="vital"><dt class="vital-label">Saturasi SpO2</dt><dd class="vital-value">{{ $pemeriksaanAwal->saturasi_oksigen ? $pemeriksaanAwal->saturasi_oksigen . '%' : '-' }}</dd></div>
                        <div class="vital"><dt class="vital-label">Berat Badan</dt><dd class="vital-value">{{ $pemeriksaanAwal->berat_badan ?? '-' }}<span class="vital-unit">kg</span></dd></div>
                        <div class="vital"><dt class="vital-label">Tinggi Badan</dt><dd class="vital-value">{{ $pemeriksaanAwal->tinggi_badan ?? '-' }}<span class="vital-unit">cm</span></dd></div>
                    </dl>

                    @if ($pemeriksaanAwal->catatan)
                        <div class="p-4 rounded-[18px] bg-pale-lavender border border-border-hover text-sm">
                            <span class="font-semibold text-medical-navy block mb-1">Catatan Awal Perawat:</span>
                            <p class="text-text-body leading-relaxed">{{ $pemeriksaanAwal->catatan }}</p>
                        </div>
                    @endif
                @else
                    <div class="py-8 text-center text-xs text-text-secondary bg-soft-offwhite rounded-[18px] border border-dashed border-border-hover">
                        Pasien ini belum memiliki catatan pemeriksaan awal TTV dari Nurse Station.
                    </div>
                @endif
            </section>
        </div>

        {{-- Kolom kanan: perekam wawancara dengan cincin timer --}}
        <section class="card spotlight lg:col-span-7 rise" style="--i: 3" aria-labelledby="judulRekam">
            <div class="relative flex flex-col sm:flex-row sm:items-start justify-between gap-3 p-5 sm:p-6">
                <div>
                    <h2 id="judulRekam" class="text-lg font-semibold text-medical-navy">Rekam Wawancara Klinis</h2>
                    <p class="text-xs text-text-secondary mt-0.5">Rekam dialog anamnesis dan wawancara klinis dokter-pasien secara langsung.</p>
                </div>
                <div id="recordingStatusBadge" aria-live="polite">
                    <span id="badgeRecordingText" class="status status-active">Mikrofon Siap</span>
                </div>
            </div>

            <div class="relative px-5 sm:px-8 pb-8 flex flex-col items-center text-center gap-6">
                {{-- Cincin: terisi penuh setiap satu menit rekaman --}}
                <div class="relative w-60 h-60">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 200 200" aria-hidden="true">
                        <circle class="ring-track" cx="100" cy="100" r="88" fill="none" stroke-width="12" />
                        <circle id="timerRing" class="ring-value" cx="100" cy="100" r="88" fill="none" stroke-width="12" stroke-linecap="round"
                                stroke-dasharray="552.92" stroke-dashoffset="552.92" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div id="timerDisplay" class="text-[2.6rem] font-semibold tracking-[-0.04em] text-medical-navy num" aria-label="Durasi rekaman">00:00</div>
                        <div id="timerSubtitle" class="text-[11px] text-text-secondary max-w-[9rem] leading-snug">Durasi Wawancara Klinis</div>
                    </div>
                </div>

                <div class="relative w-full max-w-md h-16 rounded-[18px] bg-soft-offwhite border border-border-subtle overflow-hidden">
                    <canvas id="waveformCanvas" width="440" height="64" class="w-full h-full" aria-hidden="true"></canvas>
                    <p id="visualizerPlaceholder" class="absolute inset-0 flex items-center justify-center gap-2 px-4 text-xs text-text-secondary pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-royal-indigo" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                        Visualizer suara aktif saat rekaman dimulai
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3">
                    <button type="button" id="btnMulaiRekam" onclick="startClinicalRecording()" class="btn btn-primary min-h-12 px-7">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                        Mulai Rekam
                    </button>
                    <button type="button" id="btnStopRekam" onclick="stopClinicalRecording()" class="hidden btn btn-danger min-h-12 px-7">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="5" y="5" width="10" height="10" rx="2" /></svg>
                        Stop Rekam
                    </button>
                    <button type="button" id="btnRekamUlang" onclick="restartRecordingSession()" class="hidden btn btn-secondary min-h-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Rekam Ulang
                    </button>
                </div>

                <div id="audioPlayerContainer" class="{{ $rekamanAudio ? '' : 'hidden' }} w-full max-w-md text-left space-y-2">
                    <div class="text-[11px] font-semibold text-text-secondary flex items-center justify-between">
                        <span>Pratinjau Hasil Rekaman Audio</span>
                        @if ($rekamanAudio)
                            <span class="status status-ok">Audio Tersimpan di Server</span>
                        @endif
                    </div>
                    <audio id="audioPreview" controls class="w-full h-10">
                        @if ($rekamanAudio)
                            <source src="{{ route('dokter.rekam-medis.stream-audio', $antrean->id_antrean) }}" type="audio/webm">
                        @endif
                        Browser Anda tidak mendukung elemen pemutar audio.
                    </audio>
                </div>

                <div id="recordFeedbackAlert" class="hidden w-full max-w-md text-left" role="status">
                    <span id="recordFeedbackMessage" class="flex-1"></span>
                    <button type="button" onclick="document.getElementById('recordFeedbackAlert').classList.add('hidden')" class="btn btn-ghost btn-icon btn-sm -my-2 -mr-2" aria-label="Tutup pesan">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <p class="relative flex items-center gap-2 px-5 sm:px-6 py-4 border-t border-border-subtle text-xs text-text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-royal-indigo flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                Rekaman audio tersimpan di repositori privat terlindungi untuk dokumentasi rekam medis.
            </p>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const uploadAudioUrl = "{{ route('dokter.rekam-medis.upload-audio', $antrean->id_antrean) }}";
    const csrfToken = "{{ csrf_token() }}";
    const RING_LENGTH = 552.92;

    let mediaRecorder = null;
    let audioStream = null;
    let recordedChunks = [];
    let recordStartTime = 0;
    let timerInterval = null;
    let isRecording = false;

    let audioCtx = null;
    let analyserNode = null;
    let audioSource = null;
    let animFrameId = null;

    const BADGE = {
        ready: ['status status-active', 'Mikrofon Siap'],
        permission: ['status status-warn', 'Menunggu Izin Mikrofon...'],
        recording: ['status status-danger', 'Sedang Merekam Wawancara Klinis'],
        saving: ['status status-warn', 'Menyimpan Rekaman...'],
        saved: ['status status-ok', 'Rekaman Tersimpan'],
        failed: ['status status-danger', 'Gagal Menyimpan'],
    };

    function setBadge(state) {
        const el = document.getElementById('badgeRecordingText');
        el.className = BADGE[state][0] + (state === 'recording' ? ' is-live' : '');
        el.textContent = BADGE[state][1];
    }

    function setRing(seconds, recording) {
        const ring = document.getElementById('timerRing');
        if (!ring) return;
        const fraction = seconds === 0 ? 0 : ((seconds % 60) || 60) / 60;
        ring.style.strokeDashoffset = String(RING_LENGTH * (1 - fraction));
        ring.classList.toggle('is-recording', !!recording);
    }

    async function startClinicalRecording() {
        if (isRecording) return;

        const btnMulai = document.getElementById('btnMulaiRekam');
        const btnStop = document.getElementById('btnStopRekam');
        const btnUlang = document.getElementById('btnRekamUlang');
        const timerSub = document.getElementById('timerSubtitle');
        const placeholder = document.getElementById('visualizerPlaceholder');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showFeedback('error', 'Browser Anda tidak mendukung Web Audio / MediaRecorder API. Gunakan browser modern (Chrome, Edge, Firefox).');
            return;
        }

        if (window.isSecureContext === false && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            showFeedback('warning', 'Akses mikrofon browser memerlukan koneksi aman (HTTPS) atau origin localhost.');
            return;
        }

        try {
            setBadge('permission');
            btnMulai.disabled = true;

            audioStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                }
            });

            const supportedTypes = [
                'audio/webm;codecs=opus',
                'audio/webm',
                'audio/ogg;codecs=opus',
                'audio/mp4',
                'audio/wav'
            ];
            let selectedMime = '';
            for (const type of supportedTypes) {
                if (MediaRecorder.isTypeSupported(type)) {
                    selectedMime = type;
                    break;
                }
            }

            mediaRecorder = new MediaRecorder(audioStream, selectedMime ? { mimeType: selectedMime } : {});
            recordedChunks = [];

            mediaRecorder.ondataavailable = function(e) {
                if (e.data && e.data.size > 0) {
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = async function() {
                handleRecordingFinished(selectedMime);
            };

            mediaRecorder.start(250);
            isRecording = true;
            recordStartTime = Date.now();

            timerInterval = setInterval(updateTimerDisplay, 100);

            startAudioVisualizer(audioStream);

            btnMulai.classList.add('hidden');
            btnMulai.disabled = false;
            btnStop.classList.remove('hidden');
            btnStop.focus();
            btnUlang.classList.add('hidden');

            setBadge('recording');
            setRing(0, true);
            timerSub.textContent = 'Merekam suara dokter & pasien secara langsung...';
            if (placeholder) placeholder.classList.add('hidden');

            showFeedback('info', 'Perekaman aktif. Silakan lakukan anamnesis klinis dengan pasien.');

        } catch (err) {
            btnMulai.disabled = false;
            setBadge('ready');

            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                showFeedback('error', 'Akses mikrofon ditolak. Izinkan akses mikrofon melalui pengaturan izin situs browser (ikon gembok di bilah alamat), lalu coba kembali.');
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                showFeedback('error', 'Perangkat mikrofon tidak ditemukan. Pastikan mikrofon terpasang pada komputer Anda.');
            } else {
                showFeedback('error', 'Gagal mengakses mikrofon: ' + err.message);
            }
        }
    }

    function stopClinicalRecording() {
        if (!mediaRecorder || !isRecording) return;

        const btnStop = document.getElementById('btnStopRekam');
        const timerSub = document.getElementById('timerSubtitle');

        btnStop.disabled = true;
        isRecording = false;

        clearInterval(timerInterval);
        stopAudioVisualizer();

        setBadge('saving');
        document.getElementById('timerRing')?.classList.remove('is-recording');
        timerSub.textContent = 'Memfinalisasi berkas audio dan mengunggah ke server...';

        mediaRecorder.stop();

        // Lepas seluruh track audio mikrofon agar lampu indikator mic mati.
        if (audioStream) {
            audioStream.getTracks().forEach(track => track.stop());
            audioStream = null;
        }
    }

    async function handleRecordingFinished(mimeType) {
        const btnMulai = document.getElementById('btnMulaiRekam');
        const btnStop = document.getElementById('btnStopRekam');
        const btnUlang = document.getElementById('btnRekamUlang');
        const audioPlayerCont = document.getElementById('audioPlayerContainer');
        const audioPreview = document.getElementById('audioPreview');
        const timerSub = document.getElementById('timerSubtitle');

        btnStop.classList.add('hidden');
        btnStop.disabled = false;
        btnMulai.classList.remove('hidden');
        btnUlang.classList.remove('hidden');

        if (recordedChunks.length === 0) {
            showFeedback('error', 'Tidak ada data audio yang tertangkap. Silakan periksa volume mikrofon Anda.');
            return;
        }

        const audioBlob = new Blob(recordedChunks, { type: mimeType || 'audio/webm' });
        const audioUrl = URL.createObjectURL(audioBlob);

        audioPreview.src = audioUrl;
        audioPlayerCont.classList.remove('hidden');

        showFeedback('info', 'Mengunggah rekaman audio ke server...');

        try {
            const formData = new FormData();
            formData.append('audio', audioBlob, 'wawancara_klinis.webm');

            const response = await fetch(uploadAudioUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                setBadge('saved');
                timerSub.textContent = 'Audio wawancara klinis berhasil disimpan di server.';
                showFeedback('success', result.message || 'Rekaman wawancara klinis berhasil disimpan di server.');
            } else {
                setBadge('failed');
                showFeedback('error', result.message || 'Gagal menyimpan rekaman ke server.');
            }
        } catch (uploadErr) {
            showFeedback('error', 'Terjadi kendala jaringan saat mengunggah audio: ' + uploadErr.message);
        }
    }

    function restartRecordingSession() {
        if (isRecording) stopClinicalRecording();

        clearInterval(timerInterval);
        stopAudioVisualizer();

        document.getElementById('timerDisplay').textContent = '00:00';
        document.getElementById('timerSubtitle').textContent = 'Durasi Wawancara Klinis';
        document.getElementById('btnRekamUlang').classList.add('hidden');
        document.getElementById('btnMulaiRekam').classList.remove('hidden');
        document.getElementById('btnStopRekam').classList.add('hidden');

        setBadge('ready');
        setRing(0, false);

        const placeholder = document.getElementById('visualizerPlaceholder');
        if (placeholder) placeholder.classList.remove('hidden');

        showFeedback('info', 'Sesi rekaman direset. Anda dapat menekan Mulai Rekam kembali.');
        document.getElementById('btnMulaiRekam').focus();
    }

    function updateTimerDisplay() {
        if (!isRecording) return;
        const elapsed = Math.floor((Date.now() - recordStartTime) / 1000);
        const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
        const secs = String(elapsed % 60).padStart(2, '0');
        document.getElementById('timerDisplay').textContent = `${mins}:${secs}`;
        setRing(elapsed, true);
    }

    function startAudioVisualizer(stream) {
        const canvas = document.getElementById('waveformCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyserNode = audioCtx.createAnalyser();
            analyserNode.fftSize = 64;

            audioSource = audioCtx.createMediaStreamSource(stream);
            audioSource.connect(analyserNode);

            const bufferLen = analyserNode.frequencyBinCount;
            const dataArr = new Uint8Array(bufferLen);

            function renderWaveform() {
                if (!isRecording) return;
                animFrameId = requestAnimationFrame(renderWaveform);

                analyserNode.getByteFrequencyData(dataArr);
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const barWidth = (canvas.width / bufferLen) * 1.5;
                let x = 4;

                for (let i = 0; i < bufferLen; i++) {
                    const normHeight = (dataArr[i] / 255);
                    const barHeight = Math.max(4, normHeight * (canvas.height - 10));

                    // Palet Royal Indigo MedScribe AI #5865DC
                    ctx.fillStyle = normHeight > 0.6 ? '#5865DC' : '#818CF8';
                    const y = (canvas.height - barHeight) / 2;

                    if (ctx.roundRect) {
                        ctx.beginPath();
                        ctx.roundRect(x, y, Math.max(3, barWidth - 3), barHeight, 2);
                        ctx.fill();
                    } else {
                        ctx.fillRect(x, y, Math.max(3, barWidth - 3), barHeight);
                    }

                    x += barWidth + 3;
                }
            }

            renderWaveform();
        } catch (e) {
            console.warn('AudioContext visualizer not supported or blocked:', e);
        }
    }

    function stopAudioVisualizer() {
        if (animFrameId) cancelAnimationFrame(animFrameId);
        if (audioCtx && audioCtx.state !== 'closed') {
            audioCtx.close().catch(() => {});
        }
        const canvas = document.getElementById('waveformCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    function showFeedback(type, message) {
        const alertBox = document.getElementById('recordFeedbackAlert');
        const msgEl = document.getElementById('recordFeedbackMessage');
        if (!alertBox || !msgEl) return;

        const variant = { success: 'alert-ok', error: 'alert-danger', warning: 'alert-warn' }[type] || 'alert-info';
        alertBox.className = 'alert ' + variant + ' items-center w-full max-w-md text-left';
        alertBox.setAttribute('role', type === 'error' ? 'alert' : 'status');
        msgEl.textContent = message;
    }

    // Cegah dokter meninggalkan halaman secara tidak sengaja saat rekaman masih berlangsung.
    window.addEventListener('beforeunload', function(e) {
        if (isRecording) {
            e.preventDefault();
            e.returnValue = 'Rekaman wawancara klinis sedang berlangsung. Jika Anda meninggalkan halaman ini, rekaman akan hilang.';
            return e.returnValue;
        }
    });
</script>
@endsection

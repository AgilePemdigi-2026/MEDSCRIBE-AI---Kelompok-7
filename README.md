# MedScribe AI

MedScribe adalah aplikasi Laravel untuk alur pemeriksaan awal, wawancara medis, transkripsi lokal, review speaker, dan draf SOAP yang tetap harus diverifikasi dokter.

## Arsitektur audio dan privasi

- Browser merekam dengan `MediaRecorder` dan mengunggah file multipart ke Laravel lokal.
- Laravel menjalankan job queue `ProcessInterviewAudio` yang memanggil API lokal `services/audio_processor/app.py`.
- Service memakai Transformers/PyTorch dengan default `openai/whisper-small`, mendeteksi CPU/GPU saat runtime, dan memuat model sekali selama service hidup. Audio tidak dikirim ke layanan STT cloud.
- Segmen disimpan dengan timestamp, `speakerId`, `speakerRole`, dan status review. Identitas dokter/pasien harus dipetakan manual.
- File audio disimpan sementara pada disk lokal dan kebijakan retensi dikonfigurasi dengan `MEDSCRIBE_AUDIO_RETENTION_HOURS`. Penghapusan terjadwal perlu dijalankan sesuai kebijakan deployment.
- Jangan gunakan data pasien sungguhan pada pengembangan awal. Transkrip dan konteks klinis yang dikirim ke OpenRouter meninggalkan komputer lokal dan memerlukan persetujuan/kebijakan yang sesuai.

## Prasyarat

- PHP 8.3+, Composer, Node.js/npm, dan SQLite atau database Laravel yang dikonfigurasi.
- Python 3.10-3.12 dan FFmpeg pada `PATH`.
- Ruang disk dan RAM yang cukup untuk model yang dipilih. CPU adalah baseline tetapi tidak dijamin realtime; GPU kompatibel hanya digunakan bila tersedia dan terdeteksi.

## Instalasi

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
python -m venv .venv
.venv\Scripts\activate
pip install -r services/audio_processor/requirements.txt
```

Linux/macOS memakai `.venv/bin/activate`. FFmpeg harus tersedia melalui `ffmpeg -version`.

Model `MEDSCRIBE_STT_MODEL` diunduh dan dicache saat service STT pertama kali dijalankan. Default `openai/whisper-small` dipakai untuk validasi awal. `MEDSCRIBE_STT_DEVICE=auto` mendeteksi CUDA, MPS, atau CPU; gunakan `cpu`, `cuda`, atau `mps` hanya jika sudah diverifikasi pada perangkat target. PyTorch/Transformers dapat membutuhkan RAM besar, dan CPU fallback dapat lebih lambat daripada durasi audio.

## Menjalankan

Jalankan worker Laravel terpisah dari server web:

```bash
php artisan serve --host=127.0.0.1
php artisan queue:work --queue=default
python services/audio_processor/app.py
npm run dev
```

Jaga server Laravel dan STT tetap bind ke `127.0.0.1` bila deployment hanya untuk komputer lokal. Status transkripsi dipantau frontend melalui polling. Audio diproses oleh service lokal dan tidak dikirim ke OpenRouter; hanya transkrip yang sudah ditinjau dan TTV minimum yang dapat dikirim ke SOAP AI OpenRouter.

Jalankan `php artisan medscribe:purge-audio` melalui Task Scheduler/cron sesuai kebijakan retensi untuk menghapus file audio kedaluwarsa.

## Generasi SOAP OpenRouter

Untuk mengaktifkan generasi berbasis teks:

```dotenv
MEDSCRIBE_SOAP_DRIVER=openrouter
OPENROUTER_API_KEY=...
OPENROUTER_MODEL=nvidia/nemotron-3-ultra-550b-a55b:free
```

Key hanya boleh berada di environment backend dan tidak boleh masuk bundle frontend atau Git. Aplikasi tidak berpindah diam-diam ke model berbayar. Jika key, model gratis, jaringan, atau kuota tidak tersedia, error ditampilkan dan draf sebelumnya tidak ditimpa. Audio tidak pernah dikirim ke OpenRouter.

## Test dan troubleshooting

```bash
php artisan test
npm run build
```

- `Service STT tidak tersedia`: aktifkan virtualenv, install requirements, jalankan `python services/audio_processor/app.py`, lalu periksa `http://127.0.0.1:8765/health`.
- `Model gagal dimuat`: pastikan nama model valid, cache dapat ditulis, internet tersedia saat download pertama, dan RAM mencukupi.
- `CPU lambat`: ukur rasio waktu inferensi terhadap durasi audio pada laptop target sebelum memilih model lebih besar.
- `FFmpeg` error: pastikan executable `ffmpeg` ada di `PATH`.
- Job tetap `queued`: jalankan `php artisan queue:work`.
- Audio kosong/rusak: rekam ulang dengan mikrofon yang tersedia dan format browser yang didukung.
- `HTTP 403` atau CSRF: gunakan sesi login dokter yang valid dan jangan menghapus middleware CSRF/role.
- Rate limit OpenRouter: tunggu kuota pulih atau tinjau SOAP manual; aplikasi tidak memilih model berbayar.

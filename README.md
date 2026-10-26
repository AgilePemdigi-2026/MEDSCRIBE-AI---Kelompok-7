# MedScribe AI

MedScribe adalah aplikasi Laravel untuk alur pemeriksaan awal, wawancara medis, transkripsi lokal, review speaker, dan draf SOAP yang tetap harus diverifikasi dokter.

## Arsitektur audio dan privasi

- Browser merekam dengan `MediaRecorder` dan mengunggah file multipart ke Laravel lokal.
- Laravel menjalankan job queue `ProcessInterviewAudio` yang memanggil `services/audio_processor/process_audio.py`.
- `faster-whisper` dan `pyannote.audio` berjalan lokal. Audio tidak dikirim ke OpenRouter atau layanan inference cloud.
- Segmen disimpan dengan timestamp, `speakerId`, `speakerRole`, dan status review. Identitas dokter/pasien harus dipetakan manual.
- File audio disimpan sementara pada disk lokal dan kebijakan retensi dikonfigurasi dengan `MEDSCRIBE_AUDIO_RETENTION_HOURS`. Penghapusan terjadwal perlu dijalankan sesuai kebijakan deployment.
- Jangan gunakan data pasien sungguhan pada pengembangan awal. Transkrip dan konteks klinis yang dikirim ke OpenRouter meninggalkan komputer lokal dan memerlukan persetujuan/kebijakan yang sesuai.

## Prasyarat

- PHP 8.3+, Composer, Node.js/npm, dan SQLite atau database Laravel yang dikonfigurasi.
- Python 3.10+ dan FFmpeg pada `PATH`.
- Ruang disk dan RAM yang cukup untuk model Whisper/pyannote. CPU didukung tetapi lebih lambat; gunakan `MEDSCRIBE_WHISPER_DEVICE=cuda` dan compute type yang sesuai jika GPU NVIDIA tersedia.

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

Model faster-whisper diunduh dan dicache oleh library saat pertama kali dipakai. Pilih ukuran melalui `MEDSCRIBE_WHISPER_MODEL` (`tiny`, `base`, `small`, atau ukuran lain yang didukung). Pyannote `speaker-diarization-3.1` memerlukan akun Hugging Face, token `HF_TOKEN`, dan persetujuan syarat model. Token hanya dipakai untuk akses model lokal, bukan pengiriman audio inference.

## Menjalankan

Jalankan worker Laravel terpisah dari server web:

```bash
php artisan serve --host=127.0.0.1
php artisan queue:work --queue=default
npm run dev
```

Jaga server tetap bind ke `127.0.0.1` bila deployment hanya untuk komputer lokal. Status transkripsi dipantau frontend melalui polling.

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

- `faster-whisper belum terpasang`: aktifkan virtualenv dan install requirements.
- `FFmpeg` error: pastikan executable `ffmpeg` ada di `PATH`.
- `HF_TOKEN diperlukan`: buat token Hugging Face, set environment variable, dan terima model card pyannote.
- Job tetap `queued`: jalankan `php artisan queue:work`.
- Audio kosong/rusak: rekam ulang dengan mikrofon yang tersedia dan format browser yang didukung.
- Rate limit OpenRouter: tunggu kuota pulih atau tinjau SOAP manual; aplikasi tidak memilih model berbayar.

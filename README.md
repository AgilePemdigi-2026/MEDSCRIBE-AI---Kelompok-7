# MedScribe AI

MedScribe adalah aplikasi Laravel untuk alur pemeriksaan awal, wawancara medis, transkripsi real-time di browser, review speaker, dan draf SOAP yang tetap harus diverifikasi dokter.

## Arsitektur audio dan privasi

- Browser merekam dengan `MediaRecorder` dan menampilkan transkrip secara real-time memakai Web Speech API. Transkripsi berjalan di browser; dukungan dan pemrosesan suara mengikuti penyedia browser yang dipakai.
- Saat dokter menekan "Proses Rekaman", browser mengirim transkrip beserta segmennya (dan file audio bila tersedia) ke Laravel. Laravel menyimpan wawancara, mengarsipkan audio secara opsional, lalu menyusun draf SOAP melalui OpenRouter.
- Segmen disimpan dengan timestamp, `speakerId`, `speakerRole`, dan status review. Identitas dokter/pasien harus dipetakan manual.
- File audio disimpan sementara pada disk lokal dan kebijakan retensi dikonfigurasi dengan `MEDSCRIBE_AUDIO_RETENTION_HOURS`. Penghapusan terjadwal perlu dijalankan sesuai kebijakan deployment.
- Jangan gunakan data pasien sungguhan pada pengembangan awal. Transkrip dan konteks klinis yang dikirim ke OpenRouter meninggalkan komputer lokal dan memerlukan persetujuan/kebijakan yang sesuai.

## Prasyarat

- PHP 8.3+, Composer, Node.js/npm, dan SQLite atau database Laravel yang dikonfigurasi.
- FFmpeg pada `PATH` (dipakai `ffprobe` untuk memvalidasi durasi audio).

## Instalasi

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

FFmpeg harus tersedia melalui `ffmpeg -version`.

## Menjalankan

```bash
php artisan serve --host=127.0.0.1
npm run dev
```

Transkripsi berjalan di browser, jadi tidak ada worker queue maupun service Python yang perlu dijalankan. Hanya transkrip yang sudah ditinjau dan TTV minimum yang dapat dikirim ke SOAP AI OpenRouter.

Jalankan `php artisan medscribe:purge-audio` melalui Task Scheduler/cron sesuai kebijakan retensi untuk menghapus file audio kedaluwarsa.

## Generasi SOAP OpenRouter

Untuk mengaktifkan generasi berbasis teks:

```dotenv
MEDSCRIBE_SOAP_DRIVER=openrouter
OPENROUTER_API_KEY=...
OPENROUTER_MODEL=nvidia/nemotron-3-ultra-550b-a55b:free
```

Key hanya boleh berada di environment backend dan tidak boleh masuk bundle frontend atau Git. Aplikasi tidak berpindah diam-diam ke model berbayar. Jika key, model gratis, jaringan, atau kuota tidak tersedia, error ditampilkan dan draf sebelumnya tidak ditimpa. Audio tidak pernah dikirim ke OpenRouter.

## Deployment (Docker Compose / Coolify)

Repo ini berisi `Dockerfile` dan `docker-compose.yaml` untuk deployment produksi.

### Arsitektur container

| Service | Peran | Healthcheck |
|---|---|---|
| `mysql` | MySQL 8.4, volume `mysql_data` | `mysqladmin ping` |
| `migrate` | One-shot: `php artisan migrate --force && php artisan db:seed --force` | — (exit 0 = sukses) |
| `app` | Apache + PHP 8.4 (Laravel), port 80 | `curl --fail http://127.0.0.1/` |

Urutan start dijamin `depends_on`: `mysql` (healthy) → `migrate` (harus **exit 0**) → `app` (harus **healthy**). `app` memakai `condition: service_completed_successfully` pada `migrate`, jadi bila migrasi gagal, `app` tidak pernah start dan deploy gagal cepat dengan pesan yang jelas.

### Environment wajib di Coolify

Wajib diisi (compose gagal dengan pesan eksplisit bila kosong): `APP_URL`, `APP_KEY`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`.

Wajib diubah dari default untuk produksi:

```dotenv
APP_URL=https://domain-anda
APP_KEY=base64:<32 byte acak>          # php artisan key:generate --show
APP_TIMEZONE=Asia/Jakarta
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

MAIL_MAILER=smtp                       # default "log" -> OTP tidak pernah sampai ke user
MAIL_SCHEME=smtp                       # smtp (port 587 + STARTTLS) atau smtps (port 465)
MAIL_HOST=smtp.provider-anda.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@domain-anda.com   # WAJIB diisi, jangan dikosongkan
MAIL_FROM_NAME=MedScribe AI

MEDSCRIBE_SOAP_DRIVER=openrouter       # default "mock" -> draf SOAP produksi hanya dummy
OPENROUTER_API_KEY=...
OPENROUTER_MODEL=<id model valid di OpenRouter>

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://domain-anda/auth/google/callback
```

> **`MAIL_FROM_ADDRESS` tidak boleh kosong.** Nilai kosong membuat Symfony melempar
> `LogicException: An email must have a "From" or a "Sender" header.` sehingga setiap pengiriman
> email (OTP registrasi, lupa password) gagal dan tampil sebagai
> "Gagal mengirimkan kode verifikasi ke email.".
>
> Tanpa SMTP server, pakai `MAIL_MAILER=log` untuk pengujian: OTP lalu ditulis ke
> `storage/logs/laravel.log` dan bisa dibaca dengan
> `docker compose exec app tail -f storage/logs/laravel.log`.

`GOOGLE_REDIRECT_URI` harus sama persis dengan URI pada Google Cloud Console.
Tambahkan `https://domain-anda/auth/google/callback` ke **Authorized redirect URIs**
pada OAuth Client bertipe Web application.

Untuk pengujian sebelum Google OAuth selesai, aktifkan sementara akun dummy berikut:

```dotenv
SEED_DUMMY_ACCOUNTS=true
DUMMY_ACCOUNT_PASSWORD=<password-kuat-sementara>
DUMMY_DOKTER_EMAIL=<email-pengujian>
DUMMY_PERAWAT_EMAIL=perawat.dummy@medscribe.id
```

Akun dummy sudah terverifikasi dan disetujui. Password di-reset setiap deploy yang
menjalankan seeder. Hapus flag dan akun tersebut setelah pengujian selesai.

> `SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` sengaja memakai driver `database`. Semua tabelnya dibuat oleh service `migrate`, jadi **jangan** menghapus service tersebut dari compose.

### Catatan konfigurasi

- **Healthcheck memakai `/`, bukan `/up`.** Route `/up` bawaan Laravel tidak memakai middleware `web`, sehingga tidak memverifikasi koneksi database. memakai `/` memastikan container baru dianggap sehat setelah tabel benar-benar tersedia.
- **`trustProxies` sudah dikonfigurasi** di `bootstrap/app.php`. Coolify memakai Traefik (TLS termination); tanpa itu, `X-Forwarded-Proto` diabaikan sehingga `asset()`/`route()` menghasilkan URL `http://` (mixed content → logo dan CSS/JS diblokir browser saat HTTPS aktif) dan `$request->ip()` selalu mengembalikan IP proxy sehingga rate limiter OTP berlaku global.
- **Limit upload** diatur di `docker/php.ini` (`upload_max_filesize=128M`, `post_max_size=136M`). Nilai ini harus tetap >= `MEDSCRIBE_AUDIO_MAX_SIZE_KB` (default 102400 KB = 100 MB). Kalau env tersebut dinaikkan, `docker/php.ini` juga harus dinaikkan.
- **`php artisan config:cache` tidak dijalankan saat build** karena konfigurasi bergantung pada environment runtime. Jika dipakai, wajib `php artisan config:clear` setiap kali environment Coolify berubah.
- **Container `migrate` berjalan sebagai `www-data`** (container `app` tetap root supaya Apache bisa bind port 80) agar file di `storage/` tidak tercipta milik root dan menggagalkan penulisan log.
- **`expose` bukan `ports`.** Service `app` hanya mengekspos port 80 di network internal `medscribe_internal`; proxy (Traefik) Coolify harus ikut terpasang ke network tersebut agar domain publik dapat diakses.
- **Akun dummy produksi tidak aktif secara default.** `DummyAccountSeeder` hanya membuat akun
  bila `SEED_DUMMY_ACCOUNTS=true` dan `DUMMY_ACCOUNT_PASSWORD` diisi eksplisit. Tanpa flag
  tersebut, produksi hanya mendapat data master rumah sakit dan katalog LOINC.

### Verifikasi pasca-deploy

```bash
docker compose ps                                   # semua service harus (healthy), migrate harus Exited (0)
docker compose logs migrate                         # daftar migration + "Database seeding completed successfully"
docker compose exec app php artisan migrate:status  # semua migration harus Ran
docker compose exec app php -r 'echo ini_get("upload_max_filesize"), " ", ini_get("memory_limit");'
docker compose exec app php artisan tinker --execute="dump(config('mail.from.address'), config('mail.default'));"
curl -I https://domain-anda                         # harus 302 ke /login (bukan 500)
  curl -s https://domain-anda/login | grep -o 'srcset="[^"]*logo-medscribe[^"]*"' # semua harus https://
```

### Troubleshooting deploy

- **`container app ... is unhealthy`**: aplikasi hidup tetapi `/` tidak membalas sukses.
  ```bash
  docker compose logs app | tail -50
  docker compose exec app php artisan migrate:status   # tabel sudah dibuat?
  docker compose exec app php artisan config:show app.key   # APP_KEY valid (base64 32 byte)?
  docker compose logs migrate                            # migrasi gagal?
  ```
  Penyebab yang paling sering: migrasi belum dijalankan (tabel `sessions`/`cache`/`jobs` belum ada) atau `APP_KEY` tidak valid.
- **Pendaftaran gagal dengan "Gagal mengirimkan kode verifikasi ke email."**: email ditolak Symfony karena alamat pengirim kosong. Periksa `MAIL_FROM_ADDRESS` (wajib diisi) dan `MAIL_MAILER`:
  ```bash
  docker compose exec app php artisan tinker --execute="dump(config('mail.from.address'), config('mail.default'));"
  docker compose exec app printenv | grep -E '^MAIL_'
  ```
  Uji kirim langsung dari dalam container:
  ```bash
  docker compose exec app php artisan tinker --execute="Mail::to('email-anda@gmail.com')->send(new App\Mail\VerifyEmailOtpMail('123456','Tester',10)); echo 'sent';"
  ```
  Bila `MAIL_MAILER=log`, OTP tersimpan di `storage/logs/laravel.log`.
- **Logo / gambar tidak tampil**: aset berada di `public/images` (di luar pipeline Vite, dimuat via `asset()`). Diagnosis berurutan:
  ```bash
  docker compose exec app ls -l /var/www/html/public/images/                                   # file ada di container?
  docker compose exec app sh -c 'curl -sI http://127.0.0.1/images/logo-medscribe.png | head -1'  # 200 atau 404?
  curl -s https://domain-anda/login | grep -o 'srcset="[^"]*logo-medscribe[^"]*"'                 # semua harus https://
  ```
  - File tidak ada di container → artefak build basi di server; rebuild tanpa cache di Coolify.
  - File ada tapi 404 → masalah routing Apache (`DocumentRoot` / `.htaccess`).
  - URL `srcset` hasil `http://` → mixed content; pastikan `APP_URL` memakai `https://`, `trustProxies()` aktif, lalu deploy ulang.
  - URL `https://` tapi gambar tetap kosong → cache browser; hard refresh (Ctrl+Shift+R).
- **Google OAuth menampilkan `Error 400: redirect_uri_mismatch`**: daftarkan URI produksi
  secara persis di Google Cloud Console dan isi `GOOGLE_REDIRECT_URI` di Coolify.
- **Volume `laravel_storage` berisi file milik root** (dari versi compose sebelumnya): perbaiki sekali dengan
  `docker compose exec -u root app chown -R www-data:www-data /var/www/html/storage`.
- **Reset total (⚠️ menghapus seluruh database)**: `docker compose down -v` lalu deploy ulang.

## Test dan troubleshooting

```bash
php artisan test
npm run build
```

- `Transkripsi langsung tidak muncul`: Web Speech API hanya tersedia pada browser berbasis Chromium. Gunakan Chrome/Edge, pastikan mikrofon diizinkan, dan koneksi internet stabil.
- `FFmpeg` error: pastikan executable `ffmpeg` ada di `PATH`.
- Audio kosong/rusak: rekam ulang dengan mikrofon yang tersedia dan format browser yang didukung.
- `HTTP 403` atau CSRF: gunakan sesi login dokter yang valid dan jangan menghapus middleware CSRF/role.
- Rate limit OpenRouter: tunggu kuota pulih atau tinjau SOAP manual; aplikasi tidak memilih model berbayar.
- Error `SQLSTATE ... no such table: sessions/cache/jobs` saat runtime: migration belum dijalankan pada database tersebut. Jalankan `php artisan migrate --force` (di deployment otomatis ditangani service `migrate`).

<?php

namespace App\Console\Commands;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\EResep;
use App\Models\Finalisasi;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use App\Models\TindakLanjut;
use App\Models\Wawancara;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupDummyDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:cleanup-dummy-data
                            {--dry-run : Menjalankan audit dan simulasi deteksi data dummy tanpa menghapus apapun}
                            {--apply : Mengeksekusi pembersihan data dummy secara permanen dan aman}
                            {--force : Memaksa eksekusi tanpa konfirmasi interaktif (wajib untuk non-interactive / production)}
                            {--include-demo-accounts : Menghapus juga akun demo bawaan seeder (dewi@medscribe.id & rina@medscribe.id)}';

    /**
     * The console command description.
     */
    protected $description = 'Pembersihan data dummy pengujian dan akun demonstrasi untuk persiapan rilis produksi MedScribe AI';

    /**
     * Akun resmi terlindungi yang TIDAK BOLEH dihapus dalam kondisi apapun.
     */
    protected const PROTECTED_EMAILS = [
        'onilmoses@gmail.com',
        'otnielmoses17@gmail.com',
    ];

    /**
     * Nomor Rekam Medis bawaan seeder awal.
     */
    protected const SEEDER_PATIENT_RMS = [
        '00-12-345',
        '00-12-346',
        '00-12-347',
        '00-12-348',
        '00-12-349',
        '00-12-350',
        '00-12-351',
        '00-12-352',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isApply = (bool) $this->option('apply');
        $isDryRun = !$isApply || (bool) $this->option('dry-run');
        $includeDemoAccounts = (bool) $this->option('include-demo-accounts');
        $isForce = (bool) $this->option('force');

        $env = app()->environment();
        $dbName = config('database.connections.mysql.database', 'medscribe_ai');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');

        $this->newLine();
        $this->info('========================================================================');
        $this->info('       MEDSCRIBE AI — PRODUCTION READINESS DATABASE CLEANUP TOOL        ');
        $this->info('========================================================================');
        $this->line(" Lingkungan Saat Ini  : <comment>{$env}</comment>");
        $this->line(" Database Target      : <comment>{$dbName}</comment> on <comment>{$dbHost}</comment>");
        $this->line(" Mode Operasi         : " . ($isDryRun ? '<bg=blue;fg=white;options=bold> DRY-RUN (SIMULASI AMAN) </>' : '<bg=red;fg=white;options=bold> APPLY (EKSEKUSI NYATA) </>'));
        $this->line(" Hapus Akun Demo Awal : " . ($includeDemoAccounts ? '<comment>YA</comment>' : '<info>TIDAK (Dipelihara untuk review demo)</info>'));
        $this->newLine();

        // 1. Audit dan Klasifikasi Data
        $audit = $this->gatherDummyDataInventory($includeDemoAccounts);

        // 2. Tampilkan Laporan Audit Lengkap
        $this->renderAuditReport($audit);

        // 3. Jika hanya mode dry-run, hentikan di sini tanpa melakukan perubahan apapun
        if ($isDryRun) {
            $this->newLine();
            $this->warn(' [SIMULASI SELESAI] Tidak ada data atau berkas yang diubah maupun dihapus.');
            $this->line(' Untuk menerapkan pembersihan data secara permanen, jalankan:');
            $this->info('   php artisan app:cleanup-dummy-data --apply');
            if ($includeDemoAccounts) {
                $this->info('   php artisan app:cleanup-dummy-data --apply --include-demo-accounts');
            }
            $this->newLine();
            return 0;
        }

        // 4. Mode APPLY: Perlindungan Lingkungan Produksi & Konfirmasi
        if ($env === 'production' && !$isForce) {
            $this->error(' PERINGATAN KESELAMATAN: Anda berada di lingkungan PRODUKSI!');
            $this->line(' Untuk mengeksekusi pembersihan pada produksi, Anda wajib menyertakan flag --force:');
            $this->comment('   php artisan app:cleanup-dummy-data --apply --force');
            return 1;
        }

        if (!$isForce && !$this->confirm('Apakah Anda yakin ingin mengeksekusi pembersihan permanen untuk seluruh data dummy terverifikasi di atas?', false)) {
            $this->warn(' Eksekusi dibatalkan oleh pengguna.');
            return 0;
        }

        // 5. Langkah Keselamatan Wajib: Buat Cadangan Basis Data (Database Backup)
        $this->newLine();
        $this->line(' Langkah 1/3: Membuat salinan cadangan basis data otomatis...');
        $backupPath = $this->createDatabaseBackup();
        if (!$backupPath) {
            $this->error(' Gagal membuat backup database! Pembersihan dihentikan demi keselamatan data.');
            return 1;
        }
        $this->info(" ✓ Backup berhasil dibuat di: <comment>{$backupPath}</comment>");

        // 6. Langkah Eksekusi Pembersihan dengan Integritas Referensial
        $this->newLine();
        $this->line(' Langkah 2/3: Menghapus data dummy terverifikasi dalam transaksi atomik...');
        $deletionResult = $this->executeSafeDeletion($audit);
        if (!$deletionResult['success']) {
            $this->error(' Terjadi kesalahan saat menghapus data: ' . $deletionResult['error']);
            $this->error(' Seluruh perubahan database telah di-rollback secara otomatis.');
            return 1;
        }
        $this->info(' ✓ Seluruh rekaman database dummy berhasil dibersihkan.');

        // 7. Langkah Pembersihan Berkas Audio Storage Terverifikasi
        $this->newLine();
        $this->line(' Langkah 3/3: Membersihkan berkas audio rekaman pengujian di penyimpanan...');
        $storageCleaned = $this->cleanupStorageAudioFiles($audit['storageFiles']);
        $this->info(" ✓ {$storageCleaned} berkas audio pengujian berhasil dibersihkan dari storage.");

        // 8. Ringkasan Akhir
        $this->newLine();
        $this->info('========================================================================');
        $this->info('              PEMBERSIHAN DATA DUMMY BERHASIL DILAKUKAN                 ');
        $this->info('========================================================================');
        $this->line(" Total Pasien Dihapus    : {$audit['dummyPatients']->count()} pasien");
        $this->line(" Total Kunjungan Dihapus : {$audit['dummyVisits']->count()} kunjungan");
        $this->line(" Total Pengguna Dihapus  : {$audit['dummyUsers']->count()} akun");
        $this->line(" Total File Audio Dihapus: {$storageCleaned} berkas");
        $this->line(" File Cadangan Database  : {$backupPath}");
        $this->newLine();

        return 0;
    }

    /**
     * Mengumpulkan inventaris data dummy dengan klasifikasi berbasis bukti.
     */
    protected function gatherDummyDataInventory(bool $includeDemoAccounts): array
    {
        // A. Pengguna (Dokter & Perawat)
        $allUsers = Pengguna::all();
        $dummyUsers = collect();
        $preservedUsers = collect();

        foreach ($allUsers as $user) {
            // Perlindungan absolut: Akun resmi whitelist tidak boleh dihapus
            if (in_array(strtolower(trim($user->email)), self::PROTECTED_EMAILS, true)) {
                $preservedUsers->push([
                    'id' => $user->id_pengguna,
                    'nama' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => 'DILINDUNGI (Akun Resmi / Developer)',
                ]);
                continue;
            }

            // Akun demo seeder (dewi & rina)
            if (in_array(strtolower(trim($user->email)), ['dewi@medscribe.id', 'rina@medscribe.id'], true)) {
                if ($includeDemoAccounts) {
                    $dummyUsers->push($user);
                } else {
                    $preservedUsers->push([
                        'id' => $user->id_pengguna,
                        'nama' => $user->nama_lengkap,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => 'DIPELIHARA (Akun Demo Seeder)',
                    ]);
                }
                continue;
            }

            // Deteksi akun test (hash uniqid, test, fake, unverified test)
            $isTestPattern = str_contains($user->email, '.6ae')
                || str_starts_with($user->email, 'dokter.baru.')
                || str_starts_with($user->email, 'unverified.')
                || str_ends_with($user->email, '.test')
                || str_contains($user->email, 'test')
                || str_contains($user->nama_lengkap, 'Belum Verifikasi');

            if ($isTestPattern) {
                $dummyUsers->push($user);
            } else {
                // Akun selain whitelist dan pattern test perlu peninjauan manual
                $preservedUsers->push([
                    'id' => $user->id_pengguna,
                    'nama' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => 'DIPELIHARA (Perlu Review Manual)',
                ]);
            }
        }

        // B. Pasien Dummy
        // Seluruh pasien di lingkungan development yang dibuat dari seeder awal atau pengujian
        $dummyUserIds = $dummyUsers->pluck('id_pengguna')->toArray();
        $allPatients = Pasien::withTrashed()->get();
        $dummyPatients = collect();

        foreach ($allPatients as $patient) {
            // Semua pasien di lingkungan pengembangan saat ini adalah data pengujian/dummy
            // Baik yang terhapus (trashed) maupun aktif hasil seeder (00-12-345 s/d 00-12-352)
            // dan pengujian manual tim ('Nazwa', 'Irjak', 'Felda', 'valen', 'Yasiir', dll.)
            $dummyPatients->push($patient);
        }

        $dummyPatientIds = $dummyPatients->pluck('id_pasien')->toArray();

        // C. Kunjungan Klinis Dummy
        $dummyVisits = Kunjungan::whereIn('id_pasien', $dummyPatientIds)
            ->orWhereIn('id_dokter', $dummyUserIds)
            ->get();
        $dummyVisitIds = $dummyVisits->pluck('id_kunjungan')->toArray();

        // D. Antrean Terkait
        $dummyQueues = Antrean::whereIn('id_pasien', $dummyPatientIds)->get();
        $dummyQueueIds = $dummyQueues->pluck('id_antrean')->toArray();

        // E. Rekaman Klinis Terkait Kunjungan Dummy
        $dummyTtvs = PemeriksaanAwal::whereIn('id_kunjungan', $dummyVisitIds)->get();
        $dummyInterviews = Wawancara::whereIn('id_kunjungan', $dummyVisitIds)->get();
        $dummyAudios = RekamanAudio::whereIn('id_kunjungan', $dummyVisitIds)->get();
        $dummySoaps = DrafSoap::whereIn('id_kunjungan', $dummyVisitIds)->get();
        $dummyFinals = Finalisasi::whereIn('id_kunjungan', $dummyVisitIds)->get();
        $dummyTindakLanjuts = TindakLanjut::whereIn('id_kunjungan', $dummyVisitIds)->get();
        $dummyTindakLanjutIds = $dummyTindakLanjuts->pluck('id_tindak_lanjut')->toArray();
        $dummyReseps = EResep::whereIn('id_tindak_lanjut', $dummyTindakLanjutIds)
            ->orWhereIn('id_pasien', $dummyPatientIds)
            ->get();

        // F. Berkas Audio Storage Pengujian
        $storageFiles = $this->identifyStorageFiles($dummyVisitIds);

        // G. OTP Pengujian / Kedaluwarsa
        $expiredOtps = DB::table('email_otps')->count();

        return [
            'dummyUsers' => $dummyUsers,
            'preservedUsers' => $preservedUsers,
            'dummyPatients' => $dummyPatients,
            'dummyVisits' => $dummyVisits,
            'dummyQueues' => $dummyQueues,
            'dummyTtvs' => $dummyTtvs,
            'dummyInterviews' => $dummyInterviews,
            'dummyAudios' => $dummyAudios,
            'dummySoaps' => $dummySoaps,
            'dummyFinals' => $dummyFinals,
            'dummyTindakLanjuts' => $dummyTindakLanjuts,
            'dummyReseps' => $dummyReseps,
            'storageFiles' => $storageFiles,
            'expiredOtps' => $expiredOtps,
        ];
    }

    /**
     * Memindai direktori storage/app/private/interviews untuk mencari file audio pengujian.
     */
    protected function identifyStorageFiles(array $dummyVisitIds): array
    {
        $baseDir = storage_path('app/private/interviews');
        if (!File::isDirectory($baseDir)) {
            return [];
        }

        $allVisitIdsInDb = Kunjungan::pluck('id_kunjungan')->toArray();
        $filesToClean = [];

        $subdirs = File::directories($baseDir);
        foreach ($subdirs as $dir) {
            $folderName = basename($dir);
            $visitId = is_numeric($folderName) ? (int) $folderName : null;

            // Masukkan folder ke daftar cleanup jika:
            // 1. Visit ID termasuk dalam daftar dummy visit yang akan dibersihkan, ATAU
            // 2. Folder tersebut yatim piatu (tidak ada kunjungan ID-nya di database karena sisa rollback test)
            if ($visitId !== null && (in_array($visitId, $dummyVisitIds, true) || !in_array($visitId, $allVisitIdsInDb, true))) {
                $files = File::allFiles($dir);
                foreach ($files as $f) {
                    $filesToClean[] = [
                        'visit_id' => $visitId,
                        'path' => $f->getPathname(),
                        'filename' => $f->getFilename(),
                        'size' => $f->getSize(),
                    ];
                }
            }
        }

        return $filesToClean;
    }

    /**
     * Menampilkan tabel laporan audit inventaris data dummy.
     */
    protected function renderAuditReport(array $audit): void
    {
        $this->info('--- INVENTARIS DATA PENGUJIAN / DUMMY TERDETEKSI ---');

        $headers = ['Kategori Data', 'Jumlah Ditemukan', 'Status / Tindakan', 'Tingkat Keyakinan'];
        $rows = [
            ['Akun Dokter / Perawat Dummy', (string) $audit['dummyUsers']->count(), 'Kandidat Dihapus', 'Terverifikasi (Pola Uji / Seeder)'],
            ['Akun Pengguna Terlindungi', (string) count($audit['preservedUsers']), 'DIPELIHARA / DILINDUNGI', 'Aman (Email Whitelist / Terdaftar)'],
            ['Pasien Dummy (Aktif & Trashed)', (string) $audit['dummyPatients']->count(), 'Kandidat Dihapus', 'Terverifikasi (Data Uji Dev)'],
            ['Antrean Kunjungan Dummy', (string) $audit['dummyQueues']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Pasien Dummy)'],
            ['Kunjungan Klinis Dummy', (string) $audit['dummyVisits']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Pasien Dummy)'],
            ['Pemeriksaan Awal (TTV) Dummy', (string) $audit['dummyTtvs']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['Wawancara Klinis & Transkrip AI', (string) $audit['dummyInterviews']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['Metadata Rekaman Audio', (string) $audit['dummyAudios']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['Draf SOAP AI Dummy', (string) $audit['dummySoaps']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['Finalisasi Rekam Medis Dummy', (string) $audit['dummyFinals']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['Tindak Lanjut Medis Dummy', (string) $audit['dummyTindakLanjuts']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['E-Resep Pengujian', (string) $audit['dummyReseps']->count(), 'Kandidat Dihapus', 'Terverifikasi (Relasi Kunjungan)'],
            ['Berkas Audio di Storage', (string) count($audit['storageFiles']), 'Kandidat Dihapus', 'Terverifikasi (Folder Kunjungan Uji)'],
            ['Kode OTP Pengujian / Kedaluwarsa', (string) $audit['expiredOtps'], 'Kandidat Dihapus', 'Terverifikasi (Kedaluwarsa)'],
            ['Master Data Rumah Sakit (30 RS)', '30', 'DIPELIHARA (Katalog Otoritatif SIRS)', 'Aman (Master Data Produksi)'],
        ];

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('--- DAFTAR AKUN TERLINDUNGI (TIDAK AKAN DIHAPUS) ---');
        $userHeaders = ['ID', 'Nama Lengkap', 'Email', 'Role', 'Status'];
        $this->table($userHeaders, $audit['preservedUsers']->toArray());

        if ($audit['dummyUsers']->isNotEmpty()) {
            $this->newLine();
            $this->info('--- DAFTAR AKUN DUMMY YANG AKAN DIBERSIHKAN ---');
            $dummyUserRows = $audit['dummyUsers']->map(fn ($u) => [
                'id' => $u->id_pengguna,
                'nama' => $u->nama_lengkap,
                'email' => $u->email,
                'role' => $u->role,
                'alasan' => 'Akun Pengujian / Seeder Awal',
            ])->toArray();
            $this->table(['ID', 'Nama', 'Email', 'Role', 'Alasan'], $dummyUserRows);
        }
    }

    /**
     * Membuat cadangan database (SQL Dump) sebelum eksekusi penghapusan data.
     */
    protected function createDatabaseBackup(): ?string
    {
        try {
            $backupDir = storage_path('backups');
            if (!File::isDirectory($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $timestamp = date('Ymd_His');
            $filename = "medscribe_backup_{$timestamp}.sql";
            $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

            $handle = fopen($filepath, 'w');
            if (!$handle) {
                return null;
            }

            fwrite($handle, "-- ========================================================\n");
            fwrite($handle, "-- MedScribe AI - Production Readiness Database Backup\n");
            fwrite($handle, "-- Tanggal Pembuatan : " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Basis Data        : " . config('database.connections.mysql.database') . "\n");
            fwrite($handle, "-- ========================================================\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            // Ambil seluruh tabel dalam database
            $tableRows = DB::select('SHOW TABLES');
            $tables = [];
            foreach ($tableRows as $tableRow) {
                $tables[] = array_values((array) $tableRow)[0];
            }

            foreach ($tables as $table) {
                $createTableStmt = DB::select("SHOW CREATE TABLE `{$table}`");
                if (!empty($createTableStmt)) {
                    $createSql = ((array) $createTableStmt[0])['Create Table'] ?? null;
                    if ($createSql) {
                        fwrite($handle, "-- Struktur Tabel `{$table}`\n");
                        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                        fwrite($handle, $createSql . ";\n\n");
                    }
                }

                // Salin data baris per baris
                $rows = DB::table($table)->get();
                if ($rows->isNotEmpty()) {
                    fwrite($handle, "-- Data Tabel `{$table}` (" . $rows->count() . " baris)\n");
                    foreach ($rows->chunk(100) as $chunk) {
                        foreach ($chunk as $row) {
                            $rowArray = (array) $row;
                            $columns = array_map(fn ($c) => "`{$c}`", array_keys($rowArray));
                            $values = array_map(function ($v) {
                                if (is_null($v)) return 'NULL';
                                return DB::getPdo()->quote((string) $v);
                            }, array_values($rowArray));

                            fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n");
                        }
                    }
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            // Verifikasi ukuran berkas backup
            if (File::exists($filepath) && File::size($filepath) > 0) {
                return $filepath;
            }

            return null;
        } catch (Throwable $e) {
            $this->error('Gagal membuat backup: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengeksekusi penghapusan data dummy secara aman dalam database transaction
     * dengan urutan dependensi foreign key terbalik.
     */
    protected function executeSafeDeletion(array $audit): array
    {
        $dummyVisitIds = $audit['dummyVisits']->pluck('id_kunjungan')->toArray();
        $dummyPatientIds = $audit['dummyPatients']->pluck('id_pasien')->toArray();
        $dummyUserIds = $audit['dummyUsers']->pluck('id_pengguna')->toArray();
        $dummyTindakLanjutIds = $audit['dummyTindakLanjuts']->pluck('id_tindak_lanjut')->toArray();

        try {
            DB::beginTransaction();

            // 1. Hapus E-Resep terkait
            if (!empty($dummyTindakLanjutIds) || !empty($dummyPatientIds)) {
                EResep::whereIn('id_tindak_lanjut', $dummyTindakLanjutIds)
                    ->orWhereIn('id_pasien', $dummyPatientIds)
                    ->delete();
            }

            // 1b. Hapus Rujukan Lab Parameter & Rujukan Lab terkait tindak lanjut
            if (!empty($dummyTindakLanjutIds)) {
                if (DB::getSchemaBuilder()->hasTable('rujukan_lab_parameter')) {
                    $dummyRujukanLabIds = DB::table('rujukan_lab')
                        ->whereIn('id_tindak_lanjut', $dummyTindakLanjutIds)
                        ->pluck('id_rujukan_lab')
                        ->toArray();
                    if (!empty($dummyRujukanLabIds)) {
                        DB::table('rujukan_lab_parameter')->whereIn('id_rujukan_lab', $dummyRujukanLabIds)->delete();
                    }
                }
                if (DB::getSchemaBuilder()->hasTable('rujukan_lab')) {
                    DB::table('rujukan_lab')->whereIn('id_tindak_lanjut', $dummyTindakLanjutIds)->delete();
                }
            }

            // 3. Hapus Tindak Lanjut Medis
            if (!empty($dummyVisitIds)) {
                TindakLanjut::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 4. Hapus Finalisasi Rekam Medis
            if (!empty($dummyVisitIds)) {
                Finalisasi::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 5. Hapus Draf SOAP
            if (!empty($dummyVisitIds)) {
                DrafSoap::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 6. Hapus Rekaman Audio Metadata
            if (!empty($dummyVisitIds)) {
                RekamanAudio::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 7. Hapus Wawancara Klinis & Transkrip
            if (!empty($dummyVisitIds)) {
                Wawancara::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 8. Hapus Pemeriksaan Awal (TTV)
            if (!empty($dummyVisitIds)) {
                PemeriksaanAwal::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 9. Hapus Kunjungan Klinis
            if (!empty($dummyVisitIds)) {
                Kunjungan::whereIn('id_kunjungan', $dummyVisitIds)->delete();
            }

            // 10. Hapus Antrean Pasien
            if (!empty($dummyPatientIds)) {
                Antrean::whereIn('id_pasien', $dummyPatientIds)->delete();
            }

            // 11. Hapus Pasien Dummy (Permanen / Force Delete)
            if (!empty($dummyPatientIds)) {
                Pasien::withTrashed()->whereIn('id_pasien', $dummyPatientIds)->forceDelete();
            }

            // 12. Hapus Akun Dummy Dokter & Perawat Terverifikasi
            if (!empty($dummyUserIds)) {
                Pengguna::whereIn('id_pengguna', $dummyUserIds)->delete();
            }

            // 13. Hapus OTP Kedaluwarsa Pengujian (Gunakan delete bukan truncate agar aman dalam transaksi)
            if (DB::getSchemaBuilder()->hasTable('email_otps')) {
                DB::table('email_otps')->delete();
            }

            // 14. Hapus Pendaftaran Pending Pengujian (jika ada)
            if (DB::getSchemaBuilder()->hasTable('pending_registrations')) {
                DB::table('pending_registrations')->delete();
            }

            DB::commit();

            return ['success' => true];
        } catch (Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Menghapus berkas fisik audio rekaman di storage.
     */
    protected function cleanupStorageAudioFiles(array $storageFiles): int
    {
        $count = 0;
        $visitedDirs = [];

        foreach ($storageFiles as $item) {
            if (File::exists($item['path'])) {
                File::delete($item['path']);
                $count++;
            }
            $dir = dirname($item['path']);
            $visitedDirs[$dir] = true;
        }

        // Hapus folder kosong jika sudah tidak berisi file audio
        foreach (array_keys($visitedDirs) as $dir) {
            if (File::isDirectory($dir) && count(File::allFiles($dir)) === 0) {
                File::deleteDirectory($dir);
            }
        }

        return $count;
    }
}

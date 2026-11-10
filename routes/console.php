<?php

use App\Models\Hospital;
use Dotenv\Dotenv;
use Illuminate\Support\Env;
use App\Models\Pengguna;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\RekamanAudio;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Perintah Administratif untuk Memetakan Akun Tenaga Medis Legacy ke Rumah Sakit.
 */
Artisan::command('medscribe:assign-hospital {userId} {hospitalCodeOrId}', function ($userId, $hospitalCodeOrId) {
    $user = Pengguna::find($userId);
    if (!$user) {
        $this->error("Pengguna dengan ID {$userId} tidak ditemukan.");
        return 1;
    }

    $hospital = is_numeric($hospitalCodeOrId) && (int) $hospitalCodeOrId < 1000
        ? Hospital::find($hospitalCodeOrId)
        : Hospital::where('hospital_code', $hospitalCodeOrId)->first();

    if (!$hospital) {
        $this->error("Rumah sakit dengan ID/Kode {$hospitalCodeOrId} tidak ditemukan.");
        return 1;
    }

    $user->hospital_id = $hospital->id;
    $user->save();

    $this->info("Berhasil memetakan {$user->nama_lengkap} (ID: {$user->id_pengguna}) ke {$hospital->hospital_name} ({$hospital->hospital_code}).");
    return 0;
})->purpose('Memetakan akun tenaga medis legacy ke rumah sakit terdaftar');

/**
 * Menampilkan daftar akun tenaga medis legacy yang belum memiliki asosiasi rumah sakit.
 */
Artisan::command('medscribe:unassigned-users', function () {
    $unassigned = Pengguna::whereNull('hospital_id')->get(['id_pengguna', 'nama_lengkap', 'email', 'role']);

    if ($unassigned->isEmpty()) {
        $this->info('Semua akun tenaga medis telah terasosiasi dengan rumah sakit.');
        return 0;
    }

    $this->warn("Ditemukan {$unassigned->count()} akun yang memerlukan pemetaan rumah sakit:");
    $headers = ['ID', 'Nama Lengkap', 'Email', 'Role'];
    $rows = $unassigned->map(fn ($u) => [$u->id_pengguna, $u->nama_lengkap, $u->email, $u->role])->toArray();
    $this->table($headers, $rows);
    $this->line("Gunakan: php artisan medscribe:assign-hospital {userId} {hospitalCodeOrId}");
    return 0;
})->purpose('Daftar pengguna legacy tanpa asosiasi rumah sakit');

/**
 * Menjalankan BDD (Behavior-Driven Development) Testing Suite MedScribe AI.
 */
Artisan::command('test:bdd {--filter= : Filter skenario atau kelas pengujian BDD}', function () {
    $this->newLine();
    $this->line('<fg=cyan;options=bold>====================================================================</>');
    $this->line('<fg=cyan;options=bold>   MEDSCRIBE AI - BEHAVIOR DRIVEN DEVELOPMENT (BDD) TEST RUNNER     </>');
    $this->line('<fg=cyan;options=bold>====================================================================</>');
    $this->newLine();

    $featuresDir = base_path('features');
    $featureFiles = glob($featuresDir . '/*.feature');
    $filter = $this->option('filter');

    // Baca judul Feature dan daftar Scenario dari setiap file Gherkin.
    $features = [];
    foreach ($featureFiles as $file) {
        $feature = ['title' => basename($file), 'scenarios' => []];
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $trimmed = trim($line);
            if (preg_match('/^(Fitur|Feature):\s*(.+)$/', $trimmed, $m)) {
                $feature['title'] = $m[2];
            } elseif (preg_match('/^(Skenario|Scenario):\s*(.+)$/', $trimmed, $m)) {
                $feature['scenarios'][] = $m[2];
            }
        }
        $features[basename($file)] = $feature;
    }

    $totalSkenario = array_sum(array_map(fn ($f) => count($f['scenarios']), $features));
    $this->info('Ditemukan ' . count($features) . " file fitur Gherkin dengan {$totalSkenario} skenario.");
    $this->line('<fg=gray>Menjalankan skenario' . ($filter ? " [Filter: {$filter}]" : '') . '...</>');
    $this->newLine();

    // Artisan::call('test') tidak meneruskan --testsuite/--filter ke PHPUnit (ia membaca argv proses),
    // sehingga seluruh suite ikut berjalan. Jalankan sebagai proses terpisah agar hanya suite BDD yang dieksekusi.
    $command = [PHP_BINARY, base_path('artisan'), 'test', '--testsuite=BDD'];
    if ($filter) {
        $command[] = '--filter=' . $filter;
    }

    // Bersihkan variabel .env dev yang sudah dimuat proses ini supaya tidak diwariskan ke proses test
    // (sama seperti yang dilakukan `php artisan test`), sehingga phpunit.xml dan .env.testing yang berlaku.
    $envFile = app()->environmentFilePath();
    if (is_file($envFile)) {
        foreach (array_keys(Dotenv::parse(file_get_contents($envFile))) as $name) {
            Env::getRepository()->clear($name);
        }
    }

    // Setiap test BDD menulis langkah dan hasilnya ke file ini (lihat Tests\BDD\BDDTestCase::recordBddScenario).
    $reportFile = storage_path('framework/testing/bdd-report.jsonl');
    @unlink($reportFile);

    $startedAt = microtime(true);
    $process = new Process($command, base_path(), ['BDD_REPORT_FILE' => $reportFile], null, null);
    $exitCode = $process->run();
    $totalDurasi = microtime(true) - $startedAt;

    $hasil = [];
    foreach (is_file($reportFile) ? file($reportFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) {
            $hasil[$row['feature'] ?? $row['class']][$row['scenario']] = $row;
        }
    }
    @unlink($reportFile);

    $esc = fn (string $text) => OutputFormatter::escape($text);
    $hitung = ['fitur_lulus' => 0, 'fitur_gagal' => 0, 'skenario_lulus' => 0, 'skenario_gagal' => 0,
        'skenario_lewat' => 0, 'tanpa_test' => 0, 'langkah_lulus' => 0, 'langkah_gagal' => 0];

    foreach ($features as $file => $feature) {
        $skenarioFitur = $hasil[$file] ?? [];
        if ($skenarioFitur === [] && $filter) {
            continue; // Fitur ini tidak tersaring oleh --filter.
        }

        $this->line("<options=bold>Feature: {$esc($feature['title'])}</>  <fg=gray># features/{$file}</>");

        // Urutkan sesuai file .feature; skenario yang tidak ditemukan di file ditaruh di akhir.
        $judulUrut = array_values(array_unique(array_merge($feature['scenarios'], array_keys($skenarioFitur))));
        $fiturGagal = false;

        foreach ($judulUrut as $judul) {
            $row = $skenarioFitur[$judul] ?? null;

            if ($row === null) {
                if (! $filter) {
                    $hitung['tanpa_test']++;
                    $this->line("  <fg=yellow>Scenario: {$esc($judul)}  (belum ada test)</>");
                }
                continue;
            }

            $durasi = sprintf('%.2fs', $row['duration']);
            $warna = match ($row['status']) { 'passed' => 'green', 'skipped' => 'yellow', default => 'red' };
            $this->line("  <fg={$warna};options=bold>Scenario: {$esc($judul)}</>  <fg=gray>{$durasi}</>");

            $langkah = $row['steps'] ?: [['Then', '(skenario tidak mencatat langkah)']];
            foreach ($langkah as $i => [$kataKunci, $deskripsi]) {
                // Langkah terakhir yang tercatat adalah langkah tempat skenario berhenti saat gagal.
                $gagalDiSini = $row['status'] === 'failed' && $i === array_key_last($langkah);
                $simbol = $gagalDiSini ? '<fg=red>✗</>' : ($row['status'] === 'skipped' ? '<fg=yellow>-</>' : '<fg=green>✓</>');
                $teks = $gagalDiSini ? "<fg=red>{$kataKunci} {$esc($deskripsi)}</>" : "<fg=cyan>{$kataKunci}</> {$esc($deskripsi)}";
                $this->line("    {$simbol} {$teks}");
                $gagalDiSini ? $hitung['langkah_gagal']++ : $hitung['langkah_lulus']++;
            }

            if ($row['status'] === 'failed') {
                $fiturGagal = true;
                $hitung['skenario_gagal']++;
                $pesan = trim((string) $row['message']);
                $pesan = mb_strlen($pesan) > 600 ? mb_substr($pesan, 0, 600) . ' …' : $pesan;
                foreach (array_slice(explode("\n", $pesan), 0, 8) as $baris) {
                    $this->line("      <fg=red>{$esc($baris)}</>");
                }
            } elseif ($row['status'] === 'skipped') {
                $hitung['skenario_lewat']++;
            } else {
                $hitung['skenario_lulus']++;
            }
        }

        $fiturGagal ? $hitung['fitur_gagal']++ : $hitung['fitur_lulus']++;
        $this->newLine();
    }

    $jumlahTercatat = array_sum(array_map('count', $hasil));
    if ($exitCode !== 0 && $hitung['skenario_gagal'] === 0) {
        // PHPUnit gagal di luar skenario (mis. error PHP/koneksi database), tampilkan keluaran aslinya.
        $this->line('<fg=red>PHPUnit berhenti sebelum semua skenario tercatat. Keluaran asli:</>');
        $this->line($process->getOutput() . $process->getErrorOutput());
    }

    $this->line(sprintf('%d fitur lulus, %d gagal', $hitung['fitur_lulus'], $hitung['fitur_gagal']));
    $this->line(sprintf(
        '%d skenario lulus, %d gagal, %d dilewati%s',
        $hitung['skenario_lulus'], $hitung['skenario_gagal'], $hitung['skenario_lewat'],
        $hitung['tanpa_test'] ? ", {$hitung['tanpa_test']} belum ada test" : ''
    ));
    $this->line(sprintf('%d langkah lulus, %d gagal', $hitung['langkah_lulus'], $hitung['langkah_gagal']));
    $this->line(sprintf('<fg=gray>%d skenario dijalankan dalam %.1fs</>', $jumlahTercatat, $totalDurasi));
    $this->newLine();

    if ($exitCode === 0) {
        $this->line('<fg=green;options=bold>✓ SEMUA SKENARIO BDD (BEHAVIOR DRIVEN DEVELOPMENT) BERHASIL DIUJI!</>');
    } else {
        $this->line('<fg=red;options=bold>✗ TERDAPAT KEGAGALAN PADA PENGUJIAN SKENARIO BDD.</>');
    }

    return $exitCode;
})->purpose('Menjalankan seluruh pengujian skenario BDD (Behavior-Driven Development)');

Artisan::command('medscribe:purge-audio', function () {
    $cutoff = now()->subHours(config('medscribe.audio.retention_hours'));
    $count = 0;
    RekamanAudio::where('status', 'completed')->whereNotNull('processed_at')->where('processed_at', '<', $cutoff)->each(function (RekamanAudio $recording) use (&$count) {
        if ($recording->path_audio) {
            Storage::disk(config('medscribe.audio.disk'))->delete($recording->path_audio);
        }
        $recording->update(['path_audio' => null, 'status' => 'expired']);
        $count++;
    });
    $this->info("Purged {$count} audio file(s).");
})->purpose('Delete expired local interview audio files');

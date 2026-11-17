<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Unduh dokumen terminologi ICD-10 resmi Kementerian Kesehatan RI lalu gabungkan
 * menjadi satu berkas CSV katalog master yang dipakai Icd10Seeder.
 *
 * Perintah ini hanya dipakai saat memperbarui katalog (kDevelop), bukan pada
 * alur deploy. Hasilnya di-commit ke repository sehingga seeding di produksi
 * tidak bergantung pada koneksi internet.
 */
class BuildIcd10DatasetCommand extends Command
{
    protected $signature = 'medscribe:icd10:build-dataset
                            {--output= : Lokasi berkas CSV keluaran (default: database/seeders/data/icd-10.csv)}
                            {--force : Timpa berkas keluaran tanpa konfirmasi}';

    protected $description = 'Unduh katalog ICD-10 (SATUSEHAT ICD-10 2010 + ICD-IM) dari sumber resmi Kemenkes menjadi CSV katalog master';

    /**
     * Sumber: dokumen terminologi resmi SATUSEHAT Platform (Kementerian Kesehatan RI).
     *
     * - ICD-10 2010 : https://satusehat.kemkes.go.id/platform/docs/id/terminology/icd/icd-10
     * - ICD-IM 2026 : https://satusehat.kemkes.go.id/platform/docs/id/terminology/icd/icd-im
     */
    private const SUMBER_BASE = [
        'nama' => 'SATUSEHAT ICD-10 2010',
        'url' => 'https://docs.google.com/spreadsheets/d/12_72PvHRLWny3VEwodEI6TRDc7QqWdiF/export?format=csv',
        'sumber' => 'satusehat_2010',
    ];

    private const SUMBER_IM = [
        'nama' => 'SATUSEHAT ICD-IM 2026',
        'url' => 'https://docs.google.com/spreadsheets/d/1Bz-3XTpPBNVrG4hCDXgq6KEs0aDUN73c/export?format=csv',
        'sumber' => 'icd_im_2026',
    ];

    /**
     * Pembagian bab ICD-10 (22 bab) untuk kolom kategori.
     *
     * @var array<int, array{0: string, 1: int, 2: int, 3: string}>
     */
    private const BAB = [
        ['A', 0, 99, 'Bab I - Penyakit infeksi dan parasitik'],
        ['B', 0, 99, 'Bab I - Penyakit infeksi dan parasitik'],
        ['C', 0, 99, 'Bab II - Neoplasma'],
        ['D', 0, 49, 'Bab II - Neoplasma'],
        ['D', 50, 89, 'Bab III - Penyakit darah dan gangguan imunitas'],
        ['E', 0, 90, 'Bab IV - Gangguan endokrin, nutrisi, dan metabolik'],
        ['F', 0, 99, 'Bab V - Gangguan mental dan perilaku'],
        ['G', 0, 99, 'Bab VI - Penyakit sistem saraf'],
        ['H', 0, 59, 'Bab VII - Penyakit mata dan adneksa'],
        ['H', 60, 95, 'Bab VIII - Penyakit telinga dan prosesus mastoid'],
        ['I', 0, 99, 'Bab IX - Penyakit sistem sirkulasi'],
        ['J', 0, 99, 'Bab X - Penyakit sistem pernapasan'],
        ['K', 0, 93, 'Bab XI - Penyakit sistem pencernaan'],
        ['L', 0, 99, 'Bab XII - Penyakit kulit dan jaringan subkutan'],
        ['M', 0, 99, 'Bab XIII - Penyakit sistem muskuloskeletal dan jaringan ikat'],
        ['N', 0, 99, 'Bab XIV - Penyakit sistem genitourinaria'],
        ['O', 0, 99, 'Bab XV - Kehamilan, persalinan, dan masa nifas'],
        ['P', 0, 96, 'Bab XVI - Kondisi yang bermula pada masa perinatal'],
        ['Q', 0, 99, 'Bab XVII - Malformasi kongenital dan kelainan kromosom'],
        ['R', 0, 99, 'Bab XVIII - Gejala, tanda, dan temuan klinis abnormal'],
        ['S', 0, 98, 'Bab XIX - Cedera dan keracunan'],
        ['T', 0, 98, 'Bab XIX - Cedera dan keracunan'],
        ['V', 1, 99, 'Bab XX - Penyebab eksternal morbiditas dan mortalitas'],
        ['W', 0, 99, 'Bab XX - Penyebab eksternal morbiditas dan mortalitas'],
        ['X', 0, 99, 'Bab XX - Penyebab eksternal morbiditas dan mortalitas'],
        ['Y', 0, 99, 'Bab XX - Penyebab eksternal morbiditas dan mortalitas'],
        ['Z', 0, 99, 'Bab XXI - Faktor yang memengaruhi status kesehatan'],
        ['U', 0, 99, 'Bab XXII - Kode untuk tujuan khusus'],
    ];

    public function handle(): int
    {
        $output = $this->option('output') ?: base_path('database/seeders/data/icd-10.csv');

        if (File::exists($output) && ! $this->option('force')) {
            if (! $this->confirm("Berkas {$output} sudah ada. Timpa?", false)) {
                $this->warn('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $katalog = [];

        foreach ([self::SUMBER_BASE, self::SUMBER_IM] as $sumber) {
            $this->line("Mengunduh {$sumber['nama']}...");

            try {
                $csv = $this->unduh($sumber['url']);
            } catch (Throwable $e) {
                $this->error("Gagal mengunduh {$sumber['nama']}: {$e->getMessage()}");

                return self::FAILURE;
            }

            $baris = $this->bacaCsv($csv);
            if ($baris === []) {
                $this->error("Dokumen {$sumber['nama']} tidak menghasilkan baris yang dapat dibaca.");

                return self::FAILURE;
            }

            $ditambahkan = 0;
            foreach ($baris as $barisData) {
                $kode = strtoupper(trim((string) ($barisData['CODE'] ?? '')));
                $judul = trim((string) ($barisData['DISPLAY'] ?? $barisData['DESCRIPTION'] ?? ''));

                if ($kode === '' || $judul === '' || $this->kategori($kode) === null) {
                    continue;
                }

                if (isset($katalog[$kode])) {
                    continue;
                }

                $katalog[$kode] = [
                    'kode_icd10' => $kode,
                    'deskripsi' => $this->rapikan($judul),
                    'kategori' => $this->kategori($kode),
                    'sumber' => $sumber['sumber'],
                ];
                $ditambahkan++;
            }

            $this->line("  {$ditambahkan} kode masuk katalog.");
        }

        ksort($katalog, SORT_NATURAL | SORT_FLAG_CASE);

        File::ensureDirectoryExists(dirname($output));
        $handle = fopen($output, 'w');

        if ($handle === false) {
            $this->error("Tidak dapat menulis berkas {$output}.");

            return self::FAILURE;
        }

        fwrite($handle, "# Katalog master ICD-10 MedScribe AI\n");
        fwrite($handle, '# Sumber: '.self::SUMBER_BASE['nama'].' - '.self::SUMBER_BASE['url']."\n");
        fwrite($handle, '# Sumber: '.self::SUMBER_IM['nama'].' - '.self::SUMBER_IM['url']."\n");
        fwrite($handle, '# Diunduh: '.now()->toDateString()."\n");
        fwrite($handle, "# Total kode: ".count($katalog)."\n");
        fputcsv($handle, ['kode_icd10', 'deskripsi', 'kategori', 'sumber']);

        foreach ($katalog as $baris) {
            fputcsv($handle, $baris);
        }

        fclose($handle);

        $this->info(sprintf('Katalog ICD-10 ditulis ke %s (%d kode).', $output, count($katalog)));

        return self::SUCCESS;
    }

    /**
     * Unduh CSV sumber; ulangi tanpa verifikasi SSL bila sertifikat lokal bermasalah.
     */
    private function unduh(string $url): string
    {
        try {
            $response = Http::timeout(120)->accept('text/csv')->get($url);
        } catch (ConnectionException $e) {
            $this->warn('  Verifikasi SSL gagal, mengulang tanpa verifikasi...');
            $response = Http::withoutVerifying()->timeout(120)->accept('text/csv')->get($url);
        }

        $response->throw();

        return $response->body();
    }

    /**
     * Baca CSV sumber menjadi array asosiatif per baris.
     *
     * @return array<int, array<string, string>>
     */
    private function bacaCsv(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(static fn ($kolom) => strtoupper(trim((string) $kolom)), $header);
        $baris = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null]) {
                continue;
            }

            $baris[] = array_combine($header, array_pad($data, count($header), ''));
        }

        fclose($handle);

        return $baris;
    }

    /**
     * Tentukan bab ICD-10 dari kode, atau null bila di luar rentang bab.
     */
    private function kategori(string $kode): ?string
    {
        $huruf = strtoupper(substr($kode, 0, 1));
        $angka = (int) preg_replace('/[^0-9].*$/', '', substr($kode, 1));

        foreach (self::BAB as [$babHuruf, $min, $max, $judul]) {
            if ($huruf === $babHuruf && $angka >= $min && $angka <= $max) {
                return $judul;
            }
        }

        return null;
    }

    /**
     * Rapikan judul: rapikan spasi ganda dan tanda baca sisa.
     */
    private function rapikan(string $judul): string
    {
        $judul = trim(preg_replace('/\s+/u', ' ', $judul) ?? $judul);

        return rtrim($judul, ",;:- ");
    }
}

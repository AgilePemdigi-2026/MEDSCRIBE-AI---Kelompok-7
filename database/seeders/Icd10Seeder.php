<?php

namespace Database\Seeders;

use App\Models\Icd10;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class Icd10Seeder extends Seeder
{
    /**
     * Berkas katalog master ICD-10 (hasil `php artisan medscribe:icd10:build-dataset`).
     */
    private const BERKAS = 'database/seeders/data/icd-10.csv';

    private const UKURAN_BATCH = 1000;

    /**
     * Mengisi katalog master ICD-10 dari berkas CSV resmi Kementerian Kesehatan RI.
     *
     * Seeder ini idempotent: data yang sudah ada diperbarui berdasarkan kode,
     * sehingga aman dijalankan ulang pada setiap deploy.
     */
    public function run(): void
    {
        $berkas = base_path(self::BERKAS);

        if (! File::exists($berkas)) {
            $this->command?->warn("Berkas katalog ICD-10 tidak ditemukan: {$berkas}. Jalankan `php artisan medscribe:icd10:build-dataset` terlebih dahulu.");

            return;
        }

        $baris = $this->baca($berkas);

        if ($baris === []) {
            $this->command?->warn('Katalog ICD-10 kosong, seeder dilewati.');

            return;
        }

        $sekarang = now();
        $total = 0;

        foreach (array_chunk($baris, self::UKURAN_BATCH) as $batch) {
            $barisUpsert = [];

            foreach ($batch as $data) {
                $barisUpsert[] = [
                    'kode_icd10' => $data['kode_icd10'],
                    'deskripsi' => $data['deskripsi'],
                    'kategori' => $data['kategori'],
                    'sumber' => $data['sumber'],
                    'is_active' => true,
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ];
            }

            Icd10::upsert(
                $barisUpsert,
                ['kode_icd10'],
                ['deskripsi', 'kategori', 'sumber', 'is_active', 'updated_at']
            );

            $total += count($barisUpsert);
        }

        $this->command?->info("Katalog ICD-10: {$total} kode disimpan.");
    }

    /**
     * Baca berkas CSV katalog, melewati baris komentar (#) dan baris kosong.
     *
     * @return array<int, array{kode_icd10: string, deskripsi: string, kategori: string, sumber: string}>
     */
    private function baca(string $berkas): array
    {
        $handle = fopen($berkas, 'r');

        if ($handle === false) {
            return [];
        }

        $header = null;
        $baris = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === []) {
                continue;
            }

            $pertama = trim((string) $data[0]);

            if ($header === null) {
                if ($pertama === '' || str_starts_with($pertama, '#')) {
                    continue;
                }

                $header = array_map(static fn ($kolom) => strtolower(trim((string) $kolom)), $data);

                continue;
            }

            if ($pertama === '' || str_starts_with($pertama, '#')) {
                continue;
            }

            $terpetakan = array_combine($header, array_pad($data, count($header), ''));

            if ($terpetakan === false) {
                continue;
            }

            $kode = strtoupper(trim((string) ($terpetakan['kode_icd10'] ?? '')));
            $deskripsi = trim((string) ($terpetakan['deskripsi'] ?? ''));

            if ($kode === '' || $deskripsi === '') {
                continue;
            }

            $baris[] = [
                'kode_icd10' => $kode,
                'deskripsi' => $deskripsi,
                'kategori' => trim((string) ($terpetakan['kategori'] ?? '')) ?: null,
                'sumber' => trim((string) ($terpetakan['sumber'] ?? '')) ?: 'satusehat_2010',
            ];
        }

        fclose($handle);

        return $baris;
    }
}

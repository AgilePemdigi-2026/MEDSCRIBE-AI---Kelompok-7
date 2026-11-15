<?php

namespace App\Services;

use App\Models\Icd10;
use Illuminate\Database\Eloquent\Collection;

/**
 * Akses katalog master ICD-10.
 *
 * Katalog berisi kode + judul resmi (WHO ICD-10 2010 dan Indonesian
 * Modification). Dokter tetap bebas mengetik kode apa pun pada formulir;
 * katalog hanya dipakai untuk mencari, memberi deskripsi, dan memvalidasi
 * saran kode yang dihasilkan AI.
 */
class Icd10Catalog
{
    /**
     * Kode ICD-10 yang paling sering dipakai di layanan primer.
     *
     * Daftar ini menjadi jangkar prompt AI agar model memilih kode yang benar
     * ada di katalog, bukan kode karangan. Deskripsi diambil dari tabel
     * `icd10` sehingga tidak perlu dicatat dua kali.
     *
     * @var array<int, string>
     */
    public const KODE_ANCHOR = [
        // Infeksi dan saluran pernapasan atas
        'A09', 'A15.0', 'A16.9', 'A41.9', 'B34.9', 'B35.1',
        'J00', 'J01.9', 'J02.9', 'J03.9', 'J04.0', 'J06.9', 'J10.1',
        'J12.9', 'J15.9', 'J18.9', 'J20.9', 'J30.1', 'J31.0', 'J35.0',
        'J40', 'J44.1', 'J45.9', 'J69.0',
        // Gejala, tanda, dan kelainan
        'R00.0', 'R03.0', 'R04.0', 'R05.1', 'R05.9', 'R06.0', 'R07.0',
        'R07.9', 'R10.4', 'R10.13', 'R10.31', 'R11.2', 'R11.10', 'R13.9',
        'R16.0', 'R19.7', 'R21.1', 'R25.2', 'R31.9', 'R35.0', 'R39.2',
        'R39.15', 'R41.0', 'R42', 'R43.0', 'R45.0', 'R45.4', 'R47.0',
        'R50.2', 'R50.9', 'R51', 'R53.3', 'R55', 'R56.9', 'R59.0', 'R60.9',
        'R68.83', 'R73.9',
        // Sistem sirkulasi
        'I10', 'I11.9', 'I25.9', 'I48.0', 'I49.9', 'I50.9', 'I63.9', 'I95.9',
        // Endokrin, nutrisi, metabolik
        'E03.9', 'E05.9', 'E10.9', 'E11.9', 'E14.9', 'E66.9', 'E78.5',
        'E86.0', 'E87.6',
        // Pencernaan
        'K21.9', 'K22.0', 'K27.0', 'K29.0', 'K29.7', 'K30', 'K52.9',
        'K59.0', 'K80.2', 'K81.0', 'K92.0',
        // Genitourinaria
        'N18.9', 'N20.9', 'N21.0', 'N30.0', 'N39.0', 'N40', 'N60',
        'N80.0', 'N83.2', 'N92.0',
        // Muskuloskeletal
        'M13.9', 'M17.9', 'M25.5', 'M54.2', 'M54.4', 'M54.5', 'M75.1',
        'M79.3', 'M79.7', 'S61.4', 'S83.5', 'S93.4',
        // Kulit
        'L20.9', 'L30.9', 'L40.0', 'L60.0', 'L72.0', 'L98.9',
        // Saraf
        'G43.9', 'G44.2', 'G47.0', 'G51.0', 'G54.0', 'G56.0', 'G93.4',
        // Jiwa dan perilaku
        'F32.9', 'F41.1', 'F45.1', 'F51.0',
        // Mata dan telinga
        'H10.9', 'H33.2', 'H40.9', 'H52.0', 'H52.1', 'H60.5', 'H66.9', 'H81.0',
        // Kehamilan
        'O21.0', 'O80',
        // Faktor luar
        'Z00.0', 'Z23.1', 'Z30.9', 'Z71.2', 'Z72.0',
    ];

    /**
     * Cache deskripsi kode dalam satu request.
     *
     * @var array<string, string|null>
     */
    private static array $cacheDeskripsi = [];

    /**
     * Cari kode ICD-10 berdasarkan kode atau potongan deskripsi.
     *
     * @return Collection<int, Icd10>
     */
    public function cari(string $kueri, int $batas = 10): Collection
    {
        $kueri = trim($kueri);

        if ($kueri === '') {
            return new Collection;
        }

        $kode = strtoupper($kueri);

        $tepat = Icd10::aktif()->where('kode_icd10', $kode)->get();
        if ($tepat->isNotEmpty()) {
            return $tepat->take($batas);
        }

        return Icd10::aktif()
            ->where(function ($query) use ($kode, $kueri) {
                $query->where('kode_icd10', 'like', $kode.'%')
                    ->orWhere('deskripsi', 'like', $kueri.'%')
                    ->orWhere('deskripsi', 'like', '%'.$kueri.'%');
            })
            ->orderByRaw('CASE WHEN kode_icd10 = ? THEN 0 ELSE 1 END', [$kode])
            ->orderBy('kode_icd10')
            ->limit($batas)
            ->get();
    }

    /**
     * Deskripsi resmi sebuah kode ICD-10, atau null bila kode tidak dikenal.
     */
    public function deskripsi(?string $kode): ?string
    {
        $kode = $this->normalisasiKode($kode);

        if ($kode === null) {
            return null;
        }

        if (array_key_exists($kode, self::$cacheDeskripsi)) {
            return self::$cacheDeskripsi[$kode];
        }

        $deskripsi = Icd10::where('kode_icd10', $kode)->value('deskripsi');

        return self::$cacheDeskripsi[$kode] = $deskripsi !== null ? (string) $deskripsi : null;
    }

    /**
     * Bab ICD-10 dari sebuah kode.
     */
    public function kategori(?string $kode): ?string
    {
        $kode = $this->normalisasiKode($kode);

        if ($kode === null) {
            return null;
        }

        $kategori = Icd10::where('kode_icd10', $kode)->value('kategori');

        return $kategori !== null ? (string) $kategori : null;
    }

    /**
     * Apakah kode ICD-10 ini ada di katalog master?
     */
    public function ada(?string $kode): bool
    {
        return $this->deskripsi($kode) !== null;
    }

    /**
     * Label yang ditampilkan untuk sebuah kode.
     *
     * Memprioritaskan deskripsi Bahasa Indonesia yang dihasilkan AI (hanya untuk
     * kode yang memang menjadi saran AI), lalu judul resmi dari katalog master.
     *
     * @param  array<int, array{code: string, description?: string}>  $saran
     */
    public function label(?string $kode, array $saran = []): ?string
    {
        $kode = $this->normalisasiKode($kode);

        if ($kode === null) {
            return null;
        }

        foreach ($saran as $item) {
            $kodeSaran = $this->normalisasiKode($item['code'] ?? null);

            if ($kodeSaran !== $kode) {
                continue;
            }

            $deskripsi = trim((string) ($item['description'] ?? ''));

            if ($deskripsi !== '') {
                return $deskripsi;
            }
        }

        return $this->deskripsi($kode);
    }

    /**
     * Label resmi untuk dokumen cetak: judul katalog master lebih diutamakan,
     * deskripsi AI hanya dipakai bila kode tidak dikenal katalog.
     *
     * @param  array<int, array{code: string, description?: string}>  $saran
     */
    public function labelResmi(?string $kode, array $saran = []): ?string
    {
        $deskripsi = $this->deskripsi($kode);

        if ($deskripsi !== null) {
            return $deskripsi;
        }

        return $this->label($kode, $saran);
    }

    /**
     * Normalisasi kode yang diketik dokter: huruf besar dan tanpa spasi.
     */
    public function normalisasiKode(?string $kode): ?string
    {
        $kode = strtoupper(trim((string) $kode));

        return $kode === '' ? null : $kode;
    }

    /**
     * Blok katalog ringkas untuk disisipkan ke prompt AI.
     *
     * @return string
     */
    public function katalogUntukPrompt(): string
    {
        $baris = Icd10::aktif()
            ->whereIn('kode_icd10', self::KODE_ANCHOR)
            ->orderBy('kode_icd10')
            ->get(['kode_icd10', 'deskripsi'])
            ->map(fn (Icd10 $kode) => $kode->kode_icd10.' - '.$kode->deskripsi)
            ->implode("\n");

        return $baris;
    }

    /**
     * Rapikan saran kode ICD-10 dari AI menjadi bentuk seragam.
     *
     * Saran dengan kode yang tidak ada di katalog master dibuang agar kode
     * karangan tidak pernah sampai ke layar dokter.
     *
     * @return array<int, array{code: string, description: string, confidence: string}>
     */
    public function normalisasiSaran(mixed $saran): array
    {
        if (! is_array($saran)) {
            return [];
        }

        $hasil = [];
        $terlihat = [];

        foreach ($saran as $item) {
            if (is_string($item)) {
                $item = ['code' => $item];
            }

            if (! is_array($item)) {
                continue;
            }

            $kode = $this->normalisasiKode($item['code'] ?? $item['kode_icd10'] ?? null);

            if ($kode === null || isset($terlihat[$kode])) {
                continue;
            }

            if (! $this->ada($kode)) {
                continue;
            }

            $terlihat[$kode] = true;

            $deskripsi = trim((string) ($item['description'] ?? $item['deskripsi'] ?? ''));

            $hasil[] = [
                'code' => $kode,
                'description' => $deskripsi !== '' ? $deskripsi : (string) ($this->deskripsi($kode) ?? ''),
                'confidence' => trim((string) ($item['confidence'] ?? $item['keyakinan'] ?? '')) ?: 'sedang',
            ];
        }

        return $hasil;
    }
}

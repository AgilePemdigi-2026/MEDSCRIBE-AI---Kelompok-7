<?php

namespace App\Http\Controllers;

use App\Services\Icd10Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Icd10Controller extends Controller
{
    public function __construct(private Icd10Catalog $catalog) {}

    /**
     * Pencarian kode ICD-10 untuk diisi manual oleh dokter.
     *
     * Mengembalikan daftar kode yang kecocokannya adalah: kode persis,
     * awalan kode, lalu potongan deskripsi.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $kueri = trim((string) ($validated['q'] ?? ''));

        if ($kueri === '') {
            return response()->json(['data' => []]);
        }

        $batas = (int) ($validated['limit'] ?? 10);

        $data = $this->catalog->cari($kueri, $batas)
            ->map(fn ($kode) => [
                'kode_icd10' => $kode->kode_icd10,
                'deskripsi' => $kode->deskripsi,
                'kategori' => $kode->kategori,
            ])
            ->values();

        return response()->json(['data' => $data]);
    }
}

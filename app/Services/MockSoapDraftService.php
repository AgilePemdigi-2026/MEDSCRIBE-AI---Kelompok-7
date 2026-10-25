<?php

namespace App\Services;

use App\Models\DrafSoap;
use App\Models\Kunjungan;

class MockSoapDraftService
{
    public const TRANSCRIPT = 'Pasien mengeluhkan sakit tenggorokan sejak tiga hari terakhir, disertai nyeri saat menelan dan sesekali batuk. Pasien tidak mengeluhkan sesak napas. Informasi penggunaan obat belum tersedia.';

    public function createFor(Kunjungan $kunjungan): DrafSoap
    {
        $vitals = $kunjungan->pemeriksaanAwal;

        return DrafSoap::firstOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            [
                'id_pengguna' => $kunjungan->id_dokter,
                'subjective_text' => "- Sakit tenggorokan sejak tiga hari terakhir.\n- Nyeri saat menelan.\n- Sesekali batuk.\n- Tidak mengeluhkan sesak napas.\n- Informasi penggunaan obat belum tersedia.",
                'objective_text' => $this->formatVitals($vitals),
                'assessment_text' => 'Penilaian awal: keluhan saluran napas atas. Diagnosis dan kesesuaian kode perlu ditinjau dokter.',
                'plan_text' => 'Rencana perlu ditinjau dan dilengkapi dokter berdasarkan pemeriksaan klinis. Belum ada tindakan atau terapi yang dinyatakan sudah dilakukan.',
                'kode_icd10' => 'J02.9',
            ]
        );
    }

    public function formatVitals($vitals): string
    {
        if (! $vitals) {
            return "Tekanan darah: Belum diisi\nDenyut nadi: Belum diisi\nSuhu tubuh: Belum diisi\nFrekuensi napas: Belum diisi";
        }

        return collect([
            $vitals->tekanan_darah ? "Tekanan darah: {$vitals->tekanan_darah} mmHg" : 'Tekanan darah: Belum diisi',
            $vitals->nadi !== null ? "Denyut nadi: {$vitals->nadi} x/menit" : 'Denyut nadi: Belum diisi',
            $vitals->suhu_tubuh !== null ? "Suhu tubuh: {$vitals->suhu_tubuh} °C" : 'Suhu tubuh: Belum diisi',
            $vitals->laju_pernapasan !== null ? "Frekuensi napas: {$vitals->laju_pernapasan} x/menit" : 'Frekuensi napas: Belum diisi',
        ])->implode("\n");
    }

    public function vitalsSnapshot($vitals): string
    {
        return json_encode([
            'tekanan_darah' => $vitals?->tekanan_darah,
            'nadi' => $vitals?->nadi,
            'suhu_tubuh' => $vitals?->suhu_tubuh,
            'laju_pernapasan' => $vitals?->laju_pernapasan,
            'berat_badan' => $vitals?->berat_badan,
            'tinggi_badan' => $vitals?->tinggi_badan,
        ], JSON_THROW_ON_ERROR);
    }
}

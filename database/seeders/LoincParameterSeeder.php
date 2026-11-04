<?php

namespace Database\Seeders;

use App\Models\ParameterLoinc;
use Illuminate\Database\Seeder;

class LoincParameterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->parameters() as [$kode, $nama, $kategori]) {
            ParameterLoinc::updateOrCreate(
                ['kode_loinc' => $kode],
                [
                    'nama_pemeriksaan' => $nama,
                    'kategori' => $kategori,
                    'is_active' => true,
                ]
            );
        }
    }

    private function parameters(): array
    {
        return [
            ['58410-2', 'Darah Lengkap', 'Hematologi'],
            ['6690-2', 'Leukosit', 'Hematologi'],
            ['789-8', 'Eritrosit', 'Hematologi'],
            ['718-7', 'Hemoglobin', 'Hematologi'],
            ['4544-3', 'Hematokrit', 'Hematologi'],
            ['777-3', 'Trombosit', 'Hematologi'],
            ['2339-0', 'Glukosa Darah', 'Kimia Klinik'],
            ['2160-0', 'Kreatinin', 'Kimia Klinik'],
            ['3094-0', 'Ureum', 'Kimia Klinik'],
            ['1742-6', 'ALT (SGPT)', 'Kimia Klinik'],
            ['1920-8', 'AST (SGOT)', 'Kimia Klinik'],
            ['1975-2', 'Bilirubin Total', 'Kimia Klinik'],
            ['2885-2', 'Protein Total', 'Kimia Klinik'],
            ['1751-7', 'Albumin', 'Kimia Klinik'],
            ['2951-2', 'Natrium', 'Kimia Klinik'],
            ['2823-3', 'Kalium', 'Kimia Klinik'],
            ['17861-6', 'Kalsium', 'Kimia Klinik'],
            ['4548-4', 'HbA1c', 'Endokrin'],
            ['3016-3', 'TSH', 'Endokrin'],
            ['3024-7', 'FT4', 'Endokrin'],
        ];
    }
}

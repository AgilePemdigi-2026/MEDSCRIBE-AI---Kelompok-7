<?php

namespace App\Contracts;

use App\Models\Kunjungan;

interface SoapGenerationService
{
    public function generate(Kunjungan $kunjungan, string $transcript): array;
}

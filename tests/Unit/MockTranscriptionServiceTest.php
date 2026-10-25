<?php

namespace Tests\Unit;

use App\Services\MockTranscriptionService;
use PHPUnit\Framework\TestCase;

class MockTranscriptionServiceTest extends TestCase
{
    public function test_transkrip_hanya_memuat_jawaban_yang_diisi(): void
    {
        $transcript = (new MockTranscriptionService)->transcribe([
            'keluhan_utama' => 'Sakit tenggorokan',
            'mulai_keluhan' => '',
            'alergi' => 'Belum ditanyakan',
        ]);

        $this->assertStringContainsString('Keluhan utama: Sakit tenggorokan', $transcript);
        $this->assertStringContainsString('Alergi: Belum ditanyakan', $transcript);
        $this->assertStringNotContainsString('Awal keluhan:', $transcript);
    }

    public function test_transkrip_kosong_jika_semua_jawaban_kosong(): void
    {
        $this->assertSame('', (new MockTranscriptionService)->transcribe([]));
    }
}

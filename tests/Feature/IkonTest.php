<?php

namespace Tests\Feature;

use App\Support\Ikon;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class IkonTest extends TestCase
{
    public function test_ikon_lucide_dirender_dengan_jenis_gerak(): void
    {
        $html = Blade::render('<x-icon name="trash-2" class="w-5 h-5" />');

        $this->assertStringContainsString('class="ikon w-5 h-5"', $html);
        $this->assertStringContainsString('data-gerak="tutup"', $html);
        $this->assertStringContainsString('class="ikon-tutup"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_path_heroicons_lama_dipetakan_ke_lucide(): void
    {
        $this->assertSame('check', Ikon::namaDariPath('M5 13l4 4L19 7'));

        $html = Blade::render('<x-icon :path="$d" />', ['d' => 'M6 18L18 6M6 6l12 12']);
        $this->assertStringContainsString('data-gerak="putar-90"', $html);
        $this->assertStringNotContainsString('M6 18L18 6', $html);
    }

    public function test_path_tak_dikenal_tetap_dirender_dan_static_tanpa_gerak(): void
    {
        $html = Blade::render('<x-icon path="M1 1h2" />');
        $this->assertStringContainsString('<path d="M1 1h2"/>', $html);

        $diam = Blade::render('<x-icon name="search" static />');
        $this->assertStringNotContainsString('data-gerak', $diam);
    }

    public function test_semua_ikon_di_registri_punya_isi(): void
    {
        foreach (Ikon::semua() as $nama => $ikon) {
            $this->assertNotEmpty($ikon['isi'], "Ikon {$nama} kosong");
        }
    }
}

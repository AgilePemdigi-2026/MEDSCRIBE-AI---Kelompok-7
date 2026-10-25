<?php

namespace Tests\Feature;

use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\Wawancara;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    private Pengguna $dokter;

    private Kunjungan $kunjungan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = Pengguna::firstOrCreate(
            ['email' => 'interview-test@medscribe.id'],
            ['nama_lengkap' => 'dr. Interview Test', 'role' => 'dokter', 'password' => bcrypt('password123')]
        );
        $pasien = Pasien::create([
            'no_rekam_medis' => 'TEST-INTERVIEW',
            'nama_pasien' => 'Pasien Wawancara Test',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
        ]);
        $this->kunjungan = Kunjungan::create([
            'id_pasien' => $pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => '2026-10-25',
            'status' => 'Siap Periksa',
        ]);
        PemeriksaanAwal::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'tekanan_darah' => '120/80',
            'nadi' => 78,
            'suhu_tubuh' => 36.5,
            'laju_pernapasan' => 18,
        ]);
    }

    public function test_dokter_dapat_memulai_dan_menyelesaikan_wawancara_simulasi(): void
    {
        $this->actingAs($this->dokter)
            ->post(route('dokter.interview.start', $this->kunjungan))
            ->assertRedirect();

        $answers = ['keluhan_utama' => 'Sakit tenggorokan sejak tiga hari'];
        $response = $this->actingAs($this->dokter)->post(
            route('dokter.interview.finish', $this->kunjungan),
            ['answers' => $answers]
        );

        $response->assertRedirect(route('dokter.soap.edit', $this->kunjungan));
        $this->assertDatabaseHas('wawancara', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'sumber_transkrip' => 'manual_simulation',
        ]);
        $this->assertStringContainsString(
            'Sakit tenggorokan sejak tiga hari',
            Wawancara::where('id_kunjungan', $this->kunjungan->id_kunjungan)->first()->transkrip
        );
    }

    public function test_review_soap_menggunakan_transkrip_sesi_wawancara_dan_ttv(): void
    {
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'jawaban' => ['keluhan_utama' => 'Nyeri saat menelan'],
            'transkrip' => 'Keluhan utama: Nyeri saat menelan',
            'sumber_transkrip' => 'manual_simulation',
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $response = $this->actingAs($this->dokter)->get(route('dokter.soap.edit', $this->kunjungan));

        $response->assertOk()->assertSee('Nyeri saat menelan')->assertSee('120/80');
        $this->assertDatabaseHas('draf_soap', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'source_type' => 'mock_generation',
            'needs_review' => 0,
        ]);
        $this->assertStringContainsString('Nyeri saat menelan', DrafSoap::where('id_kunjungan', $this->kunjungan->id_kunjungan)->first()->subjective_text);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PemeriksaanAwalTest extends TestCase
{
    protected Pengguna $perawat;
    protected Pengguna $dokter;
    protected Pasien $pasien;
    protected Antrean $antrean;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan akun perawat & dokter
        $this->perawat = Pengguna::firstOrCreate(
            ['email' => 'dewi@medscribe.id'],
            [
                'nama_lengkap' => 'Dewi Pratiwi',
                'role' => 'perawat',
                'password' => bcrypt('password123'),
            ]
        );

        $this->dokter = Pengguna::firstOrCreate(
            ['email' => 'rina@medscribe.id'],
            [
                'nama_lengkap' => 'dr. Rina Anggraini, Sp.PD',
                'role' => 'dokter',
                'password' => bcrypt('password123'),
            ]
        );

        // Ambil atau buat antrean pasien
        $this->antrean = Antrean::where('status', 'Menunggu TTV')->first() 
            ?? Antrean::create([
                'id_pasien' => 4,
                'tanggal_kunjungan' => '2026-09-19',
                'status' => 'Menunggu TTV',
            ]);
    }

    /**
     * REQ-1 & REQ-3: Halaman pemeriksaan awal dapat diakses oleh Perawat.
     */
    public function test_perawat_dapat_mengakses_dashboard_pemeriksaan_awal(): void
    {
        $response = $this->actingAs($this->perawat)->get('/perawat/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Formulir Pemeriksaan Awal', false);
        $response->assertSee('Daftar Antrean');
        $response->assertSee('Tanda-tanda vital');
    }

    /**
     * Otorisasi: Dokter tidak boleh membuka halaman input TTV perawat.
     */
    public function test_dokter_tidak_dapat_mengakses_dashboard_pemeriksaan_awal(): void
    {
        $response = $this->actingAs($this->dokter)->get('/perawat/dashboard');

        $response->assertRedirect('/dokter/dashboard');
        $response->assertSessionHas('warning');
    }

    /**
     * REQ-1: Perawat dapat memfilter/mencari antrean pasien.
     */
    public function test_perawat_dapat_mencari_pasien_di_antrean(): void
    {
        $response = $this->actingAs($this->perawat)->get('/perawat/dashboard?cari=Budi');

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
    }

    /**
     * REQ-2: Perawat dapat memilih pasien tertentu dari daftar antrean.
     */
    public function test_perawat_dapat_memilih_pasien_tertentu_dari_antrean(): void
    {
        $targetAntrean = Antrean::first();

        $response = $this->actingAs($this->perawat)->get('/perawat/dashboard?antrean_id=' . $targetAntrean->id_antrean);

        $response->assertStatus(200);
        $response->assertSee($targetAntrean->pasien->nama_pasien);
    }

    /**
     * REQ-4 s/d REQ-10: Perawat dapat menyimpan data TTV, status antrean terupdate,
     * dan data TTV otomatis masuk ke komponen Objective draf SOAP dokter.
     */
    public function test_simpan_pemeriksaan_awal_berhasil_dan_terintegrasi(): void
    {
        // Pastikan status antrean awal adalah 'Menunggu TTV'
        $this->antrean->update(['status' => 'Menunggu TTV']);

        $payload = [
            'id_antrean'      => $this->antrean->id_antrean,
            'tekanan_darah'   => '120/80',
            'nadi'            => 78,
            'suhu_tubuh'      => 38.6,
            'laju_pernapasan' => 18,
            'berat_badan'     => 68,
            'tinggi_badan'    => 170,
            'catatan'         => 'Demam 3 hari, nyeri menelan, dan batuk kering. Suhu tubuh 38,6°C.',
        ];

        $response = $this->actingAs($this->perawat)->post('/perawat/pemeriksaan-awal', $payload);

        // 1. Verifikasi redirect dan flash message (REQ-9)
        $response->assertRedirect('/perawat/dashboard?antrean_id=' . $this->antrean->id_antrean);
        $response->assertSessionHas('status', 'Data berhasil disimpan!');

        // 2. Verifikasi data tersimpan di tabel pemeriksaan_awal (REQ-7)
        $this->assertDatabaseHas('pemeriksaan_awal', [
            'id_perawat'      => $this->perawat->id_pengguna,
            'tekanan_darah'   => '120/80',
            'nadi'            => 78,
            'suhu_tubuh'      => 38.6,
            'laju_pernapasan' => 18,
            'berat_badan'     => 68.0,
            'tinggi_badan'    => 170.0,
            'catatan'         => 'Demam 3 hari, nyeri menelan, dan batuk kering. Suhu tubuh 38,6°C.',
        ]);

        // 3. Verifikasi status antrean berubah menjadi 'Siap Periksa' (REQ-8)
        $this->assertEquals('Siap Periksa', $this->antrean->fresh()->status);

        // 4. Verifikasi komponen Objective terisi otomatis di draf_soap (REQ-10)
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->first();
        $this->assertNotNull($kunjungan);

        $drafSoap = DrafSoap::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
        $this->assertNotNull($drafSoap);
        $this->assertStringContainsString('TD 120/80 mmHg', $drafSoap->objective_text);
        $this->assertStringContainsString('Nadi 78 x/menit', $drafSoap->objective_text);
        $this->assertStringContainsString('Suhu 38,6°C', $drafSoap->objective_text);
        $this->assertStringContainsString('RR 18 x/menit', $drafSoap->objective_text);
        $this->assertStringContainsString('BB 68 kg', $drafSoap->objective_text);
        $this->assertStringContainsString('TB 170 cm', $drafSoap->objective_text);
    }

    /**
     * REQ-6: Validasi gagal jika kolom wajib kosong atau format tekanan darah salah.
     */
    public function test_validasi_pemeriksaan_awal_gagal_jika_format_salah(): void
    {
        $payload = [
            'id_antrean'      => $this->antrean->id_antrean,
            'tekanan_darah'   => '120-80', // Format salah (harus 120/80)
            'nadi'            => 'bukan_angka',
            'suhu_tubuh'      => 99.9, // Melebihi batas fisiologis normal
            'laju_pernapasan' => '',
            'berat_badan'     => '',
            'tinggi_badan'    => '',
            'catatan'         => '',
        ];

        $response = $this->actingAs($this->perawat)->post('/perawat/pemeriksaan-awal', $payload);

        $response->assertSessionHasErrors([
            'tekanan_darah',
            'nadi',
            'suhu_tubuh',
            'laju_pernapasan',
            'berat_badan',
            'tinggi_badan',
            'catatan',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DokterRekamMedisTest extends TestCase
{
    protected Pengguna $dokter;
    protected Pengguna $perawat;
    protected Pasien $pasien;
    protected Antrean $antrean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = Pengguna::firstOrCreate(
            ['email' => 'rina@medscribe.id'],
            [
                'nama_lengkap' => 'dr. Rina Anggraini, Sp.PD',
                'role' => 'dokter',
                'password' => bcrypt('password123'),
            ]
        );

        $this->perawat = Pengguna::firstOrCreate(
            ['email' => 'dewi@medscribe.id'],
            [
                'nama_lengkap' => 'Dewi Pratiwi',
                'role' => 'perawat',
                'password' => bcrypt('password123'),
            ]
        );

        $this->pasien = Pasien::firstOrCreate(
            ['no_rekam_medis' => '00-12-999'],
            [
                'nama_pasien' => 'Tes Pasien Dokter',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'umur' => 36,
            ]
        );

        $this->antrean = Antrean::firstOrCreate(
            ['id_pasien' => $this->pasien->id_pasien],
            [
                'tanggal_kunjungan' => '2026-09-19',
                'status' => 'Siap Periksa',
            ]
        );
    }

    /**
     * 1. Dokter dapat mengakses halaman rekam medis pasien.
     * Bagian Draf Catatan Medis (SOAP) dan kontrol AI dipastikan tidak ditampilkan pada sprint ini.
     */
    public function test_dokter_dapat_mengakses_halaman_rekam_medis(): void
    {
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $response->assertStatus(200);
        $response->assertSee($this->pasien->nama_pasien);
        $response->assertSee('TTV & Catatan Perawat');
        $response->assertSee('Rekam Wawancara Klinis');

        // Pastikan draf SOAP dan kontrol AI tidak tampil di halaman
        $response->assertDontSee('Draf Catatan Medis (SOAP)');
        $response->assertDontSee('Proses AI Ulang');
    }

    /**
     * 2. Perawat tidak diizinkan mengakses halaman rekam medis dokter.
     */
    public function test_perawat_ditolak_mengakses_halaman_rekam_medis_dokter(): void
    {
        $response = $this->actingAs($this->perawat)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $response->assertRedirect('/perawat/dashboard');
        $response->assertSessionHas('warning');
    }

    /**
     * 3. Halaman menampilkan data TTV dan catatan perawat yang sebenarnya.
     */
    public function test_rekam_medis_menampilkan_data_ttv_dan_catatan_perawat(): void
    {
        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $this->antrean->id_antrean],
            [
                'id_pasien' => $this->pasien->id_pasien,
                'id_dokter' => $this->dokter->id_pengguna,
                'tanggal' => '2026-09-19',
                'status' => 'Sedang Diperiksa',
            ]
        );

        PemeriksaanAwal::updateOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            [
                'id_perawat' => $this->perawat->id_pengguna,
                'tekanan_darah' => '125/85',
                'suhu_tubuh' => 37.2,
                'nadi' => 82,
                'laju_pernapasan' => 19,
                'berat_badan' => 70,
                'tinggi_badan' => 172,
                'catatan' => 'Pasien mengeluh pusing dan pegal pada leher belakang.',
            ]
        );

        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $response->assertStatus(200);
        $response->assertSee('125/85');
        $response->assertSee('37.2');
        $response->assertSee('82');
        $response->assertSee('Pasien mengeluh pusing dan pegal pada leher belakang.');
        $response->assertSee('Dewi Pratiwi');
    }

    /**
     * 4. Dokter dapat mengunggah rekaman audio klinis tanpa memicu integrasi AI.
     */
    public function test_dokter_dapat_mengunggah_rekaman_audio(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('wawancara.webm', 500, 'audio/webm');

        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/' . $this->antrean->id_antrean . '/audio',
            ['audio' => $file]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Rekaman wawancara klinis berhasil disimpan di server.',
        ]);

        $this->assertDatabaseHas('rekaman_audio', [
            'status' => 'Tersimpan',
        ]);
    }

    /**
     * 5. Unggah audio tidak memicu ekstraksi SOAP atau panggilan AI otomatis.
     */
    public function test_unggah_audio_tidak_memicu_generasi_draf_soap_otomatis(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('wawancara_klinis.webm', 400, 'audio/webm');

        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/' . $this->antrean->id_antrean . '/audio',
            ['audio' => $file]
        );

        $response->assertStatus(200);
        $response->assertJsonMissing(['ai_processed' => true]);
        $response->assertJsonMissing(['soap' => []]);
    }

    /**
     * 6. Rute pemrosesan AI dinonaktifkan dari alur aktif sprint ini.
     */
    public function test_rute_proses_ai_tidak_aktif(): void
    {
        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/' . $this->antrean->id_antrean . '/proses-ai'
        );

        $response->assertStatus(404);
    }
}

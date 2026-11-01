<?php

namespace Tests\BDD;

use App\Models\Antrean;
use App\Models\Hospital;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * BDD Test Suite: Fitur 04 - Isolasi Data Multi-Tenancy Berbasis Rumah Sakit
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/04_isolasi_multi_tenancy_rumah_sakit.feature
 */
class IsolasiMultiTenancyBDDTest extends BDDTestCase
{
    protected Hospital $hospitalA;
    protected Hospital $hospitalB;
    protected Pengguna $perawatA;
    protected Pengguna $dokterA;
    protected Pengguna $perawatB;
    protected Pengguna $dokterB;
    protected Pasien $pasienA;
    protected Antrean $antreanA;
    protected Pasien $pasienB;
    protected Antrean $antreanB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospitalA = $this->getDefaultHospital();
        $this->hospitalB = $this->getSecondaryHospital();

        $this->perawatA = $this->createPerawat(['hospital' => $this->hospitalA]);
        $this->dokterA  = $this->createDokter(['hospital' => $this->hospitalA]);

        $this->perawatB = $this->createPerawat(['hospital' => $this->hospitalB]);
        $this->dokterB  = $this->createDokter(['hospital' => $this->hospitalB]);

        $this->pasienA = Pasien::create([
            'hospital_id'    => $this->hospitalA->id,
            'no_rekam_medis' => 'BDD-A-' . uniqid(),
            'nama_pasien'    => 'Pasien Unik RS A',
            'jenis_kelamin'  => 'Laki-laki',
        ]);

        $this->antreanA = Antrean::create([
            'hospital_id'       => $this->hospitalA->id,
            'id_pasien'         => $this->pasienA->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Siap Periksa',
        ]);

        $this->pasienB = Pasien::create([
            'hospital_id'    => $this->hospitalB->id,
            'no_rekam_medis' => 'BDD-B-' . uniqid(),
            'nama_pasien'    => 'Pasien Rahasia RS B',
            'jenis_kelamin'  => 'Perempuan',
        ]);

        $this->antreanB = Antrean::create([
            'hospital_id'       => $this->hospitalB->id,
            'id_pasien'         => $this->pasienB->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Siap Periksa',
        ]);
    }

    /**
     * Skenario: Perawat Rumah Sakit A hanya dapat melihat antrean pasien Rumah Sakit A
     */
    public function test_skenario_perawat_hanya_melihat_antrean_rumah_sakitnya(): void
    {
        $this->given('Perawat RS A login dan membuka dashboard Nurse Station');
        $this->when('Perawat RS A mengakses daftar antrean');
        $response = $this->actingAs($this->perawatA)->get('/perawat/dashboard');

        $this->then('Sistem menampilkan data pasien RS A');
        $response->assertStatus(200);
        $response->assertSee('Pasien Unik RS A');

        $this->and('Sistem menyembunyikan data pasien RS B');
        $response->assertDontSee('Pasien Rahasia RS B');
    }

    /**
     * Skenario: Dokter Rumah Sakit A ditolak saat mengakses rekam medis pasien Rumah Sakit B
     */
    public function test_skenario_dokter_rs_a_dilarang_akses_rekam_medis_rs_b(): void
    {
        $this->given('Dokter aktif yang bertugas di Rumah Sakit A');
        $this->when('Dokter RS A mencoba mengakses rekam medis pasien RS B melalui URL langsung');
        $response = $this->actingAs($this->dokterA)->get('/dokter/rekam-medis/' . $this->antreanB->id_antrean);

        $this->then('Sistem menolak akses dengan kode HTTP 403 Forbidden');
        $response->assertStatus(403);
    }

    /**
     * Skenario: Dokter Rumah Sakit A dilarang mengunggah audio konsultasi untuk pasien Rumah Sakit B
     */
    public function test_skenario_dokter_rs_a_dilarang_unggah_audio_untuk_pasien_rs_b(): void
    {
        $this->given('Dokter yang bertugas di RS A dan file audio');
        Storage::fake('public');
        $audioFile = UploadedFile::fake()->create('rekaman.wav', 200, 'audio/wav');

        $this->when('Dokter RS A mengirim unggahan audio ke antrean milik RS B');
        $response = $this->actingAs($this->dokterA)->postJson('/dokter/rekam-medis/' . $this->antreanB->id_antrean . '/audio', [
            'audio' => $audioFile,
        ]);

        $this->then('Sistem menolak unggahan dengan status 403 Forbidden');
        $response->assertStatus(403);
    }

    /**
     * Skenario: Dokter Rumah Sakit A dilarang streaming audio konsultasi pasien Rumah Sakit B
     */
    public function test_skenario_dokter_rs_a_dilarang_streaming_audio_pasien_rs_b(): void
    {
        $this->given('Dokter bertugas di RS A');
        $this->when('Dokter RS A meminta file streaming audio dari antrean RS B');
        $response = $this->actingAs($this->dokterA)->get('/dokter/rekam-medis/' . $this->antreanB->id_antrean . '/audio-file');

        $this->then('Sistem merespons dengan 403 Forbidden');
        $response->assertStatus(403);
    }

    /**
     * Skenario: Perawat Rumah Sakit A dilarang membatalkan pasien antrean Rumah Sakit B
     */
    public function test_skenario_perawat_rs_a_dilarang_membatalkan_pasien_rs_b(): void
    {
        $this->given('Perawat bertugas di RS A dan data pasien di RS B');
        $this->when('Perawat RS A mengirim permintaan pembatalan pasien RS B');
        $response = $this->actingAs($this->perawatA)->delete('/perawat/pasien/' . $this->pasienB->id_pasien);

        $this->then('Sistem menolak aksi dengan kode 403 Forbidden');
        $response->assertStatus(403);

        $this->and('Data pasien RS B tetap utuh di database');
        $this->assertDatabaseHas('pasien', [
            'id_pasien' => $this->pasienB->id_pasien,
        ]);
    }

    /**
     * Skenario: Registrasi pasien baru otomatis terikat dengan rumah sakit perawat yang bertugas
     */
    public function test_skenario_registrasi_pasien_baru_otomatis_terikat_ke_rumah_sakit_perawat(): void
    {
        $this->given('Perawat RS A yang sedang login');
        $this->when('Perawat mendaftarkan pasien baru');
        $response = $this->actingAs($this->perawatA)->post('/perawat/pasien-baru', [
            'nama_pasien'             => 'Bambang Sudarsono',
            'umur'                    => 42,
            'jenis_kelamin'           => 'Laki-laki',
            'tekanan_darah_sistolik'  => 120,
            'tekanan_darah_diastolik' => 80,
            'nadi'                    => 78,
            'suhu_tubuh'              => 36.5,
            'laju_pernapasan'         => 18,
            'saturasi_oksigen'        => 98,
        ]);

        $this->then('Pasien baru otomatis tersimpan dengan hospital_id RS A');
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('pasien', [
            'nama_pasien' => 'Bambang Sudarsono',
            'hospital_id' => $this->hospitalA->id,
        ]);
    }
}

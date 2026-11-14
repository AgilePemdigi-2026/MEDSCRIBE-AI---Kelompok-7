<?php

namespace Tests\BDD;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Support\Facades\Storage;

/**
 * BDD Test Suite: Fitur 06 - Poliklinik Dokter - Rekam Medis dan AI Medical Scribe
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/06_poliklinik_dokter_rekam_medis.feature
 */
class PoliklinikDokterRekamMedisBDDTest extends BDDTestCase
{
    protected Pengguna $dokter;
    protected Pengguna $perawat;
    protected Pasien $pasien;
    protected Antrean $antrean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter  = $this->createDokter();
        $this->perawat = $this->createPerawat(['hospital' => $this->dokter->hospital]);

        $this->pasien = Pasien::create([
            'hospital_id'    => $this->dokter->hospital_id,
            'no_rekam_medis' => 'BDD-DOC-' . uniqid(),
            'nama_pasien'    => 'Pasien Poliklinik BDD',
            'jenis_kelamin'  => 'Laki-laki',
        ]);

        $this->antrean = Antrean::create([
            'hospital_id'       => $this->dokter->hospital_id,
            'id_pasien'         => $this->pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Menunggu TTV',
        ]);

        // Alur nyata: perawat mengisi TTV lebih dulu, sehingga antrean menjadi "Siap Periksa".
        // Tanpa TTV, bagian wawancara di halaman rekam medis masih terkunci.
        $this->actingAs($this->perawat)->post('/perawat/pemeriksaan-awal', [
            'id_antrean'      => $this->antrean->id_antrean,
            'tekanan_darah'   => '130/85',
            'nadi'            => 84,
            'suhu_tubuh'      => 37.2,
            'laju_pernapasan' => 20,
            'berat_badan'     => 68,
            'tinggi_badan'    => 170,
            'catatan'         => 'Keluhan batuk berdahak 4 hari',
        ])->assertSessionDoesntHaveErrors();
        $this->antrean->refresh();
    }

    /**
     * Skenario: Dokter membuka halaman rekam medis pasien yang siap periksa
     * @scenario Open Patient Medical Record
     */
    public function test_skenario_dokter_membuka_halaman_rekam_medis_pasien_siap_periksa(): void
    {
        $this->given('Dokter aktif bertugas dan pasien siap diperiksa');
        $this->assertEquals('Siap Periksa', $this->antrean->status);

        $this->when('Dokter membuka halaman rekam medis untuk antrean pasien tersebut');
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $this->then('Sistem menampilkan halaman rekam medis pasien beserta bagian TTV dan wawancara');
        $response->assertStatus(200);
        $response->assertSee($this->pasien->nama_pasien);
        $response->assertSee('TTV & Catatan Perawat', false);
        $response->assertSee('Mulai Wawancara');

        $this->and('Status antrean bertransisi menjadi "Sedang Diperiksa"');
        $this->antrean->refresh();
        $this->assertEquals('Sedang Diperiksa', $this->antrean->status);

        $this->and('Kunjungan pasien tercatat dengan status "Sedang Diperiksa"');
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->first();
        $this->assertNotNull($kunjungan);
        $this->assertEquals('Sedang Diperiksa', $kunjungan->status);
    }

    /**
     * Skenario: Data TTV perawat ditampilkan pada halaman rekam medis dokter
     * @scenario Review Vital Signs Recorded by the Nurse
     */
    public function test_skenario_data_ttv_perawat_ditampilkan_pada_rekam_medis_dokter(): void
    {
        $this->given('Perawat telah menginput TTV pasien (TD 130/85, suhu 37.2, nadi 84)');
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->firstOrFail();
        $this->assertDatabaseHas('pemeriksaan_awal', [
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_perawat'   => $this->perawat->id_pengguna,
        ]);

        $this->when('Dokter membuka rekam medis pasien');
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $this->then('Halaman menampilkan nilai TTV dan catatan perawat');
        $response->assertStatus(200);
        $response->assertSee('130/85');
        $response->assertSee('37.2');
        $response->assertSee('84');
        $response->assertSee('Keluhan batuk berdahak 4 hari');
    }

    /**
     * Skenario: Dokter memulai sesi wawancara klinis
     * @scenario Start Clinical Interview Session
     */
    public function test_skenario_dokter_memulai_sesi_wawancara(): void
    {
        $this->given('Dokter sedang membuka rekam medis pasien');
        $halamanRekamMedis = '/dokter/rekam-medis/' . $this->antrean->id_antrean;
        $this->actingAs($this->dokter)->get($halamanRekamMedis)->assertStatus(200);
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->firstOrFail();

        $this->when('Dokter menekan tombol "Mulai Wawancara"');
        $response = $this->actingAs($this->dokter)
            ->from($halamanRekamMedis)
            ->post(route('dokter.interview.start', $kunjungan));

        $this->then('Dokter kembali ke bagian wawancara pada halaman rekam medis dengan pesan sukses');
        $response->assertRedirect($halamanRekamMedis . '#wawancara');
        $response->assertSessionHas('status', 'Wawancara dimulai. Rekam audio untuk membuat transkrip.');

        $this->and('Sesi wawancara tercatat dengan status "Wawancara Berlangsung"');
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
        $this->assertNotNull($wawancara);
        $this->assertEquals('Wawancara Berlangsung', $wawancara->status);
    }

    /**
     * Skenario: Dokter dapat melakukan streaming/pemutaran ulang rekaman audio konsultasi
     * @scenario Stream Consultation Audio Recording
     */
    public function test_skenario_dokter_dapat_streaming_audio_rekaman(): void
    {
        $this->given('Kunjungan pasien memiliki file rekaman audio tersimpan di storage');
        Storage::fake('local');

        $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean)->assertStatus(200);
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->firstOrFail();

        $fakeFilePath = 'audio/sample_audio.wav';
        Storage::disk('local')->put($fakeFilePath, 'fake-wav-audio-content');

        RekamanAudio::create([
            'id_kunjungan'  => $kunjungan->id_kunjungan,
            'path_audio'    => $fakeFilePath,
            'status'        => 'completed',
            'waktu_selesai' => now(),
        ]);

        $this->when('Dokter mengakses rute streaming audio pasien');
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean . '/audio-file');

        $this->then('Sistem mengalirkan konten file audio dengan status 200');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'audio/wav');
    }
}

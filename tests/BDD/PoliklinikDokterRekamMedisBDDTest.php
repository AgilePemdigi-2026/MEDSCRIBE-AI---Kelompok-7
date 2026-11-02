<?php

namespace Tests\BDD;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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
            'status'            => 'Siap Periksa',
        ]);
    }

    /**
     * Skenario: Dokter membuka halaman rekam medis pasien yang siap periksa
     */
    public function test_skenario_dokter_membuka_halaman_rekam_medis_pasien_siap_periksa(): void
    {
        $this->given('Dokter aktif bertugas dan pasien siap diperiksa');
        $this->when('Dokter membuka halaman rekam medis untuk antrean pasien tersebut');
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $this->then('Sistem menampilkan halaman rekam medis pasien');
        $response->assertStatus(200);
        $response->assertSee($this->pasien->nama_pasien);

        $this->and('Status antrean bertransisi menjadi "Sedang Diperiksa"');
        $this->antrean->refresh();
        $this->assertEquals('Sedang Diperiksa', $this->antrean->status);

        $this->and('Kunjungan pasien tercatat dengan status "Sedang Diperiksa"');
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->first();
        $this->assertNotNull($kunjungan);
        $this->assertEquals('Sedang Diperiksa', $kunjungan->status);
    }

    /**
     * Skenario: Data TTV perawat otomatis terintegrasi ke komponen Objective draf SOAP dokter
     */
    public function test_skenario_data_ttv_perawat_ditampilkan_pada_rekam_medis_dokter(): void
    {
        $this->given('Kunjungan pasien memiliki data TTV yang diinput oleh perawat');
        $kunjungan = Kunjungan::create([
            'hospital_id' => $this->dokter->hospital_id,
            'id_antrean'  => $this->antrean->id_antrean,
            'id_pasien'   => $this->pasien->id_pasien,
            'id_dokter'   => $this->dokter->id_pengguna,
            'tanggal'     => now()->toDateString(),
            'status'      => 'Sedang Diperiksa',
        ]);

        PemeriksaanAwal::create([
            'id_kunjungan'    => $kunjungan->id_kunjungan,
            'id_perawat'      => $this->perawat->id_pengguna,
            'tekanan_darah'   => '130/85',
            'suhu_tubuh'      => 37.2,
            'nadi'            => 84,
            'laju_pernapasan' => 20,
            'berat_badan'     => 68,
            'tinggi_badan'    => 170,
            'catatan'         => 'Keluhan batuk berdahak 4 hari',
        ]);

        $this->when('Dokter membuka rekam medis pasien');
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean);

        $this->then('Halaman menampilkan nilai TTV perawat');
        $response->assertStatus(200);
        $response->assertSee('130/85');
        $response->assertSee('37.2');
        $response->assertSee('84');
        $response->assertSee('Keluhan batuk berdahak 4 hari');
    }

    /**
     * Skenario: Dokter berhasil mengunggah rekaman audio konsultasi dengan format yang valid
     */
    public function test_skenario_dokter_berhasil_mengunggah_rekaman_audio(): void
    {
        $this->given('Dokter menangani pasien dan file audio wawancara klinis WAV');
        Storage::fake('local');
        Queue::fake();
        $wavContent = 'RIFF' . pack('V', 36 + 32000) . 'WAVEfmt ' . pack('VvvVVv', 16, 1, 1, 16000, 32000, 2) . 'data' . pack('V', 32000) . str_repeat("\0", 32000);
        $audio = UploadedFile::fake()->createWithContent('konsultasi.wav', $wavContent);

        $this->when('Dokter mengirimkan file audio via endpoint audio');
        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/' . $this->antrean->id_antrean . '/audio',
            ['audio' => $audio]
        );

        $this->then('Sistem menerima file dan merespons sukses');
        $this->assertTrue(in_array($response->status(), [200, 202]));
        $response->assertJson([
            'success' => true,
        ]);

        $this->and('Metadata rekaman audio tersimpan di database');
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->first();
        $this->assertDatabaseHas('rekaman_audio', [
            'id_kunjungan' => $kunjungan->id_kunjungan,
        ]);
    }

    /**
     * Skenario: Unggahan audio ditolak jika format file tidak valid
     */
    public function test_skenario_unggahan_audio_ditolak_jika_format_tidak_valid(): void
    {
        $this->given('Dokter mencoba mengirim data yang bukan merupakan berkas audio');

        $this->when('Dokter mengirim payload invalid (string bukan berkas)');
        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/' . $this->antrean->id_antrean . '/audio',
            ['audio' => 'bukan-berkas-file-audio']
        );

        $this->then('Sistem menolak dengan error validasi file audio 422 Unprocessable Entity');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('audio');
    }

    /**
     * Skenario: Dokter dapat melakukan streaming/pemutaran ulang rekaman audio konsultasi
     */
    public function test_skenario_dokter_dapat_streaming_audio_rekaman(): void
    {
        $this->given('Kunjungan pasien memiliki file rekaman audio tersimpan di storage');
        Storage::fake('local');

        $kunjungan = Kunjungan::create([
            'hospital_id' => $this->dokter->hospital_id,
            'id_antrean'  => $this->antrean->id_antrean,
            'id_pasien'   => $this->pasien->id_pasien,
            'id_dokter'   => $this->dokter->id_pengguna,
            'tanggal'     => now()->toDateString(),
            'status'      => 'Sedang Diperiksa',
        ]);

        $fakeFilePath = 'audio/sample_audio.wav';
        Storage::disk('local')->put($fakeFilePath, 'fake-wav-audio-content');

        RekamanAudio::create([
            'id_kunjungan'  => $kunjungan->id_kunjungan,
            'path_audio'    => $fakeFilePath,
            'status'        => 'Tersimpan',
            'waktu_selesai' => now(),
        ]);

        $this->when('Dokter mengakses rute streaming audio pasien');
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/' . $this->antrean->id_antrean . '/audio-file');

        $this->then('Sistem mengalirkan konten file audio dengan status 200');
        $response->assertStatus(200);
    }
}

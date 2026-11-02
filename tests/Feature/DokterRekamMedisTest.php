<?php

namespace Tests\Feature;

use App\Jobs\ProcessInterviewAudio;
use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DokterRekamMedisTest extends TestCase
{
    use DatabaseTransactions;

    protected Pengguna $dokter;

    protected Pengguna $perawat;

    protected Pasien $pasien;

    protected Antrean $antrean;

    protected string $ffprobeStubPath;

    protected function setUp(): void
    {
        parent::setUp();

        $probeDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'medscribe-ffprobe-'.uniqid();
        mkdir($probeDirectory);
        $this->ffprobeStubPath = $probeDirectory.DIRECTORY_SEPARATOR.'ffprobe.bat';
        file_put_contents($this->ffprobeStubPath, "@echo {\"streams\":[{\"codec_type\":\"audio\"}],\"format\":{\"format_name\":\"matroska,webm\",\"duration\":\"1.0\"}}\r\n");
        config(['medscribe.audio.ffprobe_path' => $this->ffprobeStubPath]);

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

        $this->pasien = Pasien::withTrashed()->firstOrCreate(
            ['no_rekam_medis' => '00-12-999'],
            [
                'nama_pasien' => 'Tes Pasien Dokter',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'umur' => 36,
            ]
        );
        if ($this->pasien->trashed()) {
            $this->pasien->restore();
        }

        $this->antrean = Antrean::firstOrCreate(
            ['id_pasien' => $this->pasien->id_pasien],
            [
                'tanggal_kunjungan' => '2026-09-19',
                'status' => 'Siap Periksa',
            ]
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->ffprobeStubPath) && is_file($this->ffprobeStubPath)) {
            $directory = dirname($this->ffprobeStubPath);
            unlink($this->ffprobeStubPath);
            @rmdir($directory);
        }

        parent::tearDown();
    }

    /**
     * 1. Dokter dapat mengakses halaman rekam medis pasien.
     * Bagian Draf Catatan Medis (SOAP) dan kontrol AI dipastikan tidak ditampilkan pada sprint ini.
     */
    public function test_dokter_dapat_mengakses_halaman_rekam_medis(): void
    {
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/'.$this->antrean->id_antrean);

        $response->assertStatus(200);
        $response->assertSee($this->pasien->nama_pasien);
        $response->assertSee('TTV & Catatan Perawat', false);
        $response->assertSee('Status Kunjungan');

        // Pastikan draf SOAP dan kontrol AI tidak tampil di halaman
        $response->assertDontSee('Draf Catatan Medis (SOAP)');
        $response->assertDontSee('Proses AI Ulang');
    }

    public function test_rekam_medis_mengarahkan_ke_wawancara_dengan_kunjungan_yang_sama_dan_tidak_memiliki_recorder(): void
    {
        PemeriksaanAwal::create([
            'id_kunjungan' => Kunjungan::firstOrCreate(
                ['id_antrean' => $this->antrean->id_antrean],
                [
                    'id_pasien' => $this->pasien->id_pasien,
                    'id_dokter' => $this->dokter->id_pengguna,
                    'tanggal' => '2026-09-19',
                    'status' => 'Sedang Diperiksa',
                ]
            )->id_kunjungan,
            'id_perawat' => $this->perawat->id_pengguna,
        ]);
        $response = $this->actingAs($this->dokter)->get(route('dokter.rekam-medis', $this->antrean));
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->firstOrFail();

        $response->assertOk()
            ->assertSee(route('dokter.interview.show', $kunjungan), false)
            ->assertDontSee('recording-console')
            ->assertDontSee('startClinicalRecording');
    }

    public function test_lanjutkan_wawancara_tidak_membuat_sesi_baru(): void
    {
        $kunjungan = Kunjungan::create([
            'id_antrean' => $this->antrean->id_antrean,
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => '2026-09-19',
            'status' => 'Sedang Diperiksa',
        ]);
        $interview = Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
        ]);

        $this->actingAs($this->dokter)
            ->get(route('dokter.interview.show', $kunjungan))
            ->assertOk()
            ->assertSee('Wawancara Audio')
            ->assertSee('KJ-'.str_pad($kunjungan->id_kunjungan, 4, '0', STR_PAD_LEFT));

        $this->assertSame($interview->id_wawancara, Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->value('id_wawancara'));
        $this->assertSame(1, Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->count());
    }

    /**
     * 2. Perawat tidak diizinkan mengakses halaman rekam medis dokter.
     */
    public function test_perawat_ditolak_mengakses_halaman_rekam_medis_dokter(): void
    {
        $response = $this->actingAs($this->perawat)->get('/dokter/rekam-medis/'.$this->antrean->id_antrean);

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

        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/'.$this->antrean->id_antrean);

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
        Queue::fake();

        $file = UploadedFile::fake()->create('wawancara.webm', 500, 'audio/webm');

        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/'.$this->antrean->id_antrean.'/audio',
            ['audio' => $file]
        );

        $response->assertStatus(202);
        $response->assertJson([
            'success' => true,
            'message' => 'Rekaman disimpan dan diantrikan ke service transkripsi lokal.',
        ]);
        Queue::assertPushed(ProcessInterviewAudio::class);

        $this->assertDatabaseHas('rekaman_audio', [
            'status' => 'queued',
        ]);
    }

    public function test_upload_legacy_ditolak_setelah_recording_completed(): void
    {
        Storage::fake('local');
        Queue::fake();
        $kunjungan = Kunjungan::create([
            'id_antrean' => $this->antrean->id_antrean,
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => '2026-09-19',
            'status' => 'Sedang Diperiksa',
        ]);
        $interview = Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
        ]);
        RekamanAudio::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_wawancara' => $interview->id_wawancara,
            'path_audio' => 'interviews/completed.webm',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.rekam-medis.upload-audio', $this->antrean),
            ['audio' => UploadedFile::fake()->create('interview.webm', 100, 'audio/webm')]
        );

        $response->assertStatus(409);
        $this->assertDatabaseCount('rekaman_audio', 1);
        Queue::assertNothingPushed();
    }

    public function test_upload_legacy_dengan_recording_failed_membuat_recording_baru(): void
    {
        Storage::fake('local');
        Queue::fake();
        $kunjungan = Kunjungan::create([
            'id_antrean' => $this->antrean->id_antrean,
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => '2026-09-19',
            'status' => 'Sedang Diperiksa',
        ]);
        $interview = Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'processing_status' => 'failed',
        ]);
        RekamanAudio::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_wawancara' => $interview->id_wawancara,
            'path_audio' => 'interviews/failed.webm',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.rekam-medis.upload-audio', $this->antrean),
            ['audio' => UploadedFile::fake()->create('interview.webm', 100, 'audio/webm')]
        );

        $response->assertStatus(202)->assertJson(['status' => 'queued']);
        $this->assertDatabaseCount('rekaman_audio', 2);
        Queue::assertPushed(ProcessInterviewAudio::class);
    }

    public function test_dokter_ditolak_mengunggah_audio_yang_melebihi_batas_durasi(): void
    {
        Storage::fake('local');
        Queue::fake();
        config(['medscribe.audio.max_duration_seconds' => 1]);

        $file = UploadedFile::fake()->createWithContent('wawancara.wav', $this->wavBytes(2));
        file_put_contents($this->ffprobeStubPath, "@echo {\"streams\":[{\"codec_type\":\"audio\"}],\"format\":{\"format_name\":\"wav\",\"duration\":\"2.0\"}}\r\n");

        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/'.$this->antrean->id_antrean.'/audio',
            ['audio' => $file]
        );

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Durasi audio melebihi batas konfigurasi.']);
        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('rekaman_audio', [
            'id_kunjungan' => Kunjungan::where('id_antrean', $this->antrean->id_antrean)->value('id_kunjungan'),
            'status' => 'queued',
        ]);
    }

    public function test_dokter_upload_gagal_tertutup_jika_ffprobe_tidak_tersedia(): void
    {
        Storage::fake('local');
        Queue::fake();
        config(['medscribe.audio.ffprobe_path' => 'ffprobe-yang-tidak-ada.exe']);

        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/'.$this->antrean->id_antrean.'/audio',
            ['audio' => UploadedFile::fake()->create('wawancara.webm', 500, 'audio/webm')]
        );

        $response->assertStatus(503)
            ->assertJsonFragment(['message' => 'ffprobe tidak dapat dijalankan untuk validasi audio.']);
        Queue::assertNothingPushed();
    }

    /**
     * 5. Unggah audio tidak memicu ekstraksi SOAP atau panggilan AI otomatis.
     */
    public function test_unggah_audio_tidak_memicu_generasi_draf_soap_otomatis(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('wawancara_klinis.webm', 400, 'audio/webm');

        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/'.$this->antrean->id_antrean.'/audio',
            ['audio' => $file]
        );

        $response->assertStatus(202);
        $response->assertJsonMissing(['ai_processed' => true]);
        $response->assertJsonMissing(['soap' => []]);
        Queue::assertPushed(ProcessInterviewAudio::class);
    }

    /**
     * 6. Rute pemrosesan AI dinonaktifkan dari alur aktif sprint ini.
     */
    public function test_rute_proses_ai_tidak_aktif(): void
    {
        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/'.$this->antrean->id_antrean.'/proses-ai'
        );

        $response->assertStatus(404);
    }

    /**
     * 7. Dashboard antrean dokter mengurutkan nomor antrean dari terbesar ke terkecil (descending).
     */
    public function test_dashboard_dokter_mengurutkan_antrean_dari_terbesar_ke_terkecil(): void
    {
        $pasien2 = Pasien::create([
            'no_rekam_medis' => 'TEST-'.uniqid(),
            'nama_pasien' => 'Pasien Antrean Baru',
            'umur' => 28,
            'jenis_kelamin' => 'Perempuan',
        ]);

        $antreanLebihBesar = Antrean::create([
            'id_pasien' => $pasien2->id_pasien,
            'tanggal_kunjungan' => date('Y-m-d'),
            'status' => 'Siap Periksa',
        ]);

        $response = $this->actingAs($this->dokter)->get('/dokter/dashboard');

        $response->assertStatus(200);
        $daftarAntrean = $response->viewData('daftarAntrean');
        $this->assertNotEmpty($daftarAntrean);

        // Pastikan nomor antrean terurut menurun (terbesar ke terkecil)
        $ids = $daftarAntrean->pluck('id_antrean')->toArray();
        $sortedIds = $ids;
        rsort($sortedIds);
        $this->assertEquals($sortedIds, $ids);
    }

    private function wavBytes(int $seconds): string
    {
        $sampleRate = 16000;
        $dataSize = $sampleRate * $seconds * 2;

        return 'RIFF'.pack('V', 36 + $dataSize).'WAVE'.'fmt '.pack('VvvVVv', 16, 1, 1, $sampleRate, $sampleRate * 2, 2)
            .'data'.pack('V', $dataSize).str_repeat("\0", $dataSize);
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\ProcessInterviewAudio;
use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Finalisasi;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\TindakLanjut;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RealTimeTranscriptionAndFinalizationTest extends TestCase
{
    use DatabaseTransactions;

    protected Pengguna $dokter;
    protected Pasien $pasien;
    protected Antrean $antrean;
    protected Kunjungan $kunjungan;
    protected string $ffprobeStubPath;

    protected function setUp(): void
    {
        parent::setUp();

        $probeDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'medscribe-ffprobe-' . uniqid();
        mkdir($probeDirectory);
        $this->ffprobeStubPath = $probeDirectory . DIRECTORY_SEPARATOR . 'ffprobe.bat';
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

        $this->pasien = Pasien::withTrashed()->firstOrCreate(
            ['no_rekam_medis' => '99-88-771'],
            [
                'nama_pasien' => 'Pasien Tes Realtime & Finalisasi',
                'tanggal_lahir' => '1988-08-08',
                'jenis_kelamin' => 'Perempuan',
                'umur' => 38,
            ]
        );
        if ($this->pasien->trashed()) {
            $this->pasien->restore();
        }

        $this->antrean = Antrean::create([
            'id_pasien' => $this->pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status' => 'Sedang Diperiksa',
        ]);

        $this->kunjungan = Kunjungan::create([
            'id_antrean' => $this->antrean->id_antrean,
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => now()->toDateString(),
            'status' => 'Sedang Diperiksa',
        ]);
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
     * Phase 1: Upload audio dengan transkripsi langsung (real-time) tersimpan otomatis
     * dan tidak memasukkan audio ke antrean job batch Whisper.
     */
    public function test_upload_audio_dengan_transkrip_realtime_langsung_selesai_tanpa_job_batch(): void
    {
        Queue::fake();

        $wawancara = Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'jawaban' => [],
        ]);

        $audioFile = UploadedFile::fake()->create('interview.webm', 100, 'audio/webm');
        $realtimeTranscript = 'Selamat pagi dok, kepala saya terasa sangat pusing berputar.';
        $realtimeSegments = [
            [
                'id' => 'seg-1',
                'startTime' => 0.0,
                'endTime' => 3.5,
                'text' => $realtimeTranscript,
                'speakerRole' => 'patient',
                'reviewStatus' => 'confirmed',
            ],
        ];

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.audio.upload', $this->kunjungan),
            [
                'audio' => $audioFile,
                'realtime_transcript' => $realtimeTranscript,
                'realtime_segments' => $realtimeSegments,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'completed',
        ]);

        Queue::assertNotPushed(ProcessInterviewAudio::class);

        $wawancara->refresh();
        $this->assertEquals('completed', $wawancara->processing_status);
        $this->assertEquals('realtime_streaming', $wawancara->sumber_transkrip);
        $this->assertEquals($realtimeTranscript, $wawancara->transkrip);
    }

    /**
     * Phase 2: Verifikasi transkrip oleh dokter menyimpan status wawancara selesai,
     * mencatat ID dokter dan timestamp, serta memicu pembuatan draf SOAP otomatis.
     */
    public function test_verifikasi_transkrip_menyimpan_status_dan_memicu_soap(): void
    {
        $wawancara = Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'transkrip' => 'Pasien mengeluh demam dan batuk pilek 3 hari.',
            'processing_status' => 'completed',
            'jawaban' => [],
        ]);

        $verifiedSegments = [
            [
                'id' => 'seg-1',
                'startTime' => 0.0,
                'endTime' => 2.0,
                'text' => 'Dokter: Selamat pagi apa keluhannya?',
                'speakerRole' => 'doctor',
                'reviewStatus' => 'confirmed',
            ],
            [
                'id' => 'seg-2',
                'startTime' => 2.1,
                'endTime' => 4.5,
                'text' => 'Pasien: Demam dan batuk pilek sejak 3 hari lalu.',
                'speakerRole' => 'patient',
                'reviewStatus' => 'confirmed',
            ],
        ];

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.transcript.verify', $this->kunjungan),
            [
                'segments' => $verifiedSegments,
                'speakerMap' => ['seg-1' => 'doctor', 'seg-2' => 'patient'],
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $wawancara->refresh();
        $this->assertEquals('Wawancara Selesai', $wawancara->status);
        $this->assertEquals($this->dokter->id_pengguna, $wawancara->transcript_verified_by);
        $this->assertNotNull($wawancara->transcript_reviewed_at);

        // SOAP draft record exists for this visit
        $this->assertDatabaseHas('draf_soap', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
        ]);
    }

    /**
     * Phase 3: Dokter dapat menyetujui dan memfinalisasi rekam medis ketika keempat syarat kelayakan terpenuhi.
     */
    public function test_dokter_dapat_menyetujui_dan_memfinalisasi_rekam_medis(): void
    {
        $this->kunjungan->update(['status' => Kunjungan::STATUS_SOAP_TERVERIFIKASI]);

        // A. Draf SOAP lengkap
        DrafSoap::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_pengguna' => $this->dokter->id_pengguna,
            'subjective_text' => 'Pasien mengeluh pusing dan lemas.',
            'objective_text' => 'TD 120/80 mmHg, Nadi 80x/m, Suhu 36.8 C.',
            'assessment_text' => 'Cephalea tension type.',
            'plan_text' => 'Istirahat cukup dan paracetamol 500mg.',
            'kode_icd10' => 'G44.2',
        ]);

        // B. TTV & Pemeriksaan Awal
        PemeriksaanAwal::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_perawat' => $this->dokter->id_pengguna,
            'tekanan_darah' => '120/80',
            'nadi' => 80,
            'suhu_tubuh' => 36.8,
            'laju_pernapasan' => 18,
            'berat_badan' => 60,
            'tinggi_badan' => 165,
            'catatan' => 'Kondisi umum baik.',
        ]);

        // C. Transkrip Wawancara terverifikasi
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'transkrip' => 'Dokter: Halo. Pasien: Pusing.',
            'transcript_reviewed_at' => now(),
            'transcript_verified_by' => $this->dokter->id_pengguna,
            'processing_status' => 'completed',
            'jawaban' => [],
        ]);

        // D. Tindak Lanjut Medis tersimpan
        TindakLanjut::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_TANPA_TINDAKAN,
            'status' => 'Selesai Tanpa Tindakan Lanjut',
        ]);

        $response = $this->actingAs($this->dokter)->post(
            route('dokter.rekam-medis.finalisasi', $this->kunjungan),
            [
                'konfirmasi_persetujuan' => '1',
                'catatan_finalisasi' => 'Pemeriksaan klinis lengkap. Pasien diedukasi istirahat.',
            ]
        );

        $response->assertRedirect(route('dokter.rekam-medis', $this->antrean->id_antrean));
        $response->assertSessionHas('status');

        $this->kunjungan->refresh();
        $this->antrean->refresh();

        $this->assertEquals(Kunjungan::STATUS_FINALISASI, $this->kunjungan->status);
        $this->assertEquals('Selesai', $this->antrean->status);
        $this->assertTrue($this->kunjungan->isFinalized());

        $this->assertDatabaseHas('finalisasi', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => 'Final',
            'FINAL' => 1,
            'finalized_by' => $this->dokter->id_pengguna,
            'catatan_finalisasi' => 'Pemeriksaan klinis lengkap. Pasien diedukasi istirahat.',
        ]);
    }

    /**
     * Phase 3: Finalisasi gagal jika SOAP belum diverifikasi.
     */
    public function test_finalisasi_gagal_jika_soap_belum_diverifikasi(): void
    {
        $this->kunjungan->update(['status' => 'Sedang Diperiksa']);

        $response = $this->actingAs($this->dokter)->post(
            route('dokter.rekam-medis.finalisasi', $this->kunjungan),
            [
                'konfirmasi_persetujuan' => '1',
            ]
        );

        $response->assertSessionHas('error');
        $this->assertFalse($this->kunjungan->fresh()->isFinalized());
    }

    /**
     * Phase 3: Finalisasi gagal jika checkbox persetujuan tidak dicentang.
     */
    public function test_finalisasi_gagal_jika_checkbox_persetujuan_tidak_dicentang(): void
    {
        $this->kunjungan->update(['status' => Kunjungan::STATUS_SOAP_TERVERIFIKASI]);

        $response = $this->actingAs($this->dokter)->post(
            route('dokter.rekam-medis.finalisasi', $this->kunjungan),
            [
                'konfirmasi_persetujuan' => '0',
            ]
        );

        $response->assertSessionHasErrors('konfirmasi_persetujuan');
        $this->assertFalse($this->kunjungan->fresh()->isFinalized());
    }

    /**
     * Phase 3: Seluruh endpoint mutasi rekam medis mengembalikan HTTP 403 setelah kunjungan difinalisasi.
     */
    public function test_seluruh_endpoint_mutasi_menolak_perubahan_setelah_rekam_medis_final(): void
    {
        Finalisasi::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => 'Final',
            'FINAL' => 1,
            'finalized_at' => now(),
            'finalized_by' => $this->dokter->id_pengguna,
        ]);
        $this->kunjungan->update(['status' => Kunjungan::STATUS_FINALISASI]);

        $this->assertTrue($this->kunjungan->fresh()->isFinalized());

        // 1. Upload audio wawancara ditolak
        $audio = UploadedFile::fake()->create('test.webm', 50, 'audio/webm');
        $resAudio = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.audio.upload', $this->kunjungan),
            ['audio' => $audio]
        );
        $resAudio->assertStatus(403);

        // 2. Update transkrip ditolak
        $resTranscript = $this->actingAs($this->dokter)->putJson(
            route('dokter.interview.transcript.update', $this->kunjungan),
            ['segments' => []]
        );
        $resTranscript->assertStatus(403);

        // 3. Verifikasi transkrip ditolak
        $resVerifyTranscript = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.transcript.verify', $this->kunjungan),
            ['transkrip' => 'Teks baru']
        );
        $resVerifyTranscript->assertStatus(403);

        // 4. Simpan SOAP ditolak
        $resSaveSoap = $this->actingAs($this->dokter)->put(
            route('dokter.soap.save', $this->kunjungan),
            [
                'subjective_text' => 'Keluhan baru',
                'objective_text' => 'TTV baru',
                'assessment_text' => 'Diagnosis baru',
                'plan_text' => 'Terapi baru',
                'nama_pasien' => $this->pasien->nama_pasien,
                'no_rekam_medis' => $this->pasien->no_rekam_medis,
            ]
        );
        $resSaveSoap->assertStatus(403);

        // 5. Generate ulang SOAP ditolak
        $resGenSoap = $this->actingAs($this->dokter)->post(
            route('dokter.soap.generate', $this->kunjungan)
        );
        $resGenSoap->assertStatus(403);

        // 6. Simpan tindak lanjut ditolak
        $resTindakLanjut = $this->actingAs($this->dokter)->postJson(
            route('dokter.tindak-lanjut.store', $this->kunjungan),
            ['opsi' => ['tanpa_tindakan']]
        );
        $resTindakLanjut->assertStatus(403);

        // 7. Proses rekaman ditolak jika rekam medis sudah final
        $resProcess = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.process', $this->kunjungan),
            ['realtime_transcript' => 'Percakapan baru setelah final']
        );
        $resProcess->assertStatus(403);
    }

    /**
     * Phase 5: Tombol 'Proses Rekaman' menyimpan transkrip snapshot dan memicu OpenRouter SOAP.
     */
    public function test_proses_rekaman_berhasil_menyimpan_transkrip_dan_membuat_draf_soap(): void
    {
        PemeriksaanAwal::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_perawat' => $this->dokter->id_pengguna,
            'tekanan_darah' => '120/80',
            'nadi' => 80,
            'suhu_tubuh' => 36.6,
        ]);

        $transcriptText = "Dokter: Selamat pagi Ibu, apa yang dirasakan?\nPasien: Pusing berputar sejak kemarin pagi dok.";

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.process', $this->kunjungan),
            [
                'realtime_transcript' => $transcriptText,
                'accumulated_duration' => 75,
                'realtime_segments' => [
                    ['id' => 'seg-1', 'startTime' => 0, 'endTime' => 3, 'text' => 'Dokter: Selamat pagi Ibu, apa yang dirasakan?'],
                    ['id' => 'seg-2', 'startTime' => 3, 'endTime' => 6, 'text' => 'Pasien: Pusing berputar sejak kemarin pagi dok.'],
                ],
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $interview = Wawancara::where('id_kunjungan', $this->kunjungan->id_kunjungan)->first();
        $this->assertNotNull($interview);
        $this->assertEquals('Wawancara Selesai', $interview->status);
        $this->assertEquals($transcriptText, $interview->transkrip);
        $this->assertNotNull($interview->transcript_reviewed_at);

        // Draf SOAP otomatis dihasilkan
        $draf = DrafSoap::where('id_kunjungan', $this->kunjungan->id_kunjungan)->first();
        $this->assertNotNull($draf);
        $this->assertNotEmpty($draf->subjective_text);
    }

    /**
     * Phase 5: Proses rekaman gagal validasi jika transkrip kosong.
     */
    public function test_proses_rekaman_gagal_jika_transkrip_kosong(): void
    {
        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.process', $this->kunjungan),
            [
                'realtime_transcript' => '   ',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('realtime_transcript');
    }
}

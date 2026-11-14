<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $dokter;

    private Kunjungan $kunjungan;

    private string $ffprobeStubPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $probeDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'medscribe-ffprobe-'.uniqid();
        mkdir($probeDirectory);
        $this->ffprobeStubPath = $probeDirectory.DIRECTORY_SEPARATOR.'ffprobe.bat';
        file_put_contents($this->ffprobeStubPath, "@echo {\"streams\":[{\"codec_type\":\"audio\"}],\"format\":{\"format_name\":\"matroska,webm\",\"duration\":\"1.0\"}}\r\n");
        config(['medscribe.audio.ffprobe_path' => $this->ffprobeStubPath]);

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
        $antrean = Antrean::create([
            'id_pasien' => $pasien->id_pasien,
            'tanggal_kunjungan' => '2026-10-25',
            'status' => 'Sedang Diperiksa',
        ]);
        $this->kunjungan = Kunjungan::create([
            'id_antrean' => $antrean->id_antrean,
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

    protected function tearDown(): void
    {
        if (isset($this->ffprobeStubPath) && is_file($this->ffprobeStubPath)) {
            $directory = dirname($this->ffprobeStubPath);
            unlink($this->ffprobeStubPath);
            @rmdir($directory);
        }

        parent::tearDown();
    }

    public function test_dokter_dapat_menyelesaikan_wawancara_setelah_transkrip_ditinjau(): void
    {
        $this->actingAs($this->dokter)
            ->post(route('dokter.interview.start', $this->kunjungan))
            ->assertRedirect();

        $interview = Wawancara::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();
        $interview->update([
            'transkrip' => 'Sakit tenggorokan sejak tiga hari',
            'transcript_segments' => [[
                'id' => 'segment_0', 'startTime' => 0, 'endTime' => 2,
                'text' => 'Sakit tenggorokan sejak tiga hari', 'speakerId' => null,
                'speakerRole' => 'patient', 'reviewStatus' => 'confirmed',
            ]],
            'transcript_reviewed_at' => now(),
            'sumber_transkrip' => 'local_reviewed',
        ]);
        $response = $this->actingAs($this->dokter)->post(
            route('dokter.interview.finish', $this->kunjungan)
        );

        $response->assertRedirect(route('dokter.rekam-medis', $this->kunjungan->id_antrean).'#soap');
        $this->assertDatabaseHas('wawancara', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'sumber_transkrip' => 'local_reviewed',
        ]);
        $this->assertStringContainsString(
            'Sakit tenggorokan sejak tiga hari',
            Wawancara::where('id_kunjungan', $this->kunjungan->id_kunjungan)->first()->transkrip
        );
    }

    public function test_halaman_wawancara_berpusat_pada_audio_dan_tidak_menampilkan_input_manual(): void
    {
        $this->actingAs($this->dokter)
            ->post(route('dokter.interview.start', $this->kunjungan));

        $response = $this->actingAs($this->dokter)
            ->followingRedirects()->get(route('dokter.interview.show', $this->kunjungan));

        $response->assertOk()
            ->assertDontSee('Catatan manual')
            ->assertDontSee('Panduan wawancara')
            ->assertDontSee('Simpan Catatan');
    }

    public function test_review_soap_menggunakan_transkrip_sesi_wawancara_dan_ttv(): void
    {
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'jawaban' => ['keluhan_utama' => 'Nyeri saat menelan'],
            'transkrip' => 'Keluhan utama: Nyeri saat menelan',
            'sumber_transkrip' => 'local_reviewed',
            'transcript_reviewed_at' => now(),
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $response = $this->actingAs($this->dokter)->followingRedirects()->get(route('dokter.soap.edit', $this->kunjungan));

        $response->assertOk()->assertSee('Nyeri saat menelan')->assertSee('120/80');
        $this->assertDatabaseHas('draf_soap', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'source_type' => 'mock_generation',
            'needs_review' => 0,
        ]);
        $this->assertStringContainsString('Nyeri saat menelan', DrafSoap::where('id_kunjungan', $this->kunjungan->id_kunjungan)->first()->subjective_text);
    }

    public function test_koreksi_transkrip_menyimpan_role_speaker_dan_teks(): void
    {
        $interview = Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'processing_status' => 'completed',
            'transcript_segments' => [],
        ]);

        $response = $this->actingAs($this->dokter)->putJson(route('dokter.interview.transcript.update', $this->kunjungan), [
            'segments' => [[
                'id' => 'segment_0', 'startTime' => 0, 'endTime' => 2.5,
                'text' => 'Saya merasa pusing sejak kemarin.', 'speakerId' => 'speaker_0',
                'speakerRole' => 'patient', 'reviewStatus' => 'confirmed',
            ]],
            'speakerMap' => ['speaker_0' => 'patient'],
        ]);

        $response->assertOk()->assertJson(['status' => 'saved']);
        $this->assertSame('Saya merasa pusing sejak kemarin.', $interview->fresh()->transkrip);
        $this->assertSame('patient', $interview->fresh()->speaker_map['speaker_0']);
    }

    public function test_perubahan_transkrip_menandai_draf_soap_perlu_ditinjau(): void
    {
        $interview = Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'processing_status' => 'completed',
            'transcript_segments' => [],
            'transkrip' => 'Keluhan awal',
            'transcript_reviewed_at' => now(),
        ]);
        $draft = DrafSoap::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_pengguna' => $this->dokter->id_pengguna,
            'subjective_text' => 'Keluhan awal',
            'objective_text' => 'TTV tersedia',
            'assessment_text' => 'Perlu tinjau',
            'plan_text' => 'Konfirmasi',
            'needs_review' => false,
            'source_transcript_hash' => hash('sha256', 'Keluhan awal'),
        ]);

        $this->actingAs($this->dokter)->putJson(route('dokter.interview.transcript.update', $this->kunjungan), [
            'segments' => [[
                'id' => 'segment_0', 'startTime' => 0, 'endTime' => 1,
                'text' => 'Keluhan berubah', 'speakerId' => null,
                'speakerRole' => 'patient', 'reviewStatus' => 'edited',
            ]],
            'speakerMap' => [],
        ])->assertOk();

        $this->assertTrue($draft->fresh()->needs_review);
    }

    public function test_proses_rekaman_mengklaim_kunjungan_yang_belum_ditugaskan(): void
    {
        $this->kunjungan->update(['id_dokter' => null]);
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
        ]);

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.process', $this->kunjungan),
            ['realtime_transcript' => 'Pasien mengeluh demam sejak kemarin.']
        );

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('kunjungan', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
        ]);
    }

    public function test_dokter_tidak_dapat_memproses_rekaman_ke_kunjungan_dokter_lain(): void
    {
        $otherDoctor = Pengguna::create([
            'email' => 'other-doctor-'.uniqid().'@medscribe.id',
            'nama_lengkap' => 'Dokter Lain',
            'role' => 'dokter',
            'password' => bcrypt('password123'),
        ]);
        $this->kunjungan->update(['id_dokter' => $otherDoctor->id_pengguna]);
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
        ]);

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.process', $this->kunjungan),
            ['realtime_transcript' => 'Pasien mengeluh demam sejak kemarin.']
        );

        $response->assertForbidden();
    }

    public function test_transcript_completed_menampilkan_review_tanpa_konsol_rekaman(): void
    {
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'processing_status' => 'completed',
            'transkrip' => 'Pasien merasa pusing.',
            'transcript_segments' => [[
                'id' => 'segment_0', 'startTime' => 0, 'endTime' => 2,
                'text' => 'Pasien merasa pusing.', 'speakerId' => null,
                'speakerRole' => 'unknown', 'reviewStatus' => 'ambiguous',
            ]],
        ]);

        $response = $this->actingAs($this->dokter)->followingRedirects()->get(route('dokter.interview.show', $this->kunjungan));

        $response->assertOk()
            ->assertSee('Review Transkrip Wawancara')
            ->assertSee('Simpan Koreksi')
            ->assertDontSee('id="recording-console"');
    }

    public function test_transkrip_diverifikasi_menampilkan_teks_terkunci(): void
    {
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'processing_status' => 'completed',
            'transkrip' => 'Pasien batuk berdahak 3 hari.',
            'transcript_reviewed_at' => now(),
            'transcript_segments' => [],
        ]);

        $response = $this->actingAs($this->dokter)->followingRedirects()->get(route('dokter.interview.show', $this->kunjungan));

        $response->assertOk()
            ->assertSee('Transkrip yang telah diverifikasi')
            ->assertSee('Pasien batuk berdahak 3 hari.')
            ->assertDontSee('id="recording-console"');
    }

    public function test_frontend_menampilkan_konsol_rekaman_real_time(): void
    {
        $this->actingAs($this->dokter)
            ->post(route('dokter.interview.start', $this->kunjungan));

        $response = $this->actingAs($this->dokter)
            ->followingRedirects()->get(route('dokter.interview.show', $this->kunjungan));

        $response->assertOk()
            ->assertSee('Transkripsi Langsung (Real-Time)')
            ->assertSee('Web Speech API')
            ->assertSee('id="process-button"', false)
            ->assertSee('id="resume-button"', false)
            ->assertSee('id="retry-button"', false);
    }

    public function test_dokter_lain_tidak_dapat_membaca_halaman_soap(): void
    {
        $otherDoctor = Pengguna::create([
            'email' => 'soap-other-'.uniqid().'@medscribe.id',
            'nama_lengkap' => 'Dokter SOAP Lain',
            'role' => 'dokter',
            'password' => bcrypt('password123'),
        ]);
        $this->kunjungan->update(['id_dokter' => $otherDoctor->id_pengguna]);

        $this->actingAs($this->dokter)
            ->get(route('dokter.soap.edit', $this->kunjungan))
            ->assertForbidden();
    }

    public function test_soap_tidak_dapat_dibuat_sebelum_transkrip_direview(): void
    {
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'transkrip' => 'Transkrip belum ditinjau.',
            'transcript_segments' => [[
                'id' => 'segment_0', 'startTime' => 0, 'endTime' => 1,
                'text' => 'Transkrip belum ditinjau.', 'speakerId' => null,
                'speakerRole' => 'unknown', 'reviewStatus' => 'ambiguous',
            ]],
        ]);

        $this->actingAs($this->dokter)
            ->post(route('dokter.soap.generate', $this->kunjungan))
            ->assertRedirect()
            ->assertSessionHas('warning');
    }
}

<?php

namespace Tests\Feature;

use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\Wawancara;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessInterviewAudio;
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

    public function test_upload_audio_mengantrikan_pemrosesan_lokal(): void
    {
        Queue::fake();
        $this->actingAs($this->dokter)->post(route('dokter.interview.start', $this->kunjungan));

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.audio.upload', $this->kunjungan),
            ['audio' => UploadedFile::fake()->create('interview.webm', 100, 'audio/webm')]
        );

        $response->assertStatus(202)->assertJson(['status' => 'queued']);
        Queue::assertPushed(ProcessInterviewAudio::class);
        $this->assertDatabaseHas('rekaman_audio', ['id_kunjungan' => $this->kunjungan->id_kunjungan, 'status' => 'queued']);
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

    public function test_upload_audio_mengklaim_kunjungan_yang_belum_ditugaskan(): void
    {
        Queue::fake();
        $this->kunjungan->update(['id_dokter' => null]);
        Wawancara::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
        ]);

        $response = $this->actingAs($this->dokter)->postJson(
            route('dokter.interview.audio.upload', $this->kunjungan),
            ['audio' => UploadedFile::fake()->create('interview.webm', 100, 'audio/webm')]
        );

        $response->assertStatus(202);
        $this->assertDatabaseHas('kunjungan', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
        ]);
    }

    public function test_dokter_tidak_dapat_upload_audio_ke_kunjungan_dokter_lain(): void
    {
        Queue::fake();
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
            route('dokter.interview.audio.upload', $this->kunjungan),
            ['audio' => UploadedFile::fake()->create('interview.webm', 100, 'audio/webm')]
        );

        $response->assertForbidden();
        Queue::assertNothingPushed();
    }
}

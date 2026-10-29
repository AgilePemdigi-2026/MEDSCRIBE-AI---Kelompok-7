<?php

namespace Tests\Feature;

use App\Jobs\ProcessInterviewAudio;
use App\Models\Kunjungan;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

class ProcessInterviewAudioTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'medscribe.audio.disk' => 'local',
            'medscribe.stt.url' => 'http://stt.test/api',
            'medscribe.stt.language' => 'indonesian',
            'medscribe.stt.token' => null,
        ]);

        Storage::fake('local');
    }

    public function test_job_sends_audio_and_persists_successful_transcription(): void
    {
        config(['medscribe.stt.token' => 'test-stt-token']);
        [$recording, $interview] = $this->createRecording();
        $segments = [
            [
                'id' => 'segment_0',
                'startTime' => 0,
                'endTime' => 2.5,
                'text' => 'Pasien merasa pusing.',
                'speakerId' => 'speaker_0',
            ],
            [
                'id' => 'segment_1',
                'startTime' => 2.5,
                'endTime' => 4,
                'text' => 'Sejak kemarin.',
                'speakerId' => null,
            ],
        ];

        Http::fake(function (Request $request) use ($segments) {
            $this->assertSame('http://stt.test/api/transcriptions', $request->url());
            $this->assertTrue($request->hasFile('audio'));
            $this->assertStringContainsString('name="language"', $request->body());
            $this->assertStringContainsString('indonesian', $request->body());
            $this->assertSame(['test-stt-token'], $request->header('X-STT-API-Key'));

            return Http::response([
                'segments' => $segments,
                'diarizationError' => 'Diarization unavailable',
            ]);
        });

        (new ProcessInterviewAudio($recording->id_rekaman))->handle();

        $recording = $recording->fresh();
        $interview = $interview->fresh();

        $this->assertSame('completed', $recording->status);
        $this->assertNotNull($recording->processed_at);
        $this->assertNotNull($recording->waktu_selesai);
        $this->assertSame('completed', $interview->processing_status);
        $this->assertSame("Pasien merasa pusing.\nSejak kemarin.", $interview->transkrip);
        $this->assertEquals($segments, $interview->transcript_segments);
        $this->assertSame(['speaker_0' => 'unknown'], $interview->speaker_map);
        $this->assertSame('local_whisper_transformers', $interview->sumber_transkrip);
        $this->assertSame('Diarization unavailable', $interview->processing_error);
    }

    public function test_http_error_is_translated_and_failed_marks_records(): void
    {
        [$recording, $interview] = $this->createRecording();

        Http::fake([
            '*' => Http::response(['message' => 'STT rejected audio'], 422),
        ]);

        $exception = $this->runJobAndCaptureException($recording);

        $this->assertSame('STT rejected audio', $exception->getMessage());
        $this->assertSame('transcribing', $recording->fresh()->status);
        $this->assertSame('transcribing', $interview->fresh()->processing_status);

        (new ProcessInterviewAudio($recording->id_rekaman))->failed($exception);

        $this->assertSame('failed', $recording->fresh()->status);
        $this->assertSame('STT rejected audio', $recording->fresh()->error_message);
        $this->assertSame('failed', $interview->fresh()->processing_status);
        $this->assertSame('STT rejected audio', $interview->fresh()->processing_error);
    }

    public function test_connection_failure_is_translated_and_failed_marks_records(): void
    {
        [$recording, $interview] = $this->createRecording();

        Http::fake([
            '*' => fn () => throw new ConnectionException('connection refused'),
        ]);

        $exception = $this->runJobAndCaptureException($recording);

        $this->assertSame(
            'Service transkripsi lokal tidak tersedia. Jalankan service STT dan pastikan URL-nya benar.',
            $exception->getMessage()
        );

        (new ProcessInterviewAudio($recording->id_rekaman))->failed($exception);

        $this->assertSame('failed', $recording->fresh()->status);
        $this->assertSame($exception->getMessage(), $recording->fresh()->error_message);
        $this->assertSame('failed', $interview->fresh()->processing_status);
        $this->assertSame($exception->getMessage(), $interview->fresh()->processing_error);
    }

    /**
     * @return array{0: RekamanAudio, 1: Wawancara}
     */
    private function createRecording(): array
    {
        $kunjungan = Kunjungan::create([
            'tanggal' => '2026-10-29',
            'status' => 'Sedang Diperiksa',
        ]);
        $interview = Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
            'processing_status' => 'queued',
        ]);
        $path = 'interviews/'.$kunjungan->id_kunjungan.'/test-audio.webm';
        Storage::disk('local')->put($path, 'deterministic fake audio');
        $recording = RekamanAudio::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_wawancara' => $interview->id_wawancara,
            'path_audio' => $path,
            'mime_type' => 'audio/webm',
            'file_size' => 24,
            'status' => 'queued',
        ]);

        return [$recording, $interview];
    }

    private function runJobAndCaptureException(RekamanAudio $recording): Throwable
    {
        try {
            (new ProcessInterviewAudio($recording->id_rekaman))->handle();
        } catch (Throwable $exception) {
            return $exception;
        }

        $this->fail('The job was expected to fail.');
    }
}

<?php

namespace Tests\Unit;

use App\Services\AudioProbeException;
use App\Services\AudioProbeService;
use Tests\TestCase;

class AudioProbeServiceTest extends TestCase
{
    public function test_valid_opus_returns_numeric_duration(): void
    {
        config(['medscribe.audio.ffprobe_path' => 'ffprobe']);

        $duration = app(AudioProbeService::class)->probe(base_path('tests/Fixtures/valid_opus_2s.webm'));

        $this->assertSame(3, $duration);
    }

    public function test_valid_streaming_webm_without_duration_returns_null(): void
    {
        config(['medscribe.audio.ffprobe_path' => 'ffprobe']);

        $duration = app(AudioProbeService::class)->probe(base_path('tests/Fixtures/streaming_webm_no_duration.webm'));

        $this->assertNull($duration);
    }

    public function test_corrupt_file_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'medscribe-corrupt-');
        file_put_contents($path, 'not an audio file');

        try {
            $this->expectException(AudioProbeException::class);
            app(AudioProbeService::class)->probe($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_file_without_audio_stream_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'medscribe-no-audio-');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        try {
            $this->expectException(AudioProbeException::class);
            $this->expectExceptionMessage('Berkas tidak memiliki stream audio yang valid.');
            app(AudioProbeService::class)->probe($path);
        } finally {
            @unlink($path);
        }
    }
}

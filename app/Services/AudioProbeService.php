<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AudioProbeService
{
    public function probe(?string $path): ?int
    {
        if (! $path || ! is_file($path)) {
            Log::warning('Audio duration probe could not access temporary upload.', ['path' => $path]);
            throw new AudioProbeException('Berkas audio sementara tidak dapat diakses untuk validasi audio.', 422);
        }

        $probe = config('medscribe.audio.ffprobe_path', 'ffprobe');
        if ((str_contains($probe, '/') || str_contains($probe, '\\') || str_ends_with(strtolower($probe), '.exe')) && ! is_file($probe)) {
            Log::warning('Configured ffprobe executable does not exist.', ['probe' => $probe]);
            throw new AudioProbeException('ffprobe tidak dapat dijalankan untuk validasi audio.', 503);
        }

        try {
            $process = new Process([
                $probe,
                '-v', 'error',
                '-show_streams',
                '-show_format',
                '-of', 'json',
                $path,
            ]);
            $process->setTimeout(15);
            $process->run();
        } catch (\Throwable $exception) {
            Log::warning('ffprobe could not be started for audio validation.', [
                'path' => $path,
                'probe' => $probe,
                'exception' => $exception->getMessage(),
            ]);
            throw new AudioProbeException('ffprobe tidak dapat dijalankan untuk validasi audio.', 503, $exception);
        }

        if (! $process->isSuccessful()) {
            Log::warning('ffprobe failed to read uploaded audio.', [
                'path' => $path,
                'exit_code' => $process->getExitCode(),
                'stderr' => trim($process->getErrorOutput()),
            ]);
            throw new AudioProbeException('ffprobe gagal membaca berkas audio. Pastikan file tidak rusak dan formatnya didukung.', 422);
        }

        $metadata = json_decode($process->getOutput(), true);
        $streams = is_array($metadata) ? ($metadata['streams'] ?? []) : [];
        $audioStreams = is_array($streams)
            ? array_values(array_filter($streams, fn ($stream) => is_array($stream) && ($stream['codec_type'] ?? null) === 'audio'))
            : [];

        if ($audioStreams === []) {
            throw new AudioProbeException('Berkas tidak memiliki stream audio yang valid.', 422);
        }

        $formatName = is_array($metadata['format'] ?? null) ? (string) ($metadata['format']['format_name'] ?? '') : '';
        if (! $this->isSupportedFormat($formatName)) {
            throw new AudioProbeException('Format audio tidak didukung oleh aplikasi.', 422);
        }

        $durations = array_merge(
            [$metadata['format']['duration'] ?? null],
            array_map(fn (array $stream) => $stream['duration'] ?? null, $audioStreams)
        );

        foreach ($durations as $duration) {
            if (is_numeric($duration) && is_finite((float) $duration) && (float) $duration >= 0) {
                return (int) ceil((float) $duration);
            }
        }

        // Valid audio may not contain finalized duration metadata, notably MediaRecorder WebM.
        return null;
    }

    private function isSupportedFormat(string $formatName): bool
    {
        return collect(explode(',', strtolower($formatName)))->contains(fn (string $format) => in_array(trim($format), [
            'matroska', 'webm', 'ogg', 'oga', 'wav', 'mp3', 'mpeg', 'mp4', 'mov', 'm4a', '3gp', '3g2', 'mj2',
        ], true));
    }
}

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
            if ($probe === 'ffprobe') {
                return $this->probeNative($path);
            }

            Log::warning('ffprobe could not be started for audio validation.', [
                'path' => $path,
                'probe' => $probe,
                'exception' => $exception->getMessage(),
            ]);
            throw new AudioProbeException('ffprobe tidak dapat dijalankan untuk validasi audio.', 503, $exception);
        }

        if (! $process->isSuccessful()) {
            if ($probe === 'ffprobe' && ($process->getExitCode() === 9009 || $process->getExitCode() === 127 || ! filled($process->getOutput()))) {
                return $this->probeNative($path);
            }

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

    private function probeNative(string $path): ?int
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            throw new AudioProbeException('ffprobe gagal membaca berkas audio. Pastikan file tidak rusak dan formatnya didukung.', 422);
        }

        $header = fread($handle, 4096);
        $fileSize = filesize($path) ?: 0;

        // 1. Check for common non-audio media
        if (str_starts_with($header, "\x89PNG") || str_starts_with($header, "\xFF\xD8\xFF") || str_starts_with($header, 'GIF8')) {
            fclose($handle);
            throw new AudioProbeException('Berkas tidak memiliki stream audio yang valid.', 422);
        }

        // 2. Check for WebM / Matroska (EBML)
        if (str_starts_with($header, "\x1A\x45\xDF\xA3")) {
            fseek($handle, 0);
            $content = fread($handle, min($fileSize, 5 * 1024 * 1024));
            fclose($handle);

            $hasAudio = str_contains($content, 'A_OPUS')
                || str_contains($content, 'A_VORBIS')
                || str_contains($content, 'A_AAC')
                || str_contains($content, "\x83\x02")
                || str_contains($content, 'OpusHead')
                || str_contains($content, "\xE1");

            if (! $hasAudio) {
                throw new AudioProbeException('Berkas tidak memiliki stream audio yang valid.', 422);
            }

            $pos = strpos($content, "\x44\x89");
            if ($pos !== false && isset($content[$pos + 2])) {
                $lenByte = ord($content[$pos + 2]);
                if ($lenByte === 0x84 && isset($content[$pos + 6])) {
                    $val = unpack('G', substr($content, $pos + 3, 4))[1] ?? null;
                    if (is_numeric($val) && $val > 0) {
                        return (int) ceil($val / 1000.0);
                    }
                } elseif ($lenByte === 0x88 && isset($content[$pos + 10])) {
                    $val = unpack('E', substr($content, $pos + 3, 8))[1] ?? null;
                    if (is_numeric($val) && $val > 0) {
                        return (int) ceil($val / 1000.0);
                    }
                }
            }

            return null;
        }

        // 3. Check for WAV (RIFF .... WAVE)
        if (str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WAVE') {
            fclose($handle);
            $byteRate = unpack('V', substr($header, 28, 4))[1] ?? 0;
            $dataPos = strpos($header, 'data');
            if ($dataPos !== false) {
                $dataSize = unpack('V', substr($header, $dataPos + 4, 4))[1] ?? 0;
                if ($byteRate > 0 && $dataSize > 0) {
                    return (int) ceil($dataSize / $byteRate);
                }
            }

            return (int) ceil($fileSize / max(1, $byteRate ?: 32000));
        }

        // 4. Check for OGG (OggS)
        if (str_starts_with($header, 'OggS')) {
            fclose($handle);
            return null;
        }

        // 5. Check for MP3 (ID3 or sync word)
        if (str_starts_with($header, 'ID3') || (isset($header[1]) && ord($header[0]) === 0xFF && (ord($header[1]) & 0xE0) === 0xE0)) {
            fclose($handle);
            return null;
        }

        fclose($handle);

        throw new AudioProbeException('ffprobe gagal membaca berkas audio. Pastikan file tidak rusak dan formatnya didukung.', 422);
    }
}

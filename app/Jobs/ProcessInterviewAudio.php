<?php

namespace App\Jobs;

use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ProcessInterviewAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries = 2;

    public function __construct(public int $recordingId) {}

    public function handle(): void
    {
        $recording = RekamanAudio::findOrFail($this->recordingId);
        $interview = Wawancara::findOrFail($recording->id_wawancara);
        $recording->update(['status' => 'transcribing', 'error_message' => null]);
        $interview->update(['processing_status' => 'transcribing', 'processing_error' => null]);

        $input = Storage::disk(config('medscribe.audio.disk'))->path($recording->path_audio);
        $output = tempnam(sys_get_temp_dir(), 'medscribe-result-').'.json';
        $command = [
            config('medscribe.audio.python'),
            config('medscribe.audio.worker'),
            '--input', $input,
            '--output', $output,
            '--model-size', config('medscribe.audio.model_size'),
            '--device', config('medscribe.audio.device'),
            '--compute-type', config('medscribe.audio.compute_type'),
        ];
        if (! config('medscribe.audio.diarization_enabled')) {
            $command[] = '--no-diarization';
        }

        $process = new Process($command, base_path(), null, null, config('medscribe.audio.max_duration_seconds') * 3 + 600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'Worker audio gagal dijalankan.');
        }

        $result = json_decode((string) file_get_contents($output), true, 512, JSON_THROW_ON_ERROR);
        @unlink($output);
        $segments = $result['segments'] ?? [];
        if (! is_array($segments)) {
            throw new \RuntimeException('Worker mengembalikan segmen transkrip yang tidak valid.');
        }

        $interview->update([
            'transkrip' => collect($segments)->pluck('text')->implode("\n"),
            'transcript_segments' => $segments,
            'speaker_map' => collect($segments)->pluck('speakerId')->filter()->unique()->mapWithKeys(fn ($id) => [$id => 'unknown'])->all(),
            'sumber_transkrip' => 'local_faster_whisper_pyannote',
            'processing_status' => 'completed',
            'processing_error' => $result['diarizationError'] ?? null,
            'status' => 'Wawancara Selesai',
            'waktu_selesai' => now(),
        ]);
        $recording->update(['status' => 'completed', 'processed_at' => now(), 'waktu_selesai' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        $recording = RekamanAudio::find($this->recordingId);
        if (! $recording) {
            return;
        }

        $message = mb_substr($exception->getMessage(), 0, 2000);
        $recording->update(['status' => 'failed', 'error_message' => $message]);
        Wawancara::whereKey($recording->id_wawancara)->update([
            'processing_status' => 'failed',
            'processing_error' => $message,
        ]);
        Log::warning('Local interview audio processing failed', ['recording_id' => $recording->id_rekaman]);
    }
}

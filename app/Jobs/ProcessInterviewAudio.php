<?php

namespace App\Jobs;

use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        if (! is_file($input) || filesize($input) === 0) {
            throw new \RuntimeException('Berkas audio tidak tersedia atau kosong.');
        }

        $request = Http::acceptJson()
            ->timeout(config('medscribe.stt.timeout'))
            ->connectTimeout(15);
        if (filled(config('medscribe.stt.token'))) {
            $request = $request->withHeaders(['X-STT-API-Key' => config('medscribe.stt.token')]);
        }

        $stream = fopen($input, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Berkas audio tidak dapat dibaca.');
        }

        try {
            $response = $request->attach('audio', $stream, basename($input))
                ->post(rtrim(config('medscribe.stt.url'), '/').'/transcriptions', [
                    'language' => config('medscribe.stt.language'),
                ]);
            $response->throw();
            $result = $response->json();
        } catch (ConnectionException) {
            throw new \RuntimeException('Service transkripsi lokal tidak tersedia. Jalankan service STT dan pastikan URL-nya benar.');
        } catch (RequestException $exception) {
            $message = $exception->response?->json('message') ?: 'Service transkripsi lokal gagal memproses audio.';
            throw new \RuntimeException(mb_substr($message, 0, 500));
        } finally {
            fclose($stream);
        }

        $segments = $result['segments'] ?? [];
        if (! is_array($segments)) {
            throw new \RuntimeException('Worker mengembalikan segmen transkrip yang tidak valid.');
        }

        $interview->update([
            'transkrip' => collect($segments)->pluck('text')->implode("\n"),
            'transcript_segments' => $segments,
            'speaker_map' => collect($segments)->pluck('speakerId')->filter()->unique()->mapWithKeys(fn ($id) => [$id => 'unknown'])->all(),
            'sumber_transkrip' => 'local_whisper_transformers',
            'processing_status' => 'completed',
            'processing_error' => $result['diarizationError'] ?? null,
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

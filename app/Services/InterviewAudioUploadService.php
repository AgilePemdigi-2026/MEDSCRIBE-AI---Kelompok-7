<?php

namespace App\Services;

use App\Jobs\ProcessInterviewAudio;
use App\Models\Kunjungan;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InterviewAudioUploadService
{
    public function upload(Kunjungan $kunjungan, UploadedFile $file, bool $requireStarted, ?string $realtimeTranscript = null, ?array $realtimeSegments = null): array
    {
        $duration = app(AudioProbeService::class)->probe($file->getRealPath());
        if ($duration !== null && $duration > config('medscribe.audio.max_duration_seconds')) {
            throw new InterviewAudioUploadException('Durasi audio melebihi batas konfigurasi.', 422);
        }

        $hasRealtimeTranscript = filled($realtimeTranscript);

        $result = DB::transaction(function () use ($kunjungan, $file, $duration, $requireStarted, $hasRealtimeTranscript, $realtimeTranscript, $realtimeSegments): array {
            // Lock the parent first so concurrent requests serialize before creating the child interview.
            $lockedVisit = Kunjungan::whereKey($kunjungan->getKey())->lockForUpdate()->firstOrFail();
            $interview = Wawancara::where('id_kunjungan', $lockedVisit->id_kunjungan)
                ->lockForUpdate()
                ->first();

            if (! $interview) {
                if ($requireStarted) {
                    throw new InterviewAudioUploadException('Mulai sesi wawancara sebelum merekam.', 422);
                }

                $interview = Wawancara::create([
                    'id_kunjungan' => $lockedVisit->id_kunjungan,
                    'status' => 'Wawancara Berlangsung',
                    'jawaban' => [],
                ]);
            }

            if ($requireStarted && $interview->status !== 'Wawancara Berlangsung') {
                throw new InterviewAudioUploadException('Mulai sesi wawancara sebelum merekam.', 422);
            }

            if (! $requireStarted && $interview->status === 'Wawancara Selesai') {
                throw new InterviewAudioUploadException('Sesi wawancara sudah selesai. Gunakan transkrip yang ada untuk melanjutkan ke draf SOAP.', 409);
            }

            if ($interview->processing_status && ! in_array($interview->processing_status, ['failed', 'queued', 'transcribing', 'completed'], true)) {
                throw new InterviewAudioUploadException('Status pemrosesan wawancara tidak dikenal. Upload baru dihentikan untuk menjaga konsistensi data.', 409);
            }

            if (in_array($interview->processing_status, ['queued', 'transcribing'], true) && ! $interview->rekamanAudio()->exists()) {
                throw new InterviewAudioUploadException('Wawancara sedang diproses tetapi recording tidak ditemukan. Upload baru dihentikan.', 409);
            }

            $latestRecording = $interview->rekamanAudio()->latest('id_rekaman')->first();
            if ($latestRecording && in_array($latestRecording->status, ['queued', 'transcribing'], true)) {
                return [
                    'outcome' => 'existing',
                    'interview' => $interview,
                    'recording' => $latestRecording,
                ];
            }

            if ($latestRecording && $latestRecording->status !== 'failed' && ! $hasRealtimeTranscript) {
                throw new InterviewAudioUploadException(
                    'Recording sebelumnya sudah selesai atau memiliki status yang tidak dikenal. Gunakan sesi yang tersedia sebelum mengunggah ulang.',
                    409
                );
            }

            if (! $requireStarted && $interview->status === 'Belum Dimulai') {
                $interview->update(['status' => 'Wawancara Berlangsung', 'waktu_mulai' => $interview->waktu_mulai ?? now()]);
            }

            $path = $file->store('interviews/'.$lockedVisit->id_kunjungan, config('medscribe.audio.disk'));
            $status = $hasRealtimeTranscript ? 'completed' : 'queued';

            $recording = RekamanAudio::create([
                'id_kunjungan' => $lockedVisit->id_kunjungan,
                'id_wawancara' => $interview->id_wawancara,
                'path_audio' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'duration_seconds' => $duration,
                'status' => $status,
                'processed_at' => $hasRealtimeTranscript ? now() : null,
            ]);

            if ($hasRealtimeTranscript) {
                $interview->update([
                    'transkrip' => $realtimeTranscript,
                    'transcript_segments' => $realtimeSegments ?? [],
                    'processing_status' => 'completed',
                    'processing_error' => null,
                    'sumber_transkrip' => 'realtime_streaming',
                ]);
            } else {
                $interview->update(['processing_status' => 'queued', 'processing_error' => null]);
            }

            Log::info('Interview audio stored.', [
                'visit_id' => $lockedVisit->id_kunjungan,
                'recording_id' => $recording->id_rekaman,
                'has_realtime' => $hasRealtimeTranscript,
                'file_size' => $recording->file_size,
                'duration_seconds' => $recording->duration_seconds,
            ]);

            return [
                'outcome' => $hasRealtimeTranscript ? 'created_realtime' : 'created',
                'interview' => $interview->fresh(),
                'recording' => $recording,
            ];
        });

        if ($result['outcome'] === 'created') {
            $job = ProcessInterviewAudio::dispatch($result['recording']->id_rekaman)
                ->onQueue(config('medscribe.stt.queue', 'default'));
            $result['recording']->update([
                'job_id' => method_exists($job, 'getJobId') ? $job->getJobId() : (string) Str::uuid(),
            ]);
            Log::info('Interview audio job dispatched.', [
                'recording_id' => $result['recording']->id_rekaman,
                'queue' => config('medscribe.stt.queue'),
            ]);
        }

        return $result;
    }
}

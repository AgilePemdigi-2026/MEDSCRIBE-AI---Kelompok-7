<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInterviewAudio;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class InterviewAudioController extends Controller
{
    public function upload(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        $interview = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        $this->authorizeVisit($kunjungan);
        abort_unless($interview->status === 'Wawancara Berlangsung', 422, 'Mulai sesi wawancara sebelum merekam.');

        $request->validate([
            'audio' => ['required', 'file', 'max:'.config('medscribe.audio.max_size_kb'), 'mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a'],
        ]);
        $file = $request->file('audio');
        abort_if(($file->getSize() ?? 0) <= 0, 422, 'Berkas audio kosong.');
        $duration = $this->probeDuration($file->getRealPath());
        abort_if($duration !== null && $duration > config('medscribe.audio.max_duration_seconds'), 422, 'Durasi audio melebihi batas konfigurasi.');
        $disk = config('medscribe.audio.disk');
        $path = $file->store('interviews/'.$kunjungan->id_kunjungan, $disk);

        $recording = RekamanAudio::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_wawancara' => $interview->id_wawancara,
            'path_audio' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'duration_seconds' => $duration,
            'status' => 'queued',
        ]);
        $interview->update(['processing_status' => 'queued', 'processing_error' => null]);
        $job = ProcessInterviewAudio::dispatch($recording->id_rekaman);
        $recording->update(['job_id' => method_exists($job, 'getJobId') ? $job->getJobId() : (string) Str::uuid()]);

        return response()->json(['id' => $recording->id_rekaman, 'status' => 'queued'], 202);
    }

    public function status(Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        $interview = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        $recording = $interview->rekamanAudio()->latest('id_rekaman')->first();

        return response()->json([
            'status' => $interview->processing_status,
            'error' => $interview->processing_error,
            'recording' => $recording ? [
                'id' => $recording->id_rekaman,
                'status' => $recording->status,
                'size' => $recording->file_size,
                'durationSeconds' => $recording->duration_seconds,
                'mimeType' => $recording->mime_type,
                'error' => $recording->error_message,
            ] : null,
            'segments' => $interview->transcript_segments ?? [],
            'speakerMap' => $interview->speaker_map ?? [],
        ]);
    }

    public function retry(Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        $interview = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        $recording = $interview->rekamanAudio()->latest('id_rekaman')->firstOrFail();
        $recording->update(['status' => 'queued', 'error_message' => null]);
        $interview->update(['processing_status' => 'queued', 'processing_error' => null]);
        ProcessInterviewAudio::dispatch($recording->id_rekaman);

        return response()->json(['status' => 'queued']);
    }

    public function updateTranscript(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        $interview = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        $validated = $request->validate([
            'segments' => ['required', 'array', 'max:2000'],
            'segments.*.id' => ['required', 'string', 'max:100'],
            'segments.*.startTime' => ['required', 'numeric', 'min:0'],
            'segments.*.endTime' => ['required', 'numeric', 'gte:segments.*.startTime'],
            'segments.*.text' => ['required', 'string', 'max:5000'],
            'segments.*.speakerId' => ['nullable', 'string', 'max:100'],
            'segments.*.speakerRole' => ['required', 'in:doctor,patient,unknown'],
            'segments.*.reviewStatus' => ['required', 'in:confirmed,ambiguous,edited'],
            'speakerMap' => ['array'],
            'speakerMap.*' => ['in:doctor,patient,unknown'],
        ]);

        $segments = collect($validated['segments'])->values()->all();
        $interview->update([
            'transcript_segments' => $segments,
            'speaker_map' => $validated['speakerMap'] ?? [],
            'transkrip' => collect($segments)->pluck('text')->implode("\n"),
            'transcript_reviewed_at' => now(),
            'sumber_transkrip' => 'local_reviewed',
        ]);
        DrafSoap::where('id_kunjungan', $kunjungan->id_kunjungan)
            ->update(['needs_review' => true]);

        return response()->json(['status' => 'saved']);
    }

    private function authorizeVisit(Kunjungan $kunjungan): void
    {
        $userId = (int) auth()->user()->id_pengguna;

        // An unassigned queue item may be claimed by the authenticated doctor.
        // Once assigned, every audio/transcript operation remains owner-only.
        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);

            return;
        }

        abort_unless((int) $kunjungan->id_dokter === $userId, 403, 'Kunjungan ini ditugaskan kepada dokter lain.');
    }

    private function probeDuration(?string $path): ?int
    {
        if (! $path || ! is_file($path)) {
            Log::warning('Audio duration probe could not access temporary upload.', ['path' => $path]);
            abort(422, 'Berkas audio sementara tidak dapat diakses untuk validasi durasi.');
        }

        $probe = config('medscribe.audio.ffprobe_path', 'ffprobe');
        if ((str_contains($probe, '/') || str_contains($probe, '\\') || str_ends_with(strtolower($probe), '.exe')) && ! is_file($probe)) {
            Log::warning('Configured ffprobe executable does not exist.', ['probe' => $probe]);
            abort(503, 'ffprobe tidak dapat dijalankan untuk validasi durasi audio.');
        }

        try {
            $process = new Process([
                $probe, '-v', 'error', '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1', $path,
            ]);
            $process->setTimeout(15);
            $process->run();
        } catch (\Throwable $exception) {
            Log::warning('ffprobe could not be started for audio duration validation.', [
                'path' => $path,
                'probe' => $probe,
                'exception' => $exception->getMessage(),
            ]);
            abort(503, 'ffprobe tidak dapat dijalankan untuk validasi durasi audio.');
        }

        $duration = trim($process->getOutput());

        if (! $process->isSuccessful()) {
            Log::warning('ffprobe failed to read uploaded audio.', [
                'path' => $path,
                'exit_code' => $process->getExitCode(),
                'stderr' => trim($process->getErrorOutput()),
            ]);
            abort(422, 'ffprobe gagal membaca berkas audio. Pastikan format audio valid.');
        }

        if (! is_numeric($duration)) {
            Log::warning('ffprobe returned an invalid audio duration.', [
                'path' => $path,
                'stdout' => $duration,
            ]);
            abort(422, 'ffprobe mengembalikan durasi audio yang tidak valid.');
        }

        return (int) ceil((float) $duration);
    }
}

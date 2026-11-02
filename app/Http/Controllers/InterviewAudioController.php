<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInterviewAudio;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Wawancara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AudioProbeException;
use App\Services\InterviewAudioUploadException;
use App\Services\InterviewAudioUploadService;

class InterviewAudioController extends Controller
{
    public function upload(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);

        $request->validate([
            'audio' => ['required', 'file', 'max:'.config('medscribe.audio.max_size_kb'), 'mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a'],
        ]);
        $file = $request->file('audio');
        abort_if(($file->getSize() ?? 0) <= 0, 422, 'Berkas audio kosong.');

        try {
            $result = app(InterviewAudioUploadService::class)->upload($kunjungan, $file, true);
        } catch (AudioProbeException|InterviewAudioUploadException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], $exception->statusCode);
        }

        $recording = $result['recording'];
        $isExisting = $result['outcome'] === 'existing';

        return response()->json([
            'success' => true,
            'message' => $isExisting
                ? 'Rekaman ini sedang diproses. Tidak dibuat rekaman baru.'
                : 'Rekaman disimpan dan diantrikan ke service transkripsi lokal.',
            'audio_id' => $recording->id_rekaman,
            'status' => $recording->status === 'transcribing' ? 'transcribing' : 'queued',
        ], 202);
    }

    public function status(Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        $interview = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        $recording = $interview->rekamanAudio()->latest('id_rekaman')->first();
        $status = $interview->processing_status ?: $recording?->status;

        return response()->json([
            'status' => $status,
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
        ProcessInterviewAudio::dispatch($recording->id_rekaman)
            ->onQueue(config('medscribe.stt.queue', 'default'));

        return response()->json([
            'success' => true,
            'message' => 'Pemrosesan audio diantrikan ulang.',
            'status' => 'queued',
        ]);
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
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->hospital_id) {
            $targetHospitalId = $kunjungan->hospital_id ?? $kunjungan->pasien?->hospital_id;
            if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain.');
            }
        }

        $userId = (int) $user->id_pengguna;

        // An unassigned queue item may be claimed by the authenticated doctor.
        // Once assigned, every audio/transcript operation remains owner-only.
        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);

            return;
        }

        abort_unless((int) $kunjungan->id_dokter === $userId, 403, 'Kunjungan ini ditugaskan kepada dokter lain.');
    }

}

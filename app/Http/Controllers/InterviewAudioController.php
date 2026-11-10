<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInterviewAudio;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use App\Services\AudioProbeException;
use App\Services\AudioProbeService;
use App\Services\InterviewAudioUploadException;
use App\Services\InterviewAudioUploadService;
use App\Services\SoapDraftPreparer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InterviewAudioController extends Controller
{
    public function upload(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        abort_if($kunjungan->isFinalized(), 403, 'Rekam medis telah difinalisasi dan tidak dapat diubah.');

        $request->validate([
            'audio' => ['required', 'file', 'max:'.config('medscribe.audio.max_size_kb'), 'mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a'],
            'realtime_transcript' => ['nullable', 'string'],
            'realtime_segments' => ['nullable'],
        ]);
        $file = $request->file('audio');
        abort_if(($file->getSize() ?? 0) <= 0, 422, 'Berkas audio kosong.');

        $realtimeSegments = $request->input('realtime_segments');
        if (is_string($realtimeSegments)) {
            $decoded = json_decode($realtimeSegments, true);
            if (is_array($decoded)) {
                $realtimeSegments = $decoded;
            }
        }

        try {
            $result = app(InterviewAudioUploadService::class)->upload(
                $kunjungan,
                $file,
                true,
                $request->input('realtime_transcript'),
                is_array($realtimeSegments) ? $realtimeSegments : null
            );
        } catch (AudioProbeException|InterviewAudioUploadException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], $exception->statusCode);
        }

        $recording = $result['recording'];
        $isExisting = $result['outcome'] === 'existing';
        $isRealtime = $result['outcome'] === 'created_realtime';

        if ($isRealtime) {
            return response()->json([
                'success' => true,
                'message' => 'Rekaman dan transkripsi langsung berhasil disimpan. Silakan tinjau dan verifikasi transkrip.',
                'audio_id' => $recording->id_rekaman,
                'status' => 'completed',
            ], 200);
        }

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
        abort_if($kunjungan->isFinalized(), 403, 'Rekam medis telah difinalisasi dan tidak dapat diubah.');

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

    /**
     * Memverifikasi transkrip wawancara klinis oleh dokter dan
     * secara otomatis memicu pembuatan Draf SOAP berbasis AI (OpenRouter).
     */
    public function verifyTranscript(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        abort_if($kunjungan->isFinalized(), 403, 'Rekam medis telah difinalisasi dan tidak dapat diubah.');

        $interview = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();

        $validated = $request->validate([
            'segments' => ['nullable', 'array', 'max:2000'],
            'segments.*.id' => ['nullable', 'string', 'max:100'],
            'segments.*.startTime' => ['nullable', 'numeric', 'min:0'],
            'segments.*.endTime' => ['nullable', 'numeric'],
            'segments.*.text' => ['nullable', 'string', 'max:5000'],
            'segments.*.speakerId' => ['nullable', 'string', 'max:100'],
            'segments.*.speakerRole' => ['nullable', 'in:doctor,patient,unknown'],
            'segments.*.reviewStatus' => ['nullable', 'in:confirmed,ambiguous,edited'],
            'speakerMap' => ['nullable', 'array'],
            'speakerMap.*' => ['in:doctor,patient,unknown'],
            'transkrip' => ['nullable', 'string'],
        ]);

        $segments = !empty($validated['segments']) ? collect($validated['segments'])->values()->all() : ($interview->transcript_segments ?? []);
        $transcriptText = trim($validated['transkrip'] ?? (collect($segments)->pluck('text')->filter()->implode("\n")));

        if (empty($transcriptText) && filled($interview->transkrip)) {
            $transcriptText = trim($interview->transkrip);
        }

        abort_if(empty($transcriptText), 422, 'Transkrip wawancara tidak boleh kosong sebelum diverifikasi.');

        $userId = auth()->id();

        $interview->update([
            'transkrip' => $transcriptText,
            'transcript_segments' => $segments,
            'speaker_map' => $validated['speakerMap'] ?? ($interview->speaker_map ?? []),
            'transcript_reviewed_at' => now(),
            'transcript_verified_by' => $userId,
            'status' => 'Wawancara Selesai',
            'sumber_transkrip' => $interview->sumber_transkrip ?: 'local_reviewed',
        ]);

        // Secara otomatis generate draf SOAP via OpenRouter
        $soapResult = null;
        try {
            $soapResult = app(SoapDraftPreparer::class)->generateAndSave($kunjungan, $transcriptText);
        } catch (\Throwable $e) {
            Log::error('Auto SOAP generation failed upon transcript verification: '.$e->getMessage(), [
                'visit_id' => $kunjungan->id_kunjungan,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transkripsi berhasil diverifikasi. Draf SOAP telah otomatis disusun.',
            'redirect_url' => route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean]).'#soap',
            'soap' => $soapResult,
        ]);
    }

    /**
     * Memproses transkrip hasil rekaman dan secara langsung memicu pembuatan Draf SOAP via OpenRouter.
     * Hanya dijalankan ketika dokter secara eksplisit menekan tombol "Proses Rekaman".
     */
    public function processRecording(Request $request, Kunjungan $kunjungan): JsonResponse
    {
        $this->authorizeVisit($kunjungan);
        abort_if($kunjungan->isFinalized(), 403, 'Rekam medis telah difinalisasi dan tidak dapat diubah.');

        $request->validate([
            'realtime_transcript' => ['required', 'string'],
            'realtime_segments' => ['nullable'],
            'accumulated_duration' => ['nullable', 'numeric', 'min:0'],
            'audio' => ['nullable', 'file', 'max:'.config('medscribe.audio.max_size_kb')],
        ], [
            'realtime_transcript.required' => 'Transkrip wawancara belum tersedia. Silakan lakukan rekaman terlebih dahulu.',
        ]);

        $transcriptText = trim($request->input('realtime_transcript'));
        if (empty($transcriptText)) {
            return response()->json([
                'success' => false,
                'message' => 'Transkrip wawancara belum tersedia. Silakan lakukan rekaman terlebih dahulu.',
            ], 422);
        }

        $realtimeSegments = $request->input('realtime_segments');
        if (is_string($realtimeSegments)) {
            $decoded = json_decode($realtimeSegments, true);
            if (is_array($decoded)) {
                $realtimeSegments = $decoded;
            }
        }
        if (!is_array($realtimeSegments)) {
            $realtimeSegments = [];
        }

        $userId = auth()->id();

        // Cari atau buat Wawancara untuk kunjungan ini
        $interview = Wawancara::firstOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            [
                'status' => 'Wawancara Selesai',
                'jawaban' => [],
            ]
        );

        $interview->update([
            'transkrip' => $transcriptText,
            'transcript_segments' => $realtimeSegments,
            'transcript_reviewed_at' => now(),
            'transcript_verified_by' => $userId,
            'status' => 'Wawancara Selesai',
            'processing_status' => 'completed',
            'processing_error' => null,
            'sumber_transkrip' => 'realtime_streaming',
        ]);

        // Simpan file audio jika dikirimkan oleh browser
        if ($request->hasFile('audio') && $request->file('audio')->isValid() && ($request->file('audio')->getSize() ?? 0) > 0) {
            try {
                $file = $request->file('audio');
                $duration = $request->input('accumulated_duration');
                if (!$duration && class_exists(AudioProbeService::class)) {
                    $duration = app(AudioProbeService::class)->probe($file->getRealPath());
                }
                $path = $file->store('interviews/'.$kunjungan->id_kunjungan, config('medscribe.audio.disk'));

                RekamanAudio::create([
                    'id_kunjungan' => $kunjungan->id_kunjungan,
                    'id_wawancara' => $interview->id_wawancara,
                    'path_audio' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'duration_seconds' => $duration ?: 0,
                    'status' => 'completed',
                    'processed_at' => now(),
                ]);
            } catch (\Throwable $audioEx) {
                Log::warning('Audio archiving skipped during processRecording: ' . $audioEx->getMessage());
            }
        }

        // Panggil OpenRouter untuk menyusun draf SOAP
        try {
            $soapResult = app(SoapDraftPreparer::class)->generateAndSave($kunjungan, $transcriptText);
        } catch (\Throwable $e) {
            Log::error('OpenRouter SOAP generation failed during processRecording: ' . $e->getMessage(), [
                'visit_id' => $kunjungan->id_kunjungan,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyusun Draf SOAP dengan AI: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Draf SOAP berhasil dibuat. Silakan tinjau hasilnya sebelum melanjutkan.',
            'redirect_url' => route('dokter.rekam-medis', ['id_antrean' => $kunjungan->id_antrean]) . '#soap',
            'soap' => $soapResult['draf'] ?? null,
        ]);
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

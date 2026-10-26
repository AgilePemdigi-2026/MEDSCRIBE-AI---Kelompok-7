<?php

namespace App\Http\Controllers;

use App\Contracts\TranscriptionService;
use App\Models\Kunjungan;
use App\Models\Wawancara;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InterviewController extends Controller
{
    private const QUESTIONS = [
        'keluhan_utama' => ['Keluhan utama', 'Apa keluhan utama yang dirasakan pasien?'],
        'mulai_keluhan' => ['Awal keluhan', 'Sejak kapan keluhan mulai dirasakan?'],
        'awal_muncul' => ['Perjalanan awal keluhan', 'Bagaimana awal munculnya keluhan?'],
        'pola_keluhan' => ['Pola keluhan', 'Apakah keluhan menetap atau hilang timbul?'],
        'gejala_penyerta' => ['Gejala penyerta', 'Apakah ada gejala lain yang menyertai?'],
        'faktor_pemicu' => ['Faktor pemicu/pereda', 'Apakah ada faktor yang memperburuk atau meringankan keluhan?'],
        'riwayat_penyakit' => ['Riwayat penyakit', 'Apakah pasien memiliki riwayat penyakit yang relevan?'],
        'alergi' => ['Alergi', 'Apakah pasien memiliki alergi yang diketahui?'],
        'obat' => ['Obat yang digunakan', 'Apakah pasien sedang menggunakan obat?'],
        'informasi_lain' => ['Informasi lain', 'Adakah informasi lain yang dianggap relevan oleh dokter?'],
    ];

    public function __construct(private TranscriptionService $transcriptionService) {}

    public function show(Kunjungan $kunjungan): View
    {
        $kunjungan->load(['pasien', 'dokter', 'pemeriksaanAwal', 'wawancara']);
        $wawancara = $kunjungan->wawancara ?? new Wawancara(['status' => 'Belum Dimulai', 'jawaban' => []]);

        return view('interview.start', [
            'kunjungan' => $kunjungan,
            'wawancara' => $wawancara,
            'questions' => self::QUESTIONS,
        ]);
    }

    public function start(Kunjungan $kunjungan): RedirectResponse
    {
        $userId = (int) auth()->user()->id_pengguna;
        if ($kunjungan->id_dokter !== null && (int) $kunjungan->id_dokter !== $userId) {
            abort(403, 'Kunjungan ini ditugaskan kepada dokter lain.');
        }
        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);
        }

        $wawancara = Wawancara::firstOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            ['status' => 'Belum Dimulai', 'jawaban' => []]
        );

        if ($wawancara->status === 'Wawancara Selesai') {
            return back()->with('warning', 'Sesi wawancara sudah selesai. Gunakan transkrip yang ada untuk melanjutkan ke draf SOAP.');
        }

        $wawancara->update([
            'status' => 'Wawancara Berlangsung',
            'waktu_mulai' => $wawancara->waktu_mulai ?? now(),
        ]);

        return back()->with('status', 'Wawancara simulasi dimulai.');
    }

    public function save(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        if ($wawancara->status !== 'Wawancara Berlangsung') {
            return back()->with('warning', 'Catatan hanya dapat diubah saat wawancara berlangsung.');
        }

        $answers = $this->validateAnswers($request);
        $wawancara->update(['jawaban' => $answers]);

        return back()->with('status', 'Catatan wawancara berhasil disimpan.');
    }

    public function finish(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        if ($wawancara->status !== 'Wawancara Berlangsung') {
            return back()->with('warning', 'Sesi wawancara belum dimulai atau sudah selesai.');
        }

        $answers = $this->validateAnswers($request);
        $transcript = $wawancara->transcript_segments
            ? collect($wawancara->transcript_segments)->pluck('text')->implode("\n")
            : $this->transcriptionService->transcribe($answers);

        DB::transaction(function () use ($wawancara, $answers, $transcript) {
            $wawancara->update([
                'status' => 'Wawancara Selesai',
                'jawaban' => $answers,
                'transkrip' => $transcript,
                'sumber_transkrip' => $wawancara->transcript_segments ? ($wawancara->sumber_transkrip ?: 'local_reviewed') : 'manual_simulation',
                'waktu_selesai' => now(),
            ]);
        });

        $redirect = redirect()->route('dokter.soap.edit', $kunjungan)
            ->with('status', 'Wawancara selesai. Transkrip simulasi siap ditinjau.');

        if ($transcript === '') {
            $redirect->with('warning', 'Wawancara selesai tanpa jawaban yang dicatat. Transkrip belum memuat informasi klinis.');
        }

        return $redirect;
    }

    private function validateAnswers(Request $request): array
    {
        $validated = $request->validate(['answers' => ['nullable', 'array']]);
        $answers = $validated['answers'] ?? [];

        return collect(self::QUESTIONS)->keys()->mapWithKeys(
            fn (string $key) => [$key => Str::of((string) ($answers[$key] ?? ''))->limit(2000, '')->__toString()]
        )->all();
    }
}

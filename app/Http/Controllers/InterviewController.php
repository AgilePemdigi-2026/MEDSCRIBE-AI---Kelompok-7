<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\RekamanAudio;
use App\Models\Wawancara;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InterviewController extends Controller
{
    public function show(Kunjungan $kunjungan): View
    {
        $this->authorizeVisit($kunjungan);
        $kunjungan->load(['pasien', 'dokter', 'pemeriksaanAwal', 'wawancara']);
        $wawancara = $kunjungan->wawancara ?? new Wawancara(['status' => 'Belum Dimulai', 'jawaban' => []]);
        $rekamanAudio = RekamanAudio::where('id_kunjungan', $kunjungan->id_kunjungan)
            ->latest('id_rekaman')
            ->first();

        return view('interview.start', [
            'kunjungan' => $kunjungan,
            'wawancara' => $wawancara,
            'rekamanAudio' => $rekamanAudio,
        ]);
    }

    public function start(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);

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

        return back()->with('status', 'Wawancara dimulai. Rekam audio untuk membuat transkrip.');
    }

    public function finish(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        if ($wawancara->status !== 'Wawancara Berlangsung') {
            return back()->with('warning', 'Sesi wawancara belum dimulai atau sudah selesai.');
        }

        if (! filled($wawancara->transkrip) || ! $wawancara->transcript_reviewed_at) {
            return back()->with('warning', 'Selesaikan pemrosesan audio dan simpan koreksi transkrip sebelum membuka SOAP.');
        }

        $wawancara->update(['status' => 'Wawancara Selesai', 'waktu_selesai' => now()]);

        return redirect()->route('dokter.soap.edit', $kunjungan)
            ->with('status', 'Wawancara selesai. Transkrip yang telah ditinjau siap digunakan untuk SOAP.');
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
        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $userId]);

            return;
        }

        abort_unless((int) $kunjungan->id_dokter === $userId, 403, 'Kunjungan ini ditugaskan kepada dokter lain.');
    }
}

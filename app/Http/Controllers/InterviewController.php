<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\Wawancara;
use Illuminate\Http\RedirectResponse;

class InterviewController extends Controller
{
    /**
     * Wawancara klinis kini menjadi bagian halaman rekam medis; URL lama diarahkan ke sana.
     */
    public function show(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);

        return $this->toRekamMedis($kunjungan, 'wawancara');
    }

    public function start(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);

        $wawancara = Wawancara::firstOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            ['status' => 'Belum Dimulai', 'jawaban' => []]
        );

        if ($wawancara->status === 'Wawancara Selesai') {
            return back()->withFragment('wawancara')->with('warning', 'Sesi wawancara sudah selesai. Gunakan transkrip yang ada untuk melanjutkan ke draf SOAP.');
        }

        $wawancara->update([
            'status' => 'Wawancara Berlangsung',
            'waktu_mulai' => $wawancara->waktu_mulai ?? now(),
        ]);

        return back()->withFragment('wawancara')->with('status', 'Wawancara dimulai. Rekam audio untuk membuat transkrip.');
    }

    public function finish(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorizeVisit($kunjungan);
        $wawancara = Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->firstOrFail();
        if ($wawancara->status !== 'Wawancara Berlangsung') {
            return back()->withFragment('wawancara')->with('warning', 'Sesi wawancara belum dimulai atau sudah selesai.');
        }

        if (! filled($wawancara->transkrip) || ! $wawancara->transcript_reviewed_at) {
            return back()->withFragment('wawancara')->with('warning', 'Selesaikan pemrosesan audio dan simpan koreksi transkrip sebelum membuka SOAP.');
        }

        $wawancara->update(['status' => 'Wawancara Selesai', 'waktu_selesai' => now()]);

        return $this->toRekamMedis($kunjungan, 'soap')
            ->with('status', 'Wawancara selesai. Transkrip yang telah ditinjau siap digunakan untuk SOAP.');
    }

    private function toRekamMedis(Kunjungan $kunjungan, string $bagian): RedirectResponse
    {
        if (! $kunjungan->id_antrean) {
            return redirect()->route('dokter.dashboard')
                ->with('warning', 'Kunjungan ini tidak terhubung ke antrean pasien.');
        }

        return redirect()->to(route('dokter.rekam-medis', $kunjungan->id_antrean).'#'.$bagian);
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

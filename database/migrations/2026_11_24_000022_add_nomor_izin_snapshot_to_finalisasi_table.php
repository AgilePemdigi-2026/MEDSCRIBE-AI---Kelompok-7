<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salinan SIP dokter dan SIPP perawat pada saat rekam medis difinalisasi.
     * Dokumen final tidak boleh ikut berubah kalau nomor izin di profil berubah atau dikosongkan saat mutasi.
     */
    public function up(): void
    {
        Schema::table('finalisasi', function (Blueprint $table) {
            $table->string('sip_dokter', 50)->nullable()->after('catatan_finalisasi');
            $table->string('sipp_perawat', 50)->nullable()->after('sip_dokter');
        });

        // Rekam medis yang sudah final dibekukan dengan nomor yang berlaku sekarang, selama
        // dokter/perawatnya masih bertugas di RS kunjungan itu. Yang sudah pindah dibiarkan kosong
        // dan nanti dicari dari riwayat izin_praktik.
        $finalisasi = DB::table('finalisasi as f')
            ->join('kunjungan as k', 'k.id_kunjungan', '=', 'f.id_kunjungan')
            ->leftJoin('pemeriksaan_awal as pa', 'pa.id_kunjungan', '=', 'k.id_kunjungan')
            ->whereNotNull('f.finalized_at')
            ->get(['f.id_finalisasi', 'f.finalized_by', 'f.id_dokter', 'k.hospital_id', 'pa.id_perawat']);

        foreach ($finalisasi as $f) {
            $nomorDi = function ($idPengguna) use ($f) {
                if (! $idPengguna || ! $f->hospital_id) {
                    return null;
                }
                $p = DB::table('pengguna')->where('id_pengguna', $idPengguna)->first(['hospital_id', 'nomor_sip']);

                return $p && (int) $p->hospital_id === (int) $f->hospital_id && filled($p->nomor_sip) ? $p->nomor_sip : null;
            };

            DB::table('finalisasi')->where('id_finalisasi', $f->id_finalisasi)->update([
                'sip_dokter' => $nomorDi($f->finalized_by ?? $f->id_dokter),
                'sipp_perawat' => $nomorDi($f->id_perawat),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('finalisasi', function (Blueprint $table) {
            $table->dropColumn(['sip_dokter', 'sipp_perawat']);
        });
    }
};

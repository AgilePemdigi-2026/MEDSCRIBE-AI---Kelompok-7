<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat nomor izin praktik (SIP dokter / SIPP perawat) per rumah sakit.
     * SIP berlaku per tempat praktik, jadi saat dimutasi nomor di RS lama tidak boleh hilang:
     * dipakai lagi sebagai usulan kalau tenaga medis kembali ke RS itu, dan sebagai cadangan
     * nomor yang dicetak di rekam medis RS itu. pengguna.nomor_sip tetap berisi nomor di RS saat ini.
     */
    public function up(): void
    {
        Schema::create('izin_praktik', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_pengguna');
            $table->unsignedBigInteger('hospital_id');
            $table->string('nomor_sip', 50);
            $table->timestamps();

            $table->unique(['id_pengguna', 'hospital_id']);
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->cascadeOnDelete();
            $table->foreign('hospital_id')->references('id')->on('hospitals');
        });

        // Nomor yang sudah terisi sekarang menjadi riwayat di RS tempat mereka bertugas.
        $sekarang = now();
        DB::table('pengguna')
            ->whereNotNull('nomor_sip')
            ->where('nomor_sip', '!=', '')
            ->whereNotNull('hospital_id')
            ->whereIn('hospital_id', DB::table('hospitals')->select('id'))
            ->orderBy('id_pengguna')
            ->get(['id_pengguna', 'hospital_id', 'nomor_sip'])
            ->each(fn ($p) => DB::table('izin_praktik')->insert([
                'id_pengguna' => $p->id_pengguna,
                'hospital_id' => $p->hospital_id,
                'nomor_sip' => $p->nomor_sip,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_praktik');
    }
};

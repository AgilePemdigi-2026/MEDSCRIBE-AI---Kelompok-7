<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permintaan pindah penugasan dokter/perawat antar rumah sakit.
     * Diajukan oleh admin RS asal (lepas) atau RS tujuan (tarik), diputuskan admin RS satunya.
     */
    public function up(): void
    {
        Schema::create('mutasi_tenaga_medis', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_pengguna');
            $table->unsignedBigInteger('hospital_asal_id');
            $table->unsignedBigInteger('hospital_tujuan_id');
            $table->string('arah', 10);               // lepas | tarik
            $table->string('status', 20)->default('menunggu'); // menunggu | disetujui | ditolak | dibatalkan
            $table->text('alasan');
            $table->unsignedInteger('diajukan_oleh');
            $table->unsignedInteger('diputuskan_oleh')->nullable();
            $table->timestamp('diputuskan_at')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->unsignedInteger('kunjungan_dilepas')->default(0);
            $table->timestamp('dilihat_staf_at')->nullable();
            $table->timestamps();

            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->cascadeOnDelete();
            $table->foreign('hospital_asal_id')->references('id')->on('hospitals');
            $table->foreign('hospital_tujuan_id')->references('id')->on('hospitals');
            $table->foreign('diajukan_oleh')->references('id_pengguna')->on('pengguna');
            $table->foreign('diputuskan_oleh')->references('id_pengguna')->on('pengguna');
            $table->index(['status', 'hospital_asal_id']);
            $table->index(['status', 'hospital_tujuan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_tenaga_medis');
    }
};

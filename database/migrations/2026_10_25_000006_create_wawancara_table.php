<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wawancara', function (Blueprint $table) {
            $table->increments('id_wawancara');
            // Match the existing legacy schema, where kunjungan.id_kunjungan is a signed INT.
            $table->integer('id_kunjungan')->unique();
            $table->string('status', 30)->default('Belum Dimulai');
            $table->json('jawaban')->nullable();
            $table->text('transkrip')->nullable();
            $table->string('sumber_transkrip', 40)->nullable();
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->foreign('id_kunjungan')->references('id_kunjungan')->on('kunjungan')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wawancara');
    }
};

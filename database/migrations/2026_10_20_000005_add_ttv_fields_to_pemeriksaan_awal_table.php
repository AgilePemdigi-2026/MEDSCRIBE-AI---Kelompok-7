<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom data tanda-tanda vital (TTV) dan fisik lengkap
     * sesuai spesifikasi SRS Bab 4.1.3 REQ-4 & Mockup Gambar 3.1.
     */
    public function up(): void
    {
        Schema::table('pemeriksaan_awal', function (Blueprint $table) {
            $table->integer('laju_pernapasan')->nullable()->after('nadi');
            $table->decimal('berat_badan', 5, 1)->nullable()->after('laju_pernapasan');
            $table->decimal('tinggi_badan', 5, 1)->nullable()->after('berat_badan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemeriksaan_awal', function (Blueprint $table) {
            $table->dropColumn(['laju_pernapasan', 'berat_badan', 'tinggi_badan']);
        });
    }
};

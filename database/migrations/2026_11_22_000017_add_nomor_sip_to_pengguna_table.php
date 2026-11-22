<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor Surat Izin Praktik dokter, dicetak di bawah tanda tangan dokumen SOAP.
     * Diisi dokter sendiri lewat halaman profil.
     */
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('nomor_sip', 50)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn('nomor_sip');
        });
    }
};

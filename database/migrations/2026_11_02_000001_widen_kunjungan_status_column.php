<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 20 karakter tidak cukup untuk status seperti 'SOAP Belum Diverifikasi' (23 karakter).
        Schema::table('kunjungan', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->change();
        });
    }
};

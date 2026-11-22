<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identitas tambahan pasien. Semuanya boleh kosong karena pasien darurat
     * bisa didaftarkan hanya dengan nama.
     */
    public function up(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->after('jenis_kelamin');
            $table->string('no_bpjs', 13)->nullable()->after('nik');
            $table->string('no_hp', 20)->nullable()->after('no_bpjs');
        });
    }

    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropColumn(['nik', 'no_bpjs', 'no_hp']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontak rumah sakit untuk kop dokumen SOAP. Diisi admin RS di halaman Profil RS.
     */
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('telepon', 30)->nullable()->after('province');
            $table->string('email', 100)->nullable()->after('telepon');
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn(['telepon', 'email']);
        });
    }
};

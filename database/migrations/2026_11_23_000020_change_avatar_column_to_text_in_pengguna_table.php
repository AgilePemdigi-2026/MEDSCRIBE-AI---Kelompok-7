<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * URL foto profil dari Google bisa lebih dari 1.000 karakter, sehingga
     * VARCHAR(255) membuat login/daftar lewat Google gagal (SQLSTATE 22001).
     */
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     * Avatar yang lebih panjang dari 255 karakter dikosongkan dulu agar
     * kolom bisa dikembalikan ke VARCHAR(255) tanpa error.
     */
    public function down(): void
    {
        DB::table('pengguna')
            ->whereRaw('CHAR_LENGTH(avatar) > 255')
            ->update(['avatar' => null]);

        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('avatar', 255)->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom email unik dan remember_token ke tabel pengguna
     * secara aman dan backward-compatible dengan data lama.
     */
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            if (!Schema::hasColumn('pengguna', 'email')) {
                // Kolom email nullable saat migrasi agar kompatibel dengan record lama,
                // namun memiliki indeks unik. Validasi pendaftaran baru wajib diisi.
                $table->string('email', 100)->nullable()->unique()->after('nama_lengkap');
            }

            if (!Schema::hasColumn('pengguna', 'remember_token')) {
                $table->rememberToken()->nullable()->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            if (Schema::hasColumn('pengguna', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('pengguna', 'remember_token')) {
                $table->dropRememberToken();
            }
        });
    }
};

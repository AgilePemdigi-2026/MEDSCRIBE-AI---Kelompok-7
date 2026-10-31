<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan relasi hospital_id dan metadata autentikasi ke tabel pengguna, pasien, antrean, dan kunjungan.
     */
    public function up(): void
    {
        // 1. Perbarui tabel pengguna
        Schema::table('pengguna', function (Blueprint $table) {
            if (!Schema::hasColumn('pengguna', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('role');
                $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('set null');
            }
            if (!Schema::hasColumn('pengguna', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('pengguna', 'approval_status')) {
                $table->string('approval_status', 20)->default('approved')->after('role');
            }
            if (!Schema::hasColumn('pengguna', 'google_id')) {
                $table->string('google_id', 255)->nullable()->unique()->after('password');
            }
            if (!Schema::hasColumn('pengguna', 'avatar')) {
                $table->string('avatar', 255)->nullable()->after('google_id');
            }
        });

        // Tandai pengguna yang sudah ada di database sebagai terverifikasi (agar backward-compatible)
        \Illuminate\Support\Facades\DB::table('pengguna')
            ->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => now(),
                'approval_status' => 'approved',
            ]);

        // 2. Perbarui tabel pasien
        Schema::table('pasien', function (Blueprint $table) {
            if (!Schema::hasColumn('pasien', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('id_pasien');
                $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('set null');
                $table->index('hospital_id', 'pasien_hospital_id_index');
            }
        });

        // 3. Perbarui tabel antrean
        Schema::table('antrean', function (Blueprint $table) {
            if (!Schema::hasColumn('antrean', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('id_antrean');
                $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('set null');
                $table->index('hospital_id', 'antrean_hospital_id_index');
            }
        });

        // 4. Perbarui tabel kunjungan
        Schema::table('kunjungan', function (Blueprint $table) {
            if (!Schema::hasColumn('kunjungan', 'hospital_id')) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('id_kunjungan');
                $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('set null');
                $table->index('hospital_id', 'kunjungan_hospital_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungan', function (Blueprint $table) {
            if (Schema::hasColumn('kunjungan', 'hospital_id')) {
                $table->dropForeign(['hospital_id']);
                $table->dropIndex('kunjungan_hospital_id_index');
                $table->dropColumn('hospital_id');
            }
        });

        Schema::table('antrean', function (Blueprint $table) {
            if (Schema::hasColumn('antrean', 'hospital_id')) {
                $table->dropForeign(['hospital_id']);
                $table->dropIndex('antrean_hospital_id_index');
                $table->dropColumn('hospital_id');
            }
        });

        Schema::table('pasien', function (Blueprint $table) {
            if (Schema::hasColumn('pasien', 'hospital_id')) {
                $table->dropForeign(['hospital_id']);
                $table->dropIndex('pasien_hospital_id_index');
                $table->dropColumn('hospital_id');
            }
        });

        Schema::table('pengguna', function (Blueprint $table) {
            if (Schema::hasColumn('pengguna', 'hospital_id')) {
                $table->dropForeign(['hospital_id']);
                $table->dropColumn('hospital_id');
            }
            $dropCols = [];
            foreach (['email_verified_at', 'approval_status', 'google_id', 'avatar'] as $col) {
                if (Schema::hasColumn('pengguna', $col)) {
                    $dropCols[] = $col;
                }
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom umur, timestamps, dan indeks unik pada tabel pasien.
     * Menambahkan kolom saturasi oksigen (SpO2) dan timestamps pada tabel pemeriksaan_awal.
     * Menambahkan indeks performa untuk pencarian dan filter tanggal.
     */
    public function up(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            // Kolom umur (integer tahun) — alternatif tanggal_lahir untuk input cepat
            if (! Schema::hasColumn('pasien', 'umur')) {
                $table->unsignedTinyInteger('umur')->nullable()->after('nama_pasien');
            }

            // Timestamps untuk registrasi dan tracking
            if (! Schema::hasColumn('pasien', 'created_at')) {
                $table->timestamps();
            }
        });

        // Isi default created_at dan updated_at untuk pasien yang sudah ada
        DB::table('pasien')->whereNull('created_at')->update([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Existing development data may contain duplicate placeholders. Do not rewrite or delete them.
        $hasDuplicateMedicalRecords = DB::table('pasien')
            ->whereNotNull('no_rekam_medis')
            ->select('no_rekam_medis')
            ->groupBy('no_rekam_medis')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        // Add uniqueness only when legacy data already satisfies the constraint.
        if (! $hasDuplicateMedicalRecords && ! $this->hasIndex('pasien', 'pasien_no_rekam_medis_unique')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->unique('no_rekam_medis', 'pasien_no_rekam_medis_unique');
            });
        }

        // Indeks pencarian pada nama_pasien
        if (! $this->hasIndex('pasien', 'pasien_nama_pasien_index')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->index('nama_pasien', 'pasien_nama_pasien_index');
            });
        }

        // Indeks filter tanggal pada created_at
        if (! $this->hasIndex('pasien', 'pasien_created_at_index')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->index('created_at', 'pasien_created_at_index');
            });
        }

        Schema::table('pemeriksaan_awal', function (Blueprint $table) {
            // Saturasi Oksigen (SpO2) — standar TTV klinis
            if (! Schema::hasColumn('pemeriksaan_awal', 'saturasi_oksigen')) {
                $table->integer('saturasi_oksigen')->nullable()->after('suhu_tubuh');
            }

            // Tekanan darah terpisah (sistolik/diastolik) untuk pencarian dan validasi lebih baik
            if (! Schema::hasColumn('pemeriksaan_awal', 'tekanan_darah_sistolik')) {
                $table->unsignedSmallInteger('tekanan_darah_sistolik')->nullable()->after('tekanan_darah');
            }
            if (! Schema::hasColumn('pemeriksaan_awal', 'tekanan_darah_diastolik')) {
                $table->unsignedSmallInteger('tekanan_darah_diastolik')->nullable()->after('tekanan_darah_sistolik');
            }

            // Timestamps untuk tracking waktu pemeriksaan
            if (! Schema::hasColumn('pemeriksaan_awal', 'created_at')) {
                $table->timestamps();
            }
        });

        // Isi default created_at dan updated_at untuk pemeriksaan awal yang sudah ada
        DB::table('pemeriksaan_awal')->whereNull('created_at')->update([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Indeks pada waktu pemeriksaan
        if (! $this->hasIndex('pemeriksaan_awal', 'pemeriksaan_awal_created_at_index')) {
            Schema::table('pemeriksaan_awal', function (Blueprint $table) {
                $table->index('created_at', 'pemeriksaan_awal_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->hasIndex('pemeriksaan_awal', 'pemeriksaan_awal_created_at_index')) {
            Schema::table('pemeriksaan_awal', function (Blueprint $table) {
                $table->dropIndex('pemeriksaan_awal_created_at_index');
            });
        }

        Schema::table('pemeriksaan_awal', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('pemeriksaan_awal', 'saturasi_oksigen')) {
                $columns[] = 'saturasi_oksigen';
            }
            if (Schema::hasColumn('pemeriksaan_awal', 'tekanan_darah_sistolik')) {
                $columns[] = 'tekanan_darah_sistolik';
            }
            if (Schema::hasColumn('pemeriksaan_awal', 'tekanan_darah_diastolik')) {
                $columns[] = 'tekanan_darah_diastolik';
            }
            if (Schema::hasColumn('pemeriksaan_awal', 'created_at')) {
                $columns[] = 'created_at';
                $columns[] = 'updated_at';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        if ($this->hasIndex('pasien', 'pasien_created_at_index')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->dropIndex('pasien_created_at_index');
            });
        }

        if ($this->hasIndex('pasien', 'pasien_nama_pasien_index')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->dropIndex('pasien_nama_pasien_index');
            });
        }

        if ($this->hasIndex('pasien', 'pasien_no_rekam_medis_unique')) {
            Schema::table('pasien', function (Blueprint $table) {
                $table->dropUnique('pasien_no_rekam_medis_unique');
            });
        }

        Schema::table('pasien', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('pasien', 'umur')) {
                $columns[] = 'umur';
            }
            if (Schema::hasColumn('pasien', 'created_at')) {
                $columns[] = 'created_at';
                $columns[] = 'updated_at';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            return Schema::hasIndex($table, $indexName);
        } catch (Throwable) {
            try {
                $indexes = DB::select("SHOW INDEXES FROM `{$table}` WHERE Key_name = ?", [$indexName]);

                return ! empty($indexes);
            } catch (Throwable) {
                return false;
            }
        }
    }
};

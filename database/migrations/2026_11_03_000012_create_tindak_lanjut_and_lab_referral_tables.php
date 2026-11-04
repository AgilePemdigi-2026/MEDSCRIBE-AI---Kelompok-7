<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat fondasi tindak lanjut medis, rujukan laboratorium, dan katalog LOINC.
     *
     * Migration ini bersifat additive. Tabel e_resep yang sudah ada tidak dihapus
     * dan kolom lamanya tidak diubah namanya. Tipe kolom relasi diselaraskan dengan
     * tipe kolom aktual karena database lama dapat berasal dari dump SQL.
     */
    public function up(): void
    {
        $kunjunganUnsigned = $this->isUnsigned('kunjungan', 'id_kunjungan');
        $tindakLanjutUnsigned = $this->isUnsigned('e_resep', 'id_tindak_lanjut');

        if (! Schema::hasTable('tindak_lanjut')) {
            Schema::create('tindak_lanjut', function (Blueprint $table) use ($kunjunganUnsigned, $tindakLanjutUnsigned) {
                $table->integer('id_tindak_lanjut', true, $tindakLanjutUnsigned);

                if ($kunjunganUnsigned) {
                    $table->unsignedInteger('id_kunjungan')->nullable()->unique();
                } else {
                    $table->integer('id_kunjungan')->nullable()->unique();
                }

                $table->string('jenis_tindak_lanjut', 30);
                $table->string('status', 30)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rujukan_lab')) {
            Schema::create('rujukan_lab', function (Blueprint $table) use ($tindakLanjutUnsigned) {
                $table->increments('id_rujukan_lab');

                if ($tindakLanjutUnsigned) {
                    $table->unsignedInteger('id_tindak_lanjut')->unique();
                } else {
                    $table->integer('id_tindak_lanjut')->unique();
                }

                $table->text('catatan')->nullable();
                $table->string('status', 30)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('parameter_loinc')) {
            Schema::create('parameter_loinc', function (Blueprint $table) {
                $table->increments('id_parameter_loinc');
                $table->string('kode_loinc', 20)->unique();
                $table->string('nama_pemeriksaan', 150);
                $table->string('kategori', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rujukan_lab_parameter')) {
            Schema::create('rujukan_lab_parameter', function (Blueprint $table) {
                $table->increments('id_rujukan_lab_parameter');
                $table->unsignedInteger('id_rujukan_lab');
                $table->unsignedInteger('id_parameter_loinc')->nullable();
                $table->string('nama_pemeriksaan', 150);
                $table->string('kode_loinc', 20)->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index('kode_loinc', 'rujukan_lab_parameter_kode_loinc_index');
            });
        }

        $this->addForeignKeyIfMissing(
            'tindak_lanjut',
            'id_kunjungan',
            'tindak_lanjut_id_kunjungan_foreign',
            'kunjungan',
            'id_kunjungan'
        );

        $this->addForeignKeyIfMissing(
            'rujukan_lab',
            'id_tindak_lanjut',
            'rujukan_lab_id_tindak_lanjut_foreign',
            'tindak_lanjut',
            'id_tindak_lanjut'
        );

        $this->addForeignKeyIfMissing(
            'rujukan_lab_parameter',
            'id_rujukan_lab',
            'rujukan_lab_parameter_id_rujukan_lab_foreign',
            'rujukan_lab',
            'id_rujukan_lab'
        );

        $this->addForeignKeyIfMissing(
            'rujukan_lab_parameter',
            'id_parameter_loinc',
            'rujukan_lab_parameter_id_parameter_loinc_foreign',
            'parameter_loinc',
            'id_parameter_loinc',
            'set null'
        );

        $this->backfillLegacyTindakLanjut();
        $this->ensureEResepTindakLanjutRelation();
    }

    /**
     * Membuat induk tindak_lanjut untuk id_tindak_lanjut lama yang sudah dipakai e_resep.
     *
     * Pemetaan kunjungan hanya dilakukan jika kandidatnya tunggal dan tidak ambigu.
     * Jika tidak dapat dipastikan, id_kunjungan dibiarkan null agar tidak mengarang
     * hubungan medis. Data lama tidak dihapus atau diubah nilainya.
     */
    private function backfillLegacyTindakLanjut(): void
    {
        if (! Schema::hasTable('e_resep')
            || ! Schema::hasTable('tindak_lanjut')
            || ! Schema::hasTable('kunjungan')) {
            return;
        }

        $legacyIds = DB::table('e_resep')
            ->whereNotNull('id_tindak_lanjut')
            ->distinct()
            ->orderBy('id_tindak_lanjut')
            ->pluck('id_tindak_lanjut');

        if ($legacyIds->isEmpty()) {
            return;
        }

        $usedKunjungan = DB::table('tindak_lanjut')
            ->whereNotNull('id_kunjungan')
            ->pluck('id_kunjungan')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();

        $now = now();

        foreach ($legacyIds as $legacyId) {
            if (DB::table('tindak_lanjut')->where('id_tindak_lanjut', $legacyId)->exists()) {
                continue;
            }

            $pasienIds = DB::table('e_resep')
                ->where('id_tindak_lanjut', $legacyId)
                ->whereNotNull('id_pasien')
                ->distinct()
                ->pluck('id_pasien');

            $idKunjungan = null;

            if ($pasienIds->count() === 1) {
                $pasienId = $pasienIds->first();

                $directMatch = DB::table('kunjungan')
                    ->where('id_kunjungan', $legacyId)
                    ->where('id_pasien', $pasienId)
                    ->value('id_kunjungan');

                if ($directMatch !== null) {
                    $idKunjungan = (int) $directMatch;
                } else {
                    $candidates = DB::table('kunjungan')
                        ->where('id_pasien', $pasienId)
                        ->pluck('id_kunjungan');

                    if ($candidates->count() === 1) {
                        $idKunjungan = (int) $candidates->first();
                    }
                }
            }

            if ($idKunjungan !== null && isset($usedKunjungan[$idKunjungan])) {
                $idKunjungan = null;
            }

            DB::table('tindak_lanjut')->insert([
                'id_tindak_lanjut' => $legacyId,
                'id_kunjungan' => $idKunjungan,
                'jenis_tindak_lanjut' => 'resep',
                'status' => 'Tersimpan',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($idKunjungan !== null) {
                $usedKunjungan[$idKunjungan] = true;
            }
        }
    }

    /**
     * Menambahkan indeks dan foreign key e_resep.id_tindak_lanjut hanya setelah
     * seluruh nilai lama memiliki induk yang valid.
     */
    private function ensureEResepTindakLanjutRelation(): void
    {
        if (! Schema::hasTable('e_resep')
            || ! Schema::hasColumn('e_resep', 'id_tindak_lanjut')
            || ! Schema::hasTable('tindak_lanjut')) {
            return;
        }

        $hasNegativeIds = DB::table('e_resep')
            ->where('id_tindak_lanjut', '<', 0)
            ->exists();

        if (! Schema::hasIndex('e_resep', 'e_resep_id_tindak_lanjut_index')) {
            Schema::table('e_resep', function (Blueprint $table) {
                $table->index('id_tindak_lanjut', 'e_resep_id_tindak_lanjut_index');
            });
        }

        $hasOrphans = DB::table('e_resep as er')
            ->leftJoin('tindak_lanjut as tl', 'tl.id_tindak_lanjut', '=', 'er.id_tindak_lanjut')
            ->whereNull('tl.id_tindak_lanjut')
            ->exists();

        if ($hasNegativeIds || $hasOrphans || $this->hasForeignKey('e_resep', 'id_tindak_lanjut')) {
            return;
        }

        Schema::table('e_resep', function (Blueprint $table) {
            $table->foreign('id_tindak_lanjut', 'e_resep_id_tindak_lanjut_foreign')
                ->references('id_tindak_lanjut')
                ->on('tindak_lanjut')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $name,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'cascade',
        string $onUpdate = 'cascade'
    ): void {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
            || $this->hasForeignKey($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $name, $referencedTable, $referencedColumn, $onDelete, $onUpdate) {
            $blueprint->foreign($column, $name)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->onDelete($onDelete)
                ->onUpdate($onUpdate);
        });
    }

    private function isUnsigned(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql'
            || ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)) {
            return false;
        }

        $columnInfo = DB::selectOne(
            'select COLUMN_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ?',
            [$table, $column]
        );

        if ($columnInfo === null) {
            return false;
        }

        $columnType = (string) ($columnInfo->COLUMN_TYPE ?? $columnInfo->column_type ?? '');

        return str_contains(strtolower($columnType), 'unsigned');
    }

    private function hasForeignKey(string $table, string $column, ?string $name = null): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($name !== null && $foreignKey['name'] !== $name) {
                continue;
            }

            if (in_array($column, $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('e_resep') && Schema::hasColumn('e_resep', 'id_tindak_lanjut')) {
            if ($this->hasForeignKey('e_resep', 'id_tindak_lanjut', 'e_resep_id_tindak_lanjut_foreign')) {
                Schema::table('e_resep', function (Blueprint $table) {
                    $table->dropForeign('e_resep_id_tindak_lanjut_foreign');
                });
            }

            if (Schema::hasIndex('e_resep', 'e_resep_id_tindak_lanjut_index')) {
                Schema::table('e_resep', function (Blueprint $table) {
                    $table->dropIndex('e_resep_id_tindak_lanjut_index');
                });
            }
        }

        Schema::dropIfExists('rujukan_lab_parameter');
        Schema::dropIfExists('rujukan_lab');
        Schema::dropIfExists('parameter_loinc');
        Schema::dropIfExists('tindak_lanjut');
    }
};

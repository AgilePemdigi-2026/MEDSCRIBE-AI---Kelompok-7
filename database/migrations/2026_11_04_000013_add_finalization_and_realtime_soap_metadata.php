<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan metadata finalisasi dan metadata realtime draf SOAP.
     *
     * Foreign key pada tabel ini menunjuk ke pengguna.id_pengguna. Tipe kolom
     * acuan ikut menyesuaikan kolom yang ditunjuknya: database yang dibuat dari
     * dump SQL lama dapat menyimpan id_pengguna sebagai INT (signed) sehingga
     * kolom acuan dengan INT UNSIGNED tidak dapat dijadikan foreign key.
     */
    public function up(): void
    {
        if (Schema::hasTable('finalisasi')) {
            if (! Schema::hasColumn('finalisasi', 'finalized_at')) {
                Schema::table('finalisasi', function (Blueprint $table) {
                    $table->timestamp('finalized_at')->nullable()->after('FINAL');
                });
            }

            if (! Schema::hasColumn('finalisasi', 'finalized_by')) {
                Schema::table('finalisasi', function (Blueprint $table) {
                    $table->integer('finalized_by')->nullable()->after('finalized_at');
                });
            }

            $this->selaraskanTipe('finalisasi', 'finalized_by');
            $this->tambahForeignKey(
                'finalisasi',
                'finalized_by',
                'finalisasi_finalized_by_foreign',
                'pengguna',
                'id_pengguna',
                'set null',
                'cascade'
            );

            if (! Schema::hasColumn('finalisasi', 'catatan_finalisasi')) {
                Schema::table('finalisasi', function (Blueprint $table) {
                    $table->text('catatan_finalisasi')->nullable()->after('finalized_by');
                });
            }
        }

        if (Schema::hasTable('wawancara')) {
            if (! Schema::hasColumn('wawancara', 'transcript_verified_by')) {
                Schema::table('wawancara', function (Blueprint $table) {
                    $table->integer('transcript_verified_by')->nullable()->after('transcript_reviewed_at');
                });
            }

            $this->selaraskanTipe('wawancara', 'transcript_verified_by');
            $this->tambahForeignKey(
                'wawancara',
                'transcript_verified_by',
                'wawancara_transcript_verified_by_foreign',
                'pengguna',
                'id_pengguna',
                'set null',
                'cascade'
            );
        }

        if (Schema::hasTable('draf_soap')) {
            Schema::table('draf_soap', function (Blueprint $table) {
                if (! Schema::hasColumn('draf_soap', 'generation_status')) {
                    $table->string('generation_status', 30)->default('completed')->after('source_type');
                }
                if (! Schema::hasColumn('draf_soap', 'generation_error')) {
                    $table->text('generation_error')->nullable()->after('generation_status');
                }
                if (! Schema::hasColumn('draf_soap', 'attempt_id')) {
                    $table->string('attempt_id', 100)->nullable()->after('generation_error');
                }
            });
        }
    }

    /**
     * Samakan tipe kolom acuan foreign key dengan kolom yang ditunjuknya.
     *
     * Tanpa penyelarasan ini, MySQL menolak foreign key karena tanda bilangan
     * (signed/unsigned) dan kolom sudah terisi lebih dulu oleh percobaan
     * migrasi sebelumnya.
     */
    private function selaraskanTipe(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $tipe = $this->isUnsigned('pengguna', 'id_pengguna') ? 'INT UNSIGNED' : 'INT';
        $sekarang = $this->columnType($table, $column);

        if ($sekarang === null) {
            return;
        }

        $sekarangSigned = str_contains(strtolower($sekarang), 'unsigned') ? 'unsigned' : 'signed';
        $targetSigned = $tipe === 'INT UNSIGNED' ? 'unsigned' : 'signed';

        if ($sekarangSigned === $targetSigned) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$tipe} NULL");
    }

    private function tambahForeignKey(
        string $table,
        string $column,
        string $name,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'cascade',
        string $onUpdate = 'cascade'
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->hasForeignKey($table, $name)) {
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

    private function columnType(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'select COLUMN_TYPE from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ?',
            [$table, $column]
        );

        return $row === null ? null : (string) ($row->COLUMN_TYPE ?? $row->column_type ?? '');
    }

    private function isUnsigned(string $table, string $column): bool
    {
        $type = $this->columnType($table, $column);

        return $type !== null && str_contains(strtolower($type), 'unsigned');
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['name'] === $name) {
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
        if (Schema::hasTable('draf_soap')) {
            $columns = array_filter(['generation_status', 'generation_error', 'attempt_id'], fn ($col) => Schema::hasColumn('draf_soap', $col));
            if (! empty($columns)) {
                Schema::table('draf_soap', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('wawancara')) {
            if ($this->hasForeignKey('wawancara', 'wawancara_transcript_verified_by_foreign')) {
                Schema::table('wawancara', function (Blueprint $table) {
                    $table->dropForeign('wawancara_transcript_verified_by_foreign');
                });
            }
            if (Schema::hasColumn('wawancara', 'transcript_verified_by')) {
                Schema::table('wawancara', function (Blueprint $table) {
                    $table->dropColumn('transcript_verified_by');
                });
            }
        }

        if (Schema::hasTable('finalisasi')) {
            if ($this->hasForeignKey('finalisasi', 'finalisasi_finalized_by_foreign')) {
                Schema::table('finalisasi', function (Blueprint $table) {
                    $table->dropForeign('finalisasi_finalized_by_foreign');
                });
            }
            $columns = array_filter(['finalized_at', 'finalized_by', 'catatan_finalisasi'], fn ($col) => Schema::hasColumn('finalisasi', $col));
            if (! empty($columns)) {
                Schema::table('finalisasi', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};

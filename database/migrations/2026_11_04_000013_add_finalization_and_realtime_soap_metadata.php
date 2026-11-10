<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finalisasi')) {
            Schema::table('finalisasi', function (Blueprint $table) {
                if (! Schema::hasColumn('finalisasi', 'finalized_at')) {
                    $table->timestamp('finalized_at')->nullable()->after('FINAL');
                }
                if (! Schema::hasColumn('finalisasi', 'finalized_by')) {
                    $table->unsignedInteger('finalized_by')->nullable()->after('finalized_at');
                    $table->foreign('finalized_by', 'finalisasi_finalized_by_foreign')
                        ->references('id_pengguna')
                        ->on('pengguna')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                }
                if (! Schema::hasColumn('finalisasi', 'catatan_finalisasi')) {
                    $table->text('catatan_finalisasi')->nullable()->after('finalized_by');
                }
            });
        }

        if (Schema::hasTable('wawancara')) {
            Schema::table('wawancara', function (Blueprint $table) {
                if (! Schema::hasColumn('wawancara', 'transcript_verified_by')) {
                    $table->unsignedInteger('transcript_verified_by')->nullable()->after('transcript_reviewed_at');
                    $table->foreign('transcript_verified_by', 'wawancara_transcript_verified_by_foreign')
                        ->references('id_pengguna')
                        ->on('pengguna')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                }
            });
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

    public function down(): void
    {
        if (Schema::hasTable('draf_soap')) {
            Schema::table('draf_soap', function (Blueprint $table) {
                $columns = array_filter(['generation_status', 'generation_error', 'attempt_id'], fn ($col) => Schema::hasColumn('draf_soap', $col));
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('wawancara')) {
            Schema::table('wawancara', function (Blueprint $table) {
                if (Schema::hasColumn('wawancara', 'transcript_verified_by')) {
                    $table->dropForeign('wawancara_transcript_verified_by_foreign');
                    $table->dropColumn('transcript_verified_by');
                }
            });
        }

        if (Schema::hasTable('finalisasi')) {
            Schema::table('finalisasi', function (Blueprint $table) {
                if (Schema::hasColumn('finalisasi', 'finalized_by')) {
                    $table->dropForeign('finalisasi_finalized_by_foreign');
                }
                $columns = array_filter(['finalized_at', 'finalized_by', 'catatan_finalisasi'], fn ($col) => Schema::hasColumn('finalisasi', $col));
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};


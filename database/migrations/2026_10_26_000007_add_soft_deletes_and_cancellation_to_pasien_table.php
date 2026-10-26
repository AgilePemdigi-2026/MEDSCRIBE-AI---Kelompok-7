<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom soft deletes, cancelled_by, dan cancellation_reason
     * pada tabel pasien untuk pembatalan registrasi pasien secara aman.
     */
    public function up(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            if (!Schema::hasColumn('pasien', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('pasien', 'cancelled_by')) {
                $table->unsignedInteger('cancelled_by')->nullable()->after('deleted_at');
            }
            if (!Schema::hasColumn('pasien', 'cancellation_reason')) {
                $table->string('cancellation_reason', 255)->nullable()->after('cancelled_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('pasien', 'cancellation_reason')) {
                $columns[] = 'cancellation_reason';
            }
            if (Schema::hasColumn('pasien', 'cancelled_by')) {
                $columns[] = 'cancelled_by';
            }
            if (Schema::hasColumn('pasien', 'deleted_at')) {
                $columns[] = 'deleted_at';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan metadata persetujuan dan penonaktifan staf ke tabel pengguna,
     * serta membuat tabel admin_audit_logs untuk riwayat audit aktivitas admin rumah sakit.
     */
    public function up(): void
    {
        // 1. Kolom metadata persetujuan dan penonaktifan pada tabel pengguna
        Schema::table('pengguna', function (Blueprint $table) {
            if (!Schema::hasColumn('pengguna', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('pengguna', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
            if (!Schema::hasColumn('pengguna', 'approved_by')) {
                $table->unsignedInteger('approved_by')->nullable()->after('approval_status');
                $table->foreign('approved_by')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            }
            if (!Schema::hasColumn('pengguna', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('pengguna', 'rejected_by')) {
                $table->unsignedInteger('rejected_by')->nullable()->after('approved_at');
                $table->foreign('rejected_by')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            }
            if (!Schema::hasColumn('pengguna', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('pengguna', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('pengguna', 'deactivated_by')) {
                $table->unsignedInteger('deactivated_by')->nullable()->after('rejection_reason');
                $table->foreign('deactivated_by')->references('id_pengguna')->on('pengguna')->onDelete('set null');
            }
            if (!Schema::hasColumn('pengguna', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('deactivated_by');
            }
            if (!Schema::hasColumn('pengguna', 'deactivation_reason')) {
                $table->text('deactivation_reason')->nullable()->after('deactivated_at');
            }
        });

        // Backfill created_at untuk pengguna lama yang belum memiliki timestamp
        \Illuminate\Support\Facades\DB::table('pengguna')
            ->whereNull('created_at')
            ->update([
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // 2. Tabel audit log aktivitas admin rumah sakit
        if (!Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('admin_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedInteger('target_user_id')->nullable();
                $table->string('action', 50); // approve_staff, reject_staff, deactivate_staff, reactivate_staff
                $table->string('target_role', 30)->nullable();
                $table->string('target_name', 150)->nullable();
                $table->string('status_before', 30)->nullable();
                $table->string('status_after', 30)->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('admin_id')->references('id_pengguna')->on('pengguna')->onDelete('cascade');
                $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('cascade');
                $table->foreign('target_user_id')->references('id_pengguna')->on('pengguna')->onDelete('set null');

                $table->index(['hospital_id', 'created_at'], 'audit_hospital_created_idx');
                $table->index('admin_id', 'audit_admin_idx');
                $table->index('action', 'audit_action_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');

        Schema::table('pengguna', function (Blueprint $table) {
            $foreignKeys = ['approved_by', 'rejected_by', 'deactivated_by'];
            foreach ($foreignKeys as $fk) {
                if (Schema::hasColumn('pengguna', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }

            $columns = [
                'created_at', 'updated_at',
                'approved_by', 'approved_at',
                'rejected_by', 'rejected_at', 'rejection_reason',
                'deactivated_by', 'deactivated_at', 'deactivation_reason',
            ];
            $table->dropColumn(array_filter($columns, fn ($col) => Schema::hasColumn('pengguna', $col)));
        });
    }
};

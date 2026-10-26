<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekaman_audio', function (Blueprint $table) {
            $table->unsignedInteger('id_wawancara')->nullable()->after('id_kunjungan');
            $table->string('mime_type', 100)->nullable()->after('path_audio');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->unsignedInteger('duration_seconds')->nullable()->after('file_size');
            $table->string('job_id', 100)->nullable()->index()->after('status');
            $table->text('error_message')->nullable()->after('job_id');
            $table->timestamp('processed_at')->nullable()->after('waktu_selesai');
        });

        Schema::table('wawancara', function (Blueprint $table) {
            $table->json('transcript_segments')->nullable()->after('transkrip');
            $table->json('speaker_map')->nullable()->after('transcript_segments');
            $table->string('processing_status', 30)->nullable()->after('sumber_transkrip');
            $table->text('processing_error')->nullable()->after('processing_status');
            $table->timestamp('transcript_reviewed_at')->nullable()->after('waktu_selesai');
        });
    }

    public function down(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->dropColumn(['transcript_segments', 'speaker_map', 'processing_status', 'processing_error', 'transcript_reviewed_at']);
        });

        Schema::table('rekaman_audio', function (Blueprint $table) {
            $table->dropColumn(['id_wawancara', 'mime_type', 'file_size', 'duration_seconds', 'job_id', 'error_message', 'processed_at']);
        });
    }
};

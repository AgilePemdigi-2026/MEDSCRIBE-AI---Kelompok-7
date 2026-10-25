<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draf_soap', function (Blueprint $table) {
            $table->string('source_transcript_hash', 64)->nullable()->after('kode_icd10');
            $table->text('source_ttv_snapshot')->nullable()->after('source_transcript_hash');
            $table->boolean('needs_review')->default(false)->after('source_ttv_snapshot');
            $table->string('source_type', 40)->nullable()->after('needs_review');
        });
    }

    public function down(): void
    {
        Schema::table('draf_soap', function (Blueprint $table) {
            $table->dropColumn(['source_transcript_hash', 'source_ttv_snapshot', 'needs_review', 'source_type']);
        });
    }
};

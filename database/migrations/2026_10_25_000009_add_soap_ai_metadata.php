<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draf_soap', function (Blueprint $table) {
            $table->json('icd10_suggestions')->nullable()->after('kode_icd10');
            $table->json('missing_information')->nullable()->after('icd10_suggestions');
            $table->json('review_warnings')->nullable()->after('missing_information');
        });
    }

    public function down(): void
    {
        Schema::table('draf_soap', function (Blueprint $table) {
            $table->dropColumn(['icd10_suggestions', 'missing_information', 'review_warnings']);
        });
    }
};

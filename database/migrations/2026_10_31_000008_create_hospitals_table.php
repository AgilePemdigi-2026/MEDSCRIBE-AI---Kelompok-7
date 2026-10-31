<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel master data rumah sakit (hospital master data).
     */
    public function up(): void
    {
        if (!Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function (Blueprint $table) {
                $table->id();
                $table->string('hospital_code', 50)->unique();
                $table->string('hospital_name', 255);
                $table->string('hospital_type', 50)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('city', 100)->default('Kota Surabaya');
                $table->string('province', 100)->default('Jawa Timur');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
                $table->index('city');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};


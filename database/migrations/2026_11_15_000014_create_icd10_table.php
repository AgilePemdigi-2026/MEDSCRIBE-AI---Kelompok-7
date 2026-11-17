<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat katalog master ICD-10 sebagai sumber deskripsi kode diagnosis.
     *
     * Katalog diisi oleh Icd10Seeder dari berkas database/seeders/data/icd-10.csv
     * yang diunduh dari dokumen terminologi resmi Kemenkes (SATUSEHAT ICD-10 2010
     * dan ICD-IM Indonesia Modification). Kode dapat diisi bebas oleh dokter,
     * tabel ini hanya menyediakan deskripsi dan bab untuk kode yang dikenal.
     */
    public function up(): void
    {
        if (Schema::hasTable('icd10')) {
            return;
        }

        Schema::create('icd10', function (Blueprint $table) {
            $table->increments('id_icd10');
            $table->string('kode_icd10', 20)->unique();
            $table->string('deskripsi', 500);
            $table->string('kategori', 80)->nullable();
            $table->string('sumber', 30)->default('satusehat_2010');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('deskripsi', 'icd10_deskripsi_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd10');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel sesuai ketentuan dokumen SRS MedScribe AI dan PDM (Appendix B).
     */
    public function up(): void
    {
        // 1. Tabel pengguna
        Schema::create('pengguna', function (Blueprint $table) {
            $table->increments('id_pengguna');
            $table->string('nama_lengkap', 100);
            $table->string('role', 20);
            $table->string('password', 255);
        });

        // 2. Tabel pasien
        Schema::create('pasien', function (Blueprint $table) {
            $table->increments('id_pasien');
            $table->string('no_rekam_medis', 30);
            $table->string('nama_pasien', 100);
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
        });

        // 3. Tabel antrean
        Schema::create('antrean', function (Blueprint $table) {
            $table->increments('id_antrean');
            $table->unsignedInteger('id_pasien')->nullable();
            $table->date('tanggal_kunjungan');
            $table->string('status', 30)->nullable();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->onDelete('cascade')->onUpdate('cascade');
        });

        // 4. Tabel kunjungan
        Schema::create('kunjungan', function (Blueprint $table) {
            $table->increments('id_kunjungan');
            $table->unsignedInteger('id_antrean')->nullable();
            $table->unsignedInteger('id_pasien')->nullable();
            $table->unsignedInteger('id_dokter')->nullable();
            $table->date('tanggal');
            $table->string('status', 20)->nullable();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_dokter')->references('id_pengguna')->on('pengguna')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('id_antrean')->references('id_antrean')->on('antrean')->onDelete('set null')->onUpdate('cascade');
        });

        // 5. Tabel pemeriksaan_awal
        Schema::create('pemeriksaan_awal', function (Blueprint $table) {
            $table->increments('id_pemeriksaan');
            $table->unsignedInteger('id_kunjungan')->nullable();
            $table->unsignedInteger('id_perawat')->nullable();
            $table->string('tekanan_darah', 20)->nullable();
            $table->decimal('suhu_tubuh', 4, 1)->nullable();
            $table->integer('nadi')->nullable();
            $table->text('catatan')->nullable();

            $table->foreign('id_kunjungan')->references('id_kunjungan')->on('kunjungan')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_perawat')->references('id_pengguna')->on('pengguna')->onDelete('set null')->onUpdate('cascade');
        });

        // 6. Tabel rekaman_audio
        Schema::create('rekaman_audio', function (Blueprint $table) {
            $table->increments('id_rekaman');
            $table->unsignedInteger('id_kunjungan')->nullable();
            $table->string('path_audio', 255)->nullable();
            $table->string('status', 30)->nullable();
            $table->timestamp('waktu_selesai')->nullable();

            $table->foreign('id_kunjungan')->references('id_kunjungan')->on('kunjungan')->onDelete('cascade')->onUpdate('cascade');
        });

        // 7. Tabel draf_soap
        Schema::create('draf_soap', function (Blueprint $table) {
            $table->increments('id_draf_soap');
            $table->unsignedInteger('id_kunjungan')->nullable();
            $table->unsignedInteger('id_pengguna')->nullable();
            $table->text('subjective_text')->nullable();
            $table->text('objective_text')->nullable();
            $table->text('assessment_text')->nullable();
            $table->text('plan_text')->nullable();
            $table->string('kode_icd10', 255)->nullable();

            $table->foreign('id_kunjungan')->references('id_kunjungan')->on('kunjungan')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->onDelete('set null')->onUpdate('cascade');
        });

        // 8. Tabel finalisasi
        Schema::create('finalisasi', function (Blueprint $table) {
            $table->increments('id_finalisasi');
            $table->unsignedInteger('id_kunjungan')->nullable();
            $table->unsignedInteger('id_dokter')->nullable();
            $table->string('status', 20)->nullable();
            $table->boolean('FINAL')->default(false);

            $table->foreign('id_kunjungan')->references('id_kunjungan')->on('kunjungan')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_dokter')->references('id_pengguna')->on('pengguna')->onDelete('set null')->onUpdate('cascade');
        });

        // 9. Tabel e_resep
        Schema::create('e_resep', function (Blueprint $table) {
            $table->increments('id_e_resep');
            $table->unsignedInteger('id_pasien')->nullable();
            $table->integer('id_tindak_lanjut');
            $table->string('nama_obat', 100);
            $table->string('dosis', 50)->nullable();
            $table->string('bentuk_sediaan', 50)->nullable();
            $table->string('aturan_konsumsi', 100)->nullable();
            $table->integer('jumlah')->nullable();
            $table->string('status', 20)->nullable();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_resep');
        Schema::dropIfExists('finalisasi');
        Schema::dropIfExists('draf_soap');
        Schema::dropIfExists('rekaman_audio');
        Schema::dropIfExists('pemeriksaan_awal');
        Schema::dropIfExists('kunjungan');
        Schema::dropIfExists('antrean');
        Schema::dropIfExists('pasien');
        Schema::dropIfExists('pengguna');
    }
};

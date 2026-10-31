<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel pending_registrations untuk menyimpan data pendaftaran sementara
     * sebelum kode OTP diverifikasi. Akun resmi di tabel pengguna baru dibuat setelah OTP valid.
     */
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->unique();
            $table->string('role', 20);
            $table->unsignedBigInteger('hospital_id');
            $table->string('password'); // Hashed password (Bcrypt)
            $table->string('otp_hash'); // Hashed 6-digit OTP (Bcrypt)
            $table->dateTime('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->dateTime('last_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')
                ->references('id')
                ->on('hospitals')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};


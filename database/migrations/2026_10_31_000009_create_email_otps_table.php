<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel penyimpanan OTP verifikasi email dan lupa password.
     */
    public function up(): void
    {
        if (!Schema::hasTable('email_otps')) {
            Schema::create('email_otps', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->nullable();
                $table->string('email', 100);
                $table->string('otp_hash', 255);
                $table->string('purpose', 50); // 'registration_verification', 'password_reset'
                $table->timestamp('expires_at');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('max_attempts')->default(5);
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id_pengguna')->on('pengguna')->onDelete('cascade');
                $table->index(['email', 'purpose']);
                $table->index(['user_id', 'purpose']);
                $table->index('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};


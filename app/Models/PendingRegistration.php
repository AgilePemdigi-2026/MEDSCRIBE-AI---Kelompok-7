<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingRegistration extends Model
{
    /**
     * Nama tabel penyimpanan data pendaftaran sementara.
     */
    protected $table = 'pending_registrations';

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'nama_lengkap',
        'email',
        'role',
        'hospital_id',
        'password',
        'otp_hash',
        'expires_at',
        'attempts',
        'max_attempts',
        'last_sent_at',
    ];

    /**
     * Casting tipe data atribut.
     */
    protected function casts(): array
    {
        return [
            'expires_at'   => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts'     => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    /**
     * Relasi ke rumah sakit tempat bertugas.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Memeriksa apakah masa berlaku kode OTP telah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return $this->expires_at ? Carbon::now()->greaterThan($this->expires_at) : true;
    }

    /**
     * Memeriksa apakah jumlah percobaan telah melebihi batas toleransi.
     */
    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * Memeriksa apakah pengiriman ulang OTP diperbolehkan berdasarkan jeda cooldown.
     */
    public function canResend(int $cooldownSeconds = 60): bool
    {
        if (!$this->last_sent_at) {
            return true;
        }

        return $this->last_sent_at->copy()->addSeconds($cooldownSeconds)->isPast();
    }

    /**
     * Mendapatkan sisa detik sebelum pengiriman ulang diperbolehkan.
     */
    public function resendCooldownRemaining(int $cooldownSeconds = 60): int
    {
        if (!$this->last_sent_at) {
            return 0;
        }

        $unlockAt = $this->last_sent_at->copy()->addSeconds($cooldownSeconds);
        if ($unlockAt->isPast()) {
            return 0;
        }

        return (int) ceil(abs($unlockAt->diffInSeconds(Carbon::now())));
    }
}


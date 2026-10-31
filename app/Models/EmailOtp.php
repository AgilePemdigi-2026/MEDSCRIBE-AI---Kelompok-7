<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class EmailOtp extends Model
{
    public const PURPOSE_REGISTRATION = 'registration_verification';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    /**
     * Nama tabel penyimpanan OTP verifikasi email.
     */
    protected $table = 'email_otps';

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'user_id',
        'email',
        'otp_hash',
        'purpose',
        'expires_at',
        'attempts',
        'max_attempts',
        'consumed_at',
    ];

    /**
     * Casting tipe data kolom.
     */
    protected function casts(): array
    {
        return [
            'expires_at'   => 'datetime',
            'consumed_at'  => 'datetime',
            'attempts'     => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    /**
     * Relasi ke entitas pengguna terkait (jika ada).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'user_id', 'id_pengguna');
    }

    /**
     * Scope untuk mencari OTP aktif berdasarkan email dan purpose.
     */
    public function scopeActive(Builder $query, string $email, string $purpose): Builder
    {
        return $query->where('email', strtolower(trim($email)))
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Memeriksa apakah OTP telah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Memeriksa apakah OTP telah dikonsumsi (single-use).
     */
    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    /**
     * Memeriksa apakah batas percobaan salah telah tercapai.
     */
    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * Memverifikasi kode OTP plaintext terhadap representasi hash yang tersimpan.
     */
    public function verify(string $plainOtp): bool
    {
        if ($this->isExpired() || $this->isConsumed() || $this->hasExceededAttempts()) {
            return false;
        }

        $isValid = Hash::check($plainOtp, $this->otp_hash);

        if (!$isValid) {
            $this->increment('attempts');
            return false;
        }

        $this->update([
            'consumed_at' => Carbon::now(),
        ]);

        return true;
    }
}


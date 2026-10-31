<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    /**
     * Nama tabel di database relasional MedScribe AI.
     */
    protected $table = 'pengguna';

    /**
     * Primary key sesuai skema CDM/PDM MedScribe AI.
     */
    protected $primaryKey = 'id_pengguna';

    /**
     * Tabel pengguna tidak menggunakan kolom timestamps bawaan Laravel.
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'nama_lengkap',
        'email',
        'email_verified_at',
        'role',
        'hospital_id',
        'approval_status',
        'password',
        'google_id',
        'avatar',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi (keamanan data).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tipe casting otomatis untuk keamanan hashing password dan tanggal verifikasi email.
     */
    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke master data Rumah Sakit tempat tenaga medis berafiliasi.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Relasi ke kode OTP verifikasi email dan lupa password.
     */
    public function emailOtps(): HasMany
    {
        return $this->hasMany(EmailOtp::class, 'user_id', 'id_pengguna');
    }

    /**
     * Helper untuk memeriksa apakah email akun telah terverifikasi.
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Helper untuk memeriksa apakah akun telah disetujui untuk akses data klinis.
     */
    public function isApproved(): bool
    {
        return strtolower($this->approval_status ?? 'approved') === 'approved';
    }

    /**
     * Helper untuk memeriksa apakah pengguna berperan sebagai Dokter.
     */
    public function isDokter(): bool
    {
        return strtolower($this->role) === 'dokter';
    }

    /**
     * Helper untuk memeriksa apakah pengguna berperan sebagai Perawat.
     */
    public function isPerawat(): bool
    {
        return strtolower($this->role) === 'perawat';
    }

    /**
     * Accessor alias untuk properti peran agar kompatibel dengan penamaan peran/role dalam bahasa Indonesia.
     */
    public function getPeranAttribute(): ?string
    {
        return $this->role;
    }

    /**
     * Scope query berdasarkan rumah sakit pengguna.
     */
    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    /**
     * Relasi ke kunjungan pasien di mana pengguna bertindak sebagai dokter.
     */
    public function kunjunganDokter(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'id_dokter', 'id_pengguna');
    }

    /**
     * Relasi ke pemeriksaan awal fisik di mana pengguna bertindak sebagai perawat.
     */
    public function pemeriksaanPerawat(): HasMany
    {
        return $this->hasMany(PemeriksaanAwal::class, 'id_perawat', 'id_pengguna');
    }

    /**
     * Relasi ke draf catatan SOAP yang dibuat / diverifikasi oleh pengguna.
     */
    public function drafSoap(): HasMany
    {
        return $this->hasMany(DrafSoap::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi ke berkas finalisasi rekam medis dokter.
     */
    public function finalisasi(): HasMany
    {
        return $this->hasMany(Finalisasi::class, 'id_dokter', 'id_pengguna');
    }
}

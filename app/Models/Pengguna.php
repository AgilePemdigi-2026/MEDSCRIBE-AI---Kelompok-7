<?php

namespace App\Models;

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
        'role',
        'password',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi (keamanan data).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tipe casting otomatis untuk keamanan hashing password.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
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

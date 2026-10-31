<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    use HasFactory;

    /**
     * Nama tabel master data rumah sakit.
     */
    protected $table = 'hospitals';

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'hospital_code',
        'hospital_name',
        'hospital_type',
        'address',
        'city',
        'province',
        'is_active',
    ];

    /**
     * Casting atribut.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope untuk mengambil hanya rumah sakit yang aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Relasi ke seluruh tenaga medis dan staf pengguna rumah sakit.
     */
    public function pengguna(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'hospital_id');
    }

    /**
     * Relasi khusus staf dokter rumah sakit.
     */
    public function dokter(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'hospital_id')->whereRaw('LOWER(role) = ?', ['dokter']);
    }

    /**
     * Relasi khusus staf perawat rumah sakit.
     */
    public function perawat(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'hospital_id')->whereRaw('LOWER(role) = ?', ['perawat']);
    }

    /**
     * Relasi ke pasien yang terdaftar di rumah sakit ini.
     */
    public function pasien(): HasMany
    {
        return $this->hasMany(Pasien::class, 'hospital_id');
    }

    /**
     * Relasi ke antrean kunjungan di rumah sakit ini.
     */
    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class, 'hospital_id');
    }

    /**
     * Relasi ke kunjungan klinis di rumah sakit ini.
     */
    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'hospital_id');
    }
}


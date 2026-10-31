<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasien extends Model
{
    use SoftDeletes;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    public $timestamps = true;

    protected $fillable = [
        'hospital_id',
        'no_rekam_medis',
        'nama_pasien',
        'umur',
        'tanggal_lahir',
        'jenis_kelamin',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'hospital_id'   => 'integer',
        'umur'          => 'integer',
        'tanggal_lahir' => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Relasi ke rumah sakit tempat pasien terdaftar.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Scope query pembatasan data pasien berdasarkan rumah sakit (Multi-Tenancy Isolation).
     */
    public function scopeForHospital(Builder $query, ?int $hospitalId): Builder
    {
        if ($hospitalId === null) {
            return $query->whereNull('hospital_id');
        }
        return $query->where('hospital_id', $hospitalId);
    }

    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class, 'id_pasien', 'id_pasien');
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'id_pasien', 'id_pasien');
    }

    public function eResep(): HasMany
    {
        return $this->hasMany(EResep::class, 'id_pasien', 'id_pasien');
    }

    /**
     * Relasi langsung ke semua pemeriksaan awal melalui kunjungan.
     */
    public function pemeriksaanAwal()
    {
        return $this->hasManyThrough(
            PemeriksaanAwal::class,
            Kunjungan::class,
            'id_pasien',      // FK on kunjungan
            'id_kunjungan',   // FK on pemeriksaan_awal
            'id_pasien',      // Local key on pasien
            'id_kunjungan'    // Local key on kunjungan
        );
    }

    /**
     * Mendapatkan pemeriksaan awal terakhir.
     */
    public function latestPemeriksaanAwal()
    {
        return $this->hasOneThrough(
            PemeriksaanAwal::class,
            Kunjungan::class,
            'id_pasien',
            'id_kunjungan',
            'id_pasien',
            'id_kunjungan'
        )->latestOfMany('id_pemeriksaan');
    }

    /**
     * Petugas/perawat yang membatalkan registrasi pasien.
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'cancelled_by', 'id_pengguna');
    }
}

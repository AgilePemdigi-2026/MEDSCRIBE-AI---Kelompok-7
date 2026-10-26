<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasien extends Model
{
    use SoftDeletes;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    public $timestamps = true;

    protected $fillable = [
        'no_rekam_medis',
        'nama_pasien',
        'umur',
        'tanggal_lahir',
        'jenis_kelamin',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'umur' => 'integer',
        'tanggal_lahir' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function antrean()
    {
        return $this->hasMany(Antrean::class, 'id_pasien', 'id_pasien');
    }

    public function kunjungan()
    {
        return $this->hasMany(Kunjungan::class, 'id_pasien', 'id_pasien');
    }

    public function eResep()
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
    public function cancelledByUser()
    {
        return $this->belongsTo(Pengguna::class, 'cancelled_by', 'id_pengguna');
    }
}

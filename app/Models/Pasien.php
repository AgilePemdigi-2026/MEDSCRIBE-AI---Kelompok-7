<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    public $timestamps = false;

    protected $fillable = [
        'no_rekam_medis',
        'nama_pasien',
        'tanggal_lahir',
        'jenis_kelamin',
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
}

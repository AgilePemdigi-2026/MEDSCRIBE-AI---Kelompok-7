<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrean extends Model
{
    protected $table = 'antrean';
    protected $primaryKey = 'id_antrean';
    public $timestamps = false;

    protected $fillable = [
        'id_pasien',
        'tanggal_kunjungan',
        'status',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function kunjungan()
    {
        return $this->hasOne(Kunjungan::class, 'id_antrean', 'id_antrean');
    }
}

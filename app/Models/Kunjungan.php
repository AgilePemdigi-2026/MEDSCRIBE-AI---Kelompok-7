<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $table = 'kunjungan';
    protected $primaryKey = 'id_kunjungan';
    public $timestamps = false;

    protected $fillable = [
        'id_antrean',
        'id_pasien',
        'id_dokter',
        'tanggal',
        'status',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function dokter()
    {
        return $this->belongsTo(Pengguna::class, 'id_dokter', 'id_pengguna');
    }

    public function antrean()
    {
        return $this->belongsTo(Antrean::class, 'id_antrean', 'id_antrean');
    }

    public function pemeriksaanAwal()
    {
        return $this->hasOne(PemeriksaanAwal::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function rekamanAudio()
    {
        return $this->hasOne(RekamanAudio::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function wawancara()
    {
        return $this->hasOne(Wawancara::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function drafSoap()
    {
        return $this->hasOne(DrafSoap::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function finalisasi()
    {
        return $this->hasOne(Finalisasi::class, 'id_kunjungan', 'id_kunjungan');
    }
}

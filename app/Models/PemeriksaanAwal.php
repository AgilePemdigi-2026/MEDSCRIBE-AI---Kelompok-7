<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanAwal extends Model
{
    protected $table = 'pemeriksaan_awal';
    protected $primaryKey = 'id_pemeriksaan';
    public $timestamps = false;

    protected $fillable = [
        'id_kunjungan',
        'id_perawat',
        'tekanan_darah',
        'suhu_tubuh',
        'nadi',
        'catatan',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function perawat()
    {
        return $this->belongsTo(Pengguna::class, 'id_perawat', 'id_pengguna');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemeriksaanAwal extends Model
{
    protected $table = 'pemeriksaan_awal';
    protected $primaryKey = 'id_pemeriksaan';
    public $timestamps = true;

    protected $fillable = [
        'id_kunjungan',
        'id_perawat',
        'tekanan_darah',
        'tekanan_darah_sistolik',
        'tekanan_darah_diastolik',
        'suhu_tubuh',
        'saturasi_oksigen',
        'nadi',
        'laju_pernapasan',
        'berat_badan',
        'tinggi_badan',
        'catatan',
    ];

    protected $casts = [
        'tekanan_darah_sistolik' => 'integer',
        'tekanan_darah_diastolik' => 'integer',
        'suhu_tubuh' => 'decimal:1',
        'saturasi_oksigen' => 'integer',
        'nadi' => 'integer',
        'laju_pernapasan' => 'integer',
        'berat_badan' => 'decimal:1',
        'tinggi_badan' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

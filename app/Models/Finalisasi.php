<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Finalisasi extends Model
{
    protected $table = 'finalisasi';
    protected $primaryKey = 'id_finalisasi';
    public $timestamps = false;

    protected $fillable = [
        'id_kunjungan',
        'id_dokter',
        'status',
        'FINAL',
    ];

    protected $casts = [
        'FINAL' => 'boolean',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function dokter()
    {
        return $this->belongsTo(Pengguna::class, 'id_dokter', 'id_pengguna');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekamanAudio extends Model
{
    protected $table = 'rekaman_audio';
    protected $primaryKey = 'id_rekaman';
    public $timestamps = false;

    protected $fillable = [
        'id_kunjungan',
        'path_audio',
        'status',
        'waktu_selesai',
    ];

    protected $casts = [
        'waktu_selesai' => 'datetime',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }
}

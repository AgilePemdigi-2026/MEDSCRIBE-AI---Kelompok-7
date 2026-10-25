<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wawancara extends Model
{
    protected $table = 'wawancara';

    protected $primaryKey = 'id_wawancara';

    public $timestamps = false;

    protected $fillable = [
        'id_kunjungan',
        'status',
        'jawaban',
        'transkrip',
        'sumber_transkrip',
        'waktu_mulai',
        'waktu_selesai',
    ];

    protected $casts = [
        'jawaban' => 'array',
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }
}

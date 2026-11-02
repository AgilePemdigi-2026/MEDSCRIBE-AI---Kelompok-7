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
        'id_wawancara',
        'path_audio',
        'mime_type',
        'file_size',
        'duration_seconds',
        'status',
        'job_id',
        'error_message',
        'waktu_selesai',
        'processed_at',
    ];

    protected $casts = [
        'waktu_selesai' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function wawancara()
    {
        return $this->belongsTo(Wawancara::class, 'id_wawancara', 'id_wawancara');
    }
}

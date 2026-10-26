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
        'transcript_segments',
        'speaker_map',
        'sumber_transkrip',
        'processing_status',
        'processing_error',
        'waktu_mulai',
        'waktu_selesai',
        'transcript_reviewed_at',
    ];

    protected $casts = [
        'jawaban' => 'array',
        'transcript_segments' => 'array',
        'speaker_map' => 'array',
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'transcript_reviewed_at' => 'datetime',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function rekamanAudio()
    {
        return $this->hasMany(RekamanAudio::class, 'id_wawancara', 'id_wawancara');
    }
}

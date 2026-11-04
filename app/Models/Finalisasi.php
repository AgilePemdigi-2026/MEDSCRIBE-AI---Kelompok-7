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
        'finalized_at',
        'finalized_by',
        'catatan_finalisasi',
    ];

    protected $casts = [
        'FINAL' => 'boolean',
        'finalized_at' => 'datetime',
        'finalized_by' => 'integer',
    ];

    public function isLocked(): bool
    {
        return ((bool) $this->FINAL && in_array($this->status, [
            'Final',
            'Terkunci',
            'Terintegrasi SATUSEHAT',
            'Sinkronisasi Tertunda',
        ], true)) || $this->finalized_at !== null;
    }

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function dokter()
    {
        return $this->belongsTo(Pengguna::class, 'id_dokter', 'id_pengguna');
    }

    public function finalizedBy()
    {
        return $this->belongsTo(Pengguna::class, 'finalized_by', 'id_pengguna');
    }
}

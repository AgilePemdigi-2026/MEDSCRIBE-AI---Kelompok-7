<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nomor izin praktik terakhir seorang tenaga medis di satu rumah sakit
 * (SIP dokter / SIPP perawat). Satu baris per pasangan pengguna-RS.
 */
class IzinPraktik extends Model
{
    protected $table = 'izin_praktik';

    protected $fillable = [
        'id_pengguna',
        'hospital_id',
        'nomor_sip',
    ];

    protected $casts = [
        'id_pengguna' => 'integer',
        'hospital_id' => 'integer',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }
}

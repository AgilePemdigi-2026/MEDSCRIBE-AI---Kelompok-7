<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Antrean extends Model
{
    protected $table = 'antrean';
    protected $primaryKey = 'id_antrean';
    public $timestamps = false;

    protected $fillable = [
        'hospital_id',
        'id_pasien',
        'tanggal_kunjungan',
        'status',
    ];

    protected $casts = [
        'hospital_id' => 'integer',
        'id_pasien'   => 'integer',
    ];

    /**
     * Relasi ke rumah sakit tempat antrean terdaftar.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Scope query pembatasan data antrean berdasarkan rumah sakit.
     */
    public function scopeForHospital(Builder $query, ?int $hospitalId): Builder
    {
        if ($hospitalId === null) {
            return $query->whereNull('hospital_id');
        }

        return $query->where(function ($q) use ($hospitalId) {
            $q->where('hospital_id', $hospitalId)
              ->orWhere(function ($sub) use ($hospitalId) {
                  $sub->whereNull('hospital_id')
                      ->whereHas('pasien', function ($p) use ($hospitalId) {
                          $p->where('hospital_id', $hospitalId);
                      });
              });
        });
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function kunjungan(): HasOne
    {
        return $this->hasOne(Kunjungan::class, 'id_antrean', 'id_antrean');
    }
}

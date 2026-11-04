<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TindakLanjut extends Model
{
    public const JENIS_RESEP = 'resep';

    public const JENIS_RESEP_RUJUKAN = 'resep_rujukan';

    public const JENIS_RUJUKAN = 'rujukan';

    public const JENIS_TANPA_TINDAKAN = 'tanpa_tindakan';

    public const STATUS_TERSIMPAN = 'Tersimpan';

    protected $table = 'tindak_lanjut';

    protected $primaryKey = 'id_tindak_lanjut';

    public $timestamps = true;

    protected $fillable = [
        'id_kunjungan',
        'jenis_tindak_lanjut',
        'status',
    ];

    protected $casts = [
        'id_kunjungan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function eResep(): HasMany
    {
        return $this->hasMany(EResep::class, 'id_tindak_lanjut', 'id_tindak_lanjut');
    }

    public function rujukanLab(): HasOne
    {
        return $this->hasOne(RujukanLab::class, 'id_tindak_lanjut', 'id_tindak_lanjut');
    }

    public function statusKunjungan(): string
    {
        return match ($this->jenis_tindak_lanjut) {
            self::JENIS_RESEP => Kunjungan::STATUS_RESEP_TERSIMPAN,
            self::JENIS_RUJUKAN => Kunjungan::STATUS_RUJUKAN_LAB_TERSIMPAN,
            self::JENIS_RESEP_RUJUKAN => Kunjungan::STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN,
            self::JENIS_TANPA_TINDAKAN => Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT,
            default => Kunjungan::STATUS_SOAP_TERVERIFIKASI,
        };
    }
}

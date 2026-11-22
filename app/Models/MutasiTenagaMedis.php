<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pindah penugasan tenaga medis antar rumah sakit.
 *
 * arah "lepas": diajukan admin RS asal, diputuskan admin RS tujuan.
 * arah "tarik": diajukan admin RS tujuan, diputuskan admin RS asal.
 */
class MutasiTenagaMedis extends Model
{
    public const LEPAS = 'lepas';
    public const TARIK = 'tarik';

    public const MENUNGGU = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK = 'ditolak';
    public const DIBATALKAN = 'dibatalkan';

    protected $table = 'mutasi_tenaga_medis';

    protected $fillable = [
        'id_pengguna', 'hospital_asal_id', 'hospital_tujuan_id', 'arah', 'status', 'alasan',
        'diajukan_oleh', 'diputuskan_oleh', 'diputuskan_at', 'alasan_penolakan',
        'kunjungan_dilepas', 'dilihat_staf_at',
    ];

    protected $casts = [
        'diputuskan_at' => 'datetime',
        'dilihat_staf_at' => 'datetime',
        'kunjungan_dilepas' => 'integer',
    ];

    public function staf(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function rsAsal(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_asal_id');
    }

    public function rsTujuan(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_tujuan_id');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diajukan_oleh', 'id_pengguna');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diputuskan_oleh', 'id_pengguna');
    }

    /** RS yang berhak memutuskan: tujuan untuk "lepas", asal untuk "tarik". */
    public function hospitalPemutusId(): int
    {
        return (int) ($this->arah === self::LEPAS ? $this->hospital_tujuan_id : $this->hospital_asal_id);
    }

    /** RS yang mengajukan. */
    public function hospitalPengajuId(): int
    {
        return (int) ($this->arah === self::LEPAS ? $this->hospital_asal_id : $this->hospital_tujuan_id);
    }

    public function isMenunggu(): bool
    {
        return $this->status === self::MENUNGGU;
    }

    /** Mutasi yang melibatkan RS tertentu, sebagai asal maupun tujuan. */
    public function scopeMelibatkan(Builder $query, int $hospitalId): Builder
    {
        return $query->where(fn ($q) => $q->where('hospital_asal_id', $hospitalId)->orWhere('hospital_tujuan_id', $hospitalId));
    }

    /** Permintaan menunggu yang harus diputuskan RS ini. */
    public function scopeMasukUntuk(Builder $query, int $hospitalId): Builder
    {
        return $query->where('status', self::MENUNGGU)->where(function ($q) use ($hospitalId) {
            $q->where(fn ($l) => $l->where('arah', self::LEPAS)->where('hospital_tujuan_id', $hospitalId))
              ->orWhere(fn ($t) => $t->where('arah', self::TARIK)->where('hospital_asal_id', $hospitalId));
        });
    }

    /** Permintaan menunggu yang diajukan RS ini. */
    public function scopeDiajukanOleh(Builder $query, int $hospitalId): Builder
    {
        return $query->where('status', self::MENUNGGU)->where(function ($q) use ($hospitalId) {
            $q->where(fn ($l) => $l->where('arah', self::LEPAS)->where('hospital_asal_id', $hospitalId))
              ->orWhere(fn ($t) => $t->where('arah', self::TARIK)->where('hospital_tujuan_id', $hospitalId));
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::MENUNGGU => 'Menunggu persetujuan',
            self::DISETUJUI => 'Disetujui',
            self::DITOLAK => 'Ditolak',
            self::DIBATALKAN => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            self::MENUNGGU => 'status-warn',
            self::DISETUJUI => 'status-ok',
            self::DITOLAK => 'status-danger',
            default => 'status-muted',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kunjungan extends Model
{
    public const STATUS_SOAP_BELUM_DIVERIFIKASI = 'SOAP Belum Diverifikasi';

    public const STATUS_SOAP_TERVERIFIKASI = 'SOAP Terverifikasi';

    public const STATUS_RESEP_TERSIMPAN = 'Resep Tersimpan';

    public const STATUS_RUJUKAN_LAB_TERSIMPAN = 'Rujukan Lab Tersimpan';

    public const STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN = 'Rujukan Lab & Resep Tersimpan';

    public const STATUS_SELESAI_TANPA_TINDAKAN_LANJUT = 'Selesai Tanpa Tindakan Lanjut';

    public static function soapVerifiedStatuses(): array
    {
        return [
            self::STATUS_SOAP_TERVERIFIKASI,
            self::STATUS_RESEP_TERSIMPAN,
            self::STATUS_RUJUKAN_LAB_TERSIMPAN,
            self::STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN,
            self::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT,
        ];
    }

    public function isSoapVerified(): bool
    {
        return in_array($this->status, self::soapVerifiedStatuses(), true);
    }

    protected $table = 'kunjungan';
    protected $primaryKey = 'id_kunjungan';
    public $timestamps = false;

    protected $fillable = [
        'hospital_id',
        'id_antrean',
        'id_pasien',
        'id_dokter',
        'tanggal',
        'status',
    ];

    protected $casts = [
        'hospital_id' => 'integer',
        'id_antrean'  => 'integer',
        'id_pasien'   => 'integer',
        'id_dokter'   => 'integer',
    ];

    /**
     * Relasi ke rumah sakit tempat kunjungan dilakukan.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Scope query pembatasan data kunjungan berdasarkan rumah sakit.
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

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_dokter', 'id_pengguna');
    }

    public function antrean(): BelongsTo
    {
        return $this->belongsTo(Antrean::class, 'id_antrean', 'id_antrean');
    }

    public function pemeriksaanAwal(): HasOne
    {
        return $this->hasOne(PemeriksaanAwal::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function rekamanAudio(): HasMany
    {
        return $this->hasMany(RekamanAudio::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function wawancara(): HasOne
    {
        return $this->hasOne(Wawancara::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function drafSoap(): HasOne
    {
        return $this->hasOne(DrafSoap::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function finalisasi(): HasOne
    {
        return $this->hasOne(Finalisasi::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function tindakLanjut(): HasOne
    {
        return $this->hasOne(TindakLanjut::class, 'id_kunjungan', 'id_kunjungan');
    }
}

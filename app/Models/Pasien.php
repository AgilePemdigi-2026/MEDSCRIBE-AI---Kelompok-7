<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasien extends Model
{
    use SoftDeletes;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    public $timestamps = true;

    protected $fillable = [
        'hospital_id',
        'no_rekam_medis',
        'nama_pasien',
        'umur',
        'tanggal_lahir',
        'jenis_kelamin',
        'nik',
        'no_bpjs',
        'no_hp',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'hospital_id'   => 'integer',
        'umur'          => 'integer',
        'tanggal_lahir' => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Usia dalam tahun: kolom umur (bisa perkiraan) diutamakan, lalu tanggal lahir.
     * Null kalau keduanya kosong. Umur 0 (bayi) tetap dianggap terisi.
     */
    public function getUsiaAttribute(): ?int
    {
        if ($this->umur !== null) {
            return (int) $this->umur;
        }

        return $this->tanggal_lahir?->age;
    }

    /**
     * Usia siap tampil: "36 tahun", atau "5 bulan" untuk bayi yang tanggal lahirnya diketahui.
     */
    public function getUsiaLabelAttribute(): ?string
    {
        $usia = $this->usia;
        if ($usia === null) {
            return null;
        }

        if ($usia === 0 && $this->tanggal_lahir) {
            return $this->tanggal_lahir->diffInMonths(now()) < 1
                ? (int) $this->tanggal_lahir->diffInDays(now()) . ' hari'
                : (int) $this->tanggal_lahir->diffInMonths(now()) . ' bulan';
        }

        return $usia === 0 ? 'Di bawah 1 tahun' : $usia . ' tahun';
    }

    /**
     * Relasi ke rumah sakit tempat pasien terdaftar.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Scope query pembatasan data pasien berdasarkan rumah sakit (Multi-Tenancy Isolation).
     */
    public function scopeForHospital(Builder $query, ?int $hospitalId): Builder
    {
        if ($hospitalId === null) {
            return $query->whereNull('hospital_id');
        }
        return $query->where('hospital_id', $hospitalId);
    }

    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class, 'id_pasien', 'id_pasien');
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'id_pasien', 'id_pasien');
    }

    public function eResep(): HasMany
    {
        return $this->hasMany(EResep::class, 'id_pasien', 'id_pasien');
    }

    /**
     * Relasi langsung ke semua pemeriksaan awal melalui kunjungan.
     */
    public function pemeriksaanAwal()
    {
        return $this->hasManyThrough(
            PemeriksaanAwal::class,
            Kunjungan::class,
            'id_pasien',      // FK on kunjungan
            'id_kunjungan',   // FK on pemeriksaan_awal
            'id_pasien',      // Local key on pasien
            'id_kunjungan'    // Local key on kunjungan
        );
    }

    /**
     * Mendapatkan pemeriksaan awal terakhir.
     */
    public function latestPemeriksaanAwal()
    {
        return $this->hasOneThrough(
            PemeriksaanAwal::class,
            Kunjungan::class,
            'id_pasien',
            'id_kunjungan',
            'id_pasien',
            'id_kunjungan'
        )->latestOfMany('id_pemeriksaan');
    }

    /**
     * Petugas/perawat yang membatalkan registrasi pasien.
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'cancelled_by', 'id_pengguna');
    }
}

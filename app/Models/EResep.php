<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EResep extends Model
{
    protected $table = 'e_resep';
    protected $primaryKey = 'id_e_resep';
    public $timestamps = false;

    protected $fillable = [
        'id_pasien',
        'id_tindak_lanjut',
        'nama_obat',
        'dosis',
        'bentuk_sediaan',
        'aturan_konsumsi',
        'jumlah',
        'status',
    ];

    protected $casts = [
        'id_pasien' => 'integer',
        'id_tindak_lanjut' => 'integer',
        'jumlah' => 'integer',
    ];

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function tindakLanjut(): BelongsTo
    {
        return $this->belongsTo(TindakLanjut::class, 'id_tindak_lanjut', 'id_tindak_lanjut');
    }
}

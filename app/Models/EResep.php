<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }
}

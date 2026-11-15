<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Katalog master kode ICD-10 (WHO ICD-10 2010 + Indonesian Modification).
 *
 * Sumber data: dokumen terminologi resmi Kementerian Kesehatan RI untuk
 * SATUSEHAT Platform. Kode pada tabel ini hanya menjadi acuan deskripsi;
 * dokter tetap bebas mengetik kode ICD-10 pada formulir draf SOAP.
 */
class Icd10 extends Model
{
    protected $table = 'icd10';

    protected $primaryKey = 'id_icd10';

    public $timestamps = true;

    protected $fillable = [
        'kode_icd10',
        'deskripsi',
        'kategori',
        'sumber',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

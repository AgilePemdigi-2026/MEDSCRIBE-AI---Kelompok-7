<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParameterLoinc extends Model
{
    protected $table = 'parameter_loinc';

    protected $primaryKey = 'id_parameter_loinc';

    public $timestamps = true;

    protected $fillable = [
        'kode_loinc',
        'nama_pemeriksaan',
        'kategori',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rujukanLabParameter(): HasMany
    {
        return $this->hasMany(RujukanLabParameter::class, 'id_parameter_loinc', 'id_parameter_loinc');
    }
}

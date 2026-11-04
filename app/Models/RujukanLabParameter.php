<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RujukanLabParameter extends Model
{
    protected $table = 'rujukan_lab_parameter';

    protected $primaryKey = 'id_rujukan_lab_parameter';

    public $timestamps = true;

    protected $fillable = [
        'id_rujukan_lab',
        'id_parameter_loinc',
        'nama_pemeriksaan',
        'kode_loinc',
        'catatan',
    ];

    protected $casts = [
        'id_rujukan_lab' => 'integer',
        'id_parameter_loinc' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rujukanLab(): BelongsTo
    {
        return $this->belongsTo(RujukanLab::class, 'id_rujukan_lab', 'id_rujukan_lab');
    }

    public function parameterLoinc(): BelongsTo
    {
        return $this->belongsTo(ParameterLoinc::class, 'id_parameter_loinc', 'id_parameter_loinc');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrafSoap extends Model
{
    protected $table = 'draf_soap';
    protected $primaryKey = 'id_draf_soap';
    public $timestamps = false;

    protected $fillable = [
        'id_kunjungan',
        'id_pengguna',
        'subjective_text',
        'objective_text',
        'assessment_text',
        'plan_text',
        'kode_icd10',
        'source_transcript_hash',
        'source_ttv_snapshot',
        'needs_review',
        'source_type',
    ];

    protected $casts = [
        'needs_review' => 'boolean',
    ];

    public function kunjungan()
    {
        return $this->belongsTo(Kunjungan::class, 'id_kunjungan', 'id_kunjungan');
    }

    public function dokter()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}

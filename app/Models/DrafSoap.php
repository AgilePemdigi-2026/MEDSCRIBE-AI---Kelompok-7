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
        'icd10_suggestions',
        'missing_information',
        'review_warnings',
        'source_transcript_hash',
        'source_ttv_snapshot',
        'needs_review',
        'source_type',
        'generation_status',
        'generation_error',
        'attempt_id',
    ];

    protected $casts = [
        'needs_review' => 'boolean',
        'icd10_suggestions' => 'array',
        'missing_information' => 'array',
        'review_warnings' => 'array',
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

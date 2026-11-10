<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RujukanLab extends Model
{
    protected $table = 'rujukan_lab';

    protected $primaryKey = 'id_rujukan_lab';

    public $timestamps = true;

    protected $fillable = [
        'id_tindak_lanjut',
        'catatan',
        'status',
    ];

    protected $casts = [
        'id_tindak_lanjut' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tindakLanjut(): BelongsTo
    {
        return $this->belongsTo(TindakLanjut::class, 'id_tindak_lanjut', 'id_tindak_lanjut');
    }

    public function parameter(): HasMany
    {
        return $this->hasMany(RujukanLabParameter::class, 'id_rujukan_lab', 'id_rujukan_lab');
    }
}

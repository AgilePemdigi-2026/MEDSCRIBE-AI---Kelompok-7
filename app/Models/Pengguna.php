<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengguna extends Model
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';
    public $timestamps = false;

    protected $fillable = [
        'nama_lengkap',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}

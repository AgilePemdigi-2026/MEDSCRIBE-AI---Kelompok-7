<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    /**
     * Nama tabel audit log admin di database.
     */
    protected $table = 'admin_audit_logs';

    /**
     * Tabel ini hanya menggunakan created_at (tanpa updated_at) untuk integritas audit trail yang tidak dapat diubah.
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi mass-assignment.
     */
    protected $fillable = [
        'admin_id',
        'hospital_id',
        'target_user_id',
        'action',
        'target_role',
        'target_name',
        'status_before',
        'status_after',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Casting atribut.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke admin yang melakukan tindakan.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'admin_id', 'id_pengguna');
    }

    /**
     * Relasi ke rumah sakit tempat tindakan berlangsung.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Relasi ke pengguna tenaga medis yang menjadi target tindakan.
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'target_user_id', 'id_pengguna');
    }

    /**
     * Label aksi dalam bahasa Indonesia yang ramah pengguna.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'approve_staff'    => 'Menyetujui Pendaftaran',
            'reject_staff'     => 'Menolak Pendaftaran',
            'deactivate_staff' => 'Menonaktifkan Tenaga Medis',
            'reactivate_staff' => 'Mengaktifkan Kembali Tenaga Medis',
            default            => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Badge status visual untuk UI log.
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action) {
            'approve_staff'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'reject_staff'     => 'bg-rose-50 text-rose-700 border-rose-200',
            'deactivate_staff' => 'bg-amber-50 text-amber-700 border-amber-200',
            'reactivate_staff' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default            => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}


<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Request;

class AdminAuditService
{
    /**
     * Mencatat aktivitas administratif admin rumah sakit ke dalam tabel audit log.
     */
    public function log(
        Pengguna $admin,
        string $action,
        ?Pengguna $targetUser = null,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
        ?string $reason = null
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'admin_id'       => $admin->id_pengguna,
            'hospital_id'    => $admin->hospital_id,
            'target_user_id' => $targetUser?->id_pengguna,
            'action'         => $action,
            'target_role'    => $targetUser?->role,
            'target_name'    => $targetUser?->nama_lengkap,
            'status_before'  => $statusBefore,
            'status_after'   => $statusAfter,
            'reason'         => $reason,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'created_at'     => now(),
        ]);
    }
}


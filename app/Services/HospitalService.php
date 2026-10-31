<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Collection;

class HospitalService
{
    /**
     * Mengambil seluruh rumah sakit aktif di Kota Surabaya.
     */
    public function getActiveHospitals(): Collection
    {
        return Hospital::active()
            ->orderBy('hospital_name', 'asc')
            ->get();
    }

    /**
     * Mengambil data rumah sakit berdasarkan ID.
     */
    public function getHospitalById(int $id): ?Hospital
    {
        return Hospital::find($id);
    }

    /**
     * Mengambil data rumah sakit berdasarkan kode SIRS.
     */
    public function getHospitalByCode(string $code): ?Hospital
    {
        return Hospital::where('hospital_code', $code)->first();
    }

    /**
     * Memetakan akun tenaga medis ke rumah sakit tertentu (untuk data legacy / admin workflow).
     */
    public function assignHospitalToUser(Pengguna $user, int $hospitalId): bool
    {
        $hospital = $this->getHospitalById($hospitalId);
        if (!$hospital || !$hospital->is_active) {
            return false;
        }

        $user->hospital_id = $hospital->id;
        return $user->save();
    }
}


<?php

namespace App\Http\Requests;

/**
 * Perbaikan identitas pasien oleh perawat: salah tulis, atau pasien tidak sadar
 * yang identitasnya baru diketahui. Aturan sama dengan registrasi, tapi rumah
 * sakit dan No. RM tidak bisa diubah.
 */
class UpdatePasienIdentitasRequest extends StorePasienPemeriksaanRequest
{
    /**
     * Error bag terpisah agar tidak membuka dialog "Input Pasien Baru".
     */
    protected $errorBag = 'editPasien';

    public function rules(): array
    {
        $rules = parent::rules();
        // Tanpa aturan, hospital_id tidak ikut di validated(), jadi tidak bisa diubah lewat form ini.
        unset($rules['hospital_id']);

        return $rules;
    }
}

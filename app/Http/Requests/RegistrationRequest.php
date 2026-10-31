<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email'        => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('pengguna', 'email')->where(function ($query) {
                    return $query->whereNotNull('email_verified_at');
                }),
            ],
            'role'         => ['required', 'string', 'in:dokter,perawat,Dokter,Perawat'],
            'hospital_id'  => [
                'required',
                'integer',
                Rule::exists('hospitals', 'id')->where('is_active', true),
            ],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia sesuai standar MedScribe AI.
     */
    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 100 karakter.',
            'email.required'        => 'Alamat email wajib diisi.',
            'email.email'           => 'Format alamat email tidak valid.',
            'email.max'             => 'Alamat email maksimal 100 karakter.',
            'email.unique'          => 'Email ini sudah terdaftar. Silakan login menggunakan akun Anda atau gunakan fitur Lupa Password jika Anda tidak dapat mengakses akun.',
            'role.required'         => 'Peran pengguna wajib dipilih.',
            'role.in'               => 'Peran yang diperbolehkan hanya Dokter atau Perawat.',
            'hospital_id.required'  => 'Rumah sakit tempat bertugas wajib dipilih.',
            'hospital_id.exists'    => 'Rumah sakit yang dipilih tidak aktif atau tidak terdaftar dalam master data.',
            'password.required'     => 'Kata sandi wajib diisi.',
            'password.min'          => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}


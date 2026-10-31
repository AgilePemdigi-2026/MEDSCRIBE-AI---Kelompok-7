<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:100'],
            'otp'   => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
            'otp.required'   => 'Kode verifikasi wajib diisi.',
            'otp.size'       => 'Kode verifikasi harus berupa 6 digit angka.',
            'otp.regex'      => 'Kode verifikasi hanya boleh berisi angka.',
        ];
    }
}


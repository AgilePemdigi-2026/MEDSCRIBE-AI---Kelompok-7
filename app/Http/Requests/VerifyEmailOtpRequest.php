<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailOtpRequest extends FormRequest
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
            'email.required' => 'Alamat email wajib disertakan.',
            'email.email'    => 'Format alamat email tidak valid.',
            'otp.required'   => 'Kode verifikasi 6 digit wajib diisi.',
            'otp.size'       => 'Kode verifikasi harus terdiri dari tepat 6 digit angka.',
            'otp.regex'      => 'Kode verifikasi hanya boleh berisi angka.',
        ];
    }
}


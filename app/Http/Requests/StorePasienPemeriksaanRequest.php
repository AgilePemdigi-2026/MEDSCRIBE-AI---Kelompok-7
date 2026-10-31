<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePasienPemeriksaanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && strtolower($this->user()->role) === 'perawat';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            // Data Pasien & Rumah Sakit
            'hospital_id'     => [
                'nullable',
                'integer',
                Rule::exists('hospitals', 'id')->where('is_active', true),
                function ($attribute, $value, $fail) use ($user) {
                    if ($user && $user->hospital_id && (int) $value !== (int) $user->hospital_id) {
                        $fail('Anda hanya memiliki wewenang untuk mendaftarkan pasien di rumah sakit tempat Anda bertugas.');
                    }
                },
            ],
            'nama_pasien'     => ['required', 'string', 'max:100', 'regex:/\S/'],
            'umur'            => ['required', 'integer', 'min:0', 'max:120'],
            'jenis_kelamin'   => ['required', 'string', 'in:Laki-laki,Perempuan'],

            // Tanda-Tanda Vital (TTV)
            'tekanan_darah_sistolik'  => ['required', 'integer', 'min:50', 'max:300'],
            'tekanan_darah_diastolik' => ['required', 'integer', 'min:20', 'max:200'],
            'nadi'                    => ['required', 'integer', 'min:30', 'max:250'],
            'suhu_tubuh'              => ['required', 'numeric', 'min:30', 'max:45'],
            'laju_pernapasan'         => ['required', 'integer', 'min:5', 'max:60'],
            'saturasi_oksigen'        => ['required', 'integer', 'min:50', 'max:100'],
            'berat_badan'             => ['nullable', 'numeric', 'min:1', 'max:300'],
            'tinggi_badan'            => ['nullable', 'numeric', 'min:30', 'max:250'],
            'catatan'                 => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Data Pasien & RS
            'hospital_id.exists'       => 'Rumah sakit yang dipilih tidak valid atau tidak aktif.',
            'nama_pasien.required'     => 'Nama lengkap pasien wajib diisi.',
            'nama_pasien.max'          => 'Nama lengkap pasien maksimal 100 karakter.',
            'nama_pasien.regex'        => 'Nama lengkap pasien tidak boleh hanya berisi spasi.',
            'umur.required'            => 'Umur pasien wajib diisi.',
            'umur.integer'             => 'Umur pasien harus berupa bilangan bulat.',
            'umur.min'                 => 'Umur pasien tidak boleh negatif.',
            'umur.max'                 => 'Umur pasien maksimal 120 tahun.',
            'jenis_kelamin.required'   => 'Jenis kelamin pasien wajib dipilih.',
            'jenis_kelamin.in'         => 'Jenis kelamin harus Laki-laki atau Perempuan.',

            // TTV
            'tekanan_darah_sistolik.required'  => 'Tekanan darah sistolik wajib diisi.',
            'tekanan_darah_sistolik.integer'   => 'Tekanan darah sistolik harus berupa bilangan bulat.',
            'tekanan_darah_sistolik.min'       => 'Tekanan darah sistolik minimal 50 mmHg.',
            'tekanan_darah_sistolik.max'       => 'Tekanan darah sistolik maksimal 300 mmHg.',
            'tekanan_darah_diastolik.required' => 'Tekanan darah diastolik wajib diisi.',
            'tekanan_darah_diastolik.integer'  => 'Tekanan darah diastolik harus berupa bilangan bulat.',
            'tekanan_darah_diastolik.min'      => 'Tekanan darah diastolik minimal 20 mmHg.',
            'tekanan_darah_diastolik.max'      => 'Tekanan darah diastolik maksimal 200 mmHg.',
            'nadi.required'            => 'Denyut nadi wajib diisi.',
            'nadi.integer'             => 'Denyut nadi harus berupa bilangan bulat.',
            'nadi.min'                 => 'Denyut nadi minimal 30 x/menit.',
            'nadi.max'                 => 'Denyut nadi maksimal 250 x/menit.',
            'suhu_tubuh.required'      => 'Suhu tubuh wajib diisi.',
            'suhu_tubuh.numeric'       => 'Suhu tubuh harus berupa angka desimal (contoh: 36.5).',
            'suhu_tubuh.min'           => 'Suhu tubuh minimal 30 °C.',
            'suhu_tubuh.max'           => 'Suhu tubuh maksimal 45 °C.',
            'laju_pernapasan.required' => 'Laju pernapasan wajib diisi.',
            'laju_pernapasan.integer'  => 'Laju pernapasan harus berupa bilangan bulat.',
            'laju_pernapasan.min'      => 'Laju pernapasan minimal 5 x/menit.',
            'laju_pernapasan.max'      => 'Laju pernapasan maksimal 60 x/menit.',
            'saturasi_oksigen.required' => 'Saturasi oksigen (SpO2) wajib diisi.',
            'saturasi_oksigen.integer'  => 'Saturasi oksigen harus berupa bilangan bulat.',
            'saturasi_oksigen.min'      => 'Saturasi oksigen minimal 50%.',
            'saturasi_oksigen.max'      => 'Saturasi oksigen maksimal 100%.',
            'berat_badan.numeric'      => 'Berat badan harus berupa angka numerik.',
            'berat_badan.min'          => 'Berat badan minimal 1 kg.',
            'berat_badan.max'          => 'Berat badan maksimal 300 kg.',
            'tinggi_badan.numeric'     => 'Tinggi badan harus berupa angka numerik.',
            'tinggi_badan.min'         => 'Tinggi badan minimal 30 cm.',
            'tinggi_badan.max'         => 'Tinggi badan maksimal 250 cm.',
            'catatan.max'              => 'Catatan awal perawat maksimal 1000 karakter.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user && $user->hospital_id && empty($this->hospital_id)) {
            $this->merge([
                'hospital_id' => $user->hospital_id,
            ]);
        }
    }
}

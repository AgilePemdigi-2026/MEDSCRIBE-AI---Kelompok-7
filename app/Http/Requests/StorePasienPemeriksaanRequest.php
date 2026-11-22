<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registrasi pasien baru oleh perawat. Hanya data diri; TTV diisi terpisah
 * lewat form pemeriksaan awal. Yang wajib cuma nama, supaya pasien darurat
 * tetap bisa didaftarkan walau identitasnya belum lengkap.
 */
class StorePasienPemeriksaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && strtolower($this->user()->role) === 'perawat';
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'hospital_id'   => [
                'nullable',
                'integer',
                Rule::exists('hospitals', 'id')->where('is_active', true),
                function ($attribute, $value, $fail) use ($user) {
                    if ($user && $user->hospital_id && (int) $value !== (int) $user->hospital_id) {
                        $fail('Anda hanya memiliki wewenang untuk mendaftarkan pasien di rumah sakit tempat Anda bertugas.');
                    }
                },
            ],
            'nama_pasien'   => ['required', 'string', 'max:100', 'regex:/\S/'],
            // "Hari ini" menurut WIB; aturan bawaan 'today' memakai UTC sehingga bayi yang lahir
            // pukul 00.00-07.00 WIB dianggap lahir besok.
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:' . now('Asia/Jakarta')->toDateString(), 'after:1900-01-01'],
            'umur'          => ['nullable', 'integer', 'min:0', 'max:130'],
            'jenis_kelamin' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            'nik'           => ['nullable', 'digits:16'],
            'no_bpjs'       => ['nullable', 'digits:13'],
            'no_hp'         => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hospital_id.exists'            => 'Rumah sakit yang dipilih tidak valid atau tidak aktif.',
            'nama_pasien.required'          => 'Nama lengkap pasien wajib diisi.',
            'nama_pasien.max'               => 'Nama lengkap pasien maksimal 100 karakter.',
            'nama_pasien.regex'             => 'Nama lengkap pasien tidak boleh hanya berisi spasi.',
            'tanggal_lahir.date'            => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh setelah hari ini.',
            'tanggal_lahir.after'           => 'Tanggal lahir tidak valid.',
            'umur.integer'                  => 'Umur pasien harus berupa bilangan bulat.',
            'umur.min'                      => 'Umur pasien tidak boleh negatif.',
            'umur.max'                      => 'Umur pasien maksimal 130 tahun.',
            'jenis_kelamin.in'              => 'Jenis kelamin harus Laki-laki atau Perempuan.',
            'nik.digits'                    => 'NIK harus 16 digit angka.',
            'no_bpjs.digits'                => 'Nomor BPJS harus 13 digit angka.',
            'no_hp.regex'                   => 'Nomor HP hanya berisi angka (8–15 digit), boleh diawali +.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user && $user->hospital_id && empty($this->hospital_id)) {
            $this->merge(['hospital_id' => $user->hospital_id]);
        }

        // Spasi dan tanda hubung sering ikut tertulis dari kartu identitas atau kontak.
        foreach (['nik', 'no_bpjs', 'no_hp'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => preg_replace('/[\s\-.]/', '', $this->input($field)) ?: null]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class DummyAccountSeeder extends Seeder
{
    /**
     * Create explicitly enabled test accounts without touching real users.
     */
    public function run(): void
    {
        $config = config('medscribe.dummy_accounts');

        if (! $config['enabled']) {
            return;
        }

        if (blank($config['password'])) {
            throw new LogicException(
                'DUMMY_ACCOUNT_PASSWORD wajib diisi saat SEED_DUMMY_ACCOUNTS=true.'
            );
        }

        $hospitalId = Hospital::where('hospital_code', '3578770')->value('id')
            ?? Hospital::query()->where('is_active', true)->value('id');

        if (! $hospitalId) {
            throw new LogicException('Tidak ada rumah sakit aktif untuk akun dummy.');
        }

        $accounts = [
            [
                'email' => $config['dokter_email'],
                'nama_lengkap' => 'Dokter Dummy Uji Coba',
                'role' => 'dokter',
            ],
            [
                'email' => $config['perawat_email'],
                'nama_lengkap' => 'Perawat Dummy Uji Coba',
                'role' => 'perawat',
            ],
        ];

        foreach ($accounts as $account) {
            Pengguna::updateOrCreate(
                ['email' => strtolower(trim($account['email']))],
                [
                    'nama_lengkap' => $account['nama_lengkap'],
                    'role' => $account['role'],
                    'hospital_id' => $hospitalId,
                    'email_verified_at' => now(),
                    'approval_status' => 'approved',
                    'password' => Hash::make($config['password']),
                ],
            );
        }
    }
}

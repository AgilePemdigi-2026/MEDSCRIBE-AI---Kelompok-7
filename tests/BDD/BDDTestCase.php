<?php

namespace Tests\BDD;

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class BDDTestCase extends TestCase
{
    use DatabaseTransactions;

    protected array $bddStepLog = [];

    /**
     * BDD Step: Given (Dengan/Diberikan)
     */
    protected function given(string $description): static
    {
        $this->bddStepLog[] = "GIVEN: {$description}";
        return $this;
    }

    /**
     * BDD Step: When (Ketika)
     */
    protected function when(string $description): static
    {
        $this->bddStepLog[] = "WHEN: {$description}";
        return $this;
    }

    /**
     * BDD Step: Then (Maka)
     */
    protected function then(string $description): static
    {
        $this->bddStepLog[] = "THEN: {$description}";
        return $this;
    }

    /**
     * BDD Step: And (Dan)
     */
    protected function and(string $description): static
    {
        $this->bddStepLog[] = "AND: {$description}";
        return $this;
    }

    /**
     * Ambil hospital default untuk testing (RSUA)
     */
    protected function getDefaultHospital(): Hospital
    {
        return Hospital::where('hospital_code', '3578770')->first()
            ?? Hospital::firstOrCreate(
                ['hospital_code' => '3578770'],
                [
                    'name'      => 'RS Universitas Airlangga',
                    'type'      => 'RSU',
                    'address'   => 'Jl. Dharmahusada Permai No. 1, Mulyorejo',
                    'city'      => 'Kota Surabaya',
                    'province'  => 'Jawa Timur',
                    'is_active' => true,
                ]
            );
    }

    /**
     * Ambil hospital sekunder untuk multi-tenancy testing (RSUD Dr Soetomo)
     */
    protected function getSecondaryHospital(): Hospital
    {
        return Hospital::where('hospital_code', '3578016')->first()
            ?? Hospital::firstOrCreate(
                ['hospital_code' => '3578016'],
                [
                    'name'      => 'RSUD Dr. Soetomo',
                    'type'      => 'RSUD',
                    'address'   => 'Jl. Mayjen Prof. Dr. Moestopo No. 6-8, Gubeng',
                    'city'      => 'Kota Surabaya',
                    'province'  => 'Jawa Timur',
                    'is_active' => true,
                ]
            );
    }

    /**
     * Helper membuat pengguna dokter aktif
     */
    protected function createDokter(array $overrides = []): Pengguna
    {
        $hospital = $overrides['hospital'] ?? $this->getDefaultHospital();
        unset($overrides['hospital']);

        return Pengguna::create(array_merge([
            'nama_lengkap'      => 'dr. ' . fake()->name() . ', Sp.PD',
            'email'             => 'dokter.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $hospital->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ], $overrides));
    }

    /**
     * Helper membuat pengguna perawat aktif
     */
    protected function createPerawat(array $overrides = []): Pengguna
    {
        $hospital = $overrides['hospital'] ?? $this->getDefaultHospital();
        unset($overrides['hospital']);

        return Pengguna::create(array_merge([
            'nama_lengkap'      => fake()->name(),
            'email'             => 'perawat.' . uniqid() . '@medscribe.id',
            'role'              => 'perawat',
            'hospital_id'       => $hospital->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ], $overrides));
    }
}


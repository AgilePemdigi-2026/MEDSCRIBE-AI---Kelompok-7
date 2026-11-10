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

    /** @var array<int, array{0: string, 1: string}> Langkah yang sudah dijalankan: [kata kunci, deskripsi] */
    protected array $bddStepLog = [];

    protected int $bddStartedAt = 0;

    protected function setUp(): void
    {
        $this->bddStartedAt = hrtime(true);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->recordBddScenario();

        parent::tearDown();
    }

    /**
     * BDD Step: Given (Dengan/Diberikan)
     */
    protected function given(string $description): static
    {
        $this->bddStepLog[] = ['Given', $description];
        return $this;
    }

    /**
     * BDD Step: When (Ketika)
     */
    protected function when(string $description): static
    {
        $this->bddStepLog[] = ['When', $description];
        return $this;
    }

    /**
     * BDD Step: Then (Maka)
     */
    protected function then(string $description): static
    {
        $this->bddStepLog[] = ['Then', $description];
        return $this;
    }

    /**
     * BDD Step: And (Dan)
     */
    protected function and(string $description): static
    {
        $this->bddStepLog[] = ['And', $description];
        return $this;
    }

    /**
     * Tulis hasil skenario (langkah, status, durasi) ke file laporan yang dibaca `php artisan test:bdd`.
     * Hanya aktif jika runner menyetel BDD_REPORT_FILE, sehingga `php artisan test` biasa tidak terpengaruh.
     */
    private function recordBddScenario(): void
    {
        $reportFile = getenv('BDD_REPORT_FILE');
        if (! $reportFile) {
            return;
        }

        $class = new \ReflectionClass($this);
        $method = $class->getMethod($this->name());
        preg_match('/features\/([\w\-]+\.feature)/', (string) $class->getDocComment(), $feature);
        preg_match('/@scenario\s+(.+)/', (string) $method->getDocComment(), $scenario);

        $status = $this->status();

        file_put_contents($reportFile, json_encode([
            'feature'  => $feature[1] ?? null,
            'class'    => $class->getShortName(),
            'scenario' => isset($scenario[1]) ? trim($scenario[1]) : $this->name(),
            'status'   => match (true) {
                $status->isSuccess() => 'passed',
                $status->isSkipped(), $status->isIncomplete() => 'skipped',
                default => 'failed',
            },
            'message'  => $status->isSuccess() ? null : $status->message(),
            'steps'    => $this->bddStepLog,
            'duration' => (hrtime(true) - $this->bddStartedAt) / 1e9,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
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


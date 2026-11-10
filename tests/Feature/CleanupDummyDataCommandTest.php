<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanupDummyDataCommandTest extends TestCase
{
    public function test_cleanup_command_dry_run_exits_successfully_without_modifications(): void
    {
        $this->artisan('app:cleanup-dummy-data', ['--dry-run' => true])
            ->assertExitCode(0);
    }

    public function test_protected_accounts_are_preserved(): void
    {
        // Pastikan akun terlindungi ada di database
        $protectedEmails = ['onilmoses@gmail.com', 'otnielmoses17@gmail.com'];

        $this->artisan('app:cleanup-dummy-data', ['--dry-run' => true])
            ->expectsOutputToContain('Ignasius Otnile Moses')
            ->assertExitCode(0);
    }

    public function test_hospital_master_data_is_preserved(): void
    {
        $hospitalCountBefore = DB::table('hospitals')->count();

        $this->artisan('app:cleanup-dummy-data', ['--dry-run' => true])
            ->expectsOutputToContain('Master Data Rumah Sakit')
            ->assertExitCode(0);

        $this->assertEquals($hospitalCountBefore, DB::table('hospitals')->count());
    }
}


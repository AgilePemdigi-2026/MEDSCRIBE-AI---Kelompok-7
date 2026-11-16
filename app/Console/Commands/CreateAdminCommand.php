<?php

namespace App\Console\Commands;

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--email= : Alamat email admin rumah sakit}
                            {--name= : Nama lengkap admin}
                            {--hospital= : ID atau Kode Rumah Sakit}
                            {--password= : Kata sandi akun admin (jika tidak diisi, sistem akan meminta input tersembunyi)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat akun Admin Rumah Sakit MedScribe AI secara aman langsung ke database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('========================================================================');
        $this->info(' MEDSCRIBE AI — PEMBUATAN AKUN ADMIN RUMAH SAKIT ');
        $this->info('========================================================================');

        // 1. Dapatkan atau minta Nama Lengkap
        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap Admin');
        if (empty(trim($name))) {
            $this->error('Nama lengkap admin tidak boleh kosong.');
            return self::FAILURE;
        }

        // 2. Dapatkan atau minta Email
        $email = Str::lower(trim($this->option('email') ?: $this->ask('Masukkan Alamat Email Admin')));
        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:100'],
        ]);
        if ($emailValidator->fails()) {
            $this->error('Format email tidak valid.');
            return self::FAILURE;
        }

        // Cek apakah email sudah terdaftar
        if (Pengguna::where('email', $email)->exists()) {
            $this->error("Email '{$email}' sudah terdaftar dalam sistem. Gunakan alamat email lain.");
            return self::FAILURE;
        }

        // 3. Dapatkan atau pilih Rumah Sakit
        $hospitalOption = $this->option('hospital');
        $hospital = null;

        if ($hospitalOption) {
            $hospital = is_numeric($hospitalOption)
                ? Hospital::find((int) $hospitalOption)
                : Hospital::where('hospital_code', $hospitalOption)->first();
        }

        if (!$hospital) {
            $hospitals = Hospital::active()->orderBy('hospital_name')->get();
            if ($hospitals->isEmpty()) {
                $this->error('Tidak ada data rumah sakit aktif di database. Jalankan seeder master data terlebih dahulu.');
                return self::FAILURE;
            }

            $choices = [];
            foreach ($hospitals as $h) {
                $choices[$h->id] = "ID: {$h->id} | {$h->hospital_name} ({$h->city})";
            }

            $selectedText = $this->choice('Pilih Rumah Sakit tempat Admin ditugaskan:', $choices);
            // Parse ID dari pilihan
            preg_match('/ID:\s*(\d+)/', $selectedText, $matches);
            $hospitalId = isset($matches[1]) ? (int) $matches[1] : array_key_first($choices);
            $hospital = Hospital::find($hospitalId);
        }

        if (!$hospital) {
            $this->error('Rumah sakit yang dipilih tidak ditemukan.');
            return self::FAILURE;
        }

        // 4. Dapatkan atau minta Password secara aman (masked)
        $password = $this->option('password');
        if (empty($password)) {
            $password = $this->secret('Masukkan Kata Sandi Admin (minimal 8 karakter)');
            $confirmPassword = $this->secret('Konfirmasi Kata Sandi');

            if ($password !== $confirmPassword) {
                $this->error('Konfirmasi kata sandi tidak cocok.');
                return self::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');
            return self::FAILURE;
        }

        // 5. Simpan Akun Admin dengan Bcrypt Hashing
        try {
            $admin = Pengguna::create([
                'nama_lengkap'      => trim($name),
                'email'             => $email,
                'role'              => 'admin',
                'hospital_id'       => $hospital->id,
                'approval_status'   => 'active',
                'email_verified_at' => now(),
                'password'          => Hash::make($password),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $this->newLine();
            $this->info('✓ Akun Admin Rumah Sakit berhasil dibuat!');
            $this->table(
                ['ID', 'Nama Lengkap', 'Email', 'Role', 'Status', 'Rumah Sakit'],
                [
                    [
                        $admin->id_pengguna,
                        $admin->nama_lengkap,
                        $admin->email,
                        $admin->role,
                        $admin->status_label,
                        "{$hospital->hospital_name} (ID: {$hospital->id})",
                    ],
                ]
            );
            $this->comment("Admin dapat langsung masuk melalui halaman login utama: " . url('/login'));
            $this->newLine();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Terjadi kesalahan saat menyimpan akun admin: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}


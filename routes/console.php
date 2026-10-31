<?php

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Perintah Administratif untuk Memetakan Akun Tenaga Medis Legacy ke Rumah Sakit.
 */
Artisan::command('medscribe:assign-hospital {userId} {hospitalCodeOrId}', function ($userId, $hospitalCodeOrId) {
    $user = Pengguna::find($userId);
    if (!$user) {
        $this->error("Pengguna dengan ID {$userId} tidak ditemukan.");
        return 1;
    }

    $hospital = is_numeric($hospitalCodeOrId) && (int) $hospitalCodeOrId < 1000
        ? Hospital::find($hospitalCodeOrId)
        : Hospital::where('hospital_code', $hospitalCodeOrId)->first();

    if (!$hospital) {
        $this->error("Rumah sakit dengan ID/Kode {$hospitalCodeOrId} tidak ditemukan.");
        return 1;
    }

    $user->hospital_id = $hospital->id;
    $user->save();

    $this->info("Berhasil memetakan {$user->nama_lengkap} (ID: {$user->id_pengguna}) ke {$hospital->hospital_name} ({$hospital->hospital_code}).");
    return 0;
})->purpose('Memetakan akun tenaga medis legacy ke rumah sakit terdaftar');

/**
 * Menampilkan daftar akun tenaga medis legacy yang belum memiliki asosiasi rumah sakit.
 */
Artisan::command('medscribe:unassigned-users', function () {
    $unassigned = Pengguna::whereNull('hospital_id')->get(['id_pengguna', 'nama_lengkap', 'email', 'role']);

    if ($unassigned->isEmpty()) {
        $this->info('Semua akun tenaga medis telah terasosiasi dengan rumah sakit.');
        return 0;
    }

    $this->warn("Ditemukan {$unassigned->count()} akun yang memerlukan pemetaan rumah sakit:");
    $headers = ['ID', 'Nama Lengkap', 'Email', 'Role'];
    $rows = $unassigned->map(fn ($u) => [$u->id_pengguna, $u->nama_lengkap, $u->email, $u->role])->toArray();
    $this->table($headers, $rows);
    $this->line("Gunakan: php artisan medscribe:assign-hospital {userId} {hospitalCodeOrId}");
    return 0;
})->purpose('Daftar pengguna legacy tanpa asosiasi rumah sakit');

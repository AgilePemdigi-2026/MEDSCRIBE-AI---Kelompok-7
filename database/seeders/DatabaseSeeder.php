<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Mengisi data awal akun medis MedScribe AI (Dokter & Perawat)
     * sesuai spesifikasi SRS Bab 3.1 & Lampiran Mockup.
     */
    public function run(): void
    {
        // 1. Akun Perawat (Nurse Station)
        Pengguna::updateOrCreate(
            ['id_pengguna' => 1],
            [
                'nama_lengkap' => 'Dewi Pratiwi',
                'email'        => 'dewi@medscribe.id',
                'role'         => 'perawat',
                'password'     => Hash::make('password123'),
            ]
        );

        // 2. Akun Dokter Poliklinik
        Pengguna::updateOrCreate(
            ['id_pengguna' => 2],
            [
                'nama_lengkap' => 'dr. Rina Anggraini, Sp.PD',
                'email'        => 'rina@medscribe.id',
                'role'         => 'dokter',
                'password'     => Hash::make('password123'),
            ]
        );

        // 3. Data pasien awal jika tabel masih kosong
        if (DB::table('pasien')->count() === 0) {
            DB::table('pasien')->insert([
                ['id_pasien' => 1, 'no_rekam_medis' => '00-12-345', 'nama_pasien' => 'Budi Santoso', 'tanggal_lahir' => '1992-05-14', 'jenis_kelamin' => 'Laki-laki'],
                ['id_pasien' => 2, 'no_rekam_medis' => '00-12-346', 'nama_pasien' => 'Siti Aminah', 'tanggal_lahir' => '1980-08-21', 'jenis_kelamin' => 'Perempuan'],
                ['id_pasien' => 3, 'no_rekam_medis' => '00-12-347', 'nama_pasien' => 'Ahmad Fauzi', 'tanggal_lahir' => '1997-03-10', 'jenis_kelamin' => 'Laki-laki'],
                ['id_pasien' => 4, 'no_rekam_medis' => '00-12-348', 'nama_pasien' => 'Sri Wahyuni', 'tanggal_lahir' => '1974-11-05', 'jenis_kelamin' => 'Perempuan'],
                ['id_pasien' => 5, 'no_rekam_medis' => '00-12-349', 'nama_pasien' => 'Dedi Kurniawan', 'tanggal_lahir' => '1985-02-18', 'jenis_kelamin' => 'Laki-laki'],
                ['id_pasien' => 6, 'no_rekam_medis' => '00-12-350', 'nama_pasien' => 'Ratna Sari', 'tanggal_lahir' => '1989-09-30', 'jenis_kelamin' => 'Perempuan'],
                ['id_pasien' => 7, 'no_rekam_medis' => '00-12-351', 'nama_pasien' => 'Rizky Pratama', 'tanggal_lahir' => '2001-07-12', 'jenis_kelamin' => 'Laki-laki'],
                ['id_pasien' => 8, 'no_rekam_medis' => '00-12-352', 'nama_pasien' => 'Nur Aisyah', 'tanggal_lahir' => '1963-01-25', 'jenis_kelamin' => 'Perempuan'],
            ]);
        }

        // 4. Data antrean awal jika tabel masih kosong
        if (DB::table('antrean')->count() === 0) {
            DB::table('antrean')->insert([
                ['id_antrean' => 1, 'id_pasien' => 1, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Siap Periksa'],
                ['id_antrean' => 2, 'id_pasien' => 2, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Siap Periksa'],
                ['id_antrean' => 3, 'id_pasien' => 3, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Siap Periksa'],
                ['id_antrean' => 4, 'id_pasien' => 4, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Menunggu TTV'],
                ['id_antrean' => 5, 'id_pasien' => 5, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Menunggu TTV'],
                ['id_antrean' => 6, 'id_pasien' => 6, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Menunggu TTV'],
                ['id_antrean' => 7, 'id_pasien' => 7, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Selesai'],
                ['id_antrean' => 8, 'id_pasien' => 8, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Selesai'],
            ]);
        }

        // 5. Data kunjungan awal jika tabel masih kosong
        if (DB::table('kunjungan')->count() === 0) {
            DB::table('kunjungan')->insert([
                ['id_kunjungan' => 1, 'id_antrean' => 1, 'id_pasien' => 1, 'id_dokter' => 2, 'tanggal' => '2026-09-19', 'status' => 'SOAP Terverifikasi'],
            ]);
        }

        // 6. Data pemeriksaan awal jika tabel masih kosong
        if (DB::table('pemeriksaan_awal')->count() === 0) {
            DB::table('pemeriksaan_awal')->insert([
                ['id_pemeriksaan' => 1, 'id_kunjungan' => 1, 'id_perawat' => 1, 'tekanan_darah' => '120/80', 'suhu_tubuh' => 38.6, 'nadi' => 78, 'catatan' => 'Demam 3 hari, nyeri menelan, dan batuk kering. Suhu tubuh 38,6°C'],
            ]);
        }

        // 7. Data draf SOAP jika tabel masih kosong
        if (DB::table('draf_soap')->count() === 0) {
            DB::table('draf_soap')->insert([
                [
                    'id_draf_soap' => 1,
                    'id_kunjungan' => 1,
                    'id_pengguna' => 2,
                    'subjective_text' => 'Pasien mengeluhkan demam sejak 3 hari, disertai nyeri menelan dan batuk kering. Riwayat lain perlu dikonfirmasi saat review dokter.',
                    'objective_text' => 'TD 120/80 mmHg, Nadi 78 x/menit, Suhu 38,6°C, RR 18 x/menit, BB 68 kg, TB 170 cm',
                    'assessment_text' => 'J02.9 - Faringitis akut, tidak spesifik. Penilaian klinis dan kesesuaian kode wajib dikonfirmasi dokter.',
                    'plan_text' => 'Pertimbangkan terapi simptomatik sesuai penilaian dokter. Review kebutuhan Paracetamol dan pemeriksaan darah lengkap sebelum tindak lanjut.',
                    'kode_icd10' => 'J02.9',
                ]
            ]);
        }
    }
}

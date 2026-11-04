<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Mengisi data awal rumah sakit Kota Surabaya dan akun medis MedScribe AI (Dokter & Perawat).
     */
    public function run(): void
    {
        // 1. Jalankan seeder master data rumah sakit Kota Surabaya dan katalog LOINC
        $this->call(HospitalSeeder::class);
        $this->call(LoincParameterSeeder::class);

        // Ambil rumah sakit default pilot (RS Universitas Airlangga)
        $rsua = Hospital::where('hospital_code', '3578770')->first();
        $rsuaId = $rsua ? $rsua->id : 1;

        // Backfill legacy records without hospital_id to primary pilot hospital (RSUA)
        DB::table('pasien')->whereNull('hospital_id')->update(['hospital_id' => $rsuaId]);
        DB::table('antrean')->whereNull('hospital_id')->update(['hospital_id' => $rsuaId]);
        DB::table('kunjungan')->whereNull('hospital_id')->update(['hospital_id' => $rsuaId]);

        // 2. Akun Perawat (Nurse Station)
        Pengguna::updateOrCreate(
            ['id_pengguna' => 1],
            [
                'nama_lengkap'      => 'Dewi Pratiwi',
                'email'             => 'dewi@medscribe.id',
                'role'              => 'perawat',
                'hospital_id'       => $rsuaId,
                'email_verified_at' => now(),
                'approval_status'   => 'approved',
                'password'          => Hash::make('password123'),
            ]
        );

        // 3. Akun Dokter Poliklinik
        Pengguna::updateOrCreate(
            ['id_pengguna' => 2],
            [
                'nama_lengkap'      => 'dr. Rina Anggraini, Sp.PD',
                'email'             => 'rina@medscribe.id',
                'role'              => 'dokter',
                'hospital_id'       => $rsuaId,
                'email_verified_at' => now(),
                'approval_status'   => 'approved',
                'password'          => Hash::make('password123'),
            ]
        );

        // 4. Data pasien awal jika tabel masih kosong atau belum ada Budi Santoso
        if (!DB::table('pasien')->where('nama_pasien', 'Budi Santoso')->exists()) {
            DB::table('pasien')->insert([
                ['id_pasien' => 1, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-345', 'nama_pasien' => 'Budi Santoso', 'tanggal_lahir' => '1992-05-14', 'jenis_kelamin' => 'Laki-laki', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 2, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-346', 'nama_pasien' => 'Siti Aminah', 'tanggal_lahir' => '1980-08-21', 'jenis_kelamin' => 'Perempuan', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 3, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-347', 'nama_pasien' => 'Ahmad Fauzi', 'tanggal_lahir' => '1997-03-10', 'jenis_kelamin' => 'Laki-laki', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 4, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-348', 'nama_pasien' => 'Sri Wahyuni', 'tanggal_lahir' => '1974-11-05', 'jenis_kelamin' => 'Perempuan', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 5, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-349', 'nama_pasien' => 'Dedi Kurniawan', 'tanggal_lahir' => '1985-02-18', 'jenis_kelamin' => 'Laki-laki', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 6, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-350', 'nama_pasien' => 'Ratna Sari', 'tanggal_lahir' => '1989-09-30', 'jenis_kelamin' => 'Perempuan', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 7, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-351', 'nama_pasien' => 'Rizky Pratama', 'tanggal_lahir' => '2001-07-12', 'jenis_kelamin' => 'Laki-laki', 'created_at' => now(), 'updated_at' => now()],
                ['id_pasien' => 8, 'hospital_id' => $rsuaId, 'no_rekam_medis' => '00-12-352', 'nama_pasien' => 'Nur Aisyah', 'tanggal_lahir' => '1963-01-25', 'jenis_kelamin' => 'Perempuan', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 5. Data antrean awal jika belum ada antrean awal
        if (!DB::table('antrean')->where('id_antrean', 1)->exists()) {
            DB::table('antrean')->insert([
                ['id_antrean' => 1, 'hospital_id' => $rsuaId, 'id_pasien' => 1, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Siap Periksa'],
                ['id_antrean' => 2, 'hospital_id' => $rsuaId, 'id_pasien' => 2, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Siap Periksa'],
                ['id_antrean' => 3, 'hospital_id' => $rsuaId, 'id_pasien' => 3, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Siap Periksa'],
                ['id_antrean' => 4, 'hospital_id' => $rsuaId, 'id_pasien' => 4, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Menunggu TTV'],
                ['id_antrean' => 5, 'hospital_id' => $rsuaId, 'id_pasien' => 5, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Menunggu TTV'],
                ['id_antrean' => 6, 'hospital_id' => $rsuaId, 'id_pasien' => 6, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Menunggu TTV'],
                ['id_antrean' => 7, 'hospital_id' => $rsuaId, 'id_pasien' => 7, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Selesai'],
                ['id_antrean' => 8, 'hospital_id' => $rsuaId, 'id_pasien' => 8, 'tanggal_kunjungan' => '2026-09-19', 'status' => 'Selesai'],
            ]);
        }

        // 6. Data kunjungan awal jika belum ada
        if (!DB::table('kunjungan')->where('id_kunjungan', 1)->exists()) {
            DB::table('kunjungan')->insert([
                ['id_kunjungan' => 1, 'hospital_id' => $rsuaId, 'id_antrean' => 1, 'id_pasien' => 1, 'id_dokter' => 2, 'tanggal' => '2026-09-19', 'status' => 'SOAP Terverifikasi'],
            ]);
        }

        // 7. Data pemeriksaan awal jika belum ada
        if (!DB::table('pemeriksaan_awal')->where('id_pemeriksaan', 1)->exists()) {
            DB::table('pemeriksaan_awal')->insert([
                ['id_pemeriksaan' => 1, 'id_kunjungan' => 1, 'id_perawat' => 1, 'tekanan_darah' => '120/80', 'suhu_tubuh' => 38.6, 'nadi' => 78, 'catatan' => 'Demam 3 hari, nyeri menelan, dan batuk kering. Suhu tubuh 38,6°C', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 8. Data draf SOAP jika belum ada
        if (!DB::table('draf_soap')->where('id_draf_soap', 1)->exists()) {
            DB::table('draf_soap')->insert([
                [
                    'id_draf_soap'    => 1,
                    'id_kunjungan'    => 1,
                    'id_pengguna'     => 2,
                    'subjective_text' => 'Pasien mengeluhkan demam sejak 3 hari, disertai nyeri menelan dan batuk kering. Riwayat lain perlu dikonfirmasi saat review dokter.',
                    'objective_text'  => 'TD 120/80 mmHg, Nadi 78 x/menit, Suhu 38,6°C, RR 18 x/menit, BB 68 kg, TB 170 cm',
                    'assessment_text' => 'J02.9 - Faringitis akut, tidak spesifik. Penilaian klinis dan kesesuaian kode wajib dikonfirmasi dokter.',
                    'plan_text'       => 'Pertimbangkan terapi simptomatik sesuai penilaian dokter. Review kebutuhan Paracetamol dan pemeriksaan darah lengkap sebelum tindak lanjut.',
                    'kode_icd10'      => 'J02.9',
                ]
            ]);
        }
    }
}

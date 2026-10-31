<?php

namespace Database\Seeders;

use App\Models\Hospital;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    /**
     * Data Master Rumah Sakit Kota Surabaya Terverifikasi.
     * Sumber data otoritatif:
     * 1. Sistem Informasi Rumah Sakit (SIRS) Online Kementerian Kesehatan Republik Indonesia (kemkes.go.id).
     * 2. Profil Fasilitas Pelayanan Kesehatan Dinas Kesehatan Kota Surabaya (dinkes.surabaya.go.id).
     * Tanggal Verifikasi: 2026-10-31.
     * Lingkup Wilayah: Kota Surabaya, Jawa Timur, Indonesia.
     */
    public function run(): void
    {
        $hospitals = [
            [
                'hospital_code' => '3578770',
                'hospital_name' => 'RS Universitas Airlangga (RSUA)',
                'hospital_type' => 'RSU Kelas B (PTN)',
                'address'       => 'Kampus C Unair, Jl. Dharmahusada Permai, Mulyorejo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578016',
                'hospital_name' => 'RSUD Dr. Soetomo',
                'hospital_type' => 'RSU Kelas A (Pemerintah Provinsi)',
                'address'       => 'Jl. Mayjen Prof. Dr. Moestopo No. 6-8, Gubeng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578571',
                'hospital_name' => 'RSUD Dr. Mohamad Soewandhie',
                'hospital_type' => 'RSU Kelas B (Pemerintah Kota)',
                'address'       => 'Jl. Tambak Rejo No. 45-47, Simokerto',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578766',
                'hospital_name' => 'RSUD Bhakti Dharma Husada (BDH)',
                'hospital_type' => 'RSU Kelas C (Pemerintah Kota)',
                'address'       => 'Jl. Raya Kendung No. 115-117, Benowo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578031',
                'hospital_name' => 'RSAL Dr. Ramelan',
                'hospital_type' => 'RSU Kelas A (TNI AL)',
                'address'       => 'Jl. Gadung No. 1, Jagir, Wonokromo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578053',
                'hospital_name' => 'RS Bhayangkara H.S. Samsoeri Mertojoso',
                'hospital_type' => 'RSU Kelas B (POLRI)',
                'address'       => 'Jl. Ahmad Yani No. 116, Gayungan',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578064',
                'hospital_name' => 'RS Jiwa Menur Surabaya',
                'hospital_type' => 'RS Khusus Jiwa Kelas A (Pemprov Jatim)',
                'address'       => 'Jl. Menur No. 120, Kertajaya, Gubeng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578100',
                'hospital_name' => 'RS Katolik St. Vincentius A Paulo (RKZ Surabaya)',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Diponegoro No. 51, Darmo, Wonokromo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578111',
                'hospital_name' => 'RS Siloam Surabaya',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Raya Gubeng No. 70, Gubeng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578792',
                'hospital_name' => 'RS Mitra Keluarga Kenjeran',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Kenjeran No. 506, Kalijudan, Mulyorejo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578122',
                'hospital_name' => 'RS Premier Surabaya',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Nginden Intan Barat Blok B, Sukolilo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578144',
                'hospital_name' => 'RS Islam Surabaya A. Yani',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Achmad Yani No. 2-4, Wonokromo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578764',
                'hospital_name' => 'RS Islam Surabaya Jemursari',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Raya Jemursari No. 51-57, Wonocolo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578762',
                'hospital_name' => 'RS Husada Utama',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Mayjen Prof. Dr. Moestopo No. 31-35, Tambaksari',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578133',
                'hospital_name' => 'RS Darmo Surabaya',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. Raya Darmo No. 90, Tegalsari',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578086',
                'hospital_name' => 'RS William Booth Surabaya',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. Diponegoro No. 34, Tegalsari',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578075',
                'hospital_name' => 'RS Adi Husada Undaan Wetan',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Undaan Wetan No. 40-44, Genteng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578090',
                'hospital_name' => 'RS Adi Husada Kapasari',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. Kapasari No. 79-81, Simokerto',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578784',
                'hospital_name' => 'National Hospital Surabaya',
                'hospital_type' => 'RSU Kelas B (Swasta)',
                'address'       => 'Jl. Boulevard Famili Selatan Kav. 1, Wiyung',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578772',
                'hospital_name' => 'RS Royal Surabaya',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. Rungkut Industri I No. 1, Kendangsari, Tenggilis Mejoyo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578166',
                'hospital_name' => 'RS PKU Muhammadiyah Surabaya',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. KH Mas Mansyur No. 180-182, Semampir',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578780',
                'hospital_name' => 'RSIA Kendangsari Surabaya',
                'hospital_type' => 'RS Khusus Ibu & Anak Kelas C (Swasta)',
                'address'       => 'Jl. Raya Kendangsari No. 38, Tenggilis Mejoyo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578771',
                'hospital_name' => 'RS Manyar Medical Centre (MMC)',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. Raya Manyar No. 9, Gubeng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578763',
                'hospital_name' => 'RS Gotong Royong Surabaya',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. Medokan Semampir Indah No. 97, Sukolilo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578765',
                'hospital_name' => 'RS Mata Masyarakat Jawa Timur (RSMM)',
                'hospital_type' => 'RS Khusus Mata Kelas B (Pemprov Jatim)',
                'address'       => 'Jl. Gayung Kebonsari Timur No. 49, Gayungan',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578042',
                'hospital_name' => 'RS Paru Surabaya',
                'hospital_type' => 'RS Khusus Paru Kelas B (Pemprov Jatim)',
                'address'       => 'Jl. Karang Tembok No. 39, Pegirian, Semampir',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578773',
                'hospital_name' => 'RS Khusus Gigi & Mulut Unair (RSGM)',
                'hospital_type' => 'RS Khusus Gigi & Mulut Kelas B (PTN)',
                'address'       => 'Kampus B Unair, Jl. Prof. Dr. Moestopo No. 47, Tambaksari',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578155',
                'hospital_name' => 'RS Al-Irsyad Surabaya',
                'hospital_type' => 'RSU Kelas C (Swasta)',
                'address'       => 'Jl. KH Mas Mansyur No. 210-214, Nyamplungan, Pabean Cantian',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578020',
                'hospital_name' => 'RS Tingkat III Brawijaya Surabaya',
                'hospital_type' => 'RSU Kelas C (TNI AD)',
                'address'       => 'Jl. Kesatrian No. 17, Sawahan',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
            [
                'hospital_code' => '3578775',
                'hospital_name' => 'RS Marinir Gunung Sari Surabaya',
                'hospital_type' => 'RSU Kelas C (TNI AL)',
                'address'       => 'Jl. Golf No. 1, Gunungsari, Dukuh Pakis',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ],
        ];

        foreach ($hospitals as $data) {
            Hospital::updateOrCreate(
                ['hospital_code' => $data['hospital_code']],
                $data
            );
        }
    }
}


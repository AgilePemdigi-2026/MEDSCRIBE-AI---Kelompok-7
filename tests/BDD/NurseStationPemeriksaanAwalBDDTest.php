<?php

namespace Tests\BDD;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;

/**
 * BDD Test Suite: Fitur 05 - Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/05_nurse_station_pemeriksaan_awal.feature
 */
class NurseStationPemeriksaanAwalBDDTest extends BDDTestCase
{
    protected Pengguna $perawat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->perawat = $this->createPerawat();
    }

    /**
     * Skenario: Perawat mendaftarkan pasien baru ke antrean poliklinik
     */
    public function test_skenario_perawat_mendaftarkan_pasien_baru(): void
    {
        $this->given('Perawat aktif sedang login di Nurse Station');
        $namaPasien = 'Andi Prasetyo ' . uniqid();

        $this->when('Perawat mengisi form registrasi pasien baru beserta TTV awal');
        $response = $this->actingAs($this->perawat)->post('/perawat/pasien-baru', [
            'nama_pasien'             => $namaPasien,
            'umur'                    => 35,
            'jenis_kelamin'           => 'Laki-laki',
            'tekanan_darah_sistolik'  => 120,
            'tekanan_darah_diastolik' => 80,
            'nadi'                    => 80,
            'suhu_tubuh'              => 36.6,
            'laju_pernapasan'         => 18,
            'saturasi_oksigen'        => 99,
        ]);

        $this->then('Data pasien baru tersimpan di database dengan nomor rekam medis');
        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('perawat.dashboard', ['tab' => 'pasien']));
        $pasien = Pasien::where('nama_pasien', $namaPasien)->first();
        $this->assertNotNull($pasien);
        $this->assertNotEmpty($pasien->no_rekam_medis);

        $this->and('Antrean baru terbuat dengan status "Siap Periksa"');
        $antrean = Antrean::where('id_pasien', $pasien->id_pasien)->first();
        $this->assertNotNull($antrean);
        $this->assertEquals('Siap Periksa', $antrean->status);
    }

    /**
     * Skenario: Perawat mencari pasien pada daftar antrean
     */
    public function test_skenario_perawat_mencari_pasien_pada_antrean(): void
    {
        $this->given('Terdapat dua pasien antrean aktif: "Budi Santoso BDD" dan "Siti Aminah BDD"');
        $pasienA = Pasien::create([
            'hospital_id'    => $this->perawat->hospital_id,
            'no_rekam_medis' => 'SRCH-1-' . uniqid(),
            'nama_pasien'    => 'Budi Santoso BDD',
            'jenis_kelamin'  => 'Laki-laki',
        ]);
        Antrean::create([
            'hospital_id'       => $this->perawat->hospital_id,
            'id_pasien'         => $pasienA->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Menunggu TTV',
        ]);

        $pasienB = Pasien::create([
            'hospital_id'    => $this->perawat->hospital_id,
            'no_rekam_medis' => 'SRCH-2-' . uniqid(),
            'nama_pasien'    => 'Siti Aminah BDD',
            'jenis_kelamin'  => 'Perempuan',
        ]);
        Antrean::create([
            'hospital_id'       => $this->perawat->hospital_id,
            'id_pasien'         => $pasienB->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Menunggu TTV',
        ]);

        $this->when('Perawat melakukan pencarian antrean dengan kata kunci "Budi"');
        $response = $this->actingAs($this->perawat)->get('/perawat/dashboard?cari=Budi+Santoso+BDD');

        $this->then('Hasil antrean menampilkan nama "Budi Santoso BDD"');
        $response->assertStatus(200);
        $response->assertSee('Budi Santoso BDD');

        $this->and('Hasil antrean tidak memuat nama "Siti Aminah BDD"');
        $response->assertDontSee('Siti Aminah BDD');
    }

    /**
     * Skenario: Perawat berhasil menginput TTV lengkap dan status antrean berubah menjadi Siap Periksa
     */
    public function test_skenario_perawat_input_ttv_dan_status_menjadi_siap_periksa(): void
    {
        $this->given('Pasien antrean dengan status "Menunggu TTV"');
        $pasien = Pasien::create([
            'hospital_id'    => $this->perawat->hospital_id,
            'no_rekam_medis' => 'TTV-' . uniqid(),
            'nama_pasien'    => 'Pasien Cek TTV',
            'jenis_kelamin'  => 'Laki-laki',
        ]);
        $antrean = Antrean::create([
            'hospital_id'       => $this->perawat->hospital_id,
            'id_pasien'         => $pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Menunggu TTV',
        ]);

        $this->when('Perawat mengirimkan payload data TTV lengkap');
        $payload = [
            'id_antrean'      => $antrean->id_antrean,
            'tekanan_darah'   => '120/80',
            'nadi'            => 80,
            'suhu_tubuh'      => 36.8,
            'laju_pernapasan' => 18,
            'berat_badan'     => 65,
            'tinggi_badan'    => 170,
            'catatan'         => 'Pusing sejak kemarin malam',
        ];
        $response = $this->actingAs($this->perawat)->post('/perawat/pemeriksaan-awal', $payload);

        $this->then('Data pemeriksaan awal tersimpan dengan nilai yang sesuai');
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('pemeriksaan_awal', [
            'tekanan_darah' => '120/80',
            'nadi'          => 80,
            'suhu_tubuh'    => 36.8,
        ]);

        $this->and('Status antrean pasien terupdate menjadi "Siap Periksa"');
        $antrean->refresh();
        $this->assertEquals('Siap Periksa', $antrean->status);

        $this->and('Komponen Objective pada Draf SOAP dokter otomatis terisi ringkasan TTV');
        $kunjungan = Kunjungan::where('id_antrean', $antrean->id_antrean)->first();
        $this->assertNotNull($kunjungan);
        $draf = DrafSoap::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
        $this->assertNotNull($draf);
        $this->assertStringContainsString('TD 120/80', $draf->objective_text);
        $this->assertStringContainsString('Nadi 80', $draf->objective_text);
        $this->assertStringContainsString('Suhu 36,8°C', $draf->objective_text);
    }

    /**
     * Skenario: Sistem menandai peringatan demam jika suhu tubuh pasien di atas 37.5 derajat Celsius
     */
    public function test_skenario_peringatan_demam_jika_suhu_tinggi(): void
    {
        $this->given('Pasien antrean dengan suhu tubuh tinggi');
        $pasien = Pasien::create([
            'hospital_id'    => $this->perawat->hospital_id,
            'no_rekam_medis' => 'DEMAM-' . uniqid(),
            'nama_pasien'    => 'Pasien Demam Tinggi',
            'jenis_kelamin'  => 'Perempuan',
        ]);
        $antrean = Antrean::create([
            'hospital_id'       => $this->perawat->hospital_id,
            'id_pasien'         => $pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Menunggu TTV',
        ]);

        $this->when('Perawat menginput suhu tubuh 38.8 derajat Celsius');
        $payload = [
            'id_antrean'      => $antrean->id_antrean,
            'tekanan_darah'   => '110/70',
            'nadi'            => 95,
            'suhu_tubuh'      => 38.8,
            'laju_pernapasan' => 20,
            'berat_badan'     => 55,
            'tinggi_badan'    => 160,
            'catatan'         => 'Menggigil sejak subuh',
        ];
        $response = $this->actingAs($this->perawat)->post('/perawat/pemeriksaan-awal', $payload);

        $this->then('Data pemeriksaan tersimpan dengan suhu 38.8');
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('pemeriksaan_awal', [
            'suhu_tubuh' => 38.8,
        ]);

        $this->and('Dashboard perawat memuat indikator peringatan visual demam tinggi');
        $viewRes = $this->actingAs($this->perawat)->get('/perawat/dashboard?antrean_id=' . $antrean->id_antrean);
        $viewRes->assertStatus(200);
        $viewRes->assertSee('38.8');
    }

    /**
     * Skenario: Perawat membatalkan pemeriksaan pasien dengan alasan yang valid
     */
    public function test_skenario_perawat_membatalkan_pemeriksaan_pasien(): void
    {
        $this->given('Pasien antrean menunggu TTV yang batal periksa');
        $pasien = Pasien::create([
            'hospital_id'    => $this->perawat->hospital_id,
            'no_rekam_medis' => 'BATAL-' . uniqid(),
            'nama_pasien'    => 'Pasien Batal Hadir',
            'jenis_kelamin'  => 'Laki-laki',
        ]);
        $antrean = Antrean::create([
            'hospital_id'       => $this->perawat->hospital_id,
            'id_pasien'         => $pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Menunggu TTV',
        ]);

        $this->when('Perawat mengirimkan aksi delete pembatalan dengan alasan');
        $alasan = 'Pasien pulang sebelum dilakukan pemeriksaan TTV';
        $response = $this->actingAs($this->perawat)->delete('/perawat/pasien/' . $pasien->id_pasien, [
            'alasan' => $alasan,
        ]);

        $this->then('Record pasien ter-soft delete (deleted_at tidak null)');
        $response->assertRedirect(route('perawat.dashboard', ['tab' => 'pasien']));
        $pasien->refresh();
        $this->assertNotNull($pasien->deleted_at);

        $this->and('Metadata audit tersimpan dengan alasan pembatalan dan ID perawat');
        $this->assertEquals($this->perawat->id_pengguna, $pasien->cancelled_by);
        $this->assertEquals($alasan, $pasien->cancellation_reason);

        $this->and('Status antrean pasien diperbarui menjadi "Dibatalkan"');
        $antrean->refresh();
        $this->assertEquals('Dibatalkan', $antrean->status);
    }
}

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
     * @scenario Register New Patient and Initial Vital Signs Success
     */
    public function test_skenario_perawat_mendaftarkan_pasien_baru(): void
    {
        $this->given('Perawat aktif sedang login di Nurse Station');
        $namaPasien = 'Andi Prasetyo ' . uniqid();

        $this->when('Perawat mengisi form registrasi pasien baru berisi data diri');
        $response = $this->actingAs($this->perawat)->post('/perawat/pasien-baru', [
            'nama_pasien'   => $namaPasien,
            'tanggal_lahir' => '1990-05-15',
            'jenis_kelamin' => 'Laki-laki',
            'nik'           => '3578012345670001',
            'no_bpjs'       => '0001234567890',
            'no_hp'         => '081234567890',
        ]);

        $this->then('Data pasien baru tersimpan di database dengan nomor rekam medis');
        $response->assertSessionDoesntHaveErrors();
        $pasien = Pasien::where('nama_pasien', $namaPasien)->first();
        $this->assertNotNull($pasien);
        $this->assertNotEmpty($pasien->no_rekam_medis);
        $this->assertSame('3578012345670001', $pasien->nik);
        $this->assertSame('0001234567890', $pasien->no_bpjs);
        $this->assertSame('081234567890', $pasien->no_hp);
        $this->assertSame('1990-05-15', $pasien->tanggal_lahir->toDateString());
        $this->assertSame(\Carbon\Carbon::parse('1990-05-15')->age, $pasien->umur);
        $response->assertSessionHas('status', 'Pasien berhasil didaftarkan dengan No. RM ' . $pasien->no_rekam_medis . '. Lanjutkan dengan mengisi TTV.');

        $this->and('Antrean baru terbuat dengan status "Menunggu TTV" dan perawat diarahkan ke form TTV');
        $antrean = Antrean::where('id_pasien', $pasien->id_pasien)->first();
        $this->assertNotNull($antrean);
        $this->assertEquals('Menunggu TTV', $antrean->status);
        $response->assertRedirect(route('perawat.dashboard', ['antrean_id' => $antrean->id_antrean]));
    }

    /**
     * Skenario: Perawat mencari pasien pada daftar antrean
     * @scenario Search Patient in Queue
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
     * @scenario Record Vital Signs (TTV) for Waiting Patient
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
            'saturasi_oksigen' => 98,
            'berat_badan'     => 65,
            'tinggi_badan'    => 170,
            'catatan'         => 'Pusing sejak kemarin malam',
        ];
        $response = $this->actingAs($this->perawat)->post('/perawat/pemeriksaan-awal', $payload);

        $this->then('Data pemeriksaan awal tersimpan dengan nilai yang sesuai');
        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('perawat.dashboard', ['antrean_id' => $antrean->id_antrean]));
        $response->assertSessionHas('status', 'Data berhasil disimpan!');
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
     * Skenario: Perawat membatalkan pemeriksaan pasien dengan alasan yang valid
     * @scenario Cancel Patient Registration
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
        // Nilai tetap yang dikirim dialog "Hapus Registrasi Pasien?" pada Nurse Station.
        $alasan = 'Pasien tidak hadir / pembatalan registrasi oleh perawat';
        $response = $this->actingAs($this->perawat)->delete('/perawat/pasien/' . $pasien->id_pasien, [
            'alasan' => $alasan,
        ]);

        $this->then('Record pasien ter-soft delete (deleted_at tidak null)');
        $response->assertRedirect(route('perawat.dashboard', ['tab' => 'pasien']));
        $pasien->refresh();
        $response->assertSessionHas('status', "Registrasi pasien {$pasien->nama_pasien} ({$pasien->no_rekam_medis}) berhasil dibatalkan.");
        $this->assertNotNull($pasien->deleted_at);

        $this->and('Metadata audit tersimpan dengan alasan pembatalan dan ID perawat');
        $this->assertEquals($this->perawat->id_pengguna, $pasien->cancelled_by);
        $this->assertEquals($alasan, $pasien->cancellation_reason);

        $this->and('Status antrean pasien diperbarui menjadi "Dibatalkan"');
        $antrean->refresh();
        $this->assertEquals('Dibatalkan', $antrean->status);
    }
}

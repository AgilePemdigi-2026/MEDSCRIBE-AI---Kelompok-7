<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DokterRekamMedisTest extends TestCase
{
    use DatabaseTransactions;

    protected Pengguna $dokter;

    protected Pengguna $perawat;

    protected Pasien $pasien;

    protected Antrean $antrean;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = Pengguna::firstOrCreate(
            ['email' => 'rina@medscribe.id'],
            [
                'nama_lengkap' => 'dr. Rina Anggraini, Sp.PD',
                'role' => 'dokter',
                'password' => bcrypt('password123'),
            ]
        );

        $this->perawat = Pengguna::firstOrCreate(
            ['email' => 'dewi@medscribe.id'],
            [
                'nama_lengkap' => 'Dewi Pratiwi',
                'role' => 'perawat',
                'password' => bcrypt('password123'),
            ]
        );

        $this->pasien = Pasien::withTrashed()->firstOrCreate(
            ['no_rekam_medis' => '00-12-999'],
            [
                'nama_pasien' => 'Tes Pasien Dokter',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'umur' => 36,
            ]
        );
        if ($this->pasien->trashed()) {
            $this->pasien->restore();
        }

        $this->antrean = Antrean::firstOrCreate(
            ['id_pasien' => $this->pasien->id_pasien],
            [
                'tanggal_kunjungan' => '2026-09-19',
                'status' => 'Siap Periksa',
            ]
        );
    }

    /**
     * 1. Dokter dapat mengakses halaman rekam medis pasien.
     * Bagian Draf Catatan Medis (SOAP) dan kontrol AI dipastikan tidak ditampilkan pada sprint ini.
     */
    public function test_dokter_dapat_mengakses_halaman_rekam_medis(): void
    {
        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/'.$this->antrean->id_antrean);

        $response->assertStatus(200);
        $response->assertSee($this->pasien->nama_pasien);
        $response->assertSee('TTV & Catatan Perawat', false);
        $response->assertSee('Rekam Wawancara Klinis');
        $response->assertSee('Review dan Edit Draf SOAP');

        // Pastikan draf SOAP dan kontrol AI tidak tampil di halaman
        $response->assertDontSee('Draf Catatan Medis (SOAP)');
        $response->assertDontSee('Proses AI Ulang');
    }

    public function test_rekam_medis_menawarkan_mulai_wawancara_untuk_kunjungan_yang_sama_sebelum_sesi_dimulai(): void
    {
        PemeriksaanAwal::create([
            'id_kunjungan' => Kunjungan::firstOrCreate(
                ['id_antrean' => $this->antrean->id_antrean],
                [
                    'id_pasien' => $this->pasien->id_pasien,
                    'id_dokter' => $this->dokter->id_pengguna,
                    'tanggal' => '2026-09-19',
                    'status' => 'Sedang Diperiksa',
                ]
            )->id_kunjungan,
            'id_perawat' => $this->perawat->id_pengguna,
        ]);
        $response = $this->actingAs($this->dokter)->get(route('dokter.rekam-medis', $this->antrean));
        $kunjungan = Kunjungan::where('id_antrean', $this->antrean->id_antrean)->firstOrFail();

        $response->assertOk()
            ->assertSee(route('dokter.interview.start', $kunjungan), false)
            ->assertDontSee('recording-console')
            ->assertDontSee('startClinicalRecording');
    }

    public function test_lanjutkan_wawancara_tidak_membuat_sesi_baru(): void
    {
        $kunjungan = Kunjungan::create([
            'id_antrean' => $this->antrean->id_antrean,
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => '2026-09-19',
            'status' => 'Sedang Diperiksa',
        ]);
        $interview = Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Wawancara Berlangsung',
        ]);

        $this->actingAs($this->dokter)
            ->get(route('dokter.interview.show', $kunjungan))
            ->assertRedirect(route('dokter.rekam-medis', $this->antrean->id_antrean).'#wawancara');

        $this->actingAs($this->dokter)
            ->get(route('dokter.rekam-medis', $this->antrean->id_antrean))
            ->assertOk()
            ->assertSee('id="recording-console"', false)
            ->assertSee('Mulai Rekaman');

        $this->assertSame($interview->id_wawancara, Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->value('id_wawancara'));
        $this->assertSame(1, Wawancara::where('id_kunjungan', $kunjungan->id_kunjungan)->count());
    }

    /**
     * 2. Perawat tidak diizinkan mengakses halaman rekam medis dokter.
     */
    public function test_perawat_ditolak_mengakses_halaman_rekam_medis_dokter(): void
    {
        $response = $this->actingAs($this->perawat)->get('/dokter/rekam-medis/'.$this->antrean->id_antrean);

        $response->assertRedirect('/perawat/dashboard');
        $response->assertSessionHas('warning');
    }

    /**
     * 3. Halaman menampilkan data TTV dan catatan perawat yang sebenarnya.
     */
    public function test_rekam_medis_menampilkan_data_ttv_dan_catatan_perawat(): void
    {
        $kunjungan = Kunjungan::firstOrCreate(
            ['id_antrean' => $this->antrean->id_antrean],
            [
                'id_pasien' => $this->pasien->id_pasien,
                'id_dokter' => $this->dokter->id_pengguna,
                'tanggal' => '2026-09-19',
                'status' => 'Sedang Diperiksa',
            ]
        );

        PemeriksaanAwal::updateOrCreate(
            ['id_kunjungan' => $kunjungan->id_kunjungan],
            [
                'id_perawat' => $this->perawat->id_pengguna,
                'tekanan_darah' => '125/85',
                'suhu_tubuh' => 37.2,
                'nadi' => 82,
                'laju_pernapasan' => 19,
                'berat_badan' => 70,
                'tinggi_badan' => 172,
                'catatan' => 'Pasien mengeluh pusing dan pegal pada leher belakang.',
            ]
        );

        $response = $this->actingAs($this->dokter)->get('/dokter/rekam-medis/'.$this->antrean->id_antrean);

        $response->assertStatus(200);
        $response->assertSee('125/85');
        $response->assertSee('37.2');
        $response->assertSee('82');
        $response->assertSee('Pasien mengeluh pusing dan pegal pada leher belakang.');
        $response->assertSee('Dewi Pratiwi');
    }

    /**
     * 4. Rute pemrosesan AI dinonaktifkan dari alur aktif sprint ini.
     */
    public function test_rute_proses_ai_tidak_aktif(): void
    {
        $response = $this->actingAs($this->dokter)->postJson(
            '/dokter/rekam-medis/'.$this->antrean->id_antrean.'/proses-ai'
        );

        $response->assertStatus(404);
    }

    /**
     * 5. Dashboard antrean dokter mengurutkan nomor antrean dari terbesar ke terkecil (descending).
     */
    public function test_dashboard_dokter_mengurutkan_antrean_dari_terbesar_ke_terkecil(): void
    {
        $pasien2 = Pasien::create([
            'no_rekam_medis' => 'TEST-'.uniqid(),
            'nama_pasien' => 'Pasien Antrean Baru',
            'umur' => 28,
            'jenis_kelamin' => 'Perempuan',
        ]);

        $antreanLebihBesar = Antrean::create([
            'id_pasien' => $pasien2->id_pasien,
            'tanggal_kunjungan' => date('Y-m-d'),
            'status' => 'Siap Periksa',
        ]);

        $response = $this->actingAs($this->dokter)->get('/dokter/dashboard');

        $response->assertStatus(200);
        $daftarAntrean = $response->viewData('daftarAntrean');
        $this->assertNotEmpty($daftarAntrean);

        // Pastikan nomor antrean terurut menurun (terbesar ke terkecil)
        $ids = $daftarAntrean->pluck('id_antrean')->toArray();
        $sortedIds = $ids;
        rsort($sortedIds);
        $this->assertEquals($sortedIds, $ids);
    }
}

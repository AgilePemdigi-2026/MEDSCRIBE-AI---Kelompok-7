<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HospitalMultiTenancyTest extends TestCase
{
    use DatabaseTransactions;

    protected Hospital $hospitalA;
    protected Hospital $hospitalB;

    protected Pengguna $perawatA;
    protected Pengguna $dokterA;

    protected Pengguna $perawatB;
    protected Pengguna $dokterB;

    protected Pasien $pasienA;
    protected Antrean $antreanA;

    protected Pasien $pasienB;
    protected Antrean $antreanB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Dapatkan dua rumah sakit resmi di Surabaya
        $this->hospitalA = Hospital::where('hospital_code', '3578770')->first()
            ?? Hospital::create([
                'hospital_code' => '3578770',
                'name'          => 'RS Universitas Airlangga',
                'type'          => 'RSU',
                'address'       => 'Jl. Dharmahusada Permai No. 1, Mulyorejo',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ]);

        $this->hospitalB = Hospital::where('hospital_code', '3578016')->first()
            ?? Hospital::create([
                'hospital_code' => '3578016',
                'name'          => 'RSUD Dr. Soetomo',
                'type'          => 'RSUD',
                'address'       => 'Jl. Mayjen Prof. Dr. Moestopo No. 6-8, Gubeng',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ]);

        // 2. Akun Rumah Sakit A
        $this->perawatA = Pengguna::create([
            'nama_lengkap'      => 'Perawat RS A',
            'email'             => 'nurse.a.' . uniqid() . '@medscribe.id',
            'role'              => 'perawat',
            'hospital_id'       => $this->hospitalA->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ]);

        $this->dokterA = Pengguna::create([
            'nama_lengkap'      => 'dr. Spesialis RS A',
            'email'             => 'doctor.a.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ]);

        // 3. Akun Rumah Sakit B
        $this->perawatB = Pengguna::create([
            'nama_lengkap'      => 'Perawat RS B',
            'email'             => 'nurse.b.' . uniqid() . '@medscribe.id',
            'role'              => 'perawat',
            'hospital_id'       => $this->hospitalB->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ]);

        $this->dokterB = Pengguna::create([
            'nama_lengkap'      => 'dr. Spesialis RS B',
            'email'             => 'doctor.b.' . uniqid() . '@medscribe.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalB->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ]);

        // 4. Data Pasien & Antrean Rumah Sakit A
        $this->pasienA = Pasien::create([
            'hospital_id'    => $this->hospitalA->id,
            'no_rekam_medis' => Carbon::now()->format('Ymd') . '-801',
            'nama_pasien'    => 'Pasien Unik RS A',
            'tanggal_lahir'  => '1990-01-01',
            'jenis_kelamin'  => 'Laki-laki',
        ]);

        $this->antreanA = Antrean::create([
            'hospital_id'       => $this->hospitalA->id,
            'id_pasien'         => $this->pasienA->id_pasien,
            'tanggal_kunjungan' => Carbon::today()->toDateString(),
            'status'            => 'Siap Periksa',
        ]);

        // 5. Data Pasien & Antrean Rumah Sakit B
        $this->pasienB = Pasien::create([
            'hospital_id'    => $this->hospitalB->id,
            'no_rekam_medis' => Carbon::now()->format('Ymd') . '-802',
            'nama_pasien'    => 'Pasien Khusus RS B',
            'tanggal_lahir'  => '1992-02-02',
            'jenis_kelamin'  => 'Perempuan',
        ]);

        $this->antreanB = Antrean::create([
            'hospital_id'       => $this->hospitalB->id,
            'id_pasien'         => $this->pasienB->id_pasien,
            'tanggal_kunjungan' => Carbon::today()->toDateString(),
            'status'            => 'Siap Periksa',
        ]);
    }

    /**
     * 1. Perawat hanya melihat antrean pasien milik rumah sakit tempatnya bertugas.
     */
    public function test_perawat_hanya_melihat_antrean_rumah_sakitnya(): void
    {
        // Perawat RS A melihat dashboard
        $responseA = $this->actingAs($this->perawatA)->get('/perawat/dashboard');
        $responseA->assertStatus(200);
        $responseA->assertSee('Pasien Unik RS A');
        $responseA->assertDontSee('Pasien Khusus RS B');

        // Perawat RS B melihat dashboard
        $responseB = $this->actingAs($this->perawatB)->get('/perawat/dashboard');
        $responseB->assertStatus(200);
        $responseB->assertSee('Pasien Khusus RS B');
        $responseB->assertDontSee('Pasien Unik RS A');
    }

    /**
     * 2. Perawat hanya melihat daftar pasien yang terdaftar di rumah sakit tempatnya bertugas.
     */
    public function test_perawat_hanya_melihat_daftar_pasien_rumah_sakitnya(): void
    {
        $responseA = $this->actingAs($this->perawatA)->get('/perawat/dashboard?tab=pasien');
        $responseA->assertStatus(200);
        $responseA->assertSee('Pasien Unik RS A');
        $responseA->assertDontSee('Pasien Khusus RS B');

        $responseB = $this->actingAs($this->perawatB)->get('/perawat/dashboard?tab=pasien');
        $responseB->assertStatus(200);
        $responseB->assertSee('Pasien Khusus RS B');
        $responseB->assertDontSee('Pasien Unik RS A');
    }

    /**
     * 3. Dokter Poliklinik hanya melihat antrean dan statistik dari rumah sakit tempatnya bertugas.
     */
    public function test_dokter_hanya_melihat_antrean_rumah_sakitnya(): void
    {
        $responseA = $this->actingAs($this->dokterA)->get('/dokter/dashboard');
        $responseA->assertStatus(200);
        $responseA->assertSee('Pasien Unik RS A');
        $responseA->assertDontSee('Pasien Khusus RS B');

        $responseB = $this->actingAs($this->dokterB)->get('/dokter/dashboard');
        $responseB->assertStatus(200);
        $responseB->assertSee('Pasien Khusus RS B');
        $responseB->assertDontSee('Pasien Unik RS A');
    }

    /**
     * 4. Dokter dilarang mengakses rekam medis pasien dari rumah sakit lain (HTTP 403 Forbidden).
     */
    public function test_dokter_dilarang_mengakses_rekam_medis_rumah_sakit_lain(): void
    {
        // Dokter RS A mencoba membuka rekam medis antrean RS B
        $response = $this->actingAs($this->dokterA)->get('/dokter/rekam-medis/' . $this->antreanB->id_antrean);
        $response->assertStatus(403);

        // Dokter RS B mencoba membuka rekam medis antrean RS A
        $responseInverse = $this->actingAs($this->dokterB)->get('/dokter/rekam-medis/' . $this->antreanA->id_antrean);
        $responseInverse->assertStatus(403);
    }

    /**
     * 5. Dokter dilarang memproses transkrip rekam medis ke kunjungan dari rumah sakit lain (HTTP 403 Forbidden).
     */
    public function test_dokter_dilarang_memproses_rekaman_untuk_pasien_rumah_sakit_lain(): void
    {
        $kunjunganB = Kunjungan::create([
            'hospital_id' => $this->hospitalB->id,
            'id_antrean'  => $this->antreanB->id_antrean,
            'id_pasien'   => $this->pasienB->id_pasien,
            'id_dokter'   => $this->dokterB->id_pengguna,
            'tanggal'     => Carbon::today()->toDateString(),
            'status'      => 'Sedang Diperiksa',
        ]);

        $response = $this->actingAs($this->dokterA)->postJson(
            route('dokter.interview.process', $kunjunganB),
            ['realtime_transcript' => 'Pasien mengeluh demam sejak kemarin.']
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('wawancara', ['id_kunjungan' => $kunjunganB->id_kunjungan]);
    }

    /**
     * 6. Dokter dilarang streaming audio pasien dari rumah sakit lain (HTTP 403 Forbidden).
     */
    public function test_dokter_dilarang_streaming_audio_pasien_rumah_sakit_lain(): void
    {
        $response = $this->actingAs($this->dokterA)->get('/dokter/rekam-medis/' . $this->antreanB->id_antrean . '/audio-file');
        $response->assertStatus(403);
    }

    /**
     * 7. Perawat dilarang menghapus/membatalkan registrasi pasien milik rumah sakit lain (HTTP 403 Forbidden).
     */
    public function test_perawat_dilarang_membatalkan_pemeriksaan_pasien_rumah_sakit_lain(): void
    {
        $response = $this->actingAs($this->perawatA)->delete('/perawat/pasien/' . $this->pasienB->id_pasien);
        $response->assertStatus(403);

        // Pasien B tetap ada di database
        $this->assertDatabaseHas('pasien', [
            'id_pasien' => $this->pasienB->id_pasien,
        ]);
    }

    /**
     * 8. Registrasi pasien baru oleh perawat otomatis terasosiasi dengan rumah sakit perawat,
     * dan validasi menolak percobaan spoofing hospital_id ke rumah sakit lain.
     */
    public function test_registrasi_pasien_baru_oleh_perawat_terkunci_ke_rumah_sakit_perawat(): void
    {
        // A. Coba spoof hospital_id ke Rumah Sakit B -> Wajib Ditolak!
        $payloadSpoofed = [
            'nama_pasien'             => 'Pasien Coba Spoof',
            'umur'                    => 30,
            'jenis_kelamin'           => 'Laki-laki',
            'hospital_id'             => $this->hospitalB->id,
            'tekanan_darah_sistolik'  => 120,
            'tekanan_darah_diastolik' => 80,
            'nadi'                    => 75,
            'suhu_tubuh'              => 36.6,
            'laju_pernapasan'         => 18,
            'saturasi_oksigen'        => 98,
        ];

        $spoofResponse = $this->actingAs($this->perawatA)->post('/perawat/pasien-baru', $payloadSpoofed);
        $spoofResponse->assertSessionHasErrors('hospital_id');

        // B. Registrasi resmi di Rumah Sakit A
        $payloadValid = [
            'nama_pasien'             => 'Pasien Baru RS A',
            'umur'                    => 28,
            'jenis_kelamin'           => 'Laki-laki',
            'hospital_id'             => $this->hospitalA->id,
            'tekanan_darah_sistolik'  => 118,
            'tekanan_darah_diastolik' => 78,
            'nadi'                    => 72,
            'suhu_tubuh'              => 36.5,
            'laju_pernapasan'         => 16,
            'saturasi_oksigen'        => 99,
            'berat_badan'             => 65,
            'tinggi_badan'            => 172,
            'catatan'                 => 'Pemeriksaan rutin mandiri',
        ];

        $response = $this->actingAs($this->perawatA)->post('/perawat/pasien-baru', $payloadValid);

        $response->assertRedirect(route('perawat.dashboard', ['tab' => 'pasien']));
        $response->assertSessionHas('status');
        $response->assertSessionHas('new_patient_id');

        // Pastikan pasien baru tersimpan dengan hospital_id milik Perawat A (Hospital A)
        $this->assertDatabaseHas('pasien', [
            'nama_pasien' => 'Pasien Baru RS A',
            'hospital_id' => $this->hospitalA->id,
        ]);

        $pasien = Pasien::where('nama_pasien', 'Pasien Baru RS A')->first();
        $this->assertNotNull($pasien);
        $this->assertEquals($this->hospitalA->id, $pasien->hospital_id);

        // Format nomor rekam medis YYYYMMDD-NNN
        $prefix = Carbon::now()->format('Ymd');
        $this->assertMatchesRegularExpression('/^' . $prefix . '-\d{3}$/', $pasien->no_rekam_medis);
    }

    /**
     * 9. Input TTV oleh perawat ditolak jika antrean milik rumah sakit lain.
     */
    public function test_input_ttv_oleh_perawat_dilarang_untuk_antrean_rumah_sakit_lain(): void
    {
        $payload = [
            'id_antrean'      => $this->antreanB->id_antrean,
            'tekanan_darah'   => '120/80',
            'nadi'            => 80,
            'suhu_tubuh'      => 36.5,
            'laju_pernapasan' => 18,
            'berat_badan'     => 60,
            'tinggi_badan'    => 165,
            'catatan'         => 'Percobaan lintas faskes',
        ];

        // Perawat RS A mencoba menginput TTV untuk antrean RS B
        $response = $this->actingAs($this->perawatA)->post('/perawat/pemeriksaan-awal', $payload);
        $response->assertStatus(403);
    }
}

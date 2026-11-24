<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pasien lama yang datang lagi didaftarkan sebagai kunjungan baru (antrean + kunjungan),
 * bukan pasien baru, supaya No. RM tetap satu dan riwayatnya tersambung.
 */
class KunjunganUlangPasienTest extends TestCase
{
    use DatabaseTransactions;

    protected Hospital $hospitalA;
    protected Hospital $hospitalB;
    protected Pengguna $perawat;
    protected Pasien $pasienLama;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospitalA = $this->rumahSakit('3578770', 'RS Universitas Airlangga');
        $this->hospitalB = $this->rumahSakit('3578016', 'RSUD Dr. Soetomo');

        $this->perawat = Pengguna::create([
            'nama_lengkap'      => 'Perawat Kunjungan Ulang',
            'email'             => 'perawat.kunjungan.ulang@test.id',
            'role'              => 'perawat',
            'hospital_id'       => $this->hospitalA->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => bcrypt('password123'),
        ]);

        // Pasien yang pernah berobat: kunjungan pertamanya sudah selesai.
        $this->pasienLama = Pasien::create([
            'hospital_id'    => $this->hospitalA->id,
            'no_rekam_medis' => '20260101-901',
            'nama_pasien'    => 'Siti Kunjungan Ulang',
            'nik'            => '3578000011112222',
            'no_bpjs'        => '0001234567890',
        ]);
        $antreanLama = Antrean::create([
            'hospital_id'       => $this->hospitalA->id,
            'id_pasien'         => $this->pasienLama->id_pasien,
            'tanggal_kunjungan' => '2026-01-01',
            'status'            => 'Selesai',
        ]);
        Kunjungan::create([
            'hospital_id' => $this->hospitalA->id,
            'id_antrean'  => $antreanLama->id_antrean,
            'id_pasien'   => $this->pasienLama->id_pasien,
            'tanggal'     => '2026-01-01',
            'status'      => 'Final',
        ]);
    }

    private function rumahSakit(string $kode, string $nama): Hospital
    {
        return Hospital::where('hospital_code', $kode)->first()
            ?? Hospital::create([
                'hospital_code' => $kode,
                'hospital_name' => $nama,
                'hospital_type' => 'RSU',
                'address'       => 'Alamat uji',
                'city'          => 'Kota Surabaya',
                'province'      => 'Jawa Timur',
                'is_active'     => true,
            ]);
    }

    public function test_kunjungan_ulang_tidak_membuat_pasien_atau_no_rm_baru(): void
    {
        $jumlahPasien = Pasien::count();

        $response = $this->actingAs($this->perawat)
            ->post(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien));

        $antreanBaru = Antrean::where('id_pasien', $this->pasienLama->id_pasien)
            ->where('status', 'Menunggu TTV')
            ->first();

        $this->assertNotNull($antreanBaru);
        $this->assertSame(now('Asia/Jakarta')->toDateString(), $antreanBaru->tanggal_kunjungan);
        $response->assertRedirect(route('perawat.dashboard', ['antrean_id' => $antreanBaru->id_antrean]));
        $response->assertSessionHas('status');

        $this->assertSame($jumlahPasien, Pasien::count());
        $this->assertSame('20260101-901', $this->pasienLama->fresh()->no_rekam_medis);

        // Riwayat tersambung: kunjungan lama dan baru milik pasien yang sama.
        $this->assertSame(2, Kunjungan::where('id_pasien', $this->pasienLama->id_pasien)->count());
        $this->assertDatabaseHas('kunjungan', [
            'id_antrean'  => $antreanBaru->id_antrean,
            'id_pasien'   => $this->pasienLama->id_pasien,
            'hospital_id' => $this->hospitalA->id,
            'status'      => 'Menunggu Dokter',
        ]);
    }

    public function test_pasien_yang_masih_di_antrean_tidak_bisa_didaftarkan_dua_kali(): void
    {
        $this->actingAs($this->perawat)->post(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien));
        $antreanPertama = Antrean::where('id_pasien', $this->pasienLama->id_pasien)
            ->where('status', 'Menunggu TTV')
            ->firstOrFail();

        $response = $this->actingAs($this->perawat)
            ->post(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien));

        $response->assertRedirect(route('perawat.dashboard', ['antrean_id' => $antreanPertama->id_antrean]));
        $response->assertSessionHas('warning');
        $this->assertSame(1, Antrean::where('id_pasien', $this->pasienLama->id_pasien)
            ->whereNotIn('status', Antrean::STATUS_TIDAK_AKTIF)
            ->count());
    }

    public function test_perawat_tidak_bisa_mendaftarkan_kunjungan_pasien_rs_lain(): void
    {
        $pasienRsLain = Pasien::create([
            'hospital_id'    => $this->hospitalB->id,
            'no_rekam_medis' => '20260101-902',
            'nama_pasien'    => 'Pasien RS Lain',
        ]);

        $this->actingAs($this->perawat)
            ->post(route('perawat.pasien.kunjungan', $pasienRsLain->id_pasien))
            ->assertForbidden();

        $this->assertSame(0, Antrean::where('id_pasien', $pasienRsLain->id_pasien)->count());
    }

    public function test_dokter_tidak_bisa_mendaftarkan_kunjungan(): void
    {
        $dokter = Pengguna::create([
            'nama_lengkap'      => 'dr. Kunjungan Ulang',
            'email'             => 'dokter.kunjungan.ulang@test.id',
            'role'              => 'dokter',
            'hospital_id'       => $this->hospitalA->id,
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => bcrypt('password123'),
        ]);

        $this->actingAs($dokter)
            ->post(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien))
            ->assertForbidden();
    }

    public function test_nik_terdaftar_memunculkan_peringatan_tanpa_menyimpan_pasien_baru(): void
    {
        $jumlahPasien = Pasien::count();

        $response = $this->actingAs($this->perawat)
            ->from(route('perawat.dashboard'))
            ->post(route('perawat.pasien.store'), [
                'nama_pasien' => 'Siti K. Ulang',
                'nik'         => '3578 0000 1111 2222',
            ]);

        $response->assertRedirect(route('perawat.dashboard'));
        $response->assertSessionHas('pasien_serupa', [$this->pasienLama->id_pasien]);
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame($jumlahPasien, Pasien::count());

        // Peringatan tampil di dialog beserta tombol kunjungan ulang pasien lama.
        $this->actingAs($this->perawat)
            ->withSession(['pasien_serupa' => [$this->pasienLama->id_pasien]])
            ->get(route('perawat.dashboard'))
            ->assertOk()
            ->assertSee('NIK atau No. BPJS ini sudah terdaftar')
            ->assertSee('20260101-901')
            ->assertSee(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien), false)
            ->assertSee('Tetap daftarkan pasien baru');
    }

    public function test_no_bpjs_terdaftar_juga_memunculkan_peringatan(): void
    {
        $this->actingAs($this->perawat)
            ->from(route('perawat.dashboard'))
            ->post(route('perawat.pasien.store'), [
                'nama_pasien' => 'Siti',
                'no_bpjs'     => '0001234567890',
            ])
            ->assertSessionHas('pasien_serupa', [$this->pasienLama->id_pasien]);
    }

    public function test_perawat_tetap_bisa_mendaftarkan_pasien_baru_setelah_peringatan(): void
    {
        $this->actingAs($this->perawat)
            ->post(route('perawat.pasien.store'), [
                'nama_pasien'         => 'Siti Orang Lain',
                'nik'                 => '3578000011112222',
                'konfirmasi_duplikat' => '1',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertSessionMissing('pasien_serupa');

        $this->assertDatabaseHas('pasien', ['nama_pasien' => 'Siti Orang Lain', 'nik' => '3578000011112222']);
    }

    public function test_nik_pasien_rs_lain_tidak_memicu_peringatan(): void
    {
        Pasien::create([
            'hospital_id'    => $this->hospitalB->id,
            'no_rekam_medis' => '20260101-903',
            'nama_pasien'    => 'Pasien RS Lain',
            'nik'            => '3578999988887777',
        ]);

        $this->actingAs($this->perawat)
            ->post(route('perawat.pasien.store'), [
                'nama_pasien' => 'Pasien Baru RS A',
                'nik'         => '3578999988887777',
            ])
            ->assertSessionMissing('pasien_serupa');

        $this->assertDatabaseHas('pasien', ['nama_pasien' => 'Pasien Baru RS A', 'hospital_id' => $this->hospitalA->id]);
    }

    public function test_daftar_pasien_menampilkan_tombol_sesuai_status_antrean(): void
    {
        $halaman = fn () => $this->actingAs($this->perawat)->get(route('perawat.dashboard', ['tab' => 'pasien']));

        $halaman()->assertOk()
            ->assertSee(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien), false);

        $this->actingAs($this->perawat)->post(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien));

        // Di tabel hanya ikon; keterangannya lewat tooltip.
        $halaman()->assertOk()
            ->assertDontSee(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien), false)
            ->assertSee('title="Masih di antrean (Menunggu TTV), buka antrean"', false);
    }

    public function test_detail_pasien_menampilkan_tombol_dan_riwayat_kedua_kunjungan(): void
    {
        $this->actingAs($this->perawat)
            ->get(route('perawat.pasien.show', $this->pasienLama->id_pasien))
            ->assertOk()
            ->assertSee('Daftarkan kunjungan');

        $this->actingAs($this->perawat)->post(route('perawat.pasien.kunjungan', $this->pasienLama->id_pasien));

        $this->actingAs($this->perawat)
            ->get(route('perawat.pasien.show', $this->pasienLama->id_pasien))
            ->assertOk()
            ->assertSee('Di antrean')
            ->assertViewHas('riwayatKunjungan', fn ($riwayat) => $riwayat->count() === 2);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Perbaikan kecil 22 November: umur bayi, "Lihat Berkas" tanpa kunjungan,
 * dan salam login yang bertumpuk di halaman lengkapi profil.
 */
class PolesKecilTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $dokter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = Pengguna::create([
            'nama_lengkap' => 'dr. Uji Poles',
            'email' => 'uji.poles.dokter@medscribe.id',
            'role' => 'dokter',
            'nomor_sip' => '503/2000/SIP-D/2026',
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);
    }

    private function pasien(array $atribut = []): Pasien
    {
        return Pasien::create(array_merge([
            'hospital_id' => 1,
            'no_rekam_medis' => 'POLES-' . uniqid(),
            'nama_pasien' => 'Pasien Poles',
        ], $atribut));
    }

    public function test_umur_nol_tahun_tetap_dianggap_terisi(): void
    {
        $bayiTanpaTanggal = $this->pasien(['umur' => 0]);
        $this->assertSame(0, $bayiTanpaTanggal->usia);
        $this->assertSame('Di bawah 1 tahun', $bayiTanpaTanggal->usia_label);

        $bayi = $this->pasien(['tanggal_lahir' => now()->subMonths(5)->subDays(3)->toDateString()]);
        $this->assertSame(0, $bayi->usia);
        $this->assertSame('5 bulan', $bayi->usia_label);

        $dewasa = $this->pasien(['tanggal_lahir' => now()->subYears(36)->subDay()->toDateString()]);
        $this->assertSame('36 tahun', $dewasa->usia_label);

        $this->assertNull($this->pasien()->usia_label);
    }

    public function test_umur_perkiraan_diutamakan_daripada_tanggal_lahir(): void
    {
        $pasien = $this->pasien(['umur' => 40, 'tanggal_lahir' => now()->subYears(36)->toDateString()]);

        $this->assertSame('40 tahun', $pasien->usia_label);
    }

    public function test_lihat_berkas_antrean_selesai_tanpa_kunjungan_tidak_membuat_kunjungan_baru(): void
    {
        $pasien = $this->pasien();
        $antrean = Antrean::create([
            'hospital_id' => 1,
            'id_pasien' => $pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status' => 'Selesai',
        ]);

        $response = $this->actingAs($this->dokter)->get(route('dokter.rekam-medis', $antrean->id_antrean));

        $response->assertRedirect(route('dokter.dashboard'));
        $response->assertSessionHas('warning');
        $this->assertFalse(Kunjungan::where('id_antrean', $antrean->id_antrean)->exists());
    }

    public function test_dashboard_dokter_menampilkan_tanpa_berkas_untuk_antrean_selesai_tanpa_kunjungan(): void
    {
        $pasien = $this->pasien(['nama_pasien' => 'Pasien Selesai Tanpa Berkas']);
        Antrean::create([
            'hospital_id' => 1,
            'id_pasien' => $pasien->id_pasien,
            'tanggal_kunjungan' => now('Asia/Jakarta')->toDateString(),
            'status' => 'Selesai',
        ]);

        $this->actingAs($this->dokter)->get(route('dokter.dashboard'))
            ->assertOk()
            ->assertSee('Pasien Selesai Tanpa Berkas')
            ->assertSee('Tanpa berkas');
    }

    public function test_salam_login_tidak_bertumpuk_di_halaman_lengkapi_profil(): void
    {
        $this->dokter->update(['nomor_sip' => null]);

        $this->post('/login', ['email' => 'uji.poles.dokter@medscribe.id', 'password' => 'password123'])
            ->assertRedirect(route('profil'));

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Lengkapi profil Anda')
            ->assertDontSee('Selamat datang kembali');
    }
}

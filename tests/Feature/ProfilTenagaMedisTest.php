<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfilController;
use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfilTenagaMedisTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $dokter;
    private Pengguna $perawat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = $this->buatTenagaMedis('dokter', 'dr. Uji Profil', 'uji.profil.dokter@medscribe.id');
        $this->perawat = $this->buatTenagaMedis('perawat', 'Ns. Uji Profil', 'uji.profil.perawat@medscribe.id');
    }

    private function buatTenagaMedis(string $role, string $nama, string $email, ?string $nomorSip = null): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap' => $nama,
            'email' => $email,
            'role' => $role,
            'nomor_sip' => $nomorSip,
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);
    }

    private function login(string $email): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', ['email' => $email, 'password' => 'password123']);
    }

    // ---------- Halaman profil ----------

    public function test_dokter_melihat_label_sip_dan_perawat_melihat_label_sipp(): void
    {
        $this->actingAs($this->dokter)->get(route('profil'))
            ->assertOk()
            ->assertSee('No. SIP (Surat Izin Praktik)');

        $this->actingAs($this->perawat)->get(route('profil'))
            ->assertOk()
            ->assertSee('No. SIPP (Surat Izin Praktik Perawat)');
    }

    public function test_tenaga_medis_dapat_memperbarui_nama_dan_nomor_izin(): void
    {
        $response = $this->actingAs($this->perawat)->put(route('profil.update'), [
            'nama_lengkap' => '  Ns. Uji Profil, S.Kep.  ',
            'nomor_sip' => '503/5678/SIPP/436.7.2/2026',
        ]);

        $response->assertRedirect(route('profil'));
        $response->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->perawat->refresh();
        $this->assertSame('Ns. Uji Profil, S.Kep.', $this->perawat->nama_lengkap);
        $this->assertSame('503/5678/SIPP/436.7.2/2026', $this->perawat->nomor_sip);
    }

    public function test_validasi_profil_menolak_nama_kosong_dan_nomor_tidak_valid(): void
    {
        $response = $this->actingAs($this->dokter)->put(route('profil.update'), [
            'nama_lengkap' => '   ',
            'nomor_sip' => '<script>',
        ]);

        $response->assertSessionHasErrors(['nama_lengkap', 'nomor_sip']);
        $this->assertSame('dr. Uji Profil', $this->dokter->fresh()->nama_lengkap);
    }

    public function test_admin_tidak_dapat_mengubah_profil_tenaga_medis(): void
    {
        $admin = Pengguna::create([
            'nama_lengkap' => 'Admin Uji Profil',
            'email' => 'uji.profil.admin@medscribe.id',
            'role' => 'admin',
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin)->get(route('profil'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->put(route('profil.update'), ['nama_lengkap' => 'X'])->assertForbidden();
    }

    // ---------- Arahan ke profil setelah login ----------

    public function test_login_tenaga_medis_tanpa_nomor_izin_diarahkan_ke_profil(): void
    {
        $this->login('uji.profil.dokter@medscribe.id')->assertRedirect(route('profil'));

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Lengkapi profil Anda')
            ->assertSee('Lewati dulu');
    }

    public function test_lewati_dulu_berlaku_untuk_sesi_ini_saja(): void
    {
        $this->login('uji.profil.perawat@medscribe.id')->assertRedirect(route('profil'));

        $this->post(route('profil.lewati'))->assertRedirect(route('perawat.dashboard'));
        $this->assertTrue(session(ProfilController::SESSION_LEWATI));

        // Selama sesi masih sama, dashboard bisa dibuka tanpa diarahkan lagi.
        $this->get(route('perawat.dashboard'))->assertOk();

        // Setelah keluar dan masuk lagi, profil muncul lagi.
        $this->post(route('logout'));
        $this->login('uji.profil.perawat@medscribe.id')->assertRedirect(route('profil'));
    }

    public function test_simpan_dari_alur_lengkapi_langsung_ke_dashboard(): void
    {
        $this->login('uji.profil.dokter@medscribe.id');

        $this->put(route('profil.update'), [
            'nama_lengkap' => 'dr. Uji Profil',
            'nomor_sip' => '503/1234/SIP-D/436.7.2/2026',
            'lanjut' => 1,
        ])->assertRedirect(route('dokter.dashboard'));

        $this->post(route('logout'));
        $this->login('uji.profil.dokter@medscribe.id')->assertRedirect(route('dokter.dashboard'));
    }

    public function test_login_tenaga_medis_dengan_nomor_izin_langsung_ke_dashboard(): void
    {
        $this->buatTenagaMedis('perawat', 'Ns. Sudah Lengkap', 'uji.profil.lengkap@medscribe.id', '503/9/SIPP');

        $this->login('uji.profil.lengkap@medscribe.id')->assertRedirect(route('perawat.dashboard'));
    }

    // ---------- Profil RS oleh admin ----------

    public function test_admin_dapat_memperbarui_alamat_dan_kontak_rs_sendiri(): void
    {
        $hospital = Hospital::find(1);
        $admin = Pengguna::create([
            'nama_lengkap' => 'Admin Uji RS',
            'email' => 'uji.rs.admin@medscribe.id',
            'role' => 'admin',
            'hospital_id' => $hospital->id,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);
        $namaAwal = $hospital->hospital_name;

        $response = $this->actingAs($admin)->put(route('admin.hospital.update'), [
            'address' => ' Jl. Mayjen Prof. Dr. Moestopo 6-8 ',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
            'telepon' => '(031) 5916290',
            'email' => 'info@rsua.co.id',
            'hospital_name' => 'Nama Diganti',
        ]);

        $response->assertRedirect(route('admin.hospital.info'));
        $hospital->refresh();
        $this->assertSame('Jl. Mayjen Prof. Dr. Moestopo 6-8', $hospital->address);
        $this->assertSame('(031) 5916290', $hospital->telepon);
        $this->assertSame('info@rsua.co.id', $hospital->email);
        $this->assertSame($namaAwal, $hospital->hospital_name);
    }

    public function test_validasi_profil_rs_menolak_kontak_tidak_valid(): void
    {
        $admin = Pengguna::create([
            'nama_lengkap' => 'Admin Uji RS',
            'email' => 'uji.rs.admin2@medscribe.id',
            'role' => 'admin',
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin)->put(route('admin.hospital.update'), [
            'city' => '',
            'province' => 'Jawa Timur',
            'telepon' => 'telp-abc',
            'email' => 'bukan-email',
        ])->assertSessionHasErrors(['city', 'telepon', 'email']);
    }

    public function test_tenaga_medis_tidak_dapat_mengubah_profil_rs(): void
    {
        $this->actingAs($this->dokter)->put(route('admin.hospital.update'), [
            'city' => 'Kota Lain',
            'province' => 'Jawa Timur',
        ])->assertForbidden();
    }
}

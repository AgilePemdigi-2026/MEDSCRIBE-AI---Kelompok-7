<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DokterProfilTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $dokter;
    private Pengguna $perawat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = Pengguna::create([
            'nama_lengkap' => 'dr. Uji Profil',
            'email' => 'uji.profil.dokter@medscribe.id',
            'role' => 'dokter',
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);

        $this->perawat = Pengguna::create([
            'nama_lengkap' => 'Ns. Uji Profil',
            'email' => 'uji.profil.perawat@medscribe.id',
            'role' => 'perawat',
            'hospital_id' => 1,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_dokter_dapat_membuka_halaman_profil(): void
    {
        $response = $this->actingAs($this->dokter)->get(route('dokter.profil'));

        $response->assertOk();
        $response->assertSee('Profil dokter');
        $response->assertSee('No. SIP (Surat Izin Praktik)');
        $response->assertSee('dr. Uji Profil');
    }

    public function test_dokter_dapat_memperbarui_nama_dan_nomor_sip(): void
    {
        $response = $this->actingAs($this->dokter)->put(route('dokter.profil.update'), [
            'nama_lengkap' => '  dr. Uji Profil, Sp.PD  ',
            'nomor_sip' => '503/1234/SIP-D/436.7.2/2026',
        ]);

        $response->assertRedirect(route('dokter.profil'));
        $response->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->dokter->refresh();
        $this->assertSame('dr. Uji Profil, Sp.PD', $this->dokter->nama_lengkap);
        $this->assertSame('503/1234/SIP-D/436.7.2/2026', $this->dokter->nomor_sip);
    }

    public function test_nomor_sip_boleh_dikosongkan(): void
    {
        $this->dokter->update(['nomor_sip' => '503/1/SIP']);

        $this->actingAs($this->dokter)->put(route('dokter.profil.update'), [
            'nama_lengkap' => 'dr. Uji Profil',
            'nomor_sip' => '',
        ])->assertSessionDoesntHaveErrors();

        $this->assertNull($this->dokter->fresh()->nomor_sip);
    }

    public function test_validasi_profil_menolak_nama_kosong_dan_sip_tidak_valid(): void
    {
        $response = $this->actingAs($this->dokter)->put(route('dokter.profil.update'), [
            'nama_lengkap' => '   ',
            'nomor_sip' => '<script>',
        ]);

        $response->assertSessionHasErrors(['nama_lengkap', 'nomor_sip']);
        $this->assertSame('dr. Uji Profil', $this->dokter->fresh()->nama_lengkap);
    }

    public function test_perawat_tidak_dapat_membuka_profil_dokter(): void
    {
        $this->actingAs($this->perawat)->get(route('dokter.profil'))->assertRedirect();
        $this->actingAs($this->perawat)->put(route('dokter.profil.update'), [
            'nama_lengkap' => 'Diubah perawat',
        ])->assertForbidden();

        $this->assertSame('Ns. Uji Profil', $this->perawat->fresh()->nama_lengkap);
    }
}

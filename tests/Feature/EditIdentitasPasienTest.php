<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Perawat memperbaiki identitas pasien (salah tulis, atau pasien tidak sadar yang identitasnya
 * baru diketahui) tanpa membuat pasien baru. No. RM dan rumah sakit tidak boleh berubah.
 */
class EditIdentitasPasienTest extends TestCase
{
    use DatabaseTransactions;

    protected Hospital $rsA;
    protected Hospital $rsB;
    protected Pengguna $perawatA;
    protected Pasien $pasien;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rsA = $this->buatRs('RS Uji Identitas A');
        $this->rsB = $this->buatRs('RS Uji Identitas B');
        $this->perawatA = $this->buatPengguna('perawat', $this->rsA);

        // Pasien darurat: hanya nama sementara yang diketahui saat datang.
        $this->pasien = Pasien::create([
            'hospital_id'    => $this->rsA->id,
            'no_rekam_medis' => 'TST-' . uniqid(),
            'nama_pasien'    => 'Pasien Tanpa Identitas',
        ]);
    }

    private function buatRs(string $nama): Hospital
    {
        return Hospital::create([
            'hospital_code' => 'TST-' . uniqid(),
            'hospital_name' => $nama,
            'hospital_type' => 'RSU',
            'is_active'     => true,
        ]);
    }

    private function buatPengguna(string $role, Hospital $rs): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap'      => ucfirst($role) . ' ' . $rs->hospital_name,
            'email'             => $role . '.' . uniqid() . '@medscribe.test',
            'role'              => $role,
            'hospital_id'       => $rs->id,
            'nomor_sip'         => '503/' . uniqid() . '/SIPP',
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ]);
    }

    private function dataLengkap(array $ubah = []): array
    {
        return array_merge([
            'nama_pasien'   => 'Siti Aminah',
            'tanggal_lahir' => '1990-03-15',
            'umur'          => 36,
            'jenis_kelamin' => 'Perempuan',
            'no_hp'         => '081234567890',
            'nik'           => '3578011503900001',
            'no_bpjs'       => '0001234567890',
        ], $ubah);
    }

    public function test_perawat_bisa_melengkapi_identitas_pasien(): void
    {
        $noRmAwal = $this->pasien->no_rekam_medis;

        $response = $this->actingAs($this->perawatA)
            ->put(route('perawat.pasien.update', $this->pasien->id_pasien), $this->dataLengkap());

        $response->assertRedirect(route('perawat.pasien.show', $this->pasien->id_pasien));
        $response->assertSessionHas('status');

        $pasien = $this->pasien->fresh();
        $this->assertSame('Siti Aminah', $pasien->nama_pasien);
        $this->assertSame('1990-03-15', $pasien->tanggal_lahir->toDateString());
        $this->assertSame(36, $pasien->umur);
        $this->assertSame('Perempuan', $pasien->jenis_kelamin);
        $this->assertSame('081234567890', $pasien->no_hp);
        $this->assertSame('3578011503900001', $pasien->nik);
        $this->assertSame('0001234567890', $pasien->no_bpjs);
        $this->assertSame($noRmAwal, $pasien->no_rekam_medis);
    }

    public function test_no_rm_dan_rumah_sakit_tidak_bisa_diubah_lewat_form(): void
    {
        $noRmAwal = $this->pasien->no_rekam_medis;

        $this->actingAs($this->perawatA)->put(
            route('perawat.pasien.update', $this->pasien->id_pasien),
            $this->dataLengkap(['no_rekam_medis' => 'PALSU-001', 'hospital_id' => $this->rsB->id])
        );

        $pasien = $this->pasien->fresh();
        $this->assertSame($noRmAwal, $pasien->no_rekam_medis);
        $this->assertSame($this->rsA->id, $pasien->hospital_id);
    }

    public function test_umur_dihitung_dari_tanggal_lahir_kalau_dikosongkan(): void
    {
        $tanggalLahir = now('Asia/Jakarta')->subYears(25)->subDay()->toDateString();

        $this->actingAs($this->perawatA)->put(
            route('perawat.pasien.update', $this->pasien->id_pasien),
            $this->dataLengkap(['tanggal_lahir' => $tanggalLahir, 'umur' => ''])
        );

        $this->assertSame(25, $this->pasien->fresh()->umur);
    }

    public function test_nik_dan_bpjs_dibersihkan_dari_spasi_dan_tanda_hubung(): void
    {
        $this->actingAs($this->perawatA)->put(
            route('perawat.pasien.update', $this->pasien->id_pasien),
            $this->dataLengkap(['nik' => '3578 0115-0390 0001', 'no_bpjs' => '0001-2345-67890'])
        );

        $pasien = $this->pasien->fresh();
        $this->assertSame('3578011503900001', $pasien->nik);
        $this->assertSame('0001234567890', $pasien->no_bpjs);
    }

    public function test_isian_tidak_valid_ditolak_dan_dialog_edit_terbuka_lagi(): void
    {
        $halamanDetail = route('perawat.pasien.show', $this->pasien->id_pasien);

        $response = $this->actingAs($this->perawatA)
            ->from($halamanDetail)
            ->put(route('perawat.pasien.update', $this->pasien->id_pasien), $this->dataLengkap(['nik' => '12345', 'nama_pasien' => '   ']));

        $response->assertRedirect($halamanDetail);
        // Error masuk ke bag terpisah supaya dialog "Input Pasien Baru" tidak ikut terbuka.
        $response->assertSessionHasErrorsIn('editPasien', ['nik', 'nama_pasien']);
        $this->assertSame('Pasien Tanpa Identitas', $this->pasien->fresh()->nama_pasien);

        $this->actingAs($this->perawatA)
            ->from($halamanDetail)
            ->followingRedirects()
            ->put(route('perawat.pasien.update', $this->pasien->id_pasien), $this->dataLengkap(['nik' => '12345']))
            ->assertSee('NIK harus 16 digit angka.')
            ->assertSee("openDialog?.('modalEditPasien')", false);
    }

    public function test_tombol_edit_tersedia_di_daftar_pasien_dan_detail(): void
    {
        $this->actingAs($this->perawatA)->get(route('perawat.pasien.show', $this->pasien->id_pasien))
            ->assertOk()
            ->assertSee('Edit identitas')
            ->assertSee(route('perawat.pasien.update', $this->pasien->id_pasien), false);

        $this->actingAs($this->perawatA)->get(route('perawat.dashboard', ['tab' => 'pasien']))
            ->assertOk()
            ->assertSee(route('perawat.pasien.show', ['id' => $this->pasien->id_pasien, 'edit' => 1]), false);
    }

    public function test_perawat_rs_lain_tidak_bisa_mengubah_identitas(): void
    {
        $perawatB = $this->buatPengguna('perawat', $this->rsB);

        $this->actingAs($perawatB)
            ->put(route('perawat.pasien.update', $this->pasien->id_pasien), $this->dataLengkap())
            ->assertForbidden();

        $this->assertSame('Pasien Tanpa Identitas', $this->pasien->fresh()->nama_pasien);
    }

    public function test_dokter_tidak_bisa_mengubah_identitas_pasien(): void
    {
        $dokter = $this->buatPengguna('dokter', $this->rsA);

        $this->actingAs($dokter)
            ->put(route('perawat.pasien.update', $this->pasien->id_pasien), $this->dataLengkap())
            ->assertForbidden();

        $this->assertSame('Pasien Tanpa Identitas', $this->pasien->fresh()->nama_pasien);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\MutasiTenagaMedis;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MutasiTenagaMedisTest extends TestCase
{
    use DatabaseTransactions;

    private Hospital $rsA;
    private Hospital $rsB;
    private Hospital $rsC;
    private Pengguna $adminA;
    private Pengguna $adminB;
    private Pengguna $adminC;
    private Pengguna $dokter;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->rsA, $this->rsB, $this->rsC] = Hospital::where('is_active', true)->orderBy('id')->take(3)->get()->all();
        $this->adminA = $this->akun('admin', 'uji.mutasi.admin.a@medscribe.id', $this->rsA);
        $this->adminB = $this->akun('admin', 'uji.mutasi.admin.b@medscribe.id', $this->rsB);
        $this->adminC = $this->akun('admin', 'uji.mutasi.admin.c@medscribe.id', $this->rsC);
        $this->dokter = $this->akun('dokter', 'uji.mutasi.dokter@medscribe.id', $this->rsA, '503/4000/SIP-D/2026');
    }

    private function akun(string $role, string $email, Hospital $rs, ?string $sip = null): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap' => 'Uji Mutasi ' . $role,
            'email' => $email,
            'role' => $role,
            'nomor_sip' => $sip,
            'hospital_id' => $rs->id,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);
    }

    private function lepasKeB(): MutasiTenagaMedis
    {
        $this->actingAs($this->adminA)->post(route('admin.mutasi.lepas', $this->dokter), [
            'hospital_tujuan_id' => $this->rsB->id,
            'alasan' => 'Penugasan baru sesuai SK direktur',
        ])->assertSessionHasNoErrors();

        return MutasiTenagaMedis::where('id_pengguna', $this->dokter->id_pengguna)->latest('id')->firstOrFail();
    }

    public function test_admin_asal_melepas_lalu_admin_tujuan_menyetujui(): void
    {
        $mutasi = $this->lepasKeB();
        $this->assertSame(MutasiTenagaMedis::MENUNGGU, $mutasi->status);
        $this->assertSame((int) $this->rsA->id, (int) $this->dokter->fresh()->hospital_id, 'Selama menunggu staf tetap di RS asal');

        // Admin RS asal tidak bisa menyetujui permintaannya sendiri.
        $this->actingAs($this->adminA)->post(route('admin.mutasi.setujui', $mutasi))->assertForbidden();

        $this->actingAs($this->adminB)->post(route('admin.mutasi.setujui', $mutasi))->assertRedirect();

        $dokter = $this->dokter->fresh();
        $this->assertSame((int) $this->rsB->id, (int) $dokter->hospital_id);
        $this->assertNull($dokter->nomor_sip);
        $this->assertSame(MutasiTenagaMedis::DISETUJUI, $mutasi->fresh()->status);
    }

    public function test_admin_tujuan_menarik_lewat_email_lalu_admin_asal_menyetujui(): void
    {
        $this->actingAs($this->adminB)->get(route('admin.mutasi', ['tab' => 'diajukan', 'email' => $this->dokter->email]))
            ->assertOk()->assertSee($this->dokter->nama_lengkap);

        $this->actingAs($this->adminB)->post(route('admin.mutasi.tarik'), [
            'email' => $this->dokter->email,
            'alasan' => 'Kebutuhan dokter penyakit dalam',
        ])->assertSessionHasNoErrors();

        $mutasi = MutasiTenagaMedis::where('id_pengguna', $this->dokter->id_pengguna)->firstOrFail();
        $this->assertSame(MutasiTenagaMedis::TARIK, $mutasi->arah);

        $this->actingAs($this->adminB)->post(route('admin.mutasi.setujui', $mutasi))->assertForbidden();
        $this->actingAs($this->adminA)->post(route('admin.mutasi.setujui', $mutasi))->assertRedirect();

        $this->assertSame((int) $this->rsB->id, (int) $this->dokter->fresh()->hospital_id);
    }

    public function test_admin_rs_lain_tidak_bisa_ikut_campur(): void
    {
        $mutasi = $this->lepasKeB();

        $this->actingAs($this->adminC)->post(route('admin.mutasi.setujui', $mutasi))->assertForbidden();
        $this->actingAs($this->adminC)->post(route('admin.mutasi.tolak', $mutasi), ['alasan_penolakan' => 'Ikut campur'])->assertForbidden();
        $this->actingAs($this->adminC)->post(route('admin.mutasi.batal', $mutasi))->assertForbidden();

        // Admin RS lain juga tidak bisa melepas staf yang bukan miliknya.
        $this->actingAs($this->adminC)->post(route('admin.mutasi.lepas', $this->dokter), [
            'hospital_tujuan_id' => $this->rsC->id, 'alasan' => 'Ambil paksa staf',
        ])->assertForbidden();

        $this->assertSame(MutasiTenagaMedis::MENUNGGU, $mutasi->fresh()->status);
    }

    public function test_tolak_dan_batal(): void
    {
        $mutasi = $this->lepasKeB();
        $this->actingAs($this->adminB)->post(route('admin.mutasi.tolak', $mutasi), ['alasan_penolakan' => ''])
            ->assertSessionHasErrors('alasan_penolakan');
        $this->actingAs($this->adminB)->post(route('admin.mutasi.tolak', $mutasi), ['alasan_penolakan' => 'Kuota dokter penuh'])
            ->assertRedirect();
        $this->assertSame(MutasiTenagaMedis::DITOLAK, $mutasi->fresh()->status);
        $this->assertSame((int) $this->rsA->id, (int) $this->dokter->fresh()->hospital_id);

        $kedua = $this->lepasKeB();
        $this->actingAs($this->adminA)->post(route('admin.mutasi.batal', $kedua))->assertRedirect();
        $this->assertSame(MutasiTenagaMedis::DIBATALKAN, $kedua->fresh()->status);
    }

    public function test_hanya_satu_permintaan_menunggu_per_staf(): void
    {
        $this->lepasKeB();

        $this->actingAs($this->adminA)->post(route('admin.mutasi.lepas', $this->dokter), [
            'hospital_tujuan_id' => $this->rsC->id, 'alasan' => 'Permintaan kedua',
        ])->assertSessionHas('warning');

        $this->assertSame(1, MutasiTenagaMedis::where('id_pengguna', $this->dokter->id_pengguna)->count());
    }

    public function test_kunjungan_belum_final_dilepas_saat_disetujui(): void
    {
        $pasien = Pasien::create(['hospital_id' => $this->rsA->id, 'no_rekam_medis' => 'MUT-' . uniqid(), 'nama_pasien' => 'Pasien Mutasi']);
        $antrean = Antrean::create(['hospital_id' => $this->rsA->id, 'id_pasien' => $pasien->id_pasien, 'tanggal_kunjungan' => now()->toDateString(), 'status' => 'Sedang Diperiksa']);
        $kunjungan = Kunjungan::create([
            'hospital_id' => $this->rsA->id, 'id_antrean' => $antrean->id_antrean, 'id_pasien' => $pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna, 'tanggal' => now()->toDateString(), 'status' => 'Sedang Diperiksa',
        ]);

        $mutasi = $this->lepasKeB();
        // Admin RS tujuan hanya melihat jumlahnya, bukan nama pasien RS asal.
        $this->actingAs($this->adminB)->get(route('admin.mutasi'))->assertOk()
            ->assertSee('Masih menangani 1 rekam medis yang belum final')
            ->assertDontSee('Pasien Mutasi');

        $this->actingAs($this->adminB)->post(route('admin.mutasi.setujui', $mutasi));

        $this->assertNull($kunjungan->fresh()->id_dokter);
        $this->assertSame(1, $mutasi->fresh()->kunjungan_dilepas);
    }

    public function test_staf_diberi_tahu_sekali_dan_diarahkan_ke_profil(): void
    {
        $mutasi = $this->lepasKeB();
        $this->actingAs($this->adminB)->post(route('admin.mutasi.setujui', $mutasi));
        $this->post(route('logout'));

        $this->post('/login', ['email' => $this->dokter->email, 'password' => 'password123'])
            ->assertRedirect(route('profil'))
            ->assertSessionHas('info', fn ($pesan) => str_contains($pesan, $this->rsB->hospital_name));
        $this->assertNotNull($mutasi->fresh()->dilihat_staf_at);

        // Login berikutnya tidak menampilkan pemberitahuan lagi.
        $this->post(route('logout'));
        $this->post('/login', ['email' => $this->dokter->email, 'password' => 'password123'])
            ->assertSessionMissing('info');
    }

    public function test_badge_menu_mutasi_untuk_admin_tujuan(): void
    {
        $this->lepasKeB();

        $this->actingAs($this->adminB)->get(route('admin.mutasi'))
            ->assertOk()
            ->assertSee('Menunggu keputusan Anda');
        $this->assertSame(1, MutasiTenagaMedis::masukUntuk((int) $this->rsB->id)->count());
        $this->assertSame(0, MutasiTenagaMedis::masukUntuk((int) $this->rsA->id)->count());
    }
}

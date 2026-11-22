<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Finalisasi;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Daftar pasien dan riwayat rekam medis untuk dokter. Rekam medis final boleh dibaca semua
 * dokter di RS yang sama; yang belum final tetap milik dokter pemeriksanya. Perawat dan
 * dokter RS lain tidak boleh membaca.
 */
class RiwayatPasienDokterTest extends TestCase
{
    use DatabaseTransactions;

    protected Hospital $rsA;
    protected Hospital $rsB;
    protected Pengguna $dokterA1;
    protected Pengguna $dokterA2;
    protected Pengguna $dokterB;
    protected Pengguna $perawatA;
    protected Pasien $pasienA;
    protected Pasien $pasienB;
    protected Kunjungan $kunjunganFinal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rsA = $this->buatRs('RS Uji Riwayat A');
        $this->rsB = $this->buatRs('RS Uji Riwayat B');

        $this->dokterA1 = $this->buatPengguna('dokter', $this->rsA, 'dr. Pemeriksa A1');
        $this->dokterA2 = $this->buatPengguna('dokter', $this->rsA, 'dr. Rekan A2');
        $this->dokterB = $this->buatPengguna('dokter', $this->rsB, 'dr. Lain B');
        $this->perawatA = $this->buatPengguna('perawat', $this->rsA, 'Perawat A');

        $this->pasienA = $this->buatPasien($this->rsA, 'Pasien Riwayat Alfa');
        $this->pasienB = $this->buatPasien($this->rsB, 'Pasien Riwayat Beta');

        // Kunjungan lama milik dokter A1 yang sudah final, dengan diagnosis.
        $this->kunjunganFinal = $this->buatKunjungan($this->pasienA, $this->dokterA1, '2026-10-01', Kunjungan::STATUS_FINALISASI);
        DrafSoap::create([
            'id_kunjungan'           => $this->kunjunganFinal->id_kunjungan,
            'id_pengguna'            => $this->dokterA1->id_pengguna,
            'subjective_text'        => 'Demam dua hari.',
            'objective_text'         => 'Suhu 38,5 °C.',
            'assessment_text'        => 'Demam tifoid dalam observasi',
            'plan_text'              => 'Cek widal.',
            'kode_icd10'             => 'A01.0',
            'source_transcript_hash' => hash('sha256', 'riwayat'),
        ]);
        Finalisasi::create([
            'id_kunjungan' => $this->kunjunganFinal->id_kunjungan,
            'id_dokter'    => $this->dokterA1->id_pengguna,
            'status'       => 'Final',
            'FINAL'        => 1,
            'finalized_at' => now(),
            'finalized_by' => $this->dokterA1->id_pengguna,
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

    private function buatPengguna(string $role, Hospital $rs, string $nama): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap'      => $nama,
            'email'             => $role . '.' . uniqid() . '@medscribe.test',
            'role'              => $role,
            'hospital_id'       => $rs->id,
            'nomor_sip'         => '503/' . uniqid() . '/SIP',
            'email_verified_at' => now(),
            'approval_status'   => 'approved',
            'password'          => Hash::make('password123'),
        ]);
    }

    private function buatPasien(Hospital $rs, string $nama): Pasien
    {
        return Pasien::create([
            'hospital_id'    => $rs->id,
            'no_rekam_medis' => 'TST-' . uniqid(),
            'nama_pasien'    => $nama,
            'jenis_kelamin'  => 'Perempuan',
            'umur'           => 40,
        ]);
    }

    private function buatKunjungan(Pasien $pasien, Pengguna $dokter, string $tanggal, string $status): Kunjungan
    {
        $antrean = Antrean::create([
            'hospital_id'       => $pasien->hospital_id,
            'id_pasien'         => $pasien->id_pasien,
            'tanggal_kunjungan' => $tanggal,
            'status'            => $status === Kunjungan::STATUS_FINALISASI ? 'Selesai' : 'Sedang Diperiksa',
        ]);

        return Kunjungan::create([
            'hospital_id' => $pasien->hospital_id,
            'id_antrean'  => $antrean->id_antrean,
            'id_pasien'   => $pasien->id_pasien,
            'id_dokter'   => $dokter->id_pengguna,
            'tanggal'     => $tanggal,
            'status'      => $status,
        ]);
    }

    // ---------- Daftar pasien ----------

    public function test_daftar_pasien_hanya_menampilkan_pasien_rs_dokter(): void
    {
        $this->actingAs($this->dokterA2)->get(route('dokter.pasien.index'))
            ->assertOk()
            ->assertSee('Pasien Riwayat Alfa')
            ->assertDontSee('Pasien Riwayat Beta');
    }

    public function test_daftar_pasien_bisa_dicari_dengan_no_rm(): void
    {
        $lain = $this->buatPasien($this->rsA, 'Pasien Riwayat Gamma');

        $this->actingAs($this->dokterA2)->get(route('dokter.pasien.index', ['cari' => $lain->no_rekam_medis]))
            ->assertOk()
            ->assertSee('Pasien Riwayat Gamma')
            ->assertDontSee('Pasien Riwayat Alfa');
    }

    public function test_perawat_tidak_bisa_membuka_daftar_pasien_dokter(): void
    {
        $this->actingAs($this->perawatA)->get(route('dokter.pasien.index'))
            ->assertRedirect(route('perawat.dashboard'));
        $this->actingAs($this->perawatA)->get(route('dokter.pasien.show', $this->pasienA->id_pasien))
            ->assertRedirect(route('perawat.dashboard'));
    }

    // ---------- Halaman riwayat pasien ----------

    public function test_riwayat_pasien_menampilkan_diagnosis_dan_tautan_rekam_medis_final(): void
    {
        $this->actingAs($this->dokterA2)->get(route('dokter.pasien.show', $this->pasienA->id_pasien))
            ->assertOk()
            ->assertSee('A01.0')
            ->assertSee('Demam tifoid dalam observasi')
            ->assertSee('dr. Pemeriksa A1')
            ->assertSee(route('dokter.soap.preview', ['kunjungan' => $this->kunjunganFinal->id_kunjungan, 'kembali' => 'pasien']), false);
    }

    public function test_riwayat_kunjungan_belum_final_tidak_menampilkan_isi_soap(): void
    {
        $berjalan = $this->buatKunjungan($this->pasienA, $this->dokterA1, '2026-11-20', 'Sedang Diperiksa');
        DrafSoap::create([
            'id_kunjungan'           => $berjalan->id_kunjungan,
            'id_pengguna'            => $this->dokterA1->id_pengguna,
            'subjective_text'        => 'Nyeri kepala.',
            'objective_text'         => 'TD 150/90.',
            'assessment_text'        => 'Draf hipertensi belum diverifikasi',
            'plan_text'              => 'Observasi.',
            'kode_icd10'             => 'I10',
            'source_transcript_hash' => hash('sha256', 'berjalan'),
        ]);

        $this->actingAs($this->dokterA2)->get(route('dokter.pasien.show', $this->pasienA->id_pasien))
            ->assertOk()
            ->assertDontSee('Draf hipertensi belum diverifikasi')
            ->assertDontSee(route('dokter.soap.preview', ['kunjungan' => $berjalan->id_kunjungan, 'kembali' => 'pasien']), false)
            // Bukan dokter pemeriksanya, jadi tidak ada tombol melanjutkan pemeriksaan.
            ->assertDontSee(route('dokter.rekam-medis', $berjalan->id_antrean), false);

        // Dokter pemeriksanya sendiri bisa melanjutkan.
        $this->actingAs($this->dokterA1)->get(route('dokter.pasien.show', $this->pasienA->id_pasien))
            ->assertSee(route('dokter.rekam-medis', $berjalan->id_antrean), false);
    }

    public function test_dokter_rs_lain_tidak_bisa_membuka_riwayat_pasien(): void
    {
        $this->actingAs($this->dokterB)->get(route('dokter.pasien.show', $this->pasienA->id_pasien))
            ->assertForbidden();
    }

    // ---------- Pratinjau rekam medis final ----------

    public function test_dokter_lain_se_rs_bisa_membaca_rekam_medis_final_tanpa_mengambil_alih(): void
    {
        $this->actingAs($this->dokterA2)
            ->get(route('dokter.soap.preview', ['kunjungan' => $this->kunjunganFinal->id_kunjungan, 'kembali' => 'pasien']))
            ->assertOk()
            ->assertSee('Demam tifoid dalam observasi')
            ->assertSee('Kembali ke riwayat pasien')
            ->assertSee(route('dokter.pasien.show', $this->pasienA->id_pasien), false);

        $this->assertSame($this->dokterA1->id_pengguna, $this->kunjunganFinal->fresh()->id_dokter);
    }

    public function test_dokter_pemeriksa_tetap_kembali_ke_rekam_medis_dari_pratinjau(): void
    {
        $this->actingAs($this->dokterA1)->get(route('dokter.soap.preview', $this->kunjunganFinal))
            ->assertOk()
            ->assertSee('Kembali ke rekam medis');
    }

    public function test_dokter_lain_se_rs_tidak_bisa_membuka_rekam_medis_yang_belum_final(): void
    {
        $berjalan = $this->buatKunjungan($this->pasienA, $this->dokterA1, '2026-11-20', 'Sedang Diperiksa');

        $this->actingAs($this->dokterA2)->get(route('dokter.soap.preview', $berjalan))
            ->assertForbidden();
        $this->assertSame($this->dokterA1->id_pengguna, $berjalan->fresh()->id_dokter);
    }

    public function test_dokter_rs_lain_tidak_bisa_membaca_rekam_medis_final(): void
    {
        $this->actingAs($this->dokterB)->get(route('dokter.soap.preview', $this->kunjunganFinal))
            ->assertForbidden();
    }

    // ---------- Panel riwayat di halaman rekam medis ----------

    public function test_halaman_rekam_medis_menampilkan_riwayat_kunjungan_sebelumnya(): void
    {
        $hariIni = $this->buatKunjungan($this->pasienA, $this->dokterA2, now('Asia/Jakarta')->toDateString(), 'Sedang Diperiksa');

        $this->actingAs($this->dokterA2)->get(route('dokter.rekam-medis', $hariIni->id_antrean))
            ->assertOk()
            ->assertSee('Riwayat kunjungan')
            ->assertSee('1 sebelumnya')
            ->assertSee('A01.0')
            ->assertSee(route('dokter.soap.preview', ['kunjungan' => $this->kunjunganFinal->id_kunjungan, 'kembali' => 'pasien']), false)
            ->assertSee(route('dokter.pasien.show', $this->pasienA->id_pasien), false);
    }

    public function test_kunjungan_pertama_menampilkan_keterangan_tanpa_riwayat(): void
    {
        $baru = $this->buatPasien($this->rsA, 'Pasien Baru Pertama');
        $hariIni = $this->buatKunjungan($baru, $this->dokterA2, now('Asia/Jakarta')->toDateString(), 'Sedang Diperiksa');

        $this->actingAs($this->dokterA2)->get(route('dokter.rekam-medis', $hariIni->id_antrean))
            ->assertOk()
            ->assertSee('Ini kunjungan pertama pasien.');
    }
}

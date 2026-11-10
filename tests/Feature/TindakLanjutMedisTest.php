<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\EResep;
use App\Models\Finalisasi;
use App\Models\Kunjungan;
use App\Models\ParameterLoinc;
use App\Models\Pasien;
use App\Models\Pengguna;
use App\Models\RujukanLab;
use App\Models\TindakLanjut;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TindakLanjutMedisTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $dokter;

    private Pasien $pasien;

    private Antrean $antrean;

    private Kunjungan $kunjungan;

    private ParameterLoinc $parameter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = Pengguna::create([
            'nama_lengkap' => 'dr. Tindak Lanjut',
            'email' => 'tindak-lanjut-'.uniqid().'@medscribe.test',
            'role' => 'dokter',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($this->dokter);

        $this->pasien = Pasien::create([
            'no_rekam_medis' => 'TL-'.uniqid(),
            'nama_pasien' => 'Pasien Tindak Lanjut',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
        ]);

        $this->antrean = Antrean::create([
            'id_pasien' => $this->pasien->id_pasien,
            'tanggal_kunjungan' => '2026-11-04',
            'status' => 'Sedang Diperiksa',
        ]);

        $this->kunjungan = Kunjungan::create([
            'id_antrean' => $this->antrean->id_antrean,
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => '2026-11-04',
            'status' => Kunjungan::STATUS_SOAP_TERVERIFIKASI,
        ]);

        DrafSoap::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_pengguna' => $this->dokter->id_pengguna,
            'subjective_text' => 'Demam sejak tiga hari.',
            'objective_text' => 'Suhu 38,6 C.',
            'assessment_text' => 'Faringitis akut.',
            'plan_text' => 'Terapi simptomatik.',
            'kode_icd10' => 'J02.9',
        ]);

        Finalisasi::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => 'Terverifikasi',
            'FINAL' => false,
        ]);

        $this->parameter = ParameterLoinc::updateOrCreate(
            ['kode_loinc' => '58410-2'],
            [
                'nama_pemeriksaan' => 'Darah Lengkap',
                'kategori' => 'Hematologi',
                'is_active' => true,
            ]
        );
    }

    public function test_halaman_rekam_medis_menampilkan_form_tindak_lanjut_dinamis(): void
    {
        $response = $this->get(route('dokter.rekam-medis', $this->antrean->id_antrean));

        $response->assertOk()
            ->assertSee('Tindak Lanjut Medis')
            ->assertSee('Buat E-Resep')
            ->assertSee('Buat Surat Rujukan Lab')
            ->assertSee('Tanpa Tindak Lanjut')
            ->assertSee('Darah Lengkap')
            ->assertSee('resep[0][nama_obat]', false);
    }

    public function test_finalisasi_legacy_terverifikasi_tidak_mengunci_form_tindak_lanjut(): void
    {
        Finalisasi::where('id_kunjungan', $this->kunjungan->id_kunjungan)->update([
            'status' => 'Terverifikasi',
            'FINAL' => true,
        ]);

        $response = $this->get(route('dokter.rekam-medis', $this->antrean->id_antrean));

        $response->assertOk()
            ->assertSee('tindak-lanjut-form', false)
            ->assertDontSee('Verifikasi draf SOAP untuk membuka tindak lanjut medis.');
    }

    public function test_resep_obat_saja_tersimpan_dengan_status_resep_tersimpan(): void
    {
        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep'],
            'resep' => [$this->obatParacetamol()],
        ]);

        $response->assertCreated()->assertJson([
            'success' => true,
            'message' => 'Resep obat berhasil disimpan.',
            'status' => Kunjungan::STATUS_RESEP_TERSIMPAN,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_RESEP,
        ]);

        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();

        $this->assertSame(TindakLanjut::JENIS_RESEP, $tindakLanjut->jenis_tindak_lanjut);
        $this->assertSame(1, $tindakLanjut->eResep()->count());
        $this->assertDatabaseHas('e_resep', [
            'id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut,
            'id_pasien' => $this->pasien->id_pasien,
            'nama_obat' => 'Paracetamol',
            'dosis' => '500 mg',
            'bentuk_sediaan' => 'Tablet',
            'jumlah' => 10,
            'aturan_konsumsi' => '3x Sehari',
        ]);
        $this->assertSame(Kunjungan::STATUS_RESEP_TERSIMPAN, $this->kunjungan->fresh()->status);
    }

    public function test_resep_obat_dan_rujukan_lab_tersimpan_pada_induk_yang_sama(): void
    {
        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep', 'rujukan'],
            'resep' => [$this->obatParacetamol()],
            'id_parameter_loinc' => $this->parameter->id_parameter_loinc,
            'catatan_rujukan' => 'Periksa darah lengkap untuk evaluasi infeksi.',
        ]);

        $response->assertCreated()->assertJson([
            'success' => true,
            'message' => 'Resep obat dan rujukan lab berhasil disimpan.',
            'status' => Kunjungan::STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_RESEP_RUJUKAN,
        ]);

        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();
        $rujukan = RujukanLab::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->firstOrFail();

        $this->assertSame(1, $tindakLanjut->eResep()->count());
        $this->assertSame('Periksa darah lengkap untuk evaluasi infeksi.', $rujukan->catatan);
        $this->assertSame(1, $rujukan->parameter()->count());
        $this->assertDatabaseHas('rujukan_lab_parameter', [
            'id_rujukan_lab' => $rujukan->id_rujukan_lab,
            'id_parameter_loinc' => $this->parameter->id_parameter_loinc,
            'nama_pemeriksaan' => 'Darah Lengkap',
            'kode_loinc' => '58410-2',
        ]);
        $this->assertSame(
            Kunjungan::STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN,
            $this->kunjungan->fresh()->status
        );
    }

    public function test_tanpa_tindakan_lanjut_tidak_membuat_resep_maupun_rujukan_lab(): void
    {
        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['tanpa_tindakan'],
        ]);

        $response->assertCreated()->assertJson([
            'success' => true,
            'message' => 'Rekam medis pasien telah ditandai selesai tanpa tindakan lanjutan.',
            'status' => Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_TANPA_TINDAKAN,
        ]);

        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();

        $this->assertSame(0, $tindakLanjut->eResep()->count());
        $this->assertNull($tindakLanjut->rujukanLab);
        $this->assertSame(
            Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT,
            $this->kunjungan->fresh()->status
        );
    }

    public function test_validasi_resep_tidak_lengkap_mengembalikan_422_tanpa_data_parsial(): void
    {
        $obat = $this->obatParacetamol();
        unset($obat['aturan_konsumsi']);

        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep'],
            'resep' => [$obat],
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'Lengkapi field wajib sebelum validasi.',
        ]);

        $this->assertDatabaseMissing('tindak_lanjut', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
        ]);
        $this->assertSame(0, EResep::where('id_pasien', $this->pasien->id_pasien)->count());
        $this->assertSame(Kunjungan::STATUS_SOAP_TERVERIFIKASI, $this->kunjungan->fresh()->status);
    }

    public function test_validasi_rujukan_lab_tanpa_parameter_ditolak_tanpa_data_parsial(): void
    {
        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['rujukan'],
            'catatan_rujukan' => 'Evaluasi fungsi ginjal.',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'Lengkapi field wajib sebelum validasi.',
        ]);

        $this->assertDatabaseMissing('tindak_lanjut', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
        ]);
        $this->assertDatabaseMissing('rujukan_lab', [
            'catatan' => 'Evaluasi fungsi ginjal.',
        ]);
        $this->assertSame(Kunjungan::STATUS_SOAP_TERVERIFIKASI, $this->kunjungan->fresh()->status);
    }

    public function test_soap_belum_terverifikasi_ditolak_backend(): void
    {
        $this->kunjungan->update(['status' => 'Sedang Diperiksa']);

        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep'],
            'resep' => [$this->obatParacetamol()],
        ]);

        $response->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Draf SOAP harus diverifikasi sebelum tindak lanjut disimpan.',
        ]);

        $this->assertDatabaseMissing('tindak_lanjut', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
        ]);
    }

    public function test_tanpa_tindakan_tidak_boleh_digabung_dengan_resep_atau_rujukan(): void
    {
        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep', 'tanpa_tindakan'],
            'resep' => [$this->obatParacetamol()],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('opsi')
            ->assertJsonPath('message', 'Lengkapi field wajib sebelum validasi.');

        $this->assertDatabaseMissing('tindak_lanjut', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
        ]);
    }

    public function test_kunjungan_terkunci_ditolak_diubah(): void
    {
        Finalisasi::where('id_kunjungan', $this->kunjungan->id_kunjungan)->update([
            'status' => 'Terkunci',
            'FINAL' => true,
        ]);

        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep'],
            'resep' => [$this->obatParacetamol()],
        ]);

        $response->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Rekam medis sudah difinalisasi dan tidak dapat diubah.',
        ]);

        $this->assertDatabaseMissing('tindak_lanjut', [
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
        ]);
    }

    public function test_banyak_item_obat_tersimpan_pada_satu_induk_tindak_lanjut(): void
    {
        $response = $this->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi' => ['resep'],
            'resep' => [
                $this->obatParacetamol(),
                [
                    'nama_obat' => 'Amoksisilin',
                    'dosis' => '500 mg',
                    'bentuk_sediaan' => 'Kapsul',
                    'jumlah' => 15,
                    'aturan_konsumsi' => '3x Sehari',
                ],
            ],
        ]);

        $response->assertCreated();

        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();

        $this->assertSame(2, $tindakLanjut->eResep()->count());
        $this->assertSame(2, EResep::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->count());
        $this->assertDatabaseHas('e_resep', [
            'id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut,
            'nama_obat' => 'Amoksisilin',
        ]);
    }

    private function obatParacetamol(): array
    {
        return [
            'nama_obat' => 'Paracetamol',
            'dosis' => '500 mg',
            'bentuk_sediaan' => 'Tablet',
            'jumlah' => 10,
            'aturan_konsumsi' => '3x Sehari',
        ];
    }
}

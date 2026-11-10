<?php

namespace Tests\BDD;

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

/**
 * BDD Test Suite: Fitur 07 - Kelola Tindak Lanjut Medis (E-Resep dan Rujukan Lab)
 * Mengimplementasikan pengujian berbasis perilaku sesuai features/07_tindak_lanjut_medis.feature
 */
class TindakLanjutMedisBDDTest extends BDDTestCase
{
    protected Pengguna $dokter;
    protected Pasien $pasien;
    protected Antrean $antrean;
    protected Kunjungan $kunjungan;
    protected ParameterLoinc $parameter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = $this->createDokter();

        $this->pasien = Pasien::create([
            'hospital_id'    => $this->dokter->hospital_id,
            'no_rekam_medis' => 'BDD-TL-' . uniqid(),
            'nama_pasien'    => 'Pasien Tindak Lanjut BDD',
            'jenis_kelamin'  => 'Perempuan',
        ]);

        $this->antrean = Antrean::create([
            'hospital_id'       => $this->dokter->hospital_id,
            'id_pasien'         => $this->pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status'            => 'Sedang Diperiksa',
        ]);

        $this->kunjungan = Kunjungan::create([
            'hospital_id' => $this->dokter->hospital_id,
            'id_antrean'  => $this->antrean->id_antrean,
            'id_pasien'   => $this->pasien->id_pasien,
            'id_dokter'   => $this->dokter->id_pengguna,
            'tanggal'     => now()->toDateString(),
            'status'      => 'Sedang Diperiksa',
        ]);

        $this->parameter = ParameterLoinc::updateOrCreate(
            ['kode_loinc' => '58410-2'],
            [
                'nama_pemeriksaan' => 'Darah Lengkap',
                'kategori'         => 'Hematologi',
                'is_active'        => true,
            ]
        );
    }

    /**
     * Skenario: Bagian tindak lanjut terkunci sebelum draf SOAP diverifikasi
     * @scenario Follow-up Section Locked Before SOAP Is Verified
     */
    public function test_skenario_tindak_lanjut_terkunci_sebelum_soap_diverifikasi(): void
    {
        $this->given('Draf SOAP pasien belum diverifikasi dokter');

        $this->when('Dokter membuka halaman rekam medis pasien');
        $response = $this->actingAs($this->dokter)->get(route('dokter.rekam-medis', $this->antrean->id_antrean));

        $this->then('Bagian "Kelola Tindak Lanjut Medis" masih terkunci');
        $response->assertOk();
        $response->assertSee('Kelola Tindak Lanjut Medis');
        $response->assertSee('Verifikasi draf SOAP untuk membuka tindak lanjut medis.');
        $response->assertDontSee('tindak-lanjut-form', false);

        $this->and('Sistem menolak penyimpanan tindak lanjut yang dikirim langsung');
        $simpan = $this->actingAs($this->dokter)->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi'  => ['resep'],
            'resep' => [$this->obatParacetamol()],
        ]);
        $simpan->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Draf SOAP harus diverifikasi sebelum tindak lanjut disimpan.',
        ]);
        $this->assertDatabaseMissing('tindak_lanjut', ['id_kunjungan' => $this->kunjungan->id_kunjungan]);
    }

    /**
     * Skenario: Form tindak lanjut terbuka setelah draf SOAP diverifikasi
     * @scenario Follow-up Form Opens After SOAP Is Verified
     */
    public function test_skenario_form_tindak_lanjut_terbuka_setelah_soap_diverifikasi(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter membuka halaman rekam medis pasien');
        $response = $this->actingAs($this->dokter)->get(route('dokter.rekam-medis', $this->antrean->id_antrean));

        $this->then('Form tindak lanjut menampilkan tiga pilihan beserta parameter LOINC');
        $response->assertOk();
        $response->assertSee('tindak-lanjut-form', false);
        $response->assertSee('Buat E-Resep');
        $response->assertSee('Buat Surat Rujukan Lab');
        $response->assertSee('Tanpa Tindak Lanjut');
        $response->assertSee('Darah Lengkap');
        $response->assertSee('Validasi &amp; Simpan', false);
    }

    /**
     * Skenario: Dokter menyimpan e-resep berisi beberapa obat
     * @scenario Save E-Prescription With Multiple Medicines
     */
    public function test_skenario_dokter_menyimpan_e_resep_beberapa_obat(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter memilih "Buat E-Resep", mengisi dua obat, lalu menekan "Validasi & Simpan"');
        $response = $this->simpanTindakLanjut([
            'opsi'  => ['resep'],
            'resep' => [
                $this->obatParacetamol(),
                [
                    'nama_obat'       => 'Amoksisilin',
                    'dosis'           => '500 mg',
                    'bentuk_sediaan'  => 'Kapsul',
                    'jumlah'          => 15,
                    'aturan_konsumsi' => '3x Sehari',
                ],
            ],
        ]);

        $this->then('Sistem menyimpan resep dan memberi pesan sukses');
        $response->assertCreated()->assertJson([
            'success'             => true,
            'message'             => 'Resep obat berhasil disimpan.',
            'status'              => Kunjungan::STATUS_RESEP_TERSIMPAN,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_RESEP,
        ]);

        $this->and('Kedua obat tersimpan pada satu tindak lanjut milik pasien');
        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();
        $this->assertSame(2, EResep::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->count());
        $this->assertDatabaseHas('e_resep', [
            'id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut,
            'id_pasien'        => $this->pasien->id_pasien,
            'nama_obat'        => 'Paracetamol',
            'aturan_konsumsi'  => '3x Sehari',
        ]);

        $this->and('Status kunjungan menjadi "Resep Tersimpan" dan halaman menampilkan ringkasan resep');
        $this->assertSame(Kunjungan::STATUS_RESEP_TERSIMPAN, $this->kunjungan->fresh()->status);
        $this->bukaRekamMedis()
            ->assertSee('Resep Obat')
            ->assertSee('Paracetamol')
            ->assertSee('Amoksisilin')
            ->assertDontSee('tindak-lanjut-form', false);
    }

    /**
     * Skenario: Dokter menyimpan rujukan laboratorium dengan parameter LOINC
     * @scenario Save Laboratory Referral
     */
    public function test_skenario_dokter_menyimpan_rujukan_lab(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter memilih "Buat Surat Rujukan Lab" dengan parameter "Darah Lengkap"');
        $response = $this->simpanTindakLanjut([
            'opsi'               => ['rujukan'],
            'id_parameter_loinc' => $this->parameter->id_parameter_loinc,
            'catatan_rujukan'    => 'Evaluasi infeksi.',
        ]);

        $this->then('Sistem menyimpan rujukan lab dan memberi pesan sukses');
        $response->assertCreated()->assertJson([
            'success'             => true,
            'message'             => 'Rujukan lab berhasil disimpan.',
            'status'              => Kunjungan::STATUS_RUJUKAN_LAB_TERSIMPAN,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_RUJUKAN,
        ]);

        $this->and('Rujukan mencatat nama pemeriksaan dan kode LOINC-nya');
        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();
        $rujukan = RujukanLab::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->firstOrFail();
        $this->assertSame('Evaluasi infeksi.', $rujukan->catatan);
        $this->assertDatabaseHas('rujukan_lab_parameter', [
            'id_rujukan_lab'   => $rujukan->id_rujukan_lab,
            'nama_pemeriksaan' => 'Darah Lengkap',
            'kode_loinc'       => '58410-2',
        ]);
        $this->assertSame(0, EResep::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->count());

        $this->and('Status kunjungan menjadi "Rujukan Lab Tersimpan"');
        $this->assertSame(Kunjungan::STATUS_RUJUKAN_LAB_TERSIMPAN, $this->kunjungan->fresh()->status);
        $this->bukaRekamMedis()->assertSee('Rujukan Laboratorium')->assertSee('58410-2');
    }

    /**
     * Skenario: Dokter menyimpan e-resep sekaligus rujukan laboratorium
     * @scenario Save E-Prescription and Laboratory Referral Together
     */
    public function test_skenario_dokter_menyimpan_resep_dan_rujukan_lab(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter memilih "Buat E-Resep" dan "Buat Surat Rujukan Lab" sekaligus');
        $response = $this->simpanTindakLanjut([
            'opsi'               => ['resep', 'rujukan'],
            'resep'              => [$this->obatParacetamol()],
            'id_parameter_loinc' => $this->parameter->id_parameter_loinc,
        ]);

        $this->then('Resep dan rujukan tersimpan pada tindak lanjut yang sama');
        $response->assertCreated()->assertJson([
            'success'             => true,
            'message'             => 'Resep obat dan rujukan lab berhasil disimpan.',
            'status'              => Kunjungan::STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_RESEP_RUJUKAN,
        ]);
        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();
        $this->assertSame(1, EResep::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->count());
        $this->assertDatabaseHas('rujukan_lab', ['id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut]);

        $this->and('Status kunjungan menjadi "Rujukan Lab & Resep Tersimpan"');
        $this->assertSame(Kunjungan::STATUS_RUJUKAN_LAB_RESEP_TERSIMPAN, $this->kunjungan->fresh()->status);
        $this->bukaRekamMedis()->assertSee('Resep Obat + Rujukan Lab');
    }

    /**
     * Skenario: Dokter menandai pasien selesai tanpa tindak lanjut
     * @scenario Finish Visit Without Further Action
     */
    public function test_skenario_dokter_menyelesaikan_tanpa_tindak_lanjut(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter memilih "Tanpa Tindak Lanjut"');
        $response = $this->simpanTindakLanjut(['opsi' => ['tanpa_tindakan']]);

        $this->then('Rekam medis ditandai selesai tanpa resep maupun rujukan');
        $response->assertCreated()->assertJson([
            'success'             => true,
            'message'             => 'Rekam medis pasien telah ditandai selesai tanpa tindakan lanjutan.',
            'status'              => Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_TANPA_TINDAKAN,
        ]);
        $tindakLanjut = TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->firstOrFail();
        $this->assertSame(0, EResep::where('id_tindak_lanjut', $tindakLanjut->id_tindak_lanjut)->count());
        $this->assertDatabaseMissing('rujukan_lab', ['id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut]);

        $this->and('Status kunjungan menjadi "Selesai Tanpa Tindakan Lanjut"');
        $this->assertSame(Kunjungan::STATUS_SELESAI_TANPA_TINDAKAN_LANJUT, $this->kunjungan->fresh()->status);
        $this->bukaRekamMedis()->assertSee('Selesai Tanpa Tindakan Lanjut');
    }

    /**
     * Skenario: "Tanpa Tindak Lanjut" tidak boleh digabung dengan resep atau rujukan
     * @scenario "Tanpa Tindak Lanjut" Cannot Be Combined With Other Options
     */
    public function test_skenario_tanpa_tindak_lanjut_tidak_boleh_digabung(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter mengirim pilihan "Buat E-Resep" bersama "Tanpa Tindak Lanjut"');
        $response = $this->simpanTindakLanjut([
            'opsi'  => ['resep', 'tanpa_tindakan'],
            'resep' => [$this->obatParacetamol()],
        ]);

        $this->then('Sistem menolak dengan pesan pilihan tidak dapat digabung');
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Lengkapi field wajib sebelum validasi.')
            ->assertJsonPath('errors.opsi.0', 'Pilihan Tanpa Tindakan Lanjut tidak dapat digabung dengan resep atau rujukan lab.');

        $this->and('Tidak ada tindak lanjut yang tersimpan');
        $this->assertTindakLanjutTidakTersimpan();
    }

    /**
     * Skenario: E-resep ditolak jika data obat belum lengkap
     * @scenario E-Prescription Rejected When Medicine Data Is Incomplete
     */
    public function test_skenario_e_resep_ditolak_jika_data_obat_tidak_lengkap(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter menyimpan e-resep tanpa mengisi "Aturan Minum"');
        $obat = $this->obatParacetamol();
        unset($obat['aturan_konsumsi']);
        $response = $this->simpanTindakLanjut(['opsi' => ['resep'], 'resep' => [$obat]]);

        $this->then('Sistem menolak dan menandai field "Aturan Minum" yang kosong');
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Lengkapi field wajib sebelum validasi.')
            ->assertJsonPath('errors', ['resep.0.aturan_konsumsi' => ['Aturan minum wajib diisi.']]);

        $this->and('Tidak ada resep parsial yang tersimpan');
        $this->assertTindakLanjutTidakTersimpan();
        $this->assertSame(0, EResep::where('id_pasien', $this->pasien->id_pasien)->count());
    }

    /**
     * Skenario: Rujukan lab ditolak jika parameter pemeriksaan belum dipilih
     * @scenario Laboratory Referral Rejected Without Examination Parameter
     */
    public function test_skenario_rujukan_lab_ditolak_tanpa_parameter(): void
    {
        $this->given('Draf SOAP pasien sudah diverifikasi dokter');
        $this->verifikasiSoap();

        $this->when('Dokter menyimpan rujukan lab tanpa memilih parameter pemeriksaan');
        $response = $this->simpanTindakLanjut([
            'opsi'            => ['rujukan'],
            'catatan_rujukan' => 'Evaluasi fungsi ginjal.',
        ]);

        $this->then('Sistem menolak dan meminta parameter pemeriksaan dipilih');
        $response->assertStatus(422)
            ->assertJsonPath('errors.id_parameter_loinc.0', 'Parameter pemeriksaan laboratorium wajib dipilih.');

        $this->and('Tidak ada rujukan yang tersimpan');
        $this->assertTindakLanjutTidakTersimpan();
        $this->assertDatabaseMissing('rujukan_lab', ['catatan' => 'Evaluasi fungsi ginjal.']);
    }

    /**
     * Skenario: Tindak lanjut tidak dapat disimpan dua kali
     * @scenario Follow-up Cannot Be Saved Twice
     */
    public function test_skenario_tindak_lanjut_tidak_dapat_disimpan_dua_kali(): void
    {
        $this->given('Dokter sudah menyimpan e-resep untuk kunjungan ini');
        $this->verifikasiSoap();
        $this->simpanTindakLanjut(['opsi' => ['resep'], 'resep' => [$this->obatParacetamol()]])->assertCreated();

        $this->when('Dokter mengirim tindak lanjut lagi untuk kunjungan yang sama');
        $response = $this->simpanTindakLanjut(['opsi' => ['tanpa_tindakan']]);

        $this->then('Sistem menolak karena tindak lanjut sudah tersimpan');
        $response->assertStatus(409)->assertJson([
            'success' => false,
            'message' => 'Tindak lanjut untuk kunjungan ini sudah tersimpan.',
        ]);
        $this->assertSame(1, TindakLanjut::where('id_kunjungan', $this->kunjungan->id_kunjungan)->count());
        $this->assertSame(Kunjungan::STATUS_RESEP_TERSIMPAN, $this->kunjungan->fresh()->status);
    }

    /**
     * Skenario: Rekam medis yang sudah difinalisasi tidak dapat diberi tindak lanjut
     * @scenario Finalized Medical Record Cannot Be Changed
     */
    public function test_skenario_rekam_medis_terkunci_tidak_dapat_diubah(): void
    {
        $this->given('Rekam medis kunjungan sudah difinalisasi dan terkunci');
        $this->verifikasiSoap();
        Finalisasi::where('id_kunjungan', $this->kunjungan->id_kunjungan)->update([
            'status' => 'Terkunci',
            'FINAL'  => true,
        ]);

        $this->when('Dokter mencoba menyimpan e-resep');
        $response = $this->simpanTindakLanjut(['opsi' => ['resep'], 'resep' => [$this->obatParacetamol()]]);

        $this->then('Sistem menolak perubahan dengan status 403');
        $response->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Rekam medis sudah difinalisasi dan tidak dapat diubah.',
        ]);
        $this->assertTindakLanjutTidakTersimpan();
    }

    /**
     * Skenario: Dokter rumah sakit lain tidak dapat menyimpan tindak lanjut
     * @scenario Doctor From Another Hospital Is Blocked
     */
    public function test_skenario_dokter_rumah_sakit_lain_ditolak(): void
    {
        $this->given('Draf SOAP pasien RS A sudah diverifikasi, dan ada dokter yang bertugas di RS B');
        $this->verifikasiSoap();
        $dokterLain = $this->createDokter(['hospital' => $this->getSecondaryHospital()]);

        $this->when('Dokter RS B mengirim tindak lanjut untuk kunjungan pasien RS A');
        $response = $this->actingAs($dokterLain)->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), [
            'opsi'  => ['resep'],
            'resep' => [$this->obatParacetamol()],
        ]);

        $this->then('Sistem menolak akses dengan status 403');
        $response->assertStatus(403)->assertJson([
            'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain.',
        ]);
        $this->assertTindakLanjutTidakTersimpan();
    }

    /**
     * Prasyarat: dokter sudah memverifikasi draf SOAP kunjungan ini.
     */
    protected function verifikasiSoap(): void
    {
        DrafSoap::updateOrCreate(
            ['id_kunjungan' => $this->kunjungan->id_kunjungan],
            [
                'id_pengguna'     => $this->dokter->id_pengguna,
                'subjective_text' => 'Demam sejak tiga hari.',
                'objective_text'  => 'Suhu 38,6 C.',
                'assessment_text' => 'Faringitis akut.',
                'plan_text'       => 'Terapi simptomatik.',
                'kode_icd10'      => 'J02.9',
            ]
        );

        Finalisasi::create([
            'id_kunjungan' => $this->kunjungan->id_kunjungan,
            'id_dokter'    => $this->dokter->id_pengguna,
            'status'       => 'Terverifikasi',
            'FINAL'        => false,
        ]);

        $this->kunjungan->update(['status' => Kunjungan::STATUS_SOAP_TERVERIFIKASI]);
    }

    /**
     * Form halaman rekam medis mengirim tindak lanjut lewat fetch dengan header Accept: application/json.
     */
    protected function simpanTindakLanjut(array $payload)
    {
        return $this->actingAs($this->dokter)->postJson(route('dokter.tindak-lanjut.store', $this->kunjungan), $payload);
    }

    protected function bukaRekamMedis()
    {
        return $this->actingAs($this->dokter)->get(route('dokter.rekam-medis', $this->antrean->id_antrean))->assertOk();
    }

    protected function assertTindakLanjutTidakTersimpan(): void
    {
        $this->assertDatabaseMissing('tindak_lanjut', ['id_kunjungan' => $this->kunjungan->id_kunjungan]);
        $this->assertSame(Kunjungan::STATUS_SOAP_TERVERIFIKASI, $this->kunjungan->fresh()->status);
    }

    protected function obatParacetamol(): array
    {
        return [
            'nama_obat'       => 'Paracetamol',
            'dosis'           => '500 mg',
            'bentuk_sediaan'  => 'Tablet',
            'jumlah'          => 10,
            'aturan_konsumsi' => '3x Sehari',
        ];
    }
}

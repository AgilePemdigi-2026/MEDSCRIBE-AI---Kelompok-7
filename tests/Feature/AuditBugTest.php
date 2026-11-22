<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Finalisasi;
use App\Models\Hospital;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Regresi untuk temuan audit bug 22 November.
 */
class AuditBugTest extends TestCase
{
    use DatabaseTransactions;

    private Pengguna $dokter;
    private Pengguna $perawat;
    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dokter = $this->staf('dokter', 'uji.audit.dokter@medscribe.id', 1);
        $this->perawat = $this->staf('perawat', 'uji.audit.perawat@medscribe.id', 1);
        $this->admin = $this->staf('admin', 'uji.audit.admin@medscribe.id', 1);
    }

    private function staf(string $role, string $email, int $hospitalId, string $status = 'approved'): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap' => 'Uji Audit ' . ucfirst($role),
            'email' => $email,
            'role' => $role,
            'nomor_sip' => in_array($role, ['dokter', 'perawat'], true) ? '503/3000/SIP/2026' : null,
            'hospital_id' => $hospitalId,
            'email_verified_at' => now(),
            'approval_status' => $status,
            'password' => bcrypt('password123'),
        ]);
    }

    /** Pasien + antrean + kunjungan di RS tertentu. */
    private function kunjungan(int $hospitalId, string $statusAntrean = 'Siap Periksa', array $kunjunganAttr = []): Kunjungan
    {
        $pasien = Pasien::create([
            'hospital_id' => $hospitalId,
            'no_rekam_medis' => 'AUDIT-' . uniqid(),
            'nama_pasien' => 'Pasien Audit',
        ]);
        $antrean = Antrean::create([
            'hospital_id' => $hospitalId,
            'id_pasien' => $pasien->id_pasien,
            'tanggal_kunjungan' => now('Asia/Jakarta')->toDateString(),
            'status' => $statusAntrean,
        ]);

        return Kunjungan::create(array_merge([
            'hospital_id' => $hospitalId,
            'id_antrean' => $antrean->id_antrean,
            'id_pasien' => $pasien->id_pasien,
            'tanggal' => now('Asia/Jakarta')->toDateString(),
            'status' => 'Menunggu Dokter',
        ], $kunjunganAttr));
    }

    private function ttv(Kunjungan $kunjungan): void
    {
        PemeriksaanAwal::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_perawat' => $this->perawat->id_pengguna,
            'tekanan_darah' => '120/80',
            'nadi' => 80,
            'suhu_tubuh' => 36.6,
            'laju_pernapasan' => 18,
            'saturasi_oksigen' => 98,
        ]);
    }

    // 1. Dokter RS lain tidak boleh "mengklaim" kunjungan lewat pratinjau/finalisasi.
    public function test_dokter_rs_lain_tidak_menjadi_dokter_kunjungan_saat_ditolak(): void
    {
        $rsLain = Hospital::where('id', '!=', 1)->firstOrFail();
        $kunjunganRsLain = $this->kunjungan($rsLain->id);

        $this->actingAs($this->dokter)->get(route('dokter.soap.preview', $kunjunganRsLain))->assertForbidden();
        $this->actingAs($this->dokter)->post(route('dokter.rekam-medis.finalisasi', $kunjunganRsLain))->assertForbidden();

        $this->assertNull($kunjunganRsLain->fresh()->id_dokter);
    }

    // 2. Pasien dengan rekam medis final tidak boleh dihapus perawat.
    public function test_pasien_dengan_rekam_medis_final_tidak_dapat_dihapus(): void
    {
        $kunjungan = $this->kunjungan(1, 'Selesai', ['status' => Kunjungan::STATUS_FINALISASI, 'id_dokter' => $this->dokter->id_pengguna]);
        $this->ttv($kunjungan);
        Finalisasi::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => 'Final',
            'FINAL' => 1,
            'finalized_at' => now(),
            'finalized_by' => $this->dokter->id_pengguna,
        ]);

        $this->actingAs($this->perawat)->delete(route('perawat.pasien.destroy', $kunjungan->id_pasien))
            ->assertSessionHasErrors('error');

        $this->assertNotSoftDeleted('pasien', ['id_pasien' => $kunjungan->id_pasien]);
        $this->assertSame(Kunjungan::STATUS_FINALISASI, $kunjungan->fresh()->status);
    }

    // 4 & 8. Koreksi TTV tidak menimpa Objektif dokter dan tidak mengembalikan antrean ke "Siap Periksa".
    public function test_koreksi_ttv_tidak_menimpa_objektif_dan_status_pemeriksaan(): void
    {
        $kunjungan = $this->kunjungan(1, 'Sedang Diperiksa', ['id_dokter' => $this->dokter->id_pengguna, 'status' => 'Sedang Diperiksa']);
        $this->ttv($kunjungan);
        DrafSoap::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_pengguna' => $this->dokter->id_pengguna,
            'subjective_text' => 'Batuk 3 hari.',
            'objective_text' => 'TD 120/80 mmHg. Faring hiperemis, ronki tidak ada.',
            'assessment_text' => 'ISPA.',
            'plan_text' => 'Edukasi.',
            'source_transcript_hash' => hash('sha256', 'transkrip'),
        ]);

        $this->actingAs($this->perawat)->post(route('perawat.pemeriksaan.store'), [
            'id_antrean' => $kunjungan->id_antrean,
            'tekanan_darah' => '130/85',
            'nadi' => 88,
            'suhu_tubuh' => 37.0,
            'laju_pernapasan' => 20,
            'saturasi_oksigen' => 97,
        ])->assertSessionDoesntHaveErrors();

        $draf = DrafSoap::where('id_kunjungan', $kunjungan->id_kunjungan)->first();
        $this->assertSame('TD 120/80 mmHg. Faring hiperemis, ronki tidak ada.', $draf->objective_text);
        $this->assertTrue((bool) $draf->needs_review);
        $this->assertSame('Sedang Diperiksa', Antrean::find($kunjungan->id_antrean)->status);
    }

    // 3. Buat ulang draf AI setelah SOAP terverifikasi mencabut status terverifikasi.
    public function test_buat_ulang_draf_ai_mencabut_verifikasi_soap(): void
    {
        config(['medscribe.soap_driver' => 'mock']);
        $kunjungan = $this->kunjungan(1, 'Sedang Diperiksa', [
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => Kunjungan::STATUS_SOAP_TERVERIFIKASI,
        ]);
        $this->ttv($kunjungan);
        Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Selesai',
            'transkrip' => 'Dokter: Keluhannya apa? Pasien: Batuk tiga hari.',
            'transcript_reviewed_at' => now(),
            'transcript_verified_by' => $this->dokter->id_pengguna,
        ]);
        Finalisasi::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => 'Terverifikasi',
            'FINAL' => 0,
        ]);

        $this->actingAs($this->dokter)->post(route('dokter.soap.generate', $kunjungan));

        $this->assertSame(Kunjungan::STATUS_SOAP_BELUM_DIVERIFIKASI, $kunjungan->fresh()->status);
        $this->assertSame('Perlu Ditinjau', Finalisasi::where('id_kunjungan', $kunjungan->id_kunjungan)->value('status'));
    }

    // 9. No. RM ganda di form SOAP jadi pesan validasi, bukan error 500.
    public function test_no_rm_ganda_di_form_soap_ditolak_dengan_pesan(): void
    {
        $lain = Pasien::create(['hospital_id' => 1, 'no_rekam_medis' => 'AUDIT-RM-DIPAKAI', 'nama_pasien' => 'Pasien Lain']);
        $kunjungan = $this->kunjungan(1, 'Sedang Diperiksa', ['id_dokter' => $this->dokter->id_pengguna]);
        Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Selesai',
            'transkrip' => 'Transkrip uji.',
            'transcript_reviewed_at' => now(),
            'transcript_verified_by' => $this->dokter->id_pengguna,
        ]);

        $this->actingAs($this->dokter)->put(route('dokter.soap.save', $kunjungan), [
            'no_rekam_medis' => $lain->no_rekam_medis,
            'nama_pasien' => 'Pasien Audit',
            'subjective_text' => 'S',
            'objective_text' => 'O',
            'assessment_text' => 'A',
            'plan_text' => 'P',
        ])->assertSessionHasErrors('no_rekam_medis');
    }

    // 5. Antrean selesai dari hari sebelumnya tidak ikut "hari ini"; yang belum selesai tetap tampil.
    public function test_antrean_hari_ini_tidak_memuat_antrean_selesai_hari_lalu(): void
    {
        $kemarinSelesai = $this->kunjungan(1, 'Selesai');
        Antrean::whereKey($kemarinSelesai->id_antrean)->update(['tanggal_kunjungan' => now('Asia/Jakarta')->subDays(3)->toDateString()]);
        $kemarinTertunda = $this->kunjungan(1, 'Siap Periksa');
        Antrean::whereKey($kemarinTertunda->id_antrean)->update(['tanggal_kunjungan' => now('Asia/Jakarta')->subDays(3)->toDateString()]);

        $ids = Antrean::hariIni()->pluck('id_antrean');

        $this->assertNotContains($kemarinSelesai->id_antrean, $ids);
        $this->assertContains($kemarinTertunda->id_antrean, $ids);
    }

    // 10. Admin tidak bisa menolak akun aktif atau menonaktifkan sesama admin.
    public function test_admin_tidak_dapat_menolak_akun_aktif_atau_menonaktifkan_admin(): void
    {
        $this->actingAs($this->admin)->post(route('admin.staff.reject', $this->dokter), ['reason' => 'Uji tolak akun aktif'])
            ->assertSessionHas('info');
        $this->assertSame('approved', $this->dokter->fresh()->approval_status);

        $adminLain = $this->staf('admin', 'uji.audit.admin2@medscribe.id', 1, 'active');
        $this->actingAs($this->admin)->post(route('admin.staff.deactivate', $adminLain), ['reason' => 'Uji nonaktif admin'])
            ->assertForbidden();
    }

    // 7. Tanggal lahir hari ini (WIB) diterima.
    public function test_tanggal_lahir_hari_ini_wib_diterima(): void
    {
        $this->actingAs($this->perawat)->post(route('perawat.pasien.store'), [
            'nama_pasien' => 'Bayi Audit',
            'tanggal_lahir' => now('Asia/Jakarta')->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(0, Pasien::where('nama_pasien', 'Bayi Audit')->value('umur'));
    }
}

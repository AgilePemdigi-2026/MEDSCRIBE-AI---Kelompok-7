<?php

namespace Tests\Feature;

use App\Models\Antrean;
use App\Models\DrafSoap;
use App\Models\Finalisasi;
use App\Models\Hospital;
use App\Models\IzinPraktik;
use App\Models\Kunjungan;
use App\Models\MutasiTenagaMedis;
use App\Models\Pasien;
use App\Models\PemeriksaanAwal;
use App\Models\Pengguna;
use App\Models\TindakLanjut;
use App\Models\Wawancara;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * SIP/SIPP berlaku per rumah sakit: nomor lama tersimpan saat mutasi, ditawarkan lagi saat
 * kembali ke RS asal, dan rekam medis final tetap mencetak nomor yang berlaku saat ditandatangani.
 */
class IzinPraktikMutasiTest extends TestCase
{
    use DatabaseTransactions;

    private const SIP_RS_A = '503/4000/SIP-D/436.7.2/2026';
    private const SIPP_RS_A = '503/5000/SIPP/436.7.2/2026';

    private Hospital $rsA;
    private Hospital $rsB;
    private Pengguna $adminA;
    private Pengguna $adminB;
    private Pengguna $dokter;
    private Pengguna $perawat;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->rsA, $this->rsB] = Hospital::where('is_active', true)->orderBy('id')->take(2)->get()->all();
        $this->adminA = $this->akun('admin', 'uji.izin.admin.a@medscribe.id', $this->rsA);
        $this->adminB = $this->akun('admin', 'uji.izin.admin.b@medscribe.id', $this->rsB);
        $this->dokter = $this->akun('dokter', 'uji.izin.dokter@medscribe.id', $this->rsA);
        $this->perawat = $this->akun('perawat', 'uji.izin.perawat@medscribe.id', $this->rsA);

        // SIP diisi lewat halaman profil, seperti alur sebenarnya.
        $this->isiProfil($this->dokter, self::SIP_RS_A);
        $this->isiProfil($this->perawat, self::SIPP_RS_A);
    }

    private function akun(string $role, string $email, Hospital $rs): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap' => 'Uji Izin ' . $role,
            'email' => $email,
            'role' => $role,
            'hospital_id' => $rs->id,
            'email_verified_at' => now(),
            'approval_status' => 'approved',
            'password' => bcrypt('password123'),
        ]);
    }

    private function isiProfil(Pengguna $user, ?string $sip): void
    {
        $this->actingAs($user)->put(route('profil.update'), [
            'nama_lengkap' => $user->nama_lengkap,
            'nomor_sip' => $sip,
        ])->assertSessionHasNoErrors();
    }

    private function mutasi(Pengguna $staf, Hospital $asal, Hospital $tujuan, Pengguna $adminAsal, Pengguna $adminTujuan): void
    {
        $this->actingAs($adminAsal)->post(route('admin.mutasi.lepas', $staf), [
            'hospital_tujuan_id' => $tujuan->id,
            'alasan' => 'Penugasan sesuai SK direktur',
        ])->assertSessionHasNoErrors();

        $mutasi = MutasiTenagaMedis::where('id_pengguna', $staf->id_pengguna)->latest('id')->firstOrFail();
        $this->actingAs($adminTujuan)->post(route('admin.mutasi.setujui', $mutasi))->assertRedirect();
        $this->assertSame((int) $tujuan->id, (int) $staf->fresh()->hospital_id);
    }

    /** Kunjungan di RS A yang semua syarat finalisasinya sudah terpenuhi. */
    private function kunjunganSiapFinal(): Kunjungan
    {
        $pasien = Pasien::create([
            'hospital_id' => $this->rsA->id,
            'no_rekam_medis' => '20260101-951',
            'nama_pasien' => 'Pasien Uji Izin Praktik',
        ]);
        $antrean = Antrean::create([
            'hospital_id' => $this->rsA->id,
            'id_pasien' => $pasien->id_pasien,
            'tanggal_kunjungan' => now()->toDateString(),
            'status' => 'Sedang Diperiksa',
        ]);
        $kunjungan = Kunjungan::create([
            'hospital_id' => $this->rsA->id,
            'id_antrean' => $antrean->id_antrean,
            'id_pasien' => $pasien->id_pasien,
            'id_dokter' => $this->dokter->id_pengguna,
            'tanggal' => now()->toDateString(),
            'status' => Kunjungan::STATUS_SOAP_TERVERIFIKASI,
        ]);

        DrafSoap::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_pengguna' => $this->dokter->id_pengguna,
            'subjective_text' => 'Pusing.',
            'objective_text' => 'TD 120/80 mmHg.',
            'assessment_text' => 'Cephalea.',
            'plan_text' => 'Istirahat.',
        ]);
        PemeriksaanAwal::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_perawat' => $this->perawat->id_pengguna,
            'tekanan_darah' => '120/80',
            'nadi' => 80,
            'suhu_tubuh' => 36.8,
            'laju_pernapasan' => 18,
        ]);
        Wawancara::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'status' => 'Wawancara Selesai',
            'transkrip' => 'Dokter: Halo. Pasien: Pusing.',
            'transcript_reviewed_at' => now(),
            'transcript_verified_by' => $this->dokter->id_pengguna,
            'processing_status' => 'completed',
            'jawaban' => [],
        ]);
        TindakLanjut::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'jenis_tindak_lanjut' => TindakLanjut::JENIS_TANPA_TINDAKAN,
            'status' => 'Selesai Tanpa Tindakan Lanjut',
        ]);

        return $kunjungan;
    }

    public function test_simpan_profil_mencatat_riwayat_izin_di_rs_saat_ini(): void
    {
        $this->assertDatabaseHas('izin_praktik', [
            'id_pengguna' => $this->dokter->id_pengguna,
            'hospital_id' => $this->rsA->id,
            'nomor_sip' => self::SIP_RS_A,
        ]);

        // Nomor diganti: riwayat RS yang sama ikut diperbarui, tidak menumpuk.
        $this->isiProfil($this->dokter, '503/4001/SIP-D/436.7.2/2026');
        $this->assertSame(1, IzinPraktik::where('id_pengguna', $this->dokter->id_pengguna)->count());
        $this->assertSame('503/4001/SIP-D/436.7.2/2026', $this->dokter->nomorIzinDi($this->rsA->id));
    }

    public function test_mutasi_mengosongkan_sip_tapi_riwayat_rs_asal_tetap_tersimpan(): void
    {
        $this->mutasi($this->dokter, $this->rsA, $this->rsB, $this->adminA, $this->adminB);

        $dokter = $this->dokter->fresh();
        $this->assertNull($dokter->nomor_sip);
        $this->assertSame(self::SIP_RS_A, $dokter->nomorIzinDi($this->rsA->id));
        $this->assertNull($dokter->nomorIzinDi($this->rsB->id));

        // Di RS baru belum ada nomor: form profil kosong, tanpa usulan.
        $this->actingAs($dokter)->get(route('profil'))
            ->assertOk()
            ->assertViewHas('izinTerakhir', null)
            ->assertDontSee(self::SIP_RS_A);
    }

    public function test_kembali_ke_rs_asal_nomor_lama_diusulkan_untuk_dikonfirmasi(): void
    {
        $this->mutasi($this->dokter, $this->rsA, $this->rsB, $this->adminA, $this->adminB);
        $this->isiProfil($this->dokter->fresh(), '503/9000/SIP-D/RS-B/2026');
        $this->mutasi($this->dokter->fresh(), $this->rsB, $this->rsA, $this->adminB, $this->adminA);

        $dokter = $this->dokter->fresh();
        // Belum otomatis aktif: tetap kosong sampai dokter menyimpan profil sendiri.
        $this->assertNull($dokter->nomor_sip);

        $this->actingAs($dokter)->get(route('profil'))
            ->assertOk()
            ->assertSee('value="' . self::SIP_RS_A . '"', false)
            ->assertSee('Pastikan masih berlaku');

        $this->isiProfil($dokter, self::SIP_RS_A);
        $this->assertSame(self::SIP_RS_A, $dokter->fresh()->nomor_sip);

        // Nomor di RS B juga tetap tersimpan.
        $this->assertSame('503/9000/SIP-D/RS-B/2026', $dokter->fresh()->nomorIzinDi($this->rsB->id));
    }

    public function test_finalisasi_menyimpan_salinan_sip_dokter_dan_sipp_perawat(): void
    {
        $kunjungan = $this->kunjunganSiapFinal();

        $this->actingAs($this->dokter)->post(route('dokter.rekam-medis.finalisasi', $kunjungan), [
            'konfirmasi_persetujuan' => '1',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('finalisasi', [
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'sip_dokter' => self::SIP_RS_A,
            'sipp_perawat' => self::SIPP_RS_A,
        ]);
    }

    public function test_perawat_yang_sudah_dimutasi_tetap_tercatat_dengan_sipp_rs_kunjungan(): void
    {
        $kunjungan = $this->kunjunganSiapFinal();
        // Perawat pindah ke RS B setelah mengisi TTV, sebelum dokter memfinalisasi.
        $this->mutasi($this->perawat, $this->rsA, $this->rsB, $this->adminA, $this->adminB);
        $this->isiProfil($this->perawat->fresh(), '503/7000/SIPP/RS-B/2026');

        $this->actingAs($this->dokter)->post(route('dokter.rekam-medis.finalisasi', $kunjungan), [
            'konfirmasi_persetujuan' => '1',
        ])->assertSessionHas('status');

        $this->assertSame(self::SIPP_RS_A, Finalisasi::where('id_kunjungan', $kunjungan->id_kunjungan)->value('sipp_perawat'));
    }

    public function test_dokumen_final_tidak_berubah_setelah_dokter_dimutasi_dan_ganti_sip(): void
    {
        $kunjungan = $this->kunjunganSiapFinal();
        $this->actingAs($this->dokter)->post(route('dokter.rekam-medis.finalisasi', $kunjungan), [
            'konfirmasi_persetujuan' => '1',
        ])->assertSessionHas('status');

        // Dokter pindah ke RS B dan mengisi SIP baru; perawat juga mengganti nomornya.
        $this->mutasi($this->dokter, $this->rsA, $this->rsB, $this->adminA, $this->adminB);
        $this->isiProfil($this->dokter->fresh(), '503/9000/SIP-D/RS-B/2026');
        $this->isiProfil($this->perawat, '503/5999/SIPP/BARU/2026');

        // Dokter lain di RS A membuka dokumen final itu sebagai riwayat pasien.
        $dokterLain = $this->akun('dokter', 'uji.izin.dokter.lain@medscribe.id', $this->rsA);
        $this->actingAs($dokterLain)->get(route('dokter.soap.preview', $kunjungan))
            ->assertOk()
            ->assertSee('SIP: ' . self::SIP_RS_A)
            ->assertSee('SIPP ' . self::SIPP_RS_A)
            ->assertDontSee('RS-B')
            ->assertDontSee('503/5999/SIPP/BARU/2026');
    }

    public function test_dokumen_final_lama_tanpa_salinan_memakai_riwayat_sip_rs_kunjungan(): void
    {
        $kunjungan = $this->kunjunganSiapFinal();
        // Difinalisasi sebelum fitur salinan ada: kolom sip_dokter/sipp_perawat kosong.
        Finalisasi::create([
            'id_kunjungan' => $kunjungan->id_kunjungan,
            'id_dokter' => $this->dokter->id_pengguna,
            'status' => 'Final',
            'FINAL' => 1,
            'finalized_at' => now(),
            'finalized_by' => $this->dokter->id_pengguna,
        ]);
        $kunjungan->update(['status' => Kunjungan::STATUS_FINALISASI]);

        $this->mutasi($this->dokter, $this->rsA, $this->rsB, $this->adminA, $this->adminB);
        $this->isiProfil($this->dokter->fresh(), '503/9000/SIP-D/RS-B/2026');

        $dokterLain = $this->akun('dokter', 'uji.izin.dokter.lain@medscribe.id', $this->rsA);
        $this->actingAs($dokterLain)->get(route('dokter.soap.preview', $kunjungan))
            ->assertOk()
            ->assertSee('SIP: ' . self::SIP_RS_A)
            ->assertDontSee('RS-B');
    }
}

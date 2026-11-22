<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Hospital;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pendaftar yang ditolak admin bisa menghapus akunnya sendiri lewat tombol "Daftar ulang",
 * agar email dan akun Google-nya bisa dipakai mendaftar lagi.
 */
class DaftarUlangAkunDitolakTest extends TestCase
{
    use DatabaseTransactions;

    protected Hospital $hospital;
    protected Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Hospital::create([
            'hospital_code' => 'TST-' . uniqid(),
            'hospital_name' => 'RS Uji Daftar Ulang',
            'hospital_type' => 'RSU',
            'is_active'     => true,
        ]);

        $this->admin = $this->buatPengguna('admin', 'approved');
    }

    private function buatPengguna(string $role, string $status): Pengguna
    {
        return Pengguna::create([
            'nama_lengkap'      => ucfirst($role) . ' ' . $status,
            'email'             => $role . '.' . $status . '.' . uniqid() . '@medscribe.test',
            'role'              => $role,
            'hospital_id'       => $this->hospital->id,
            'email_verified_at' => now(),
            'approval_status'   => $status,
            'password'          => Hash::make('password123'),
        ]);
    }

    public function test_halaman_ditolak_menampilkan_tombol_daftar_ulang(): void
    {
        $ditolak = $this->buatPengguna('dokter', 'rejected');
        $ditolak->update(['rejection_reason' => 'Nomor SIP tidak terdaftar di rumah sakit ini.']);

        $this->actingAs($ditolak)->get(route('auth.rejected'))
            ->assertOk()
            ->assertSee('Nomor SIP tidak terdaftar di rumah sakit ini.')
            ->assertSee(route('auth.rejected.reapply'), false)
            ->assertSee('Daftar ulang');
    }

    public function test_akun_ditolak_dihapus_dan_diarahkan_ke_pendaftaran(): void
    {
        $ditolak = $this->buatPengguna('perawat', 'rejected');
        $email = $ditolak->email;

        $response = $this->actingAs($ditolak)->post(route('auth.rejected.reapply'));

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('status');
        $this->assertGuest();
        $this->assertDatabaseMissing('pengguna', ['email' => $email]);
    }

    public function test_riwayat_penolakan_di_log_audit_tetap_ada_setelah_daftar_ulang(): void
    {
        $ditolak = $this->buatPengguna('dokter', 'rejected');
        $log = AdminAuditLog::create([
            'admin_id'       => $this->admin->id_pengguna,
            'hospital_id'    => $this->hospital->id,
            'target_user_id' => $ditolak->id_pengguna,
            'action'         => 'reject_staff',
            'target_role'    => 'dokter',
            'target_name'    => $ditolak->nama_lengkap,
            'status_before'  => 'pending',
            'status_after'   => 'rejected',
            'reason'         => 'Data tidak lengkap.',
            'created_at'     => now(),
        ]);

        $this->actingAs($ditolak)->post(route('auth.rejected.reapply'));

        $log->refresh();
        $this->assertNull($log->target_user_id);
        $this->assertSame($ditolak->nama_lengkap, $log->target_name);
        $this->assertSame('rejected', $log->status_after);
    }

    #[DataProvider('statusSelainDitolak')]
    public function test_akun_yang_tidak_ditolak_tidak_bisa_dihapus_lewat_daftar_ulang(string $role, string $status): void
    {
        $pengguna = $this->buatPengguna($role, $status);

        $this->actingAs($pengguna)->post(route('auth.rejected.reapply'))->assertRedirect();

        $this->assertDatabaseHas('pengguna', ['id_pengguna' => $pengguna->id_pengguna]);
        $this->assertAuthenticatedAs($pengguna);
    }

    public static function statusSelainDitolak(): array
    {
        return [
            'dokter menunggu verifikasi' => ['dokter', 'pending'],
            'dokter aktif'               => ['dokter', 'approved'],
            'perawat nonaktif'           => ['perawat', 'inactive'],
            'admin aktif'                => ['admin', 'approved'],
        ];
    }

    public function test_tamu_tidak_bisa_memanggil_daftar_ulang(): void
    {
        $this->post(route('auth.rejected.reapply'))->assertRedirect(route('login'));
    }
}

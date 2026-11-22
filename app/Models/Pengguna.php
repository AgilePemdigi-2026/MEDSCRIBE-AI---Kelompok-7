<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable
{
    use Notifiable;

    /**
     * Nama tabel di database relasional MedScribe AI.
     */
    protected $table = 'pengguna';

    /**
     * Primary key sesuai skema CDM/PDM MedScribe AI.
     */
    protected $primaryKey = 'id_pengguna';

    /**
     * Tabel pengguna kini menggunakan timestamps untuk mencatat waktu registrasi.
     */
    public $timestamps = true;

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'nama_lengkap',
        'email',
        'email_verified_at',
        'role',
        'nomor_sip',
        'hospital_id',
        'approval_status',
        'password',
        'google_id',
        'avatar',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'deactivated_by',
        'deactivated_at',
        'deactivation_reason',
        'created_at',
        'updated_at',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi (keamanan data).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Tipe casting otomatis untuk keamanan hashing password dan tanggal verifikasi email.
     */
    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'email_verified_at' => 'datetime',
            'approved_at'       => 'datetime',
            'rejected_at'       => 'datetime',
            'deactivated_at'    => 'datetime',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    /**
     * Relasi ke master data Rumah Sakit tempat tenaga medis berafiliasi.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    /**
     * Relasi ke kode OTP verifikasi email dan lupa password.
     */
    public function emailOtps(): HasMany
    {
        return $this->hasMany(EmailOtp::class, 'user_id', 'id_pengguna');
    }

    /**
     * Helper untuk memeriksa apakah email akun telah terverifikasi.
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Helper untuk memeriksa apakah akun telah disetujui / aktif untuk akses data klinis.
     */
    public function isApproved(): bool
    {
        return $this->isActive();
    }

    /**
     * Helper untuk memeriksa apakah akun berstatus aktif.
     */
    public function isActive(): bool
    {
        $status = strtolower($this->approval_status ?? 'approved');
        return in_array($status, ['active', 'approved'], true);
    }

    /**
     * Helper untuk memeriksa apakah akun berstatus pending menunggu verifikasi admin.
     */
    public function isPending(): bool
    {
        return strtolower($this->approval_status ?? '') === 'pending';
    }

    /**
     * Helper untuk memeriksa apakah pendaftaran akun ditolak oleh admin.
     */
    public function isRejected(): bool
    {
        return strtolower($this->approval_status ?? '') === 'rejected';
    }

    /**
     * Helper untuk memeriksa apakah akun telah dinonaktifkan oleh admin.
     */
    public function isInactive(): bool
    {
        return strtolower($this->approval_status ?? '') === 'inactive';
    }

    /**
     * Label status akun dalam bahasa Indonesia.
     */
    public function getStatusLabelAttribute(): string
    {
        return match (strtolower($this->approval_status ?? 'approved')) {
            'active', 'approved' => 'Aktif',
            'pending'            => 'Menunggu Verifikasi',
            'rejected'           => 'Ditolak',
            'inactive'           => 'Nonaktif',
            default              => ucfirst($this->approval_status ?? 'Aktif'),
        };
    }

    /**
     * Helper untuk memeriksa apakah pengguna berperan sebagai Admin Rumah Sakit.
     */
    public function isAdmin(): bool
    {
        return strtolower($this->role ?? '') === 'admin';
    }

    /**
     * Helper untuk memeriksa apakah pengguna berperan sebagai Dokter.
     */
    public function isDokter(): bool
    {
        return strtolower($this->role ?? '') === 'dokter';
    }

    /**
     * Helper untuk memeriksa apakah pengguna berperan sebagai Perawat.
     */
    public function isPerawat(): bool
    {
        return strtolower($this->role ?? '') === 'perawat';
    }

    /**
     * Relasi ke admin yang menyetujui akun ini.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'approved_by', 'id_pengguna');
    }

    /**
     * Relasi ke admin yang menolak akun ini.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'rejected_by', 'id_pengguna');
    }

    /**
     * Relasi ke admin yang menonaktifkan akun ini.
     */
    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'deactivated_by', 'id_pengguna');
    }

    /**
     * Relasi ke catatan riwayat audit log tindakan admin.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_id', 'id_pengguna');
    }

    /**
     * Accessor alias untuk properti peran agar kompatibel dengan penamaan peran/role dalam bahasa Indonesia.
     */
    public function getPeranAttribute(): ?string
    {
        return $this->role;
    }

    /**
     * Inisial dari dua kata pertama nama untuk avatar ("Dewi Pratiwi" -> "DP").
     */
    public function getInisialAttribute(): string
    {
        $initials = collect(preg_split('/\s+/', trim((string) $this->nama_lengkap)))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    /**
     * Label peran yang ditampilkan ke pengguna.
     */
    public function getPeranLabelAttribute(): string
    {
        return match ($this->role) {
            'dokter'  => 'Dokter',
            'perawat' => 'Perawat',
            'admin'   => 'Administrator RS',
            default   => ucfirst((string) $this->role),
        };
    }

    /**
     * Scope query berdasarkan rumah sakit pengguna.
     */
    public function scopeForHospital(Builder $query, int $hospitalId): Builder
    {
        return $query->where('hospital_id', $hospitalId);
    }

    /**
     * Relasi ke kunjungan pasien di mana pengguna bertindak sebagai dokter.
     */
    public function kunjunganDokter(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'id_dokter', 'id_pengguna');
    }

    /**
     * Relasi ke pemeriksaan awal fisik di mana pengguna bertindak sebagai perawat.
     */
    public function pemeriksaanPerawat(): HasMany
    {
        return $this->hasMany(PemeriksaanAwal::class, 'id_perawat', 'id_pengguna');
    }

    /**
     * Relasi ke draf catatan SOAP yang dibuat / diverifikasi oleh pengguna.
     */
    public function drafSoap(): HasMany
    {
        return $this->hasMany(DrafSoap::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi ke berkas finalisasi rekam medis dokter.
     */
    public function finalisasi(): HasMany
    {
        return $this->hasMany(Finalisasi::class, 'id_dokter', 'id_pengguna');
    }
}

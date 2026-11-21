{{--
    Dialog konfirmasi tindakan admin terhadap tenaga medis. Dipakai di semua halaman admin agar
    satu tindakan selalu punya satu tampilan.

    Pemicu: <button type="button" data-dialog-open="dlgRejectStaff"
                    data-staff-url="{{ route('admin.staff.reject', $user) }}"
                    data-staff-name="{{ $user->nama_lengkap }}" data-staff-role="{{ $user->peran_label }}">
    Skrip di bawah mengisi alamat formulir dan nama staf sebelum dialog tampil.
--}}
<x-dialog id="dlgApproveStaff" tone="ok" title="Setujui pendaftaran?"
          description="Akun akan aktif dan bisa langsung masuk ke dashboard sesuai perannya.">
    <form method="POST" id="formApproveStaff" data-staff-form data-submit-loading class="panel text-sm">
        @csrf
        <p class="text-text-secondary">Tenaga medis</p>
        <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span> · <span data-staff-fill="role"></span></p>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formApproveStaff" class="btn btn-primary" data-dialog-focus>Setujui</button>
    </x-slot:actions>
</x-dialog>

<x-dialog id="dlgRejectStaff" tone="danger" size="md" title="Tolak pendaftaran?"
          description="Alasan penolakan akan ditampilkan kepada pendaftar saat mencoba masuk.">
    <form method="POST" id="formRejectStaff" data-staff-form data-submit-loading class="space-y-4">
        @csrf
        <div class="panel text-sm">
            <p class="text-text-secondary">Pendaftar</p>
            <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span> · <span data-staff-fill="role"></span></p>
        </div>
        <div>
            <label for="rejectReason" class="field-label">Alasan penolakan <span class="req" aria-hidden="true">*</span></label>
            <textarea id="rejectReason" name="reason" rows="3" required minlength="5" maxlength="500" data-dialog-focus
                      class="field" placeholder="Contoh: Surat Izin Praktik belum terdaftar di rumah sakit ini."></textarea>
            <p class="field-hint">Minimal 5 karakter.</p>
        </div>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formRejectStaff" class="btn btn-danger">Tolak pendaftaran</button>
    </x-slot:actions>
</x-dialog>

<x-dialog id="dlgDeactivateStaff" tone="danger" size="md" title="Nonaktifkan tenaga medis?"
          description="Akun tidak bisa lagi membuka dashboard. Riwayat konsultasi dan rekam medis yang sudah dibuat tetap tersimpan.">
    <form method="POST" id="formDeactivateStaff" data-staff-form data-submit-loading class="space-y-4">
        @csrf
        <div class="panel text-sm">
            <p class="text-text-secondary">Tenaga medis</p>
            <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span> · <span data-staff-fill="role"></span></p>
        </div>
        <div>
            <label for="deactivateReason" class="field-label">Alasan penonaktifan <span class="req" aria-hidden="true">*</span></label>
            <textarea id="deactivateReason" name="reason" rows="3" required minlength="5" maxlength="500" data-dialog-focus
                      class="field" placeholder="Contoh: Masa penugasan selesai atau pindah fasilitas kesehatan."></textarea>
            <p class="field-hint">Minimal 5 karakter. Alasan ini dilihat oleh tenaga medis yang bersangkutan.</p>
        </div>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formDeactivateStaff" class="btn btn-danger">Nonaktifkan</button>
    </x-slot:actions>
</x-dialog>

<x-dialog id="dlgReactivateStaff" title="Aktifkan kembali akun?"
          description="Tenaga medis bisa kembali masuk dan bekerja di rumah sakit ini."
          icon="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
    <form method="POST" id="formReactivateStaff" data-staff-form data-submit-loading class="panel text-sm">
        @csrf
        <p class="text-text-secondary">Tenaga medis</p>
        <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span> · <span data-staff-fill="role"></span></p>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formReactivateStaff" class="btn btn-primary" data-dialog-focus>Aktifkan kembali</button>
    </x-slot:actions>
</x-dialog>

@push('scripts')
<script>
    // Isi formulir dialog dari tombol pemicu: alamat tindakan, nama, dan peran staf.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-dialog-open][data-staff-url]');
        if (!trigger) return;
        const dialog = document.getElementById(trigger.dataset.dialogOpen);
        const form = dialog?.querySelector('[data-staff-form]');
        if (!form) return;

        form.action = trigger.dataset.staffUrl;
        form.reset();
        dialog.querySelectorAll('[data-staff-fill="name"]').forEach((el) => { el.textContent = trigger.dataset.staffName || ''; });
        dialog.querySelectorAll('[data-staff-fill="role"]').forEach((el) => { el.textContent = trigger.dataset.staffRole || ''; });
    }, true);
</script>
@endpush

{{-- Mengisi formulir dialog tindakan staf dari tombol pemicu (data-staff-url, data-staff-name, data-staff-role). --}}
@once
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
@endonce

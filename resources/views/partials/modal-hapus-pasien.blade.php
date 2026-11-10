{{-- Dialog konfirmasi hapus / batalkan registrasi pasien. Buka dengan openModalHapusPasien(id, nama, noRM). --}}
<div id="modalHapusPasien" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalHapusTitle">
    <div class="dialog-backdrop" onclick="closeModalHapusPasien()"></div>

    <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto pointer-events-none">
        <div class="dialog w-full max-w-md p-6 pointer-events-auto animate-dialog">
            <div class="flex items-start gap-3.5">
                <span class="avatar w-11 h-11 bg-danger-bg border border-danger-border text-danger-solid" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </span>
                <div class="flex-grow">
                    <h2 id="modalHapusTitle" class="text-base font-semibold text-medical-navy">Hapus Registrasi Pasien?</h2>
                    <p class="text-xs text-text-secondary mt-1 leading-relaxed">Apakah Anda yakin ingin menghapus registrasi pasien berikut?</p>
                </div>
                <button type="button" onclick="closeModalHapusPasien()" class="btn btn-ghost btn-icon btn-sm -mt-1 -mr-2" aria-label="Tutup dialog">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <dl class="mt-5 p-4 rounded-[18px] bg-soft-offwhite border border-border-subtle space-y-2.5 text-xs">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-text-secondary">Nama Pasien:</dt>
                    <dd id="hapusNamaPasien" class="font-semibold text-medical-navy text-right">-</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-text-secondary">ID Pasien:</dt>
                    <dd><span id="hapusIdPasien" class="tag-id">-</span></dd>
                </div>
            </dl>

            <p class="mt-4 flex items-center gap-2 text-xs text-warning-text">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                Pastikan data pasien sudah benar sebelum melanjutkan.
            </p>

            <form id="formHapusPasien" method="POST" action="" onsubmit="handleHapusSubmit(event)" class="mt-6">
                @csrf
                @method('DELETE')
                <input type="hidden" name="alasan" value="Pasien tidak hadir / pembatalan registrasi oleh perawat">

                <div class="grid grid-cols-2 gap-2">
                    <button type="button" id="btnBatalHapus" onclick="closeModalHapusPasien()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btnKonfirmasiHapus" class="btn btn-danger">
                        <svg id="spinnerHapus" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="labelHapus">Hapus Registrasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.openModalHapusPasien = function (id, nama, noRekamMedis) {
        const modal = document.getElementById('modalHapusPasien');
        const form = document.getElementById('formHapusPasien');
        if (!modal || !form) return;

        document.getElementById('hapusNamaPasien').textContent = nama;
        document.getElementById('hapusIdPasien').textContent = noRekamMedis;
        form.action = "{{ url('/perawat/pasien') }}/" + id;

        const btnConfirm = document.getElementById('btnKonfirmasiHapus');
        const btnCancel = document.getElementById('btnBatalHapus');
        btnConfirm.disabled = false;
        btnCancel.disabled = false;
        document.getElementById('spinnerHapus').classList.add('hidden');
        document.getElementById('labelHapus').textContent = 'Hapus Registrasi';

        window.__hapusReturnFocus = document.activeElement;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        btnCancel.focus();
    };

    window.closeModalHapusPasien = function () {
        const modal = document.getElementById('modalHapusPasien');
        if (!modal || modal.classList.contains('hidden')) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        window.__hapusReturnFocus?.focus?.();
    };

    window.handleHapusSubmit = function (event) {
        const btnConfirm = document.getElementById('btnKonfirmasiHapus');
        if (btnConfirm.disabled) {
            event.preventDefault();
            return;
        }
        btnConfirm.disabled = true;
        document.getElementById('btnBatalHapus').disabled = true;
        document.getElementById('spinnerHapus').classList.remove('hidden');
        document.getElementById('labelHapus').textContent = 'Memproses...';
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModalHapusPasien();
    });
</script>

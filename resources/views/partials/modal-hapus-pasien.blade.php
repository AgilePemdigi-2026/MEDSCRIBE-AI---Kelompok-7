{{-- Dialog konfirmasi hapus / batalkan registrasi pasien. Buka dengan openModalHapusPasien(id, nama, noRM). --}}
<div id="modalHapusPasien" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalHapusTitle">
    <div class="dialog-backdrop" onclick="closeModalHapusPasien()"></div>

    <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto pointer-events-none">
        <div class="dialog w-full max-w-md p-6 pointer-events-auto animate-dialog">
            <div class="flex items-start gap-3.5">
                <span class="avatar w-11 h-11 bg-danger-bg border border-danger-border text-danger-solid" aria-hidden="true">
                    <x-icon name="trash-2" class="w-5 h-5" />
                </span>
                <div class="flex-grow">
                    <h2 id="modalHapusTitle" class="text-base font-semibold text-medical-navy">Hapus registrasi pasien?</h2>
                    <p class="text-xs text-text-secondary mt-1 leading-relaxed">Apakah Anda yakin ingin menghapus registrasi pasien berikut?</p>
                </div>
                <button type="button" onclick="closeModalHapusPasien()" class="btn btn-ghost btn-icon btn-sm -mt-1 -mr-2" aria-label="Tutup dialog">
                    <x-icon name="x" class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            <dl class="mt-5 panel space-y-2.5 text-xs">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-text-secondary">Nama pasien</dt>
                    <dd id="hapusNamaPasien" class="font-semibold text-medical-navy text-right">-</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-text-secondary">No. RM</dt>
                    <dd><span id="hapusIdPasien" class="tag-id">-</span></dd>
                </div>
            </dl>

            <p class="mt-4 flex items-center gap-2 text-xs text-warning-text">
                <x-icon name="triangle-alert" class="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                Pastikan data pasien sudah benar sebelum melanjutkan.
            </p>

            <form id="formHapusPasien" method="POST" action="" onsubmit="handleHapusSubmit(event)" class="mt-6">
                @csrf
                @method('DELETE')
                <input type="hidden" name="alasan" value="Pasien tidak hadir / pembatalan registrasi oleh perawat">

                <div class="grid grid-cols-2 gap-2">
                    <button type="button" id="btnBatalHapus" onclick="closeModalHapusPasien()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btnKonfirmasiHapus" class="btn btn-danger">
                        <span id="spinnerHapus" class="spinner hidden" aria-hidden="true"></span>
                        <span id="labelHapus">Hapus registrasi</span>
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
        document.getElementById('labelHapus').textContent = 'Hapus registrasi';

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

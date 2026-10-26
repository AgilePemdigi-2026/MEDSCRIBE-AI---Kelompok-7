@extends('layouts.app')

@section('title', 'Detail Pasien - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-[#69718B]">
        <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}" class="hover:text-[#5865DC] font-medium transition-colors">Daftar Pasien</a>
        <span class="text-[#CBD1EA]">›</span>
        <span class="text-[#122056] font-semibold">{{ $pasien->nama_pasien }}</span>
    </div>

    <!-- Card Identitas Pasien -->
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-6 sm:p-7">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center text-xl font-bold shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
                    {{ strtoupper(substr($pasien->nama_pasien, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-[#122056] tracking-tight">{{ $pasien->nama_pasien }}</h1>
                    <div class="flex flex-wrap items-center gap-2.5 mt-1.5 text-xs text-[#69718B]">
                        <span class="font-mono font-semibold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2.5 py-0.5 rounded-[8px]">
                            {{ $pasien->no_rekam_medis }}
                        </span>
                        <span class="text-[#CBD1EA]">·</span>
                        <span class="font-medium text-[#20294A]">
                            @if ($pasien->umur)
                                {{ $pasien->umur }} tahun
                            @elseif ($pasien->tanggal_lahir)
                                {{ \Carbon\Carbon::parse($pasien->tanggal_lahir)->age }} tahun
                            @endif
                        </span>
                        <span class="text-[#CBD1EA]">·</span>
                        <span class="font-medium text-[#20294A]">{{ $pasien->jenis_kelamin ?? '-' }}</span>
                    </div>
                    @if ($pasien->created_at)
                        <div class="text-[11px] text-[#69718B] mt-1.5">
                            Terdaftar: {{ $pasien->created_at->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        onclick="openModalHapusPasien({{ $pasien->id_pasien }}, '{{ addslashes($pasien->nama_pasien) }}', '{{ $pasien->no_rekam_medis }}')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#FFFFFF] hover:bg-[#FEF2F2] border border-[#CBD1EA] hover:border-[#FECACA] rounded-[14px] text-xs font-semibold text-[#DC2626] transition-all shadow-xs cursor-pointer"
                        title="Hapus / Batalkan Registrasi Pasien">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus Registrasi</span>
                </button>
                <a href="{{ route('perawat.dashboard', ['tab' => 'pasien']) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#FFFFFF] hover:bg-[#FAFAFD] border border-[#E4E7F4] hover:border-[#CBD1EA] rounded-[14px] text-xs font-semibold text-[#122056] transition-all shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Pasien
                </a>
            </div>
        </div>
    </div>

    <!-- Riwayat Kunjungan & TTV -->
    <div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_4px_18px_rgba(18,32,86,0.05)] p-6 sm:p-7 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-[#122056]">Riwayat Kunjungan & Pemeriksaan TTV</h2>
                <p class="text-xs text-[#69718B] mt-0.5">{{ $riwayatKunjungan->count() }} kunjungan tercatat</p>
            </div>
        </div>

        @forelse ($riwayatKunjungan as $kunjungan)
            @php
                $pa = $kunjungan->pemeriksaanAwal;
                $tglStr = str_replace('-', '', $kunjungan->tanggal);
                $kodeKunjungan = 'RSUA-' . $tglStr . '-' . str_pad($kunjungan->id_kunjungan, 3, '0', STR_PAD_LEFT);
            @endphp
            <div class="p-5 rounded-[20px] border border-[#E4E7F4] bg-[#FAFAFD] space-y-4 transition-all">
                <!-- Kunjungan Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-[#E4E7F4]">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-xs font-bold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2.5 py-1 rounded-[8px]">
                            {{ $kodeKunjungan }}
                        </span>
                        <span class="text-xs font-semibold text-[#122056]">
                            {{ \Carbon\Carbon::parse($kunjungan->tanggal)->locale('id')->isoFormat('D MMMM Y') }}
                        </span>
                    </div>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-semibold 
                        {{ $kunjungan->status === 'Selesai' || $kunjungan->status === 'SOAP Terverifikasi' 
                            ? 'bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]' 
                            : 'bg-[#EEEEFD] text-[#5865DC] border border-[#CBD1EA]' }}">
                        {{ strtoupper($kunjungan->status ?? 'N/A') }}
                    </span>
                </div>

                @if ($pa)
                    <!-- TTV Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <div class="bg-[#FFFFFF] rounded-[14px] border border-[#E4E7F4] p-3 text-center shadow-xs transition-transform hover:-translate-y-0.5">
                            <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">Tekanan Darah</div>
                            <div class="text-sm font-bold text-[#122056] mt-1">{{ $pa->tekanan_darah ?? '-' }}</div>
                            <div class="text-[10px] text-[#69718B]">mmHg</div>
                        </div>
                        <div class="bg-[#FFFFFF] rounded-[14px] border border-[#E4E7F4] p-3 text-center shadow-xs transition-transform hover:-translate-y-0.5">
                            <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">Nadi</div>
                            <div class="text-sm font-bold text-[#122056] mt-1">{{ $pa->nadi ?? '-' }}</div>
                            <div class="text-[10px] text-[#69718B]">x/menit</div>
                        </div>
                        <div class="bg-[#FFFFFF] rounded-[14px] border border-[#E4E7F4] p-3 text-center shadow-xs transition-transform hover:-translate-y-0.5">
                            <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">Suhu</div>
                            <div class="text-sm font-bold mt-1 {{ ($pa->suhu_tubuh ?? 0) > 37.5 ? 'text-[#DC2626]' : 'text-[#122056]' }}">
                                {{ $pa->suhu_tubuh ?? '-' }}
                            </div>
                            <div class="text-[10px] text-[#69718B]">°C</div>
                        </div>
                        <div class="bg-[#FFFFFF] rounded-[14px] border border-[#E4E7F4] p-3 text-center shadow-xs transition-transform hover:-translate-y-0.5">
                            <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">Pernapasan</div>
                            <div class="text-sm font-bold text-[#122056] mt-1">{{ $pa->laju_pernapasan ?? '-' }}</div>
                            <div class="text-[10px] text-[#69718B]">x/menit</div>
                        </div>
                        @if ($pa->saturasi_oksigen)
                        <div class="bg-[#FFFFFF] rounded-[14px] border border-[#E4E7F4] p-3 text-center shadow-xs transition-transform hover:-translate-y-0.5">
                            <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">SpO2</div>
                            <div class="text-sm font-bold text-[#5865DC] mt-1">{{ $pa->saturasi_oksigen }}</div>
                            <div class="text-[10px] text-[#69718B]">%</div>
                        </div>
                        @endif
                        @if ($pa->berat_badan || $pa->tinggi_badan)
                        <div class="bg-[#FFFFFF] rounded-[14px] border border-[#E4E7F4] p-3 text-center shadow-xs transition-transform hover:-translate-y-0.5">
                            <div class="text-[10px] text-[#69718B] font-semibold uppercase tracking-wider">BB / TB</div>
                            <div class="text-sm font-bold text-[#122056] mt-1">
                                {{ $pa->berat_badan ? $pa->berat_badan . ' kg' : '-' }}
                                / {{ $pa->tinggi_badan ? $pa->tinggi_badan . ' cm' : '-' }}
                            </div>
                        </div>
                        @endif
                    </div>

                    @if ($pa->catatan)
                        <div class="text-xs text-[#69718B] bg-[#FFFFFF] p-3 rounded-[12px] border border-[#E4E7F4]">
                            <span class="font-semibold text-[#122056]">Catatan:</span> {{ $pa->catatan }}
                        </div>
                    @endif

                    @if ($pa->perawat)
                        <div class="text-[11px] text-[#69718B] flex items-center gap-1.5 pt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#5865DC]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Diperiksa oleh: <strong class="text-[#122056] font-semibold">{{ $pa->perawat->nama_lengkap }}</strong></span>
                            @if ($pa->created_at)
                                <span class="text-[#CBD1EA]">·</span>
                                <span>{{ $pa->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="text-xs text-[#69718B] py-2">Belum ada data pemeriksaan awal untuk kunjungan ini.</div>
                @endif
            </div>
        @empty
            <div class="py-12 text-center text-[#69718B] space-y-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto text-[#CBD1EA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm font-semibold text-[#122056]">Belum ada riwayat kunjungan</p>
                <p class="text-xs text-[#69718B]">Pasien ini belum memiliki catatan kunjungan atau pemeriksaan TTV.</p>
            </div>
        @endforelse
    </div>

</div>

{{-- ========== MODAL: Konfirmasi Hapus / Pembatalan Registrasi Pasien ========== --}}
<div id="modalHapusPasien" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalHapusTitle">
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-[#122056]/50 backdrop-blur-xs transition-opacity" onclick="closeModalHapusPasien()"></div>

    <div class="fixed inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative w-full max-w-md bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] shadow-[0_20px_50px_rgba(18,32,86,0.2)] p-6 space-y-5 animate-scaleIn">
            
            <!-- Modal Header / Icon -->
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-[14px] bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] flex items-center justify-center flex-shrink-0 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="flex-grow">
                    <h2 id="modalHapusTitle" class="text-base font-bold text-[#122056]">Hapus Registrasi Pasien?</h2>
                    <p class="text-xs text-[#69718B] mt-1 leading-relaxed">
                        Apakah Anda yakin ingin menghapus registrasi pasien berikut?
                    </p>
                </div>
                <button type="button" onclick="closeModalHapusPasien()"
                        class="w-8 h-8 rounded-[12px] bg-[#FAFAFD] border border-[#E4E7F4] text-[#69718B] hover:text-[#122056] hover:bg-[#EEEEFD] flex items-center justify-center transition-all cursor-pointer text-sm"
                        aria-label="Tutup dialog">
                    ✕
                </button>
            </div>

            <!-- Detail Pasien Dinamis -->
            <div class="p-4 rounded-[16px] bg-[#FAFAFD] border border-[#E4E7F4] space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[#69718B]">Nama Pasien:</span>
                    <span id="hapusNamaPasien" class="font-bold text-[#122056] text-right">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#69718B]">ID Pasien:</span>
                    <span id="hapusIdPasien" class="font-mono font-bold text-[#5865DC] bg-[#EEEEFD] border border-[#CBD1EA] px-2 py-0.5 rounded-[6px]">-</span>
                </div>
            </div>

            <div class="flex items-center gap-2 text-[11px] text-[#69718B]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#D97706] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Pastikan data pasien sudah benar sebelum melanjutkan.</span>
            </div>

            <!-- Form Aksi Hapus dengan CSRF & Method DELETE -->
            <form id="formHapusPasien" method="POST" action="" onsubmit="handleHapusSubmit(event)">
                @csrf
                @method('DELETE')
                <input type="hidden" name="alasan" value="Pasien tidak hadir / pembatalan registrasi oleh perawat">

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" id="btnBatalHapus" onclick="closeModalHapusPasien()"
                            class="px-4 py-2 rounded-[14px] text-xs font-semibold bg-[#FAFAFD] hover:bg-[#EEEEFD] text-[#20294A] border border-[#CBD1EA] transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btnKonfirmasiHapus"
                            class="px-4 py-2 rounded-[14px] text-xs font-semibold bg-[#DC2626] hover:bg-[#B91C1C] active:bg-[#991B1B] text-[#FFFFFF] transition-all flex items-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(220,38,38,0.25)] hover:shadow-[0_4px_16px_rgba(220,38,38,0.35)]">
                        <svg id="iconTrashHapus" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <svg id="spinnerHapus" class="w-4 h-4 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="labelHapus">Ya, Hapus Registrasi</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.openModalHapusPasien = function(id, nama, noRekamMedis) {
        const modal = document.getElementById('modalHapusPasien');
        const form = document.getElementById('formHapusPasien');
        const namaEl = document.getElementById('hapusNamaPasien');
        const idEl = document.getElementById('hapusIdPasien');
        const btnConfirm = document.getElementById('btnKonfirmasiHapus');
        const btnCancel = document.getElementById('btnBatalHapus');
        const iconTrash = document.getElementById('iconTrashHapus');
        const spinner = document.getElementById('spinnerHapus');
        const label = document.getElementById('labelHapus');

        if (!modal || !form) return;

        namaEl.textContent = nama;
        idEl.textContent = noRekamMedis;
        form.action = "{{ url('/perawat/pasien') }}/" + id;

        // Reset submit button state
        btnConfirm.disabled = false;
        btnCancel.disabled = false;
        if (iconTrash) iconTrash.classList.remove('hidden');
        if (spinner) spinner.classList.add('hidden');
        if (label) label.textContent = 'Ya, Hapus Registrasi';

        modal.classList.remove('hidden');
        btnCancel.focus();
    };

    window.closeModalHapusPasien = function() {
        const modal = document.getElementById('modalHapusPasien');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    window.handleHapusSubmit = function(event) {
        const btnConfirm = document.getElementById('btnKonfirmasiHapus');
        const btnCancel = document.getElementById('btnBatalHapus');
        const iconTrash = document.getElementById('iconTrashHapus');
        const spinner = document.getElementById('spinnerHapus');
        const label = document.getElementById('labelHapus');

        if (btnConfirm.disabled) {
            event.preventDefault();
            return;
        }

        btnConfirm.disabled = true;
        btnCancel.disabled = true;
        if (iconTrash) iconTrash.classList.add('hidden');
        if (spinner) spinner.classList.remove('hidden');
        if (label) label.textContent = 'Memproses...';
    };

    // Keyboard navigation: Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalHapus = document.getElementById('modalHapusPasien');
            if (modalHapus && !modalHapus.classList.contains('hidden')) {
                closeModalHapusPasien();
            }
        }
    });
</script>
@endsection

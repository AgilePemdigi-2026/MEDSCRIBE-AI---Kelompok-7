@extends('layouts.admin')

@section('title', 'Manajemen Dokter - MedScribe AI')

@section('content')
<div class="space-y-6 rise">

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border-subtle">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">Manajemen Dokter Aktif</h1>
            <p class="text-xs text-text-secondary mt-0.5">Daftar dokter poliklinik aktif bertugas di {{ $hospital->hospital_name }}</p>
        </div>
        <span class="tag-id bg-pale-lavender text-royal-indigo font-medium self-start sm:self-auto">
            Total: {{ $doctors->total() }} Dokter Aktif
        </span>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.doctors') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="cari" value="{{ $search }}"
                       class="field pl-9 text-xs"
                       placeholder="Cari berdasarkan nama dokter atau alamat email...">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-text-secondary absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="sort" class="field text-xs sm:w-44" onchange="this.form.submit()">
                    <option value="nama" {{ $sort === 'nama' ? 'selected' : '' }}>Urutkan: Nama A-Z</option>
                    <option value="terbaru" {{ $sort === 'terbaru' ? 'selected' : '' }}>Urutkan: Terdaftar Baru</option>
                    <option value="terlama" {{ $sort === 'terlama' ? 'selected' : '' }}>Urutkan: Terdaftar Lama</option>
                </select>

                <button type="submit" class="btn btn-primary text-xs py-2 px-4 flex-shrink-0">
                    Cari
                </button>

                @if ($search !== '')
                    <a href="{{ route('admin.doctors') }}" class="btn btn-secondary text-xs py-2 px-3 flex-shrink-0" title="Reset Pencarian">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Dokter Aktif --}}
    <div class="card overflow-hidden">
        @if ($doctors->isEmpty())
            <div class="py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <p class="text-sm font-semibold text-medical-navy">Tidak ada data dokter ditemukan</p>
                <p class="text-xs text-text-secondary mt-1">
                    {{ $search ? 'Tidak ada hasil untuk kata kunci "' . $search . '"' : 'Belum ada dokter berstatus aktif di rumah sakit ini.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-soft-offwhite text-text-secondary border-b border-border-subtle">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Nama Dokter</th>
                            <th class="py-3 px-4 font-semibold">Email</th>
                            <th class="py-3 px-4 font-semibold">Tanggal Registrasi</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/70">
                        @foreach ($doctors as $doc)
                            <tr class="hover:bg-soft-offwhite/40 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-medical-navy flex items-center gap-3">
                                    <span class="avatar w-8 h-8 bg-pale-lavender text-royal-indigo text-xs font-semibold rounded-full flex items-center justify-center">
                                        {{ strtoupper(substr($doc->nama_lengkap, 0, 2)) }}
                                    </span>
                                    <div>
                                        <p class="leading-tight">{{ $doc->nama_lengkap }}</p>
                                        <p class="text-[11px] text-text-secondary font-normal mt-0.5">Spesialis / Poliklinik</p>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-text-secondary">{{ $doc->email }}</td>
                                <td class="py-3.5 px-4 text-text-secondary">
                                    {{ $doc->created_at ? $doc->created_at->translatedFormat('d F Y') : '-' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" onclick="openModalDeactivate({{ $doc->id_pengguna }}, '{{ addslashes($doc->nama_lengkap) }}', 'Dokter')"
                                            class="btn btn-secondary btn-sm text-xs py-1 px-2.5 text-danger-solid hover:bg-danger-bg hover:border-danger-border">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        Nonaktifkan
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($doctors->hasPages())
                <div class="p-4 border-t border-border-subtle">
                    {{ $doctors->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- Modal Konfirmasi Penonaktifan Tenaga Medis --}}
<div id="modalDeactivateStaff" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalDeactivateTitle">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModalDeactivate()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="dialog w-full max-w-md p-6 pointer-events-auto animate-dialog bg-white rounded-[24px] shadow-2xl border border-border-subtle">
            <div class="flex items-center gap-3 pb-3 border-b border-border-subtle">
                <span class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <div>
                    <h2 id="modalDeactivateTitle" class="text-base font-semibold text-medical-navy">Nonaktifkan Tenaga Medis</h2>
                    <p id="modalDeactivateSubtitle" class="text-xs text-text-secondary"></p>
                </div>
            </div>

            <form id="formDeactivateStaff" method="POST" action="" class="mt-4 space-y-4">
                @csrf
                <div class="p-3 rounded-[14px] bg-slate-50 border border-border-subtle text-xs text-text-secondary leading-relaxed">
                    Akun yang dinonaktifkan tidak dapat lagi mengakses dashboard operasional. Seluruh riwayat konsultasi dan rekam medis yang pernah dibuat tetap terjaga aman.
                </div>

                <div>
                    <label for="deactivate_reason" class="field-label text-xs">Alasan Penonaktifan <span class="text-danger-solid">*</span></label>
                    <textarea id="deactivate_reason" name="reason" rows="3" required minlength="5" maxlength="500"
                              class="field text-xs leading-relaxed"
                              placeholder="Contoh: Telah menyelesaikan masa penugasan / Pindah faskes / Pensiun..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button type="button" onclick="closeModalDeactivate()" class="btn btn-secondary justify-center text-xs">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-danger justify-center text-xs">
                        Ya, Nonaktifkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openModalDeactivate(userId, userName, userRole) {
        const modal = document.getElementById('modalDeactivateStaff');
        const form = document.getElementById('formDeactivateStaff');
        const subtitle = document.getElementById('modalDeactivateSubtitle');
        if (!modal || !form) return;

        form.action = `/admin/tenaga-medis/${userId}/nonaktifkan`;
        subtitle.textContent = `${userRole}: ${userName}`;
        document.getElementById('deactivate_reason').value = '';

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('deactivate_reason')?.focus(), 50);
    }

    function closeModalDeactivate() {
        const modal = document.getElementById('modalDeactivateStaff');
        if (!modal || modal.classList.contains('hidden')) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>
@endsection


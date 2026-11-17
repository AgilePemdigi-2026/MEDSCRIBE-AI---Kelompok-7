@extends('layouts.admin')

@section('title', 'Verifikasi Pendaftaran Tenaga Medis - MedScribe AI')

@section('content')
<div class="space-y-6 rise">

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border-subtle">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">Verifikasi Pendaftaran Staf</h1>
            <p class="text-xs text-text-secondary mt-0.5">Daftar dokter dan perawat baru yang mengajukan registrasi akun di {{ $hospital->hospital_name }}</p>
        </div>
        <span class="tag-id bg-amber-50 text-amber-800 border border-amber-200 font-medium self-start sm:self-auto">
            Menunggu Verifikasi: {{ $pendingStaff->total() }} Staf
        </span>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.pending') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="cari" value="{{ $search }}"
                       class="field pl-9 text-xs"
                       placeholder="Cari berdasarkan nama calon staf atau alamat email...">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-text-secondary absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="role" class="field text-xs sm:w-40" onchange="this.form.submit()">
                    <option value="" {{ empty($roleFilter) ? 'selected' : '' }}>Semua Peran</option>
                    <option value="dokter" {{ $roleFilter === 'dokter' ? 'selected' : '' }}>Hanya Dokter</option>
                    <option value="perawat" {{ $roleFilter === 'perawat' ? 'selected' : '' }}>Hanya Perawat</option>
                </select>

                <button type="submit" class="btn btn-primary text-xs py-2 px-4 flex-shrink-0">
                    Cari
                </button>

                @if ($search !== '' || !empty($roleFilter))
                    <a href="{{ route('admin.pending') }}" class="btn btn-secondary text-xs py-2 px-3 flex-shrink-0" title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Verifikasi Pendaftaran --}}
    <div class="card overflow-hidden">
        @if ($pendingStaff->isEmpty())
            <div class="py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-semibold text-medical-navy">Tidak ada permohonan verifikasi pending</p>
                <p class="text-xs text-text-secondary mt-1">
                    {{ $search || $roleFilter ? 'Tidak ada calon tenaga medis yang sesuai dengan kriteria filter.' : 'Semua pendaftaran dokter dan perawat telah diproses.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-soft-offwhite text-text-secondary border-b border-border-subtle">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Nama Lengkap & Jabatan</th>
                            <th class="py-3 px-4 font-semibold">Email</th>
                            <th class="py-3 px-4 font-semibold">Tanggal Pengajuan</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold text-right">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/70">
                        @foreach ($pendingStaff as $staff)
                            <tr class="hover:bg-soft-offwhite/40 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-medical-navy flex items-center gap-3">
                                    <span class="avatar w-8 h-8 {{ $staff->role === 'dokter' ? 'bg-pale-lavender text-royal-indigo' : 'bg-teal-50 text-teal-700' }} text-xs font-semibold rounded-full flex items-center justify-center">
                                        {{ strtoupper(substr($staff->nama_lengkap, 0, 2)) }}
                                    </span>
                                    <div>
                                        <p class="leading-tight">{{ $staff->nama_lengkap }}</p>
                                        <span class="inline-block mt-0.5 text-[10px] font-medium uppercase tracking-wider px-1.5 py-0.5 rounded {{ $staff->role === 'dokter' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-teal-50 text-teal-700 border border-teal-200' }}">
                                            {{ ucfirst($staff->role) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-text-secondary">{{ $staff->email }}</td>
                                <td class="py-3.5 px-4 text-text-secondary">
                                    {{ $staff->created_at ? $staff->created_at->translatedFormat('d F Y, H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-amber-800 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Menunggu Verifikasi
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        {{-- Tombol Terima / Setujui --}}
                                        <form method="POST" action="{{ route('admin.staff.approve', $staff->id_pengguna) }}" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui pendaftaran {{ addslashes($staff->nama_lengkap) }} sebagai {{ ucfirst($staff->role) }} di {{ addslashes($hospital->hospital_name) }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-primary text-xs py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white border-transparent">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Terima
                                            </button>
                                        </form>

                                        {{-- Tombol Tolak --}}
                                        <button type="button"
                                                onclick="openModalReject({{ $staff->id_pengguna }}, '{{ addslashes($staff->nama_lengkap) }}', '{{ ucfirst($staff->role) }}')"
                                                class="btn btn-secondary text-xs py-1.5 px-3 text-rose-600 hover:bg-rose-50 hover:border-rose-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($pendingStaff->hasPages())
                <div class="p-4 border-t border-border-subtle">
                    {{ $pendingStaff->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- Modal Penolakan Pendaftaran --}}
<div id="modalRejectStaff" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="card max-w-lg w-full p-6 shadow-2xl relative bg-white border border-border-subtle" onclick="event.stopPropagation()">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0 border border-rose-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-medical-navy">Tolak Pendaftaran Calon Staf</h3>
                <p class="text-xs text-text-secondary mt-1">
                    Anda akan menolak permohonan akun <strong id="rejectStaffName" class="text-medical-navy font-semibold"></strong> (<span id="rejectStaffRole"></span>).
                </p>

                <form id="formRejectStaff" method="POST" action="" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="rejectReason" class="block text-xs font-semibold text-medical-navy mb-1.5">
                            Alasan Penolakan <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="rejectReason" name="reason" rows="3" required minlength="5" maxlength="500"
                                  class="field text-xs resize-none"
                                  placeholder="Contoh: Surat Izin Praktik (SIP) belum terverifikasi atau tidak terdaftar dalam penugasan rumah sakit."></textarea>
                        <p class="text-[11px] text-text-secondary mt-1">Alasan ini akan ditampilkan kepada calon staf saat mencoba masuk.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-border-subtle">
                        <button type="button" onclick="closeModalReject()" class="btn btn-secondary text-xs py-2 px-4">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary text-xs py-2 px-4 bg-rose-600 hover:bg-rose-700 text-white border-transparent">
                            Konfirmasi Tolak Pendaftaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openModalReject(userId, userName, userRole) {
        const form = document.getElementById('formRejectStaff');
        form.action = `/admin/verifikasi/${userId}/tolak`;
        document.getElementById('rejectStaffName').innerText = userName;
        document.getElementById('rejectStaffRole').innerText = userRole;
        document.getElementById('rejectReason').value = '';
        document.getElementById('modalRejectStaff').classList.remove('hidden');
    }

    function closeModalReject() {
        document.getElementById('modalRejectStaff').classList.add('hidden');
    }

    document.getElementById('modalRejectStaff')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalReject();
        }
    });
</script>
@endsection


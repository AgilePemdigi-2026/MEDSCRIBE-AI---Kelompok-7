@extends('layouts.admin')

@section('title', 'Tenaga Medis Nonaktif - MedScribe AI')

@section('content')
<div class="space-y-6 rise">

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border-subtle">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">Tenaga Medis Nonaktif</h1>
            <p class="text-xs text-text-secondary mt-0.5">Daftar dokter dan perawat yang dinonaktifkan dari penugasan di {{ $hospital->hospital_name }}</p>
        </div>
        <span class="tag-id bg-slate-100 text-slate-700 border border-slate-200 font-medium self-start sm:self-auto">
            Total Nonaktif: {{ $inactiveStaff->total() }} Staf
        </span>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.inactive') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="cari" value="{{ $search }}"
                       class="field pl-9 text-xs"
                       placeholder="Cari berdasarkan nama tenaga medis atau email...">
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
                    <a href="{{ route('admin.inactive') }}" class="btn btn-secondary text-xs py-2 px-3 flex-shrink-0" title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Tenaga Medis Nonaktif --}}
    <div class="card overflow-hidden">
        @if ($inactiveStaff->isEmpty())
            <div class="py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <p class="text-sm font-semibold text-medical-navy">Tidak ada staf nonaktif</p>
                <p class="text-xs text-text-secondary mt-1">
                    {{ $search || $roleFilter ? 'Tidak ada tenaga medis nonaktif yang sesuai dengan kriteria filter.' : 'Seluruh tenaga medis terdaftar berstatus aktif di rumah sakit ini.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-soft-offwhite text-text-secondary border-b border-border-subtle">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Nama Tenaga Medis</th>
                            <th class="py-3 px-4 font-semibold">Email</th>
                            <th class="py-3 px-4 font-semibold">Tanggal Dinonaktifkan</th>
                            <th class="py-3 px-4 font-semibold">Alasan Penonaktifan</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/70">
                        @foreach ($inactiveStaff as $staff)
                            <tr class="hover:bg-soft-offwhite/40 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-medical-navy flex items-center gap-3">
                                    <span class="avatar w-8 h-8 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full flex items-center justify-center">
                                        {{ strtoupper(substr($staff->nama_lengkap, 0, 2)) }}
                                    </span>
                                    <div>
                                        <p class="leading-tight text-slate-700">{{ $staff->nama_lengkap }}</p>
                                        <span class="inline-block mt-0.5 text-[10px] font-medium uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ ucfirst($staff->role) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-text-secondary">{{ $staff->email }}</td>
                                <td class="py-3.5 px-4 text-text-secondary">
                                    {{ $staff->deactivated_at ? $staff->deactivated_at->translatedFormat('d F Y, H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-xs">
                                    <div class="p-2 rounded bg-slate-50 border border-slate-200 text-[11px] leading-relaxed">
                                        {{ $staff->deactivation_reason ?: 'Tidak dicantumkan alasan.' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.staff.reactivate', $staff->id_pengguna) }}" onsubmit="return confirm('Apakah Anda yakin ingin mengaktifkan kembali akun {{ addslashes($staff->nama_lengkap) }} sebagai {{ ucfirst($staff->role) }} aktif di {{ addslashes($hospital->hospital_name) }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary text-xs py-1.5 px-3 text-royal-indigo hover:bg-pale-lavender/50 border-royal-indigo/30">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Aktifkan Kembali
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($inactiveStaff->hasPages())
                <div class="p-4 border-t border-border-subtle">
                    {{ $inactiveStaff->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection


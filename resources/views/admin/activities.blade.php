@extends('layouts.admin')

@section('title', 'Riwayat Aktivitas Admin - MedScribe AI')

@section('content')
<div class="space-y-6 rise">

    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border-subtle">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-medical-navy">Audit Log Aktivitas</h1>
            <p class="text-xs text-text-secondary mt-0.5">Catatan audit seluruh tindakan administratif di {{ $hospital->hospital_name }}</p>
        </div>
        <span class="tag-id bg-pale-lavender text-royal-indigo font-medium self-start sm:self-auto">
            Total Log: {{ $logs->total() }} Catatan
        </span>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('admin.activities') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="cari" value="{{ $search }}"
                       class="field pl-9 text-xs"
                       placeholder="Cari berdasarkan nama staf atau alasan tindakan...">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-text-secondary absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="action" class="field text-xs sm:w-52" onchange="this.form.submit()">
                    <option value="" {{ empty($actionFilter) ? 'selected' : '' }}>Semua Jenis Tindakan</option>
                    <option value="approve_staff" {{ $actionFilter === 'approve_staff' ? 'selected' : '' }}>Setujui Pendaftaran</option>
                    <option value="reject_staff" {{ $actionFilter === 'reject_staff' ? 'selected' : '' }}>Tolak Pendaftaran</option>
                    <option value="deactivate_staff" {{ $actionFilter === 'deactivate_staff' ? 'selected' : '' }}>Nonaktifkan Staf</option>
                    <option value="reactivate_staff" {{ $actionFilter === 'reactivate_staff' ? 'selected' : '' }}>Aktifkan Kembali Staf</option>
                </select>

                <button type="submit" class="btn btn-primary text-xs py-2 px-4 flex-shrink-0">
                    Filter
                </button>

                @if ($search !== '' || !empty($actionFilter))
                    <a href="{{ route('admin.activities') }}" class="btn btn-secondary text-xs py-2 px-3 flex-shrink-0" title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Riwayat Audit --}}
    <div class="card overflow-hidden">
        @if ($logs->isEmpty())
            <div class="py-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-semibold text-medical-navy">Belum ada riwayat aktivitas</p>
                <p class="text-xs text-text-secondary mt-1">
                    {{ $search || $actionFilter ? 'Tidak ada catatan audit yang cocok dengan filter yang dipilih.' : 'Belum ada tindakan administratif yang tercatat di sistem rumah sakit ini.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-soft-offwhite text-text-secondary border-b border-border-subtle">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Waktu & Tanggal</th>
                            <th class="py-3 px-4 font-semibold">Tindakan Admin</th>
                            <th class="py-3 px-4 font-semibold">Tenaga Medis Terkait</th>
                            <th class="py-3 px-4 font-semibold">Perubahan Status</th>
                            <th class="py-3 px-4 font-semibold">Keterangan / Alasan</th>
                            <th class="py-3 px-4 font-semibold">Eksekutor (Admin)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/70">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-soft-offwhite/40 transition-colors">
                                <td class="py-3.5 px-4 text-text-secondary whitespace-nowrap">
                                    <div class="font-medium text-medical-navy">{{ $log->created_at ? $log->created_at->translatedFormat('d M Y') : '-' }}</div>
                                    <div class="text-[11px] text-text-secondary">{{ $log->created_at ? $log->created_at->format('H:i:s') : '-' }} WIB</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full border {{ $log->action_badge_class }}">
                                        {{ $log->action_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-medical-navy">
                                    <div class="leading-tight">{{ $log->target_name }}</div>
                                    <span class="inline-block mt-0.5 text-[10px] font-medium uppercase tracking-wider px-1.5 py-0.5 rounded {{ $log->target_role === 'dokter' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-teal-50 text-teal-700 border border-teal-200' }}">
                                        {{ ucfirst($log->target_role ?: 'Staf') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-[11px]">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px]">
                                            {{ $log->status_before ?: 'none' }}
                                        </span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                        <span class="px-2 py-0.5 rounded bg-pale-lavender text-royal-indigo font-mono text-[10px] font-semibold">
                                            {{ $log->status_after ?: 'none' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-sm">
                                    <p class="text-[11px] leading-relaxed line-clamp-2" title="{{ $log->reason }}">
                                        {{ $log->reason ?: '-' }}
                                    </p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-text-secondary">
                                    <div class="font-medium text-medical-navy">{{ $log->admin ? $log->admin->nama_lengkap : 'Admin' }}</div>
                                    <div class="text-[10px] text-text-secondary font-mono">IP: {{ $log->ip_address ?: '127.0.0.1' }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="p-4 border-t border-border-subtle">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection


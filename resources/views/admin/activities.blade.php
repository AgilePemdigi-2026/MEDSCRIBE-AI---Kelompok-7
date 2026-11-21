@extends('layouts.admin')

@section('title', 'Riwayat Aktivitas Admin - MedScribe AI')

@section('content')
@php
    $adaFilter = $search !== '' || ! empty($actionFilter);
@endphp

<div class="space-y-6">
    <x-page-header :eyebrow="'Audit Log Aktivitas · ' . $hospital->hospital_name"
                   title="Riwayat Aktivitas Admin"
                   subtitle="Setiap persetujuan, penolakan, penonaktifan, dan pengaktifan kembali tenaga medis tercatat di sini.">
        <span class="chip"><span class="num font-semibold">{{ $logs->total() }}</span> catatan</span>
    </x-page-header>

    <section class="card spotlight overflow-hidden rise" style="--i: 1" aria-label="Daftar riwayat aktivitas">
        <div class="p-5 sm:p-6">
            @include('admin.partials.filter-toolbar', [
                'route' => 'admin.activities',
                'search' => $search,
                'placeholder' => 'Nama staf atau alasan',
                'select' => ['name' => 'action', 'label' => 'Jenis tindakan', 'selected' => $actionFilter ?? '', 'options' => [
                    '' => 'Semua tindakan',
                    'approve_staff' => 'Menyetujui pendaftaran',
                    'reject_staff' => 'Menolak pendaftaran',
                    'deactivate_staff' => 'Menonaktifkan tenaga medis',
                    'reactivate_staff' => 'Mengaktifkan kembali',
                ]],
                'active' => $adaFilter,
            ])
        </div>

        @if ($logs->isEmpty())
            <x-empty-state class="border-t border-border-subtle"
                           :title="$adaFilter ? 'Tidak ada catatan yang cocok' : 'Belum ada aktivitas'"
                           :hint="$adaFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Tindakan admin terhadap tenaga medis akan tercatat di sini.'"
                           icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        @else
            <div class="overflow-x-auto border-t border-border-subtle">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Waktu</th>
                            <th scope="col">Tindakan</th>
                            <th scope="col">Tenaga Medis</th>
                            <th scope="col" class="hidden lg:table-cell">Perubahan Status</th>
                            <th scope="col" class="hidden md:table-cell">Alasan</th>
                            <th scope="col" class="hidden xl:table-cell">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php $waktu = $log->created_at?->timezone('Asia/Jakarta')->locale('id'); @endphp
                            <tr class="group">
                                <td class="whitespace-nowrap">
                                    @if ($waktu)
                                        <p class="font-medium text-medical-navy">{{ $waktu->isoFormat('D MMM Y') }}</p>
                                        <p class="text-xs text-text-secondary num">{{ $waktu->format('H:i') }} WIB</p>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><span class="status {{ $log->action_badge_class }}">{{ $log->action_label }}</span></td>
                                <td>
                                    <p class="font-semibold text-medical-navy transition-colors group-hover:text-royal-indigo-hover">{{ $log->target_name ?? '-' }}</p>
                                    @if ($log->target_role)
                                        <span class="chip mt-1">{{ ucfirst($log->target_role) }}</span>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell whitespace-nowrap text-text-secondary">
                                    {{ \App\Models\AdminAuditLog::statusLabel($log->status_before) }}
                                    <span aria-hidden="true">→</span><span class="sr-only">menjadi</span>
                                    <span class="font-semibold text-medical-navy">{{ \App\Models\AdminAuditLog::statusLabel($log->status_after) }}</span>
                                </td>
                                <td class="hidden md:table-cell max-w-sm text-text-body">
                                    <p class="line-clamp-2" title="{{ $log->reason }}">{{ $log->reason ?: '-' }}</p>
                                </td>
                                <td class="hidden xl:table-cell text-text-secondary whitespace-nowrap">
                                    {{ $log->admin?->nama_lengkap ?? '-' }}
                                    @if ($log->ip_address)
                                        <p class="text-xs num">IP {{ $log->ip_address }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="p-4 sm:px-6 border-t border-border-subtle">
                    {{ $logs->links(null, ['noun' => 'catatan']) }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection

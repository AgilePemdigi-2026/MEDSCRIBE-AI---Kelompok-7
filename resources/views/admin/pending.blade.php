@extends('layouts.admin')

@section('title', 'Verifikasi Pendaftaran Tenaga Medis - MedScribe AI')

@section('content')
@php
    $adaFilter = $search !== '' || ! empty($roleFilter);
@endphp

<div class="space-y-6">
    <x-page-header :eyebrow="'Tenaga medis · ' . $hospital->hospital_name"
                   title="Verifikasi Pendaftaran Staf"
                   subtitle="Dokter dan perawat yang mendaftar ke rumah sakit ini. Setujui agar akunnya aktif, atau tolak dengan alasan.">
        <span class="chip"><span class="num font-semibold">{{ $pendingStaff->total() }}</span> menunggu verifikasi</span>
    </x-page-header>

    @include('partials.form-errors')

    <section class="card spotlight overflow-hidden rise" style="--i: 1" aria-label="Daftar pendaftaran menunggu verifikasi">
        <div class="p-5 sm:p-6">
            @include('admin.partials.filter-toolbar', [
                'route' => 'admin.pending',
                'search' => $search,
                'placeholder' => 'Nama atau email pendaftar',
                'select' => ['name' => 'role', 'label' => 'Peran', 'selected' => $roleFilter ?? '', 'options' => [
                    '' => 'Semua peran',
                    'dokter' => 'Dokter',
                    'perawat' => 'Perawat',
                ]],
                'active' => $adaFilter,
            ])
        </div>

        @if ($pendingStaff->isEmpty())
            <x-empty-state class="border-t border-border-subtle"
                           :title="$adaFilter ? 'Tidak ada pendaftar yang cocok' : 'Tidak ada pendaftaran yang menunggu'"
                           :hint="$adaFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Semua pendaftaran dokter dan perawat sudah diproses.'"
                           icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        @else
            <div class="overflow-x-auto border-t border-border-subtle">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Nama Pendaftar</th>
                            <th scope="col" class="hidden md:table-cell">Email</th>
                            <th scope="col" class="hidden lg:table-cell">Tanggal Daftar</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingStaff as $staff)
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="avatar w-9 h-9 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:border-royal-indigo group-hover:text-white" aria-hidden="true">{{ $staff->inisial }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-medical-navy transition-colors group-hover:text-royal-indigo-hover">{{ $staff->nama_lengkap }}</p>
                                            <span class="chip mt-1">{{ $staff->peran_label }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell text-text-secondary">{{ $staff->email }}</td>
                                <td class="hidden lg:table-cell text-text-secondary whitespace-nowrap">
                                    {{ $staff->created_at ? $staff->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-' }}
                                </td>
                                <td><span class="status status-warn">Menunggu verifikasi</span></td>
                                <td class="text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm"
                                                data-dialog-open="dlgApproveStaff" data-staff-url="{{ route('admin.staff.approve', $staff) }}"
                                                data-staff-name="{{ $staff->nama_lengkap }}" data-staff-role="{{ $staff->peran_label }}">Setujui</button>
                                        <button type="button" class="btn btn-danger-quiet btn-sm"
                                                data-dialog-open="dlgRejectStaff" data-staff-url="{{ route('admin.staff.reject', $staff) }}"
                                                data-staff-name="{{ $staff->nama_lengkap }}" data-staff-role="{{ $staff->peran_label }}">Tolak</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($pendingStaff->hasPages())
                <div class="p-4 sm:px-6 border-t border-border-subtle">
                    {{ $pendingStaff->links(null, ['noun' => 'pendaftar']) }}
                </div>
            @endif
        @endif
    </section>
</div>

@include('admin.partials.staff-dialogs')
@endsection

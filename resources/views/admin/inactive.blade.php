@extends('layouts.admin')

@section('title', 'Tenaga Medis Nonaktif - MedScribe AI')

@section('content')
@php
    $adaFilter = $search !== '' || ! empty($roleFilter);
@endphp

<div class="space-y-6">
    <x-page-header :eyebrow="'Tenaga medis · ' . $hospital->hospital_name"
                   title="Tenaga Medis Nonaktif"
                   subtitle="Dokter dan perawat yang sudah tidak bertugas. Akun bisa diaktifkan kembali kapan saja.">
        <span class="chip"><span class="num font-semibold">{{ $inactiveStaff->total() }}</span> nonaktif</span>
    </x-page-header>

    @include('partials.form-errors')

    <section class="card spotlight overflow-hidden rise" style="--i: 1" aria-label="Daftar tenaga medis nonaktif">
        <div class="p-5 sm:p-6">
            @include('admin.partials.filter-toolbar', [
                'route' => 'admin.inactive',
                'search' => $search,
                'placeholder' => 'Nama atau email tenaga medis',
                'select' => ['name' => 'role', 'label' => 'Peran', 'selected' => $roleFilter ?? '', 'options' => [
                    '' => 'Semua peran',
                    'dokter' => 'Dokter',
                    'perawat' => 'Perawat',
                ]],
                'active' => $adaFilter,
            ])
        </div>

        @if ($inactiveStaff->isEmpty())
            <x-empty-state class="border-t border-border-subtle"
                           :title="$adaFilter ? 'Tidak ada tenaga medis yang cocok' : 'Tidak ada tenaga medis nonaktif'"
                           :hint="$adaFilter ? 'Coba kata kunci lain atau hapus filter.' : 'Semua dokter dan perawat terdaftar masih aktif.'"
                           icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        @else
            <div class="overflow-x-auto border-t border-border-subtle">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Nama Tenaga Medis</th>
                            <th scope="col" class="hidden xl:table-cell">Email</th>
                            <th scope="col" class="hidden lg:table-cell">Dinonaktifkan</th>
                            <th scope="col">Alasan</th>
                            <th scope="col" class="text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inactiveStaff as $staff)
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="avatar w-9 h-9 bg-soft-offwhite border border-border-subtle text-text-secondary text-xs transition-colors group-hover:bg-royal-indigo group-hover:border-royal-indigo group-hover:text-white" aria-hidden="true">{{ $staff->inisial }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-medical-navy transition-colors group-hover:text-royal-indigo-hover">{{ $staff->nama_lengkap }}</p>
                                            <span class="chip mt-1">{{ $staff->peran_label }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden xl:table-cell text-text-secondary">{{ $staff->email }}</td>
                                <td class="hidden lg:table-cell text-text-secondary whitespace-nowrap">
                                    {{ $staff->deactivated_at ? $staff->deactivated_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB' : '-' }}
                                </td>
                                <td class="text-text-body max-w-xs">
                                    <p class="line-clamp-2" title="{{ $staff->deactivation_reason }}">{{ $staff->deactivation_reason ?: '-' }}</p>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            data-dialog-open="dlgReactivateStaff" data-staff-url="{{ route('admin.staff.reactivate', $staff) }}"
                                            data-staff-name="{{ $staff->nama_lengkap }}" data-staff-role="{{ $staff->peran_label }}">
                                        Aktifkan kembali
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($inactiveStaff->hasPages())
                <div class="p-4 sm:px-6 border-t border-border-subtle">
                    {{ $inactiveStaff->links(null, ['noun' => 'tenaga medis']) }}
                </div>
            @endif
        @endif
    </section>
</div>

@include('admin.partials.staff-dialogs')
@endsection

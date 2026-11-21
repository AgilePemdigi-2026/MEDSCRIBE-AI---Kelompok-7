{{--
    Isi halaman daftar tenaga medis aktif (dokter atau perawat).
    Parameter: staff (paginator), roleLabel ('Dokter' / 'Perawat'), route, emptyIcon,
               plus $hospital, $search, $sort dari controller.
--}}
@php
    $roleLower = strtolower($roleLabel);
@endphp

<div class="space-y-6">
    <x-page-header :eyebrow="'Tenaga medis · ' . $hospital->hospital_name"
                   :title="'Manajemen ' . $roleLabel . ' Aktif'"
                   :subtitle="$roleLabel . ' yang sedang bertugas dan bisa masuk ke dashboard.'">
        <span class="chip"><span class="num font-semibold">{{ $staff->total() }}</span> {{ $roleLower }} aktif</span>
    </x-page-header>

    @include('partials.form-errors')

    <section class="card spotlight overflow-hidden rise" style="--i: 1" aria-label="Daftar {{ $roleLower }} aktif">
        <div class="p-5 sm:p-6">
            @include('admin.partials.filter-toolbar', [
                'route' => $route,
                'search' => $search,
                'placeholder' => 'Nama atau email ' . $roleLower,
                'select' => ['name' => 'sort', 'label' => 'Urutkan', 'selected' => $sort, 'options' => [
                    'nama' => 'Nama A-Z',
                    'terbaru' => 'Terdaftar terbaru',
                    'terlama' => 'Terdaftar terlama',
                ]],
                'active' => $search !== '',
            ])
        </div>

        @if ($staff->isEmpty())
            <x-empty-state class="border-t border-border-subtle"
                           :title="$search !== '' ? 'Tidak ada hasil untuk “' . $search . '”' : 'Belum ada ' . $roleLower . ' aktif'"
                           :hint="$search !== '' ? 'Coba kata kunci lain atau hapus filter.' : $roleLabel . ' yang pendaftarannya disetujui akan muncul di sini.'"
                           :icon="$emptyIcon" />
        @else
            <div class="overflow-x-auto border-t border-border-subtle">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Nama {{ $roleLabel }}</th>
                            <th scope="col" class="hidden md:table-cell">Email</th>
                            <th scope="col" class="hidden lg:table-cell">Tanggal Registrasi</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staff as $person)
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="avatar w-9 h-9 bg-pale-lavender text-royal-indigo-hover text-xs transition-colors group-hover:bg-royal-indigo group-hover:border-royal-indigo group-hover:text-white" aria-hidden="true">{{ $person->inisial }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-medical-navy transition-colors group-hover:text-royal-indigo-hover">{{ $person->nama_lengkap }}</p>
                                            <p class="md:hidden text-xs text-text-secondary truncate">{{ $person->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell text-text-secondary">{{ $person->email }}</td>
                                <td class="hidden lg:table-cell text-text-secondary whitespace-nowrap">
                                    {{ $person->created_at ? $person->created_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y') : '-' }}
                                </td>
                                <td><span class="status status-ok">Aktif</span></td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-danger-quiet btn-sm"
                                            data-dialog-open="dlgDeactivateStaff" data-staff-url="{{ route('admin.staff.deactivate', $person) }}"
                                            data-staff-name="{{ $person->nama_lengkap }}" data-staff-role="{{ $person->peran_label }}">
                                        Nonaktifkan
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($staff->hasPages())
                <div class="p-4 sm:px-6 border-t border-border-subtle">
                    {{ $staff->links(null, ['noun' => $roleLower]) }}
                </div>
            @endif
        @endif
    </section>
</div>

@include('admin.partials.staff-dialogs')

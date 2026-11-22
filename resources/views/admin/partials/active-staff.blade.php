{{--
    Isi halaman daftar tenaga medis aktif (dokter atau perawat).
    Parameter: staff (paginator), roleLabel ('Dokter' / 'Perawat'), route, emptyIcon,
               plus $hospital, $search, $sort dari controller.
--}}
@php
    $roleLower = strtolower($roleLabel);
    // Staf yang sedang dalam proses mutasi tidak bisa dimutasi atau dinonaktifkan dulu.
    $sedangMutasi = \App\Models\MutasiTenagaMedis::where('status', 'menunggu')
        ->whereIn('id_pengguna', $staff->pluck('id_pengguna'))->pluck('id_pengguna')->all();
    $rsLain = \App\Models\Hospital::active()->where('id', '!=', $hospital->id)->orderBy('hospital_name')->get();
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
                                <td>
                                    @if (in_array($person->id_pengguna, $sedangMutasi, true))
                                        <span class="status status-warn">Proses mutasi</span>
                                    @else
                                        <span class="status status-ok">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if (in_array($person->id_pengguna, $sedangMutasi, true))
                                        <a href="{{ route('admin.mutasi', ['tab' => 'diajukan']) }}" class="btn btn-secondary btn-sm">Lihat mutasi</a>
                                    @else
                                        <div class="inline-flex gap-2">
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    data-dialog-open="dlgMutasiStaff" data-staff-url="{{ route('admin.mutasi.lepas', $person) }}"
                                                    data-staff-name="{{ $person->nama_lengkap }}" data-staff-role="{{ $person->peran_label }}">
                                                <x-icon name="arrow-right" /> Mutasi
                                            </button>
                                            <button type="button" class="btn btn-danger-quiet btn-sm"
                                                    data-dialog-open="dlgDeactivateStaff" data-staff-url="{{ route('admin.staff.deactivate', $person) }}"
                                                    data-staff-name="{{ $person->nama_lengkap }}" data-staff-role="{{ $person->peran_label }}">
                                                Nonaktifkan
                                            </button>
                                        </div>
                                    @endif
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

<x-dialog id="dlgMutasiStaff" size="md" title="Mutasi ke rumah sakit lain?"
          description="Permintaan dikirim ke admin rumah sakit tujuan. Selama menunggu, tenaga medis tetap bertugas di sini.">
    <form method="POST" id="formMutasiStaff" data-staff-form data-submit-loading class="space-y-4">
        @csrf
        <div class="panel text-sm">
            <p class="text-text-secondary">Tenaga medis</p>
            <p class="font-semibold text-medical-navy"><span data-staff-fill="name"></span> · <span data-staff-fill="role"></span></p>
        </div>
        <div>
            <label for="mutasiTujuan" class="field-label">Rumah sakit tujuan <span class="req" aria-hidden="true">*</span></label>
            <select id="mutasiTujuan" name="hospital_tujuan_id" required class="field" data-dialog-focus>
                <option value="" disabled selected>Pilih rumah sakit</option>
                @foreach ($rsLain as $rs)
                    <option value="{{ $rs->id }}">{{ $rs->hospital_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="mutasiAlasan" class="field-label">Alasan mutasi <span class="req" aria-hidden="true">*</span></label>
            <textarea id="mutasiAlasan" name="alasan" rows="3" required minlength="5" maxlength="500" class="field"
                      placeholder="Contoh: Penugasan baru sesuai surat keputusan direktur."></textarea>
            <p class="field-hint">No. SIP/SIPP akan dikosongkan setelah mutasi disetujui, karena izin praktik berlaku per tempat praktik.</p>
        </div>
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formMutasiStaff" class="btn btn-primary">Kirim permintaan</button>
    </x-slot:actions>
</x-dialog>

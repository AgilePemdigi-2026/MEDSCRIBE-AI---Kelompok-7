@extends('layouts.app')

@section('title', 'Review & Edit Draf SOAP - MedScribe AI')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <a href="{{ route('dokter.dashboard') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700">← Kembali ke antrean</a>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mt-2">Review &amp; Edit Draf SOAP</h1>
            <p class="text-sm text-slate-500 mt-1">Tinjau kembali data pasien dan draf simulasi sebelum verifikasi dokter.</p>
    @if ($kunjungan->wawancara)<a href="{{ route('dokter.interview.show', $kunjungan) }}" class="inline-block text-xs font-semibold text-sky-600 hover:underline mt-2">← Kembali ke wawancara</a>@endif
            @if ($generationError)<div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ $generationError }}</div>@endif
            @if (session('warning'))<div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{{ session('warning') }}</div>@endif
        </div>
        @if (config('medscribe.soap_driver') === 'openrouter')
            <form method="POST" action="{{ route('dokter.soap.generate', $kunjungan) }}" onsubmit="return confirm('Buat ulang draf SOAP dengan Nemotron? Draf SOAP saat ini akan diganti.')">
                @csrf
                <button type="submit" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-xs font-bold text-violet-700 hover:bg-violet-100">Buat ulang dengan Nemotron</button>
            </form>
        @endif
        @php($isVerified = $kunjungan->finalisasi?->FINAL === true)
        <span class="inline-flex items-center gap-2 self-start px-3 py-2 rounded-xl text-xs font-bold border {{ $isVerified ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
            <span class="w-2 h-2 rounded-full {{ $isVerified ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
            {{ $isVerified ? 'Terverifikasi' : 'Draf AI — Belum Diverifikasi' }}
        </span>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <strong>Periksa kembali:</strong>
            <ul class="list-disc list-inside mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($draf->needs_review)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Sumber transkrip atau TTV berubah setelah draf dibuat. Isi SOAP tidak ditimpa otomatis; mohon tinjau ulang sebelum menyimpan atau memverifikasi.</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">
        <aside class="xl:col-span-4 space-y-5">
            <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div><p class="text-[11px] font-bold tracking-widest text-sky-600 uppercase">Identitas kunjungan</p><h2 class="text-lg font-bold text-slate-900 mt-1">Data pasien</h2></div>
                    <span class="font-mono text-xs font-bold text-slate-500">KJ-{{ str_pad($kunjungan->id_kunjungan, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="space-y-3 text-sm">
                    <div><span class="text-xs text-slate-500">Nama pasien</span><input name="nama_pasien" form="soap-form" value="{{ old('nama_pasien', $kunjungan->pasien?->nama_pasien) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-semibold focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 outline-none" required></div>
                    <div><span class="text-xs text-slate-500">Nomor rekam medis</span><input name="no_rekam_medis" form="soap-form" value="{{ old('no_rekam_medis', $kunjungan->pasien?->no_rekam_medis) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 outline-none" required></div>
                    <div class="grid grid-cols-2 gap-3"><label><span class="text-xs text-slate-500">Tanggal lahir</span><input type="date" name="tanggal_lahir" form="soap-form" value="{{ old('tanggal_lahir', $kunjungan->pasien?->tanggal_lahir) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-sky-500"></label><label><span class="text-xs text-slate-500">Jenis kelamin</span><select name="jenis_kelamin" form="soap-form" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:border-sky-500"><option value="">Belum diisi</option><option value="Laki-laki" @selected(old('jenis_kelamin', $kunjungan->pasien?->jenis_kelamin) === 'Laki-laki')>Laki-laki</option><option value="Perempuan" @selected(old('jenis_kelamin', $kunjungan->pasien?->jenis_kelamin) === 'Perempuan')>Perempuan</option></select></label></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-4">Data ini berasal dari input aplikasi dan dapat dikoreksi sebelum disimpan.</p>
            </section>

            <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <div class="flex justify-between items-center mb-3"><div><p class="text-[11px] font-bold tracking-widest text-sky-600 uppercase">Input manual</p><h2 class="text-lg font-bold text-slate-900 mt-1">Tanda-tanda vital</h2></div><span class="text-[11px] text-slate-400">Sumber: pemeriksaan awal</span></div>
                <div class="grid grid-cols-2 gap-2">
                    @php($vitals = [['Tekanan darah', $kunjungan->pemeriksaanAwal?->tekanan_darah, 'mmHg'], ['Denyut nadi', $kunjungan->pemeriksaanAwal?->nadi, 'x/menit'], ['Suhu tubuh', $kunjungan->pemeriksaanAwal?->suhu_tubuh, '°C'], ['Frekuensi napas', $kunjungan->pemeriksaanAwal?->laju_pernapasan, 'x/menit']])
                    @foreach ($vitals as [$label, $value, $unit])<div class="rounded-xl bg-slate-50 border border-slate-100 p-3"><p class="text-[11px] text-slate-500">{{ $label }}</p><p class="font-bold text-slate-800 mt-1">{{ $value !== null && $value !== '' ? $value : 'Belum diisi' }} <span class="text-[11px] font-medium text-slate-400">{{ $value !== null && $value !== '' ? $unit : '' }}</span></p></div>@endforeach
                </div>
                <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2.5 mt-3">TTV adalah data input aplikasi, bukan hasil verifikasi klinis otomatis.</p>
            </section>

            <details class="bg-white rounded-2xl border border-slate-200 shadow-xs group" open><summary class="cursor-pointer list-none p-5 flex justify-between items-center"><span><span class="text-[11px] font-bold tracking-widest text-violet-600 uppercase">Transkrip ditinjau</span><strong class="block text-lg text-slate-900 mt-1">Sumber lokal</strong></span><span class="text-slate-400 group-open:rotate-180 transition">⌄</span></summary><div class="border-t border-slate-100 p-5 text-sm leading-6 text-slate-600 whitespace-pre-line">{{ $transcript }}</div></details>
            @if ($draf->missing_information)<section class="bg-amber-50 rounded-2xl border border-amber-200 p-5"><h2 class="font-bold text-amber-900">Informasi belum tersedia</h2><ul class="list-disc list-inside text-sm text-amber-800 mt-2">@foreach ($draf->missing_information as $item)<li>{{ is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item }}</li>@endforeach</ul></section>@endif
        </aside>

        <form id="soap-form" method="POST" action="{{ route('dokter.soap.save', $kunjungan) }}" class="xl:col-span-8 space-y-5">
            @csrf @method('PUT')
            <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6"><div class="flex items-center justify-between mb-5"><div><p class="text-[11px] font-bold tracking-widest text-sky-600 uppercase">Catatan klinis</p><h2 class="text-lg font-bold text-slate-900 mt-1">Editor SOAP</h2></div><span class="text-xs text-slate-400">Semua bagian dapat diedit</span></div><div class="space-y-4">
                @foreach ([['S — Subjective','subjective_text','Keluhan dan informasi dari pasien.'],['O — Objective','objective_text','Hanya data pemeriksaan dan TTV yang tersedia.'],['A — Assessment','assessment_text','Penilaian awal, bukan diagnosis final.'],['P — Plan','plan_text','Rencana yang masih perlu ditinjau dokter.']] as [$label, $field, $hint])<div><label for="{{ $field }}" class="flex items-center justify-between text-sm font-bold text-slate-800"><span>{{ $label }}</span><span class="text-[11px] font-normal text-slate-400">{{ $hint }}</span></label><textarea id="{{ $field }}" name="{{ $field }}" rows="4" required class="mt-2 w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm leading-6 text-slate-700 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">{{ old($field, $draf->$field) }}</textarea></div>@endforeach
            </div></section>
            <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6"><div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-900">Rekomendasi kode</h2><p class="text-xs text-slate-500 mt-1">Data simulasi, belum divalidasi terhadap sumber kode resmi.</p></div><span class="text-[11px] rounded-lg bg-violet-50 text-violet-700 border border-violet-100 px-2 py-1">ICD-10</span></div><select name="kode_icd10" class="mt-4 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-sky-500"><option value="">Belum ada rekomendasi</option>@foreach ($icd10Options as $code => $description)<option value="{{ $code }}" @selected(old('kode_icd10', $draf->kode_icd10) === $code)>{{ $code }} — {{ $description }}</option>@endforeach</select><div class="mt-4 rounded-xl border border-dashed border-slate-200 p-3 text-xs text-slate-400">LOINC: Belum tersedia. Tidak ada hasil pemeriksaan yang ditambahkan.</div></section>
            <div class="flex flex-col sm:flex-row sm:justify-end gap-3"><button type="submit" class="rounded-xl bg-sky-600 hover:bg-sky-700 px-5 py-3 text-sm font-bold text-white shadow-md shadow-sky-600/20">Simpan Perubahan</button><button type="button" id="verify-button" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-3 text-sm font-bold text-white">Verifikasi Draf</button></div>
             <p class="text-right text-xs text-slate-400">Verifikasi adalah tindakan dokter di aplikasi, bukan validasi klinis otomatis.</p>
             <p class="text-right text-xs text-amber-700">Jika OpenRouter digunakan, hanya transkrip dan TTV minimum yang dikirim ke layanan eksternal. Audio tetap lokal.</p>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('verify-button')?.addEventListener('click', function () {
    if (!confirm('Verifikasi draf SOAP dengan perubahan yang sedang ditampilkan?')) return;
    const form = document.getElementById('soap-form');
    form.action = @json(route('dokter.soap.verify', $kunjungan));
    form.querySelector('input[name="_method"]')?.remove();
    form.submit();
});
</script>
@endsection

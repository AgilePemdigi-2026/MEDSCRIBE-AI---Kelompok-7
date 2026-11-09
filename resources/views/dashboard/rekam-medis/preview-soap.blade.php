@extends('layouts.app')

@section('title', 'Preview Dokumen SOAP Final - ' . $pasien->nama_pasien . ' - MedScribe AI')

@section('content')
<div class="space-y-6">

    {{-- Top Action Toolbar (Khusus Tampilan Layar, Tersembunyi saat Cetak/Download PDF) --}}
    <div class="print-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-white border border-border-subtle shadow-sm rise">
        <div class="flex items-center gap-3">
            <a href="{{ route('dokter.rekam-medis', $antrean->id_antrean) }}#finalisasi"
               class="btn btn-secondary min-h-10 px-4 text-xs font-semibold flex items-center gap-1.5 shadow-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Kembali</span>
            </a>
            <div class="h-6 w-px bg-border-subtle"></div>
            <div>
                <h1 class="text-sm font-semibold text-medical-navy leading-tight">Preview Rekam Medis SOAP</h1>
                <p class="text-[11px] text-text-secondary">Format cetak A4 Portrait · Dokumen Resmi Terverifikasi</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <span class="status status-ok text-xs font-mono">STATUS: FINAL</span>
            <button type="button" onclick="window.print()" id="btn-download-pdf"
                    class="btn min-h-10 px-5 bg-royal-indigo hover:bg-royal-indigo/90 text-white text-xs font-semibold rounded-xl shadow-sm flex items-center gap-2 transition-transform hover:scale-[1.02] active:scale-[0.98]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Download PDF</span>
            </button>
        </div>
    </div>

    {{-- Lembar Dokumen A4 Portrait --}}
    <div class="a4-document-wrapper flex justify-center pb-12">
        <article class="a4-sheet bg-white border border-slate-200 shadow-2xl rounded-2xl w-full max-w-[210mm] min-h-[297mm] p-8 sm:p-12 text-slate-800 space-y-6">

            {{-- 1. KOP RUMAH SAKIT RESMI --}}
            <header class="border-b-2 border-slate-800 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <span class="w-14 h-14 rounded-2xl bg-royal-indigo text-white flex items-center justify-center flex-shrink-0 font-bold text-xl shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 tracking-tight uppercase">
                                {{ $kunjungan->hospital?->hospital_name ?? 'RUMAH SAKIT UNIVERSITAS AIRLANGGA' }}
                            </h2>
                            <p class="text-xs font-semibold text-slate-700 tracking-wider uppercase">Instalasi Rawat Jalan · Poliklinik Spesialis</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $kunjungan->hospital?->address ?? 'Kampus C UNAIR, Jl. Dr. Ir. H. Soekarno, Mulyorejo, Surabaya 60115' }}
                                · Telp: (031) 5916290
                            </p>
                        </div>
                    </div>
                    <div class="text-right text-[11px] text-slate-500 font-mono">
                        <span class="inline-block px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold border border-emerald-300">DOKUMEN RESMI</span>
                        <p class="mt-1">ID: {{ $idKunjunganCode }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-200 text-center">
                    <h3 class="text-base font-extrabold text-slate-900 uppercase tracking-wide">
                        Catatan Perkembangan Pasien Terintegrasi (CPPT) - SOAP
                    </h3>
                    <p class="text-[11px] text-slate-600 mt-0.5">Rekam Medis Elektronik Rawat Jalan sesuai Permenkes No. 24 Tahun 2022</p>
                </div>
            </header>

            {{-- 2. IDENTITAS PASIEN (BAGIAN ATAS DOKUMEN) --}}
            <section class="rounded-xl border border-slate-300 bg-slate-50/70 p-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
                    <div class="space-y-1.5">
                        <div class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-500 font-medium">Nama Pasien:</span>
                            <span class="font-bold text-slate-900">{{ $pasien->nama_pasien }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-500 font-medium">No. Rekam Medis:</span>
                            <span class="font-mono font-bold text-slate-900">{{ $pasien->no_rekam_medis }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-500 font-medium">Tanggal Lahir / Usia:</span>
                            <span class="font-semibold text-slate-800">
                                {{ $pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->locale('id')->isoFormat('D MMMM Y') : '-' }}
                                ({{ $umur ? $umur . ' tahun' : '-' }})
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Jenis Kelamin:</span>
                            <span class="font-semibold text-slate-800">{{ $pasien->jenis_kelamin ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-500 font-medium">Tanggal Pemeriksaan:</span>
                            <span class="font-bold text-slate-900">{{ $tanggalPemeriksaan }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-500 font-medium">Nomor Antrean:</span>
                            <span class="font-mono font-semibold text-slate-800">A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-1">
                            <span class="text-slate-500 font-medium">Unit Pelayanan:</span>
                            <span class="font-semibold text-slate-800">Poliklinik Rawat Jalan</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Dokter Pemeriksa (DPJP):</span>
                            <span class="font-bold text-royal-indigo">{{ $dokterPemeriksa->nama_lengkap }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 3. KONTEN UTAMA SOAP (SUBJECTIVE, OBJECTIVE, ASSESSMENT, PLAN) --}}
            <section class="space-y-4 text-xs">

                {{-- S - SUBJECTIVE --}}
                <div class="border border-slate-300 rounded-xl overflow-hidden">
                    <div class="bg-slate-100 px-3.5 py-1.5 font-bold text-slate-900 border-b border-slate-300 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-royal-indigo text-white text-[11px] flex items-center justify-center font-bold">S</span>
                            <span>SUBJECTIVE (Anamnesis & Keluhan Pasien)</span>
                        </span>
                    </div>
                    <div class="p-3.5 bg-white text-slate-800 whitespace-pre-line leading-relaxed">
                        {{ $draf?->subjective_text ?: 'Tidak ada catatan keluhan subjektif.' }}
                    </div>
                </div>

                {{-- O - OBJECTIVE --}}
                <div class="border border-slate-300 rounded-xl overflow-hidden">
                    <div class="bg-slate-100 px-3.5 py-1.5 font-bold text-slate-900 border-b border-slate-300 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-royal-indigo text-white text-[11px] flex items-center justify-center font-bold">O</span>
                            <span>OBJECTIVE (Pemeriksaan Fisik & Tanda-Tanda Vital)</span>
                        </span>
                        @if ($pemeriksaanAwal?->perawat)
                            <span class="text-[10px] text-slate-500 font-normal">Perawat: {{ $pemeriksaanAwal->perawat->nama_lengkap }}</span>
                        @endif
                    </div>
                    <div class="p-3.5 bg-white space-y-3">
                        @if ($pemeriksaanAwal)
                            {{-- Tabel Ringkas TTV --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-[11px]">
                                <div><span class="text-slate-500 block">Tekanan Darah:</span><strong>{{ $pemeriksaanAwal->tekanan_darah ?: '-' }} mmHg</strong></div>
                                <div><span class="text-slate-500 block">Denyut Nadi:</span><strong>{{ $pemeriksaanAwal->nadi ?: '-' }} x/mnt</strong></div>
                                <div><span class="text-slate-500 block">Suhu Tubuh:</span><strong>{{ $pemeriksaanAwal->suhu_tubuh ?: '-' }} °C</strong></div>
                                <div><span class="text-slate-500 block">Pernapasan:</span><strong>{{ $pemeriksaanAwal->laju_pernapasan ?: '-' }} x/mnt</strong></div>
                                <div><span class="text-slate-500 block">Saturasi O2:</span><strong>{{ $pemeriksaanAwal->saturasi_oksigen ?: '-' }} %</strong></div>
                                <div><span class="text-slate-500 block">Berat / Tinggi:</span><strong>{{ $pemeriksaanAwal->berat_badan ?: '-' }} kg / {{ $pemeriksaanAwal->tinggi_badan ?: '-' }} cm</strong></div>
                                <div class="col-span-2">
                                    <span class="text-slate-500 block">Catatan Triase:</span>
                                    <span class="italic text-slate-700">{{ $pemeriksaanAwal->catatan ?: '-' }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="whitespace-pre-line leading-relaxed text-slate-800 pt-1">
                            {{ $draf?->objective_text ?: 'Pemeriksaan fisik dalam batas normal.' }}
                        </div>
                    </div>
                </div>

                {{-- A - ASSESSMENT --}}
                <div class="border border-slate-300 rounded-xl overflow-hidden">
                    <div class="bg-slate-100 px-3.5 py-1.5 font-bold text-slate-900 border-b border-slate-300 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-royal-indigo text-white text-[11px] flex items-center justify-center font-bold">A</span>
                            <span>ASSESSMENT (Penilaian Klinis & Diagnosis)</span>
                        </span>
                        @if ($draf?->kode_icd10)
                            <span class="font-mono text-[11px] bg-royal-indigo text-white px-2 py-0.5 rounded font-bold">
                                ICD-10: {{ $draf->kode_icd10 }}
                            </span>
                        @endif
                    </div>
                    <div class="p-3.5 bg-white space-y-2">
                        <p class="whitespace-pre-line leading-relaxed text-slate-800">{{ $draf?->assessment_text ?: '-' }}</p>
                        @if ($draf?->kode_icd10 && $icdDescription)
                            <div class="pt-1.5 border-t border-slate-100 text-[11px] text-slate-600 flex items-center gap-2">
                                <span class="font-semibold text-slate-800">Deskripsi ICD-10:</span>
                                <span>{{ $icdDescription }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- P - PLAN --}}
                <div class="border border-slate-300 rounded-xl overflow-hidden">
                    <div class="bg-slate-100 px-3.5 py-1.5 font-bold text-slate-900 border-b border-slate-300 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-royal-indigo text-white text-[11px] flex items-center justify-center font-bold">P</span>
                            <span>PLAN (Penatalaksanaan, Terapi & Tindak Lanjut Medis)</span>
                        </span>
                        @if ($tindakLanjut)
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold border border-emerald-300">
                                {{ $tindakLanjut->status ?: 'Tersimpan' }}
                            </span>
                        @endif
                    </div>
                    <div class="p-3.5 bg-white space-y-3.5">
                        <p class="whitespace-pre-line leading-relaxed text-slate-800">{{ $draf?->plan_text ?: '-' }}</p>

                        {{-- Rincian E-Resep Obat --}}
                        @if ($tindakLanjut && $tindakLanjut->eResep->isNotEmpty())
                            <div class="pt-2 border-t border-slate-200">
                                <h4 class="font-bold text-slate-900 mb-1.5 text-[11px] uppercase tracking-wider">Terapi Farmakologi (E-Resep):</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-[11px] border border-slate-200">
                                        <thead class="bg-slate-50 text-slate-700">
                                            <tr>
                                                <th class="p-2 border-b border-slate-200">No</th>
                                                <th class="p-2 border-b border-slate-200">Nama Obat</th>
                                                <th class="p-2 border-b border-slate-200">Sediaan</th>
                                                <th class="p-2 border-b border-slate-200">Dosis</th>
                                                <th class="p-2 border-b border-slate-200">Aturan Konsumsi</th>
                                                <th class="p-2 border-b border-slate-200 text-center">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($tindakLanjut->eResep as $idx => $resep)
                                                <tr class="border-b border-slate-100">
                                                    <td class="p-2 text-slate-500 font-mono">{{ $idx + 1 }}</td>
                                                    <td class="p-2 font-semibold text-slate-900">{{ $resep->nama_obat }}</td>
                                                    <td class="p-2 text-slate-700">{{ $resep->bentuk_sediaan ?: '-' }}</td>
                                                    <td class="p-2 text-slate-700">{{ $resep->dosis ?: '-' }}</td>
                                                    <td class="p-2 text-slate-700">{{ $resep->aturan_konsumsi ?: '-' }}</td>
                                                    <td class="p-2 text-center font-mono font-semibold">{{ $resep->jumlah ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Rincian Rujukan Laboratorium --}}
                        @if ($tindakLanjut && $tindakLanjut->rujukanLab)
                            <div class="pt-2 border-t border-slate-200">
                                <h4 class="font-bold text-slate-900 mb-1 text-[11px] uppercase tracking-wider">Pemeriksaan Penunjang Laboratorium:</h4>
                                <p class="text-[11px] text-slate-600 mb-2">Catatan Klinis: {{ $tindakLanjut->rujukanLab->catatan ?: '-' }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($tindakLanjut->rujukanLab->parameter as $param)
                                        <span class="inline-block px-2.5 py-1 rounded bg-slate-100 text-slate-800 border border-slate-300 text-[10px] font-mono">
                                            {{ $param->nama_pemeriksaan }} @if ($param->kode_loinc) (LOINC: {{ $param->kode_loinc }}) @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($tindakLanjut && $tindakLanjut->jenis_tindak_lanjut === \App\Models\TindakLanjut::JENIS_TANPA_TINDAKAN)
                            <p class="pt-2 border-t border-slate-200 text-slate-600 italic text-[11px]">Pasien menyelesaikan pelayanan poliklinik tanpa resep maupun rujukan laboratorium.</p>
                        @endif

                        {{-- Catatan Tambahan Finalisasi Dokter --}}
                        @if ($finalisasi?->catatan_finalisasi)
                            <div class="pt-2 border-t border-slate-200">
                                <span class="font-bold text-slate-900 block text-[11px]">Catatan Tambahan Dokter:</span>
                                <p class="text-slate-700 italic text-[11px] mt-0.5">{{ $finalisasi->catatan_finalisasi }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 4. AREA TANDA TANGAN DOKTER (BAGIAN BAWAH DOKUMEN) --}}
            <footer class="pt-4 mt-6 border-t border-slate-300">
                <div class="flex flex-col sm:flex-row items-end justify-between gap-6 text-xs">
                    <div class="text-slate-500 text-[10px] max-w-sm space-y-1">
                        <p>Dokumen ini dihasilkan secara otomatis oleh sistem rekam medis elektronik <strong>MedScribe AI</strong>.</p>
                        <p>Tanda tangan elektronik ini sah dan mengikat secara hukum sesuai ketentuan Undang-Undang Republik Indonesia No. 11/2008 tentang Informasi dan Transaksi Elektronik (UU ITE) dan PMK No. 24/2022.</p>
                    </div>

                    <div class="text-center sm:text-right w-full sm:w-auto">
                        <p class="text-slate-700 mb-2">
                            Surabaya, {{ $waktuFinal }}
                        </p>
                        <p class="text-slate-500 font-medium mb-3">Dokter Penanggung Jawab Pelayanan (DPJP)</p>

                        {{-- Stempel Digital / QR Signature Stamp --}}
                        <div class="inline-flex flex-col items-center p-3 rounded-xl bg-slate-50 border border-slate-300 shadow-sm text-center">
                            <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold text-[10px] uppercase tracking-wider mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Tanda Tangan Digital Terverifikasi</span>
                            </span>
                            <span class="text-[9px] text-slate-500 font-mono">WAKTU: {{ $waktuFinal }}</span>
                            <span class="text-[9px] text-slate-500 font-mono">STATUS: FINALISASI PERMANEN</span>
                        </div>

                        <div class="mt-3">
                            <p class="font-bold text-slate-900 text-sm underline">{{ $finalizedDoctor->nama_lengkap }}</p>
                            <p class="text-slate-600 text-[11px] font-mono mt-0.5">SIP: 503/0124/SIP-D/2023</p>
                        </div>
                    </div>
                </div>
            </footer>

        </article>
    </div>
</div>

{{-- Styling Khusus Cetak A4 Portrait (@media print) --}}
<style>
@page {
    size: A4 portrait;
    margin: 10mm 12mm;
}

@media print {
    /* Sembunyikan semua elemen navigasi aplikasi, header, footer luar, breadcrumb, dan toolbar */
    header,
    footer:not(.a4-sheet footer),
    .print-hidden,
    #modalLogout,
    nav {
        display: none !important;
    }

    body, html {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 11pt !important;
    }

    main {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .a4-document-wrapper {
        padding: 0 !important;
        margin: 0 !important;
    }

    .a4-sheet {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        min-height: auto !important;
        padding: 0 !important;
        margin: 0 !important;
        page-break-after: avoid;
    }

    /* Pastikan warna teks tajam saat dicetak */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
@endsection


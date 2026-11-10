@extends('layouts.app')

@section('title', 'Dokumen SOAP ' . $pasien->no_rekam_medis . ' - MedScribe AI')

@php
    $rs = $kunjungan->hospital;
    $kotaRs = $rs?->city;
    $tidakDicatat = '<span class="doc-muted">tidak dicatat</span>';
    $nilai = fn ($v, $satuan = '') => filled($v) ? e($v) . ($satuan ? ' ' . $satuan : '') : $tidakDicatat;
@endphp

@section('content')
<div class="doc-page">

    <div class="print-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-white border border-border-subtle">
        <div class="doc-bar-kiri">
            @include('partials.back-link', ['href' => route('dokter.rekam-medis', $antrean->id_antrean) . '#finalisasi', 'label' => 'Kembali ke rekam medis'])
            <div>
                <h1 class="text-sm font-semibold text-medical-navy leading-tight">Pratinjau dokumen SOAP</h1>
                <p class="text-xs text-text-secondary">Ukuran A4 tegak. Pilih "Simpan sebagai PDF" di dialog cetak untuk menyimpan berkas.</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <span class="status status-ok text-xs">Final</span>
            <button type="button" onclick="cetakDokumenSoap()" id="btn-download-pdf" class="btn btn-primary">Cetak / Simpan PDF</button>
        </div>
    </div>

    <article class="doc-sheet" aria-label="Dokumen SOAP rawat jalan">

        <header class="doc-kop">
            <div>
                <p class="doc-rs">{{ $rs?->hospital_name ?? '[Nama fasilitas belum diatur]' }}</p>
                <p class="doc-rs-sub">
                    {{ collect([$rs?->address, $kotaRs, $rs?->province])->filter()->implode(', ') ?: '[Alamat fasilitas belum diatur]' }}
                </p>
            </div>
            <dl class="doc-meta">
                <div><dt>No. dokumen</dt><dd class="doc-code">{{ $idKunjunganCode }}</dd></div>
                <div><dt>Tanggal periksa</dt><dd>{{ $tanggalPemeriksaan }}</dd></div>
            </dl>
        </header>

        <h2 class="doc-title">Catatan SOAP rawat jalan</h2>

        <section class="doc-identitas" aria-label="Identitas pasien">
            <dl>
                <div><dt>Nama pasien</dt><dd class="doc-strong">{{ $pasien->nama_pasien }}</dd></div>
                <div><dt>No. rekam medis</dt><dd class="doc-code">{{ $pasien->no_rekam_medis }}</dd></div>
                <div><dt>Tanggal lahir / umur</dt><dd>{{ $pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->locale('id')->isoFormat('D MMMM Y') : '-' }}{{ $umur ? ' / ' . $umur . ' tahun' : '' }}</dd></div>
                <div><dt>Jenis kelamin</dt><dd>{{ $pasien->jenis_kelamin ?? '-' }}</dd></div>
            </dl>
            <dl>
                <div><dt>No. antrean</dt><dd class="doc-code">A-{{ str_pad($antrean->id_antrean, 3, '0', STR_PAD_LEFT) }}</dd></div>
                <div><dt>Unit</dt><dd>Poliklinik rawat jalan</dd></div>
                <div><dt>DPJP</dt><dd class="doc-strong">{{ $dokterPemeriksa->nama_lengkap }}</dd></div>
                <div><dt>Perawat</dt><dd>{{ $pemeriksaanAwal?->perawat?->nama_lengkap ?? '-' }}</dd></div>
            </dl>
        </section>

        <section class="doc-soap" aria-label="Isi SOAP">
            <div class="doc-row">
                <div class="doc-mark"><span>S</span>Subjektif</div>
                <div class="doc-body"><p class="doc-text">{{ $draf?->subjective_text ?: 'Tidak dicatat.' }}</p></div>
            </div>

            <div class="doc-row">
                <div class="doc-mark"><span>O</span>Objektif</div>
                <div class="doc-body">
                    @if ($pemeriksaanAwal)
                        <table class="doc-ttv">
                            <tr>
                                <th>TD</th><th>Nadi</th><th>Suhu</th><th>RR</th><th>SpO2</th><th>BB / TB</th>
                            </tr>
                            <tr>
                                <td>{!! $nilai($pemeriksaanAwal->tekanan_darah, 'mmHg') !!}</td>
                                <td>{!! $nilai($pemeriksaanAwal->nadi, 'x/mnt') !!}</td>
                                <td>{!! $nilai($pemeriksaanAwal->suhu_tubuh, '°C') !!}</td>
                                <td>{!! $nilai($pemeriksaanAwal->laju_pernapasan, 'x/mnt') !!}</td>
                                <td>{!! $nilai($pemeriksaanAwal->saturasi_oksigen, '%') !!}</td>
                                <td>{!! (filled($pemeriksaanAwal->berat_badan) || filled($pemeriksaanAwal->tinggi_badan)) ? $nilai($pemeriksaanAwal->berat_badan, 'kg') . ' / ' . $nilai($pemeriksaanAwal->tinggi_badan, 'cm') : $tidakDicatat !!}</td>
                            </tr>
                        </table>
                        @if ($pemeriksaanAwal->catatan)
                            <p class="doc-note">Catatan perawat: {{ $pemeriksaanAwal->catatan }}</p>
                        @endif
                    @endif
                    <p class="doc-text">{{ $draf?->objective_text ?: 'Tidak dicatat.' }}</p>
                </div>
            </div>

            <div class="doc-row">
                <div class="doc-mark"><span>A</span>Asesmen</div>
                <div class="doc-body">
                    <p class="doc-text">{{ $draf?->assessment_text ?: 'Tidak dicatat.' }}</p>
                    @if ($draf?->kode_icd10)
                        <p class="doc-dx"><span class="doc-dx-label">Diagnosis (ICD-10)</span> <span class="doc-code">{{ $draf->kode_icd10 }}</span>{{ $icdDescription ? ' ' . $icdDescription : '' }}</p>
                    @endif
                </div>
            </div>

            <div class="doc-row">
                <div class="doc-mark"><span>P</span>Rencana</div>
                <div class="doc-body">
                    <p class="doc-text">{{ $draf?->plan_text ?: 'Tidak dicatat.' }}</p>

                    @if ($tindakLanjut && $tindakLanjut->eResep->isNotEmpty())
                        <h3 class="doc-sub">Resep</h3>
                        <table class="doc-table">
                            <thead><tr><th class="w-num">No</th><th>Obat</th><th>Sediaan</th><th>Dosis</th><th>Aturan pakai</th><th class="w-num">Jml</th></tr></thead>
                            <tbody>
                                @foreach ($tindakLanjut->eResep as $idx => $resep)
                                    <tr>
                                        <td class="w-num">{{ $idx + 1 }}</td>
                                        <td class="doc-strong">{{ $resep->nama_obat }}</td>
                                        <td>{{ $resep->bentuk_sediaan ?: '-' }}</td>
                                        <td>{{ $resep->dosis ?: '-' }}</td>
                                        <td>{{ $resep->aturan_konsumsi ?: '-' }}</td>
                                        <td class="w-num">{{ $resep->jumlah ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if ($tindakLanjut && $tindakLanjut->rujukanLab)
                        <h3 class="doc-sub">Rujukan laboratorium</h3>
                        <ul class="doc-list">
                            @foreach ($tindakLanjut->rujukanLab->parameter as $param)
                                <li>{{ $param->nama_pemeriksaan }}@if ($param->kode_loinc) <span class="doc-code">LOINC {{ $param->kode_loinc }}</span>@endif</li>
                            @endforeach
                        </ul>
                        @if ($tindakLanjut->rujukanLab->catatan)
                            <p class="doc-note">Catatan klinis: {{ $tindakLanjut->rujukanLab->catatan }}</p>
                        @endif
                    @endif

                    @if ($tindakLanjut && $tindakLanjut->jenis_tindak_lanjut === \App\Models\TindakLanjut::JENIS_TANPA_TINDAKAN)
                        <p class="doc-note">Selesai tanpa resep maupun rujukan laboratorium.</p>
                    @endif

                    @if ($finalisasi?->catatan_finalisasi)
                        <p class="doc-note">Catatan dokter saat finalisasi: {{ $finalisasi->catatan_finalisasi }}</p>
                    @endif
                </div>
            </div>
        </section>

        <footer class="doc-ttd">
            <p class="doc-fakta">Disetujui dan difinalisasi secara elektronik di MedScribe AI oleh {{ $finalizedDoctor->nama_lengkap }} pada {{ $waktuFinal }}.</p>
            <div class="doc-ttd-blok">
                <p>{{ $kotaRs ? $kotaRs . ', ' : '' }}{{ $finalisasi?->finalized_at ? \Carbon\Carbon::parse($finalisasi->finalized_at)->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y') : $tanggalPemeriksaan }}</p>
                <p>Dokter penanggung jawab pelayanan</p>
                <div class="doc-ttd-ruang" aria-hidden="true"></div>
                <p class="doc-strong doc-ttd-nama">{{ $finalizedDoctor->nama_lengkap }}</p>
                <p class="doc-muted">SIP: ............................................</p>
            </div>
        </footer>
    </article>
</div>

<style>
    .doc-page { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
    .doc-bar-kiri { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; min-width: 0; }
    .doc-bar-kiri > div { min-width: 0; flex: 1 1 14rem; }
    .doc-sheet {
        --ink: var(--color-text-body, #20294A);
        --navy: var(--color-medical-navy, #122056);
        --accent: var(--color-royal-indigo, #5865DC);
        --muted: var(--color-text-secondary, #69718B);
        --rule: #C9CEE0;
        width: 100%; max-width: 210mm; min-height: 297mm; margin: 0 auto 3rem;
        padding: 18mm 16mm; background: #fff; color: var(--ink);
        border: 1px solid var(--color-border-subtle, #E4E7F4); border-radius: 2px;
        box-shadow: 0 1px 3px rgba(18, 32, 86, .08);
        font-size: 12.5px; line-height: 1.55;
    }
    .doc-kop { display: flex; justify-content: space-between; gap: 1.5rem; padding-bottom: 10px; border-bottom: 2px solid var(--navy); }
    .doc-rs { font-size: 16px; font-weight: 600; color: var(--navy); }
    .doc-rs-sub { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .doc-meta { text-align: right; font-size: 11.5px; display: grid; gap: 2px; }
    .doc-meta dt { color: var(--muted); display: inline; }
    .doc-meta dt::after { content: ': '; }
    .doc-meta dd { display: inline; white-space: nowrap; }
    .doc-title { font-size: 15px; font-weight: 600; color: var(--navy); margin: 18px 0 12px; }
    .doc-identitas { display: grid; grid-template-columns: 1fr 1fr; gap: 0 28px; padding-bottom: 12px; border-bottom: 1px solid var(--rule); }
    .doc-identitas dl > div { display: grid; grid-template-columns: 9.5em 1fr; gap: 8px; padding: 3px 0; }
    .doc-identitas dt { color: var(--muted); }
    .doc-strong { font-weight: 600; color: var(--navy); }
    .doc-code { font-family: var(--font-mono, ui-monospace, monospace); font-size: .95em; letter-spacing: .01em; }
    .doc-muted { color: var(--muted); }
    .doc-row { display: grid; grid-template-columns: 76px 1fr; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--rule); break-inside: avoid; }
    .doc-mark { font-size: 11px; color: var(--muted); line-height: 1.2; }
    .doc-mark span { display: block; font-size: 26px; font-weight: 600; color: var(--accent); line-height: 1; margin-bottom: 4px; }
    .doc-body { display: grid; gap: 8px; min-width: 0; }
    .doc-text { white-space: pre-line; }
    .doc-note { font-size: 11.5px; color: var(--muted); }
    .doc-dx { font-size: 12px; }
    .doc-dx-label { color: var(--muted); margin-right: 4px; }
    .doc-sub { font-size: 11.5px; font-weight: 600; color: var(--navy); margin-top: 4px; }
    .doc-ttv, .doc-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
    .doc-ttv th, .doc-table th { text-align: left; font-weight: 500; color: var(--muted); padding: 4px 8px 4px 0; border-bottom: 1px solid var(--rule); }
    .doc-ttv td, .doc-table td { padding: 5px 8px 5px 0; vertical-align: top; }
    .doc-table tbody tr + tr td { border-top: 1px solid #EEF0F7; }
    .doc-table .w-num { width: 2.5em; text-align: right; padding-right: 12px; font-variant-numeric: tabular-nums; }
    .doc-list { padding-left: 1.1em; list-style: disc; }
    .doc-ttd { display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: end; margin-top: 22px; }
    .doc-fakta { font-size: 11px; color: var(--muted); max-width: 32em; }
    .doc-ttd-blok { text-align: center; min-width: 14em; font-size: 12px; }
    .doc-ttd-ruang { height: 64px; }
    .doc-ttd-nama { border-top: 1px solid var(--ink); padding-top: 4px; }
    @media (max-width: 640px) {
        .doc-meta dd { white-space: normal; }
        .doc-sheet { padding: 20px 16px; min-height: 0; }
        .doc-kop, .doc-ttd { display: grid; grid-template-columns: 1fr; }
        .doc-meta { text-align: left; }
        .doc-identitas { grid-template-columns: 1fr; }
        .doc-row { grid-template-columns: 1fr; gap: 6px; }
        .doc-mark span { display: inline; font-size: 18px; margin-right: 6px; }
        .doc-ttv, .doc-table { display: block; overflow-x: auto; }
    }
    @page { size: A4 portrait; margin: 14mm; }
    @media print {
        body { background: #fff !important; }
        body > header, body > footer, nav, .print-hidden { display: none !important; }
        main, .doc-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
        .doc-sheet { border: 0; box-shadow: none; padding: 0; min-height: 0; max-width: none; margin: 0; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endsection

@section('scripts')
<script>
    function cetakDokumenSoap() {
        const judulAsli = document.title;
        document.title = @json('SOAP_' . preg_replace('/[^A-Za-z0-9-]+/', '-', (string) $pasien->no_rekam_medis) . '_' . $idKunjunganCode);
        window.addEventListener('afterprint', function () { document.title = judulAsli; }, { once: true });
        window.print();
    }
</script>
@endsection

@php
    $index = $index ?? 0;
    $obat = $obat ?? [];
@endphp

<div class="resep-item rounded-[18px] border border-border-subtle bg-white p-4 space-y-4" data-resep-item>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-semibold text-medical-navy">Item obat</p>
        <button type="button" class="btn btn-ghost btn-sm btn-icon text-danger-text hover:!bg-danger-bg" data-hapus-obat aria-label="Hapus item obat">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="resep-{{ $index }}-nama_obat" class="field-label">Nama Obat <span class="req">*</span></label>
            <input
                id="resep-{{ $index }}-nama_obat"
                name="resep[{{ $index }}][nama_obat]"
                value="{{ old("resep.$index.nama_obat", $obat['nama_obat'] ?? '') }}"
                class="field @error("resep.$index.nama_obat") is-invalid @enderror"
                data-error-key="resep.{{ $index }}.nama_obat"
                data-required-resep
                autocomplete="off"
                placeholder="Contoh: Paracetamol"
            >
            @error("resep.$index.nama_obat")<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="resep-{{ $index }}-dosis" class="field-label">Dosis <span class="req">*</span></label>
            <input
                id="resep-{{ $index }}-dosis"
                name="resep[{{ $index }}][dosis]"
                value="{{ old("resep.$index.dosis", $obat['dosis'] ?? '') }}"
                class="field @error("resep.$index.dosis") is-invalid @enderror"
                data-error-key="resep.{{ $index }}.dosis"
                data-required-resep
                autocomplete="off"
                placeholder="Contoh: 500 mg"
            >
            @error("resep.$index.dosis")<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="resep-{{ $index }}-bentuk_sediaan" class="field-label">Bentuk Sediaan <span class="req">*</span></label>
            <select
                id="resep-{{ $index }}-bentuk_sediaan"
                name="resep[{{ $index }}][bentuk_sediaan]"
                class="field @error("resep.$index.bentuk_sediaan") is-invalid @enderror"
                data-error-key="resep.{{ $index }}.bentuk_sediaan"
                data-required-resep
            >
                <option value="">Pilih bentuk sediaan</option>
                @foreach (['Tablet', 'Kapsul', 'Sirup'] as $bentuk)
                    <option value="{{ $bentuk }}" @selected(old("resep.$index.bentuk_sediaan", $obat['bentuk_sediaan'] ?? '') === $bentuk)>{{ $bentuk }}</option>
                @endforeach
            </select>
            @error("resep.$index.bentuk_sediaan")<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="resep-{{ $index }}-jumlah" class="field-label">Jumlah Obat <span class="req">*</span></label>
            <input
                id="resep-{{ $index }}-jumlah"
                name="resep[{{ $index }}][jumlah]"
                type="number"
                min="1"
                max="1000"
                inputmode="numeric"
                value="{{ old("resep.$index.jumlah", $obat['jumlah'] ?? '') }}"
                class="field @error("resep.$index.jumlah") is-invalid @enderror"
                data-error-key="resep.{{ $index }}.jumlah"
                data-required-resep
                placeholder="Contoh: 10"
            >
            @error("resep.$index.jumlah")<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="resep-{{ $index }}-aturan_konsumsi" class="field-label">Aturan Minum <span class="req">*</span></label>
            <input
                id="resep-{{ $index }}-aturan_konsumsi"
                name="resep[{{ $index }}][aturan_konsumsi]"
                value="{{ old("resep.$index.aturan_konsumsi", $obat['aturan_konsumsi'] ?? '') }}"
                class="field @error("resep.$index.aturan_konsumsi") is-invalid @enderror"
                data-error-key="resep.{{ $index }}.aturan_konsumsi"
                data-required-resep
                autocomplete="off"
                placeholder="Contoh: 3x Sehari"
            >
            @error("resep.$index.aturan_konsumsi")<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

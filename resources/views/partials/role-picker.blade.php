{{-- Pilihan peran tenaga medis. Deskripsi mengikuti fitur yang tersedia untuk tiap peran. --}}
<fieldset>
    <legend class="field-label">Peran Tenaga Medis <span class="req" aria-hidden="true">*</span></legend>
    <div class="grid grid-cols-2 gap-2.5">
        @foreach ([
            'dokter' => ['Dokter', 'Memeriksa pasien dari antrean dan merekam wawancara klinis', 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
            'perawat' => ['Perawat', 'Mendaftarkan pasien dan mengisi tanda-tanda vital (TTV)', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ] as $value => [$title, $desc, $icon])
            <label class="group relative flex flex-col gap-2.5 p-3.5 rounded-[18px] border border-border-subtle bg-soft-offwhite select-none transition-all duration-200 hover:border-border-hover hover:-translate-y-0.5 has-checked:border-royal-indigo has-checked:bg-pale-lavender has-checked:shadow-[0_8px_20px_-10px_rgba(88,101,220,0.5)] has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-royal-indigo has-[:focus-visible]:outline-offset-2">
                <input type="radio" name="role" value="{{ $value }}" class="sr-only peer" {{ old('role', 'dokter') === $value ? 'checked' : '' }}>
                <span class="flex items-center justify-between">
                    <span class="avatar w-8 h-8 bg-white border border-border-subtle text-royal-indigo transition-colors group-has-checked:bg-royal-indigo group-has-checked:border-royal-indigo group-has-checked:text-white" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                    </span>
                    <span class="w-5 h-5 rounded-full border-2 border-border-hover flex items-center justify-center transition-colors group-has-checked:border-royal-indigo group-has-checked:bg-royal-indigo" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white opacity-0 group-has-checked:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </span>
                </span>
                <span>
                    <span class="block text-sm font-semibold text-medical-navy">{{ $title }}</span>
                    <span class="block text-xs text-text-secondary leading-snug mt-0.5">{{ $desc }}</span>
                </span>
            </label>
        @endforeach
    </div>
</fieldset>

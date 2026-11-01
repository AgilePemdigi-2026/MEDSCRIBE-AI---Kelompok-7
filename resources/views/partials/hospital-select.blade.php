{{-- Pilihan rumah sakit. Parameter: showHint (bool) --}}
<div>
    <label for="hospital_id" class="field-label">Rumah Sakit Tempat Bertugas <span class="req" aria-hidden="true">*</span></label>
    <div class="field-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <select name="hospital_id" id="hospital_id" required @if (!empty($showHint)) aria-describedby="hospitalHint" @endif
                class="field @error('hospital_id') is-invalid @enderror">
            <option value="" disabled {{ old('hospital_id') ? '' : 'selected' }}>Pilih Rumah Sakit (Kota Surabaya)...</option>
            @foreach ($hospitals as $h)
                <option value="{{ $h->id }}" {{ (string) old('hospital_id') === (string) $h->id ? 'selected' : '' }}>
                    {{ $h->hospital_name }} ({{ $h->hospital_type ?? 'RSU' }})
                </option>
            @endforeach
        </select>
    </div>
    @if (!empty($showHint))
        <p id="hospitalHint" class="field-hint">Pilot project mencakup rumah sakit resmi di wilayah Kota Surabaya.</p>
    @endif
</div>

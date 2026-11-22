{{-- Pilihan rumah sakit. Parameter: showHint (bool) --}}
<div>
    <label for="hospital_id" class="field-label">Rumah Sakit Tempat Bertugas <span class="req" aria-hidden="true">*</span></label>
    <div class="field-icon">
        <x-icon name="building-2" aria-hidden="true" />
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

{{-- Isian angka dengan satuan. Parameter: id, name, label, unit, value, placeholder, min, max, step, type, required --}}
@php $isRequired = $required ?? true; @endphp
<div>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if ($isRequired)<span class="req" aria-hidden="true">*</span>@endif
    </label>
    <div class="field-unit">
        <input
            type="{{ $type ?? 'number' }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value ?? '' }}"
            placeholder="{{ $placeholder ?? '' }}"
            @isset($min) min="{{ $min }}" @endisset
            @isset($max) max="{{ $max }}" @endisset
            @isset($step) step="{{ $step }}" @endisset
            @if ($isRequired) required @endif
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
            class="field field-num @error($name) is-invalid @enderror"
        >
        <span class="unit">{{ $unit }}</span>
    </div>
    @error($name)
        <p id="{{ $id }}-error" class="field-error">{{ $message }}</p>
    @enderror
</div>

{{-- Kolom kata sandi dengan ikon gembok dan tombol tampilkan. Parameter: id, name, label, placeholder, autocomplete, autofocus, minlength, icon ('lock'|'check') --}}
<div>
    <label for="{{ $id }}" class="field-label">{{ $label }} <span class="req" aria-hidden="true">*</span></label>
    <div class="field-icon relative">
        <x-icon :name="($icon ?? 'lock') === 'check' ? 'circle-check' : 'lock'" static />
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $id }}"
            required
            @isset($minlength) minlength="{{ $minlength }}" @endisset
            @if (!empty($autofocus)) autofocus @endif
            autocomplete="{{ $autocomplete ?? 'current-password' }}"
            placeholder="{{ $placeholder ?? '' }}"
            @isset($describedby) aria-describedby="{{ $describedby }}" @endisset
            class="field pr-12 @error($name) is-invalid @enderror"
        >
        <button type="button" data-password-toggle="{{ $id }}" aria-pressed="false" aria-label="Tampilkan kata sandi"
                class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-text-secondary hover:text-royal-indigo rounded-r-[14px] transition-colors">
            <x-icon name="eye" data-eye="open" class="h-4 w-4" aria-hidden="true" />
            <x-icon name="eye-off" data-eye="closed" class="h-4 w-4 hidden" aria-hidden="true" />
        </button>
    </div>
</div>

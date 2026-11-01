{{-- Kolom kata sandi dengan ikon gembok dan tombol tampilkan. Parameter: id, name, label, placeholder, autocomplete, autofocus, minlength, icon ('lock'|'check') --}}
<div>
    <label for="{{ $id }}" class="field-label">{{ $label }} <span class="req" aria-hidden="true">*</span></label>
    <div class="field-icon relative">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            @if (($icon ?? 'lock') === 'check')
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            @endif
        </svg>
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
            <svg data-eye="open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg data-eye="closed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
        </button>
    </div>
</div>

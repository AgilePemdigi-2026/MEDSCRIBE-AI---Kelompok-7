{{-- Logo resmi MedScribe AI. Teks nama di sampingnya sudah memberi nama, jadi gambarnya dekoratif (alt kosong). --}}
<span class="inline-flex items-center gap-2.5">
    @php($logoUrl = asset('images/logo-medscribe.png'))
    <img src="{{ $logoUrl }}" srcset="{{ $logoUrl }} 1x, {{ str_replace('.png', '@2x.png', $logoUrl) }} 2x"
         width="40" height="40" alt="" class="w-10 h-10 flex-shrink-0">
    <span class="leading-tight">
        <span class="block text-base font-semibold tracking-tight text-medical-navy">MedScribe <span class="text-royal-indigo">AI</span></span>
        <span class="block text-[11px] text-text-secondary">Sistem Rekam Medis Elektronik</span>
    </span>
</span>

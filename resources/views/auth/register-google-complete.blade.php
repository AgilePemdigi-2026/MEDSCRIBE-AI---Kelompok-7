@extends('layouts.auth')

@section('title', 'Lengkapi Profil Tenaga Medis - MedScribe AI')

@section('content')
<div class="bg-[#FFFFFF] rounded-[24px] border border-[#E4E7F4] p-6 sm:p-8 shadow-[0_10px_30px_rgba(18,32,86,0.08)] transition-all">

    <!-- Brand Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] mb-3.5 shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-[#122056]">Lengkapi Profil Medis</h1>
        <p class="text-xs sm:text-sm text-[#69718B] mt-1 font-normal">Tentukan peran profesional dan rumah sakit tempat Anda bertugas</p>
    </div>

    <!-- Info Akun Google -->
    <div class="mb-5 p-3 rounded-[14px] bg-[#EEEEFD] border border-[#CBD1EA] flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-[#FFFFFF] border border-[#CBD1EA] flex items-center justify-center text-[#5865DC] font-bold text-xs">
            G
        </div>
        <div class="overflow-hidden">
            <div class="text-xs font-semibold text-[#122056] truncate">{{ $name ?? 'Tenaga Medis' }}</div>
            <div class="text-[11px] text-[#69718B] truncate">{{ $email }} (Terverifikasi Google)</div>
        </div>
    </div>

    <!-- Alert Kesalahan -->
    @if ($errors->any())
        <div class="mb-5 p-3.5 rounded-[16px] bg-[#FEF2F2] border border-[#FECACA] text-[#DC2626] text-xs flex items-start gap-2.5 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#DC2626] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="space-y-0.5">
                @foreach ($errors->all() as $error)
                    <p class="font-medium">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('register.google.complete.submit') }}" id="googleCompleteForm" class="space-y-4">
        @csrf

        <!-- Pilihan Peran Tenaga Medis -->
        <div>
            <label class="block text-xs font-semibold text-[#122056] mb-1.5">
                Peran Tenaga Medis <span class="text-[#DC2626]">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2.5">
                <label class="relative flex flex-col p-3 rounded-[14px] border border-[#E4E7F4] cursor-pointer transition-all select-none bg-[#FAFAFD] hover:border-[#CBD1EA] has-checked:border-[#5865DC] has-checked:bg-[#EEEEFD]/60 has-checked:ring-2 has-checked:ring-[#5865DC]/20">
                    <input
                        type="radio"
                        name="role"
                        value="dokter"
                        {{ old('role', 'dokter') === 'dokter' ? 'checked' : '' }}
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-6 h-6 rounded-[8px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center text-[11px] shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </span>
                        <span class="text-xs font-semibold text-[#122056]">Dokter</span>
                    </div>
                    <span class="text-[11px] text-[#69718B] leading-tight">Wawancara & rekam medis poliklinik</span>
                </label>

                <label class="relative flex flex-col p-3 rounded-[14px] border border-[#E4E7F4] cursor-pointer transition-all select-none bg-[#FAFAFD] hover:border-[#CBD1EA] has-checked:border-[#5865DC] has-checked:bg-[#EEEEFD]/60 has-checked:ring-2 has-checked:ring-[#5865DC]/20">
                    <input
                        type="radio"
                        name="role"
                        value="perawat"
                        {{ old('role') === 'perawat' ? 'checked' : '' }}
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-6 h-6 rounded-[8px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] flex items-center justify-center text-[11px] shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </span>
                        <span class="text-xs font-semibold text-[#122056]">Perawat</span>
                    </div>
                    <span class="text-[11px] text-[#69718B] leading-tight">Nurse Station & triase TTV</span>
                </label>
            </div>
        </div>

        <!-- Pilihan Rumah Sakit (Surabaya) -->
        <div>
            <label for="hospital_id" class="block text-xs font-semibold text-[#122056] mb-1.5">
                Rumah Sakit Tempat Bertugas <span class="text-[#DC2626]">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#69718B]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <select
                    name="hospital_id"
                    id="hospital_id"
                    required
                    class="block w-full pl-10 pr-8 py-2.5 text-sm bg-[#FAFAFD] border @error('hospital_id') border-[#DC2626] @else border-[#E4E7F4] @enderror rounded-[14px] text-[#20294A] focus:bg-[#FFFFFF] focus:outline-none focus:border-[#5865DC] focus:ring-3 focus:ring-[#5865DC]/15 transition-all"
                >
                    <option value="" disabled selected>Pilih Rumah Sakit (Kota Surabaya)...</option>
                    @foreach ($hospitals as $h)
                        <option value="{{ $h->id }}" {{ (string) old('hospital_id') === (string) $h->id ? 'selected' : '' }}>
                            {{ $h->hospital_name }} ({{ $h->hospital_type ?? 'RSU' }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pt-2">
            <button
                type="submit"
                id="submitBtn"
                class="w-full py-2.5 px-4 bg-[#5865DC] hover:bg-[#4854C9] active:bg-[#3D47B0] text-[#FFFFFF] font-medium text-sm rounded-[14px] focus:outline-none focus:ring-3 focus:ring-[#5865DC]/30 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_2px_10px_rgba(88,101,220,0.24)] hover:shadow-[0_4px_16px_rgba(88,101,220,0.32)]"
            >
                <span id="btnText">Selesaikan Pendaftaran</span>
                <svg id="btnSpinner" class="hidden animate-spin h-4 w-4 text-[#FFFFFF]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    const form = document.getElementById('googleCompleteForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Menyimpan...';
        btnSpinner.classList.remove('hidden');
    });
</script>
@endsection


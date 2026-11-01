@extends('layouts.auth')

@section('title', 'Masuk - MedScribe AI')

@section('content')
<h1 class="text-[1.9rem] leading-tight font-semibold tracking-[-0.03em] text-medical-navy">Selamat Datang di MedScribe AI</h1>
<p class="mt-1.5 text-sm text-text-secondary">Sistem Rekam Medis Elektronik Terintegrasi</p>

<div class="mt-7">
    @include('partials.form-alerts')

    <form method="POST" action="{{ route('login.submit') }}" id="loginForm" class="space-y-4" data-submit-loading>
        @csrf

        <div>
            <label for="email" class="field-label">Alamat Email <span class="req" aria-hidden="true">*</span></label>
            <div class="field-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" /></svg>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="nama@medscribe.id" class="field @error('email') is-invalid @enderror">
            </div>
        </div>

        <div>
            @include('partials.password-field', ['id' => 'password', 'name' => 'password', 'label' => 'Kata Sandi', 'placeholder' => '••••••••'])
            <div class="mt-1.5 text-right">
                <a href="{{ route('password.request') }}" class="link text-xs inline-flex min-h-8 items-center">Lupa Password?</a>
            </div>
        </div>

        <label class="flex items-center gap-2.5 min-h-11 w-fit -mt-2">
            <input type="checkbox" name="remember" id="remember" class="w-4 h-4 accent-royal-indigo">
            <span class="text-xs text-text-secondary select-none">Ingat saya di perangkat ini</span>
        </label>

        @include('partials.submit-button', ['label' => 'Masuk ke Sistem', 'loading' => 'Memverifikasi...'])
    </form>

    <div class="my-5 flex items-center gap-3 text-[11px] text-text-secondary uppercase tracking-wider" aria-hidden="true">
        <span class="h-px flex-1 bg-border-subtle"></span>atau<span class="h-px flex-1 bg-border-subtle"></span>
    </div>

    <a href="{{ route('auth.google') }}" class="btn btn-secondary w-full">
        <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        <span>Masuk dengan Google</span>
    </a>

    <p class="mt-6 text-xs text-text-secondary text-center">
        Belum memiliki akun tenaga medis?
        <a href="{{ route('register') }}" class="link ml-1">Daftar Akun Baru</a>
    </p>

    <section class="mt-6 p-4 rounded-[20px] bg-pale-lavender border border-border-hover" aria-labelledby="demoTitle">
        <div class="flex items-center justify-between mb-3">
            <h2 id="demoTitle" class="text-xs font-semibold text-medical-navy">Akun Uji Coba Demo</h2>
            <span class="text-[11px] text-text-secondary">Klik untuk isi</span>
        </div>
        <div class="grid grid-cols-2 gap-2">
            @foreach ([['rina@medscribe.id', 'dr. Rina Anggraini', 'Dokter Poliklinik', 'RA'], ['dewi@medscribe.id', 'Dewi Pratiwi', 'Perawat Nurse Station', 'DP']] as [$mail, $nama, $peran, $ini])
                <button type="button" onclick="fillCredentials('{{ $mail }}', 'password123')"
                        class="group flex items-center gap-2.5 text-left p-2.5 rounded-[16px] bg-white border border-border-subtle transition-all duration-200 hover:border-royal-indigo hover:-translate-y-0.5 hover:shadow-[0_8px_18px_-10px_rgba(88,101,220,0.6)]">
                    <span class="avatar w-8 h-8 bg-pale-lavender text-royal-indigo-hover text-[11px] transition-colors group-hover:bg-royal-indigo group-hover:text-white" aria-hidden="true">{{ $ini }}</span>
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-medical-navy truncate">{{ $nama }}</span>
                        <span class="block text-[11px] text-text-secondary truncate">{{ $peran }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    function fillCredentials(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection

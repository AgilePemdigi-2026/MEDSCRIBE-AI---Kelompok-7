{{-- Navigasi utama dokter: antrean hari ini dan daftar pasien beserta riwayatnya. --}}
<nav aria-label="Tampilan dokter" class="pill-nav-scroll">
    <div class="pill-nav">
        <a href="{{ route('dokter.dashboard') }}" @if (request()->routeIs('dokter.dashboard')) aria-current="page" @endif>Antrean Hari Ini</a>
        <a href="{{ route('dokter.pasien.index') }}" @if (request()->routeIs('dokter.pasien.*')) aria-current="page" @endif>Daftar Pasien</a>
    </div>
</nav>

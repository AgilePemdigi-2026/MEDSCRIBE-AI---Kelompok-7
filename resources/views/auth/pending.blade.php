@extends('layouts.auth')

@section('title', 'Menunggu Verifikasi Admin - MedScribe AI')

@section('content')
<x-auth-status :user="$user" :hospital="$hospital" tone="warn" reload
               icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
               title="Menunggu Verifikasi Admin"
               subtitle="Pendaftaran Anda sedang ditinjau oleh admin rumah sakit."
               statusLabel="Menunggu verifikasi">
    Akun Anda menunggu persetujuan admin <span class="font-semibold">{{ $hospital->hospital_name ?? 'rumah sakit' }}</span>.
    Dashboard bisa dibuka setelah akun disetujui. Tekan "Muat ulang status" untuk memeriksa lagi.
</x-auth-status>
@endsection

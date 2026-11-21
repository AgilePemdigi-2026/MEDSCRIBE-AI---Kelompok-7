@extends('layouts.auth')

@section('title', 'Akun Dinonaktifkan - MedScribe AI')

@section('content')
<x-auth-status :user="$user" :hospital="$hospital" tone="muted"
               icon="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
               title="Akun Dinonaktifkan"
               subtitle="Akses Anda ke dashboard sedang dihentikan oleh admin rumah sakit."
               statusLabel="Nonaktif"
               reasonLabel="Alasan penonaktifan"
               :reason="$user->deactivation_reason">
    Akun Anda tidak aktif di <span class="font-semibold">{{ $hospital->hospital_name ?? 'rumah sakit' }}</span>.
    Rekam medis dan riwayat yang pernah Anda buat tetap tersimpan. Hubungi admin rumah sakit untuk mengaktifkan kembali.
</x-auth-status>
@endsection

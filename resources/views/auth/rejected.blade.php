@extends('layouts.auth')

@section('title', 'Pendaftaran Ditolak - MedScribe AI')

@section('content')
<x-auth-status :user="$user" :hospital="$hospital" tone="danger"
               icon="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
               title="Pendaftaran Ditolak"
               subtitle="Admin rumah sakit belum dapat menyetujui pendaftaran akun Anda."
               statusLabel="Ditolak"
               reasonLabel="Alasan penolakan"
               :reason="$user->rejection_reason">
    Pendaftaran Anda ditolak oleh admin <span class="font-semibold">{{ $hospital->hospital_name ?? 'rumah sakit' }}</span>.
    Hubungi pihak rumah sakit bila ada data yang perlu diperbaiki.
</x-auth-status>
@endsection

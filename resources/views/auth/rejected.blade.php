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
    Perbaiki data sesuai alasan di bawah, lalu daftar ulang dengan email yang sama.

    <x-slot:actions>
        <button type="button" data-dialog-open="dlgReapply" class="btn btn-primary w-full">
            <x-icon name="refresh-cw" class="w-4 h-4" aria-hidden="true" />
            Daftar ulang
        </button>
    </x-slot:actions>
</x-auth-status>

<x-dialog id="dlgReapply" tone="warn" title="Daftar ulang?"
          description="Akun yang ditolak ini akan dihapus dan Anda keluar dari sistem. Setelah itu email {{ $user->email }} bisa dipakai untuk mendaftar lagi.">
    <form method="POST" action="{{ route('auth.rejected.reapply') }}" id="formReapply" data-submit-loading class="m-0">
        @csrf
    </form>
    <x-slot:actions>
        <button type="button" data-dialog-close class="btn btn-secondary">Batal</button>
        <button type="submit" form="formReapply" class="btn btn-primary" data-dialog-focus>Hapus dan daftar ulang</button>
    </x-slot:actions>
</x-dialog>
@endsection

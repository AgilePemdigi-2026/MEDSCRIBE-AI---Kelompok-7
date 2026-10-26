@extends('layouts.app')

@section('title', 'Dashboard - MedScribe AI')

@section('content')
<div class="max-w-xl mx-auto bg-[#FFFFFF] p-8 rounded-[24px] border border-[#E4E7F4] shadow-[0_10px_30px_rgba(18,32,86,0.08)] text-center">
    <div class="w-13 h-13 rounded-[16px] bg-gradient-to-br from-[#122056] to-[#5865DC] text-[#FFFFFF] mx-auto flex items-center justify-center mb-4 shadow-[0_4px_16px_rgba(88,101,220,0.3)]">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
    </div>
    <h1 class="text-2xl font-bold tracking-tight text-[#122056]">Selamat Datang, {{ $user->nama_lengkap }}</h1>
    <p class="text-sm text-[#69718B] mt-1.5 font-normal">
        Anda terautentikasi sebagai <strong class="text-[#5865DC] font-semibold capitalize">{{ $user->role }}</strong> di sistem Rekam Medis Elektronik MedScribe AI.
    </p>

    <div class="mt-6 p-4 rounded-[18px] bg-[#EEEEFD] border border-[#CBD1EA] text-left text-xs space-y-2.5">
        <div class="flex justify-between items-center py-1 border-b border-[#E4E7F4]">
            <span class="text-[#69718B]">ID Pengguna:</span>
            <span class="font-mono font-semibold text-[#122056]">#{{ $user->id_pengguna }}</span>
        </div>
        <div class="flex justify-between items-center py-1 border-b border-[#E4E7F4]">
            <span class="text-[#69718B]">Alamat Email:</span>
            <span class="font-medium text-[#122056]">{{ $user->email }}</span>
        </div>
        <div class="flex justify-between items-center py-1">
            <span class="text-[#69718B]">Peran Medis:</span>
            <span class="font-semibold text-[#5865DC] capitalize">{{ $user->role }}</span>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Manajemen Dokter - MedScribe AI')

@section('content')
    @include('admin.partials.active-staff', [
        'staff' => $doctors,
        'roleLabel' => 'Dokter',
        'route' => 'admin.doctors',
        'emptyIcon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    ])
@endsection

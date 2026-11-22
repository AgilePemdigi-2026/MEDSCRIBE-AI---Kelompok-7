<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil dokter: nama lengkap bergelar dan No. SIP yang dicetak
 * di bawah tanda tangan dokumen SOAP.
 */
class DokterProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dashboard.profil-dokter', [
            'user' => $request->user()->loadMissing('hospital'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100', 'regex:/\S/'],
            'nomor_sip'    => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9.\/\-\s]+$/'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 100 karakter.',
            'nama_lengkap.regex'    => 'Nama lengkap tidak boleh hanya berisi spasi.',
            'nomor_sip.max'         => 'No. SIP maksimal 50 karakter.',
            'nomor_sip.regex'       => 'No. SIP hanya boleh berisi huruf, angka, titik, garis miring, dan tanda hubung.',
        ]);

        $request->user()->update([
            'nama_lengkap' => trim($validated['nama_lengkap']),
            'nomor_sip'    => filled($validated['nomor_sip'] ?? null) ? trim($validated['nomor_sip']) : null,
        ]);

        return redirect()
            ->route('dokter.profil')
            ->with('status', 'Profil berhasil diperbarui.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil tenaga medis: nama bergelar dan nomor izin praktik
 * (SIP untuk dokter, SIPP untuk perawat) yang tercetak di dokumen SOAP.
 *
 * Selama nomor izin belum diisi, login diarahkan ke sini dulu (lihat
 * AuthController::redirectBasedOnRole). "Lewati dulu" hanya berlaku
 * untuk sesi login yang sedang berjalan.
 */
class ProfilController extends Controller
{
    public const SESSION_LEWATI = 'profil_lengkapi_dilewati';

    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing('hospital');

        return view('dashboard.profil', [
            'user' => $user,
            'labelIzin' => $user->label_nomor_izin,
            // Mode lengkapi: dibuka otomatis setelah login karena nomor izin masih kosong.
            'modeLengkapi' => blank($user->nomor_sip) && ! $request->session()->get(self::SESSION_LEWATI),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $label = $user->label_nomor_izin;

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100', 'regex:/\S/'],
            'nomor_sip'    => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9.\/\-\s]+$/'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 100 karakter.',
            'nama_lengkap.regex'    => 'Nama lengkap tidak boleh hanya berisi spasi.',
            'nomor_sip.max'         => $label . ' maksimal 50 karakter.',
            'nomor_sip.regex'       => $label . ' hanya boleh berisi huruf, angka, titik, garis miring, dan tanda hubung.',
        ]);

        $user->update([
            'nama_lengkap' => trim($validated['nama_lengkap']),
            'nomor_sip'    => filled($validated['nomor_sip'] ?? null) ? trim($validated['nomor_sip']) : null,
        ]);

        // Dari alur lengkapi setelah login: lanjut ke dashboard.
        if ($request->boolean('lanjut')) {
            $request->session()->put(self::SESSION_LEWATI, true);

            return redirect()
                ->route($user->isDokter() ? 'dokter.dashboard' : 'perawat.dashboard')
                ->with('status', 'Profil berhasil disimpan.');
        }

        return redirect()
            ->route('profil')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    public function lewati(Request $request): RedirectResponse
    {
        $request->session()->put(self::SESSION_LEWATI, true);

        return redirect()->route($request->user()->isDokter() ? 'dokter.dashboard' : 'perawat.dashboard');
    }
}

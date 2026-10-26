<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     * Memastikan pengguna memiliki peran yang diizinkan untuk mengakses rute.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
            ]);
        }

        $user = Auth::user();
        $userRole = strtolower($user->role ?? '');
        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array($userRole, $allowedRoles, true)) {
            // Jika request berupa AJAX/JSON, tolak dengan response 403
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki wewenang untuk mengakses sumber daya ini.',
                ], 403);
            }

            // Untuk aksi mutasi data (POST, PUT, PATCH, DELETE) oleh peran lain, tolak dengan status 403
            if (!$request->isMethodSafe()) {
                abort(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk melakukan tindakan ini.');
            }

            // Jika pengguna mencoba mengakses dashboard peran lain (navigasi GET), arahkan ke dashboard perannya sendiri
            if ($userRole === 'dokter') {
                return redirect()->route('dokter.dashboard')->with('warning', 'Akses ditolak: Anda tidak memiliki akses ke halaman Nurse Station/Perawat.');
            }

            if ($userRole === 'perawat') {
                return redirect()->route('perawat.dashboard')->with('warning', 'Akses ditolak: Anda tidak memiliki akses ke halaman Dokter.');
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}

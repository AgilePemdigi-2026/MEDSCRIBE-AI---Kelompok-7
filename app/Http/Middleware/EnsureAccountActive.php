<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    /**
     * Handle an incoming request.
     * Memastikan hanya akun dengan status aktif yang dapat mengakses fitur operasional.
     * Mengarahkan pengguna pending, rejected, atau inactive ke halaman pemberitahuan yang sesuai.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Rute publik/auth status yang dikecualikan dari redirect loop
        $excludedRoutes = ['auth.pending', 'auth.rejected', 'auth.inactive', 'logout'];
        $currentRouteName = $request->route()?->getName();

        if (in_array($currentRouteName, $excludedRoutes, true)) {
            return $next($request);
        }

        // 1. Akun Pending (Menunggu Verifikasi Admin)
        if ($user->isPending()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda sedang menunggu verifikasi admin rumah sakit. Anda belum dapat mengakses fitur operasional.',
                ], 403);
            }

            return redirect()->route('auth.pending');
        }

        // 2. Akun Ditolak (Rejected)
        if ($user->isRejected()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permohonan pendaftaran akun Anda ditolak oleh admin rumah sakit.',
                ], 403);
            }

            return redirect()->route('auth.rejected');
        }

        // 3. Akun Dinonaktifkan (Inactive)
        if ($user->isInactive()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda telah dinonaktifkan oleh admin rumah sakit.',
                ], 403);
            }

            return redirect()->route('auth.inactive');
        }

        return $next($request);
    }
}


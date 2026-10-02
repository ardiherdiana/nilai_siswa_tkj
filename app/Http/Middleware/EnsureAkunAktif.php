<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan admin saat sedang masuk langsung dikeluarkan pada permintaan berikutnya,
 * tanpa menunggu sesinya habis.
 */
class EnsureAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['username' => 'Akun dinonaktifkan. Hubungi admin sekolah.']);
        }

        return $next($request);
    }
}

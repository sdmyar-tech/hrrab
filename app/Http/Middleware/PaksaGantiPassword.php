<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun bertanda "wajib ganti password" tidak bisa membuka/menjalankan apa pun selain
 * halaman ganti password dan logout — termasuk bila URL diketik manual atau dipanggil lewat fetch/form.
 */
class PaksaGantiPassword
{
    /** Rute yang tetap boleh diakses selama password belum diganti. */
    private const BOLEH = ['akun.ganti-password', 'akun.ganti-password.simpan', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->wajib_ganti_password || $request->routeIs(...self::BOLEH)) {
            return $next($request);
        }

        $pesan = 'Anda wajib mengganti password terlebih dahulu.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $pesan, 'wajib_ganti_password' => true], 423);
        }

        return redirect()->route('akun.ganti-password');
    }
}

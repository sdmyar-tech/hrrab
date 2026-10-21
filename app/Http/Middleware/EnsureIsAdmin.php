<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof User || ! in_array($user->role, User::INTERNAL_ROLES, true) || $user->isStaff()) {
            if ($user instanceof User && $user->isStaff()) {
                return redirect()
                    ->route('pegawai.dashboard')
                    ->with('error', 'Anda tidak berhak mengakses halaman admin.');
            }

            abort_if(! $user instanceof User || $user->isPelamar(), 404);

            abort(403, 'Akses hanya untuk admin.');
        }

        $reviewFormasi = $request->routeIs('admin.pengajuan-formasi.approve', 'admin.pengajuan-formasi.reject');
        if ($user->isPembina() && ! $request->isMethod('GET') && ! $reviewFormasi) {
            abort(403, 'Akun Pembina hanya memiliki akses monitoring (read-only).');
        }

        return $next($request);
    }
}

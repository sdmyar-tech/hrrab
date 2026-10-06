<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsPegawai
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof \App\Models\User || ! $user->isStaff()) {
            if ($user instanceof \App\Models\User && in_array($user->role, \App\Models\User::INTERNAL_ROLES, true)) {
                return redirect()
                    ->route('admin.dashboard')
                    ->with('error', 'Anda tidak berhak mengakses halaman pegawai.');
            }

            abort_if(! $user instanceof \App\Models\User || $user->isPelamar(), 404);

            abort(403, 'Akses hanya untuk pegawai.');
        }

        return $next($request);
    }
}

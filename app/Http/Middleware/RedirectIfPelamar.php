<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPelamar
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof \App\Models\User && $user->isPelamar()) {
            if ($tujuan = $request->session()->pull('lowongan_tujuan')) {
                return redirect($tujuan);
            }

            return redirect()->route('pelamar.lamaran.index');
        }

        return $next($request);
    }
}

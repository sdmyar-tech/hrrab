<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsPelamar
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof \App\Models\User || ! $user->isPelamar()) {
            abort(403, 'Akses hanya untuk pelamar.');
        }

        return $next($request);
    }
}

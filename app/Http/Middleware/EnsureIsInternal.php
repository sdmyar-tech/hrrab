<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsInternal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        abort_unless($user instanceof User && in_array($user->role, User::INTERNAL_ROLES, true), 404);

        return $next($request);
    }
}

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

        if ($user instanceof User && $this->staffBerhakLewatRoleKustom($user, $request)) {
            return $next($request);
        }

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

    /**
     * Staff dengan role kustom (Master Role) boleh membuka halaman admin yang dijaga hak akses (middleware can:...)
     * bila semua hak itu dimilikinya. Halaman admin tanpa penjaga hak akses tetap tertutup untuk Staff.
     */
    private function staffBerhakLewatRoleKustom(User $user, Request $request): bool
    {
        if (! $user->isStaff() || ! $user->roleKustom()) {
            return false;
        }
        $hak = collect($request->route()?->gatherMiddleware() ?? [])
            ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'can:'))
            ->map(fn ($m) => explode(',', substr($m, 4))[0]);

        return $hak->isNotEmpty() && $hak->every(fn ($a) => $user->can($a));
    }
}

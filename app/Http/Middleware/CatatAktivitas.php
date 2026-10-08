<?php

namespace App\Http\Middleware;

use App\Support\PencatatLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Catat setiap tindakan (POST/PUT/DELETE & ekspor) pengguna selain superadmin ke log aktivitas. */
class CatatAktivitas
{
    public function handle(Request $request, Closure $next): Response
    {
        PencatatLog::mulaiRequest();

        $response = $next($request);

        if (PencatatLog::routeDicatat($request)) {
            PencatatLog::catatRequest($request, $response->getStatusCode());
        }

        return $response;
    }
}

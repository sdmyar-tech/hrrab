<?php

use App\Http\Middleware\CatatAktivitas;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsInternal;
use App\Http\Middleware\EnsureIsPegawai;
use App\Http\Middleware\EnsureIsPelamar;
use App\Http\Middleware\PaksaGantiPassword;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RedirectIfPelamar;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureIsAdmin::class,
            'pegawai' => EnsureIsPegawai::class,
            'pelamar' => EnsureIsPelamar::class,
            'internal' => EnsureIsInternal::class,
            'bukan-pelamar' => RedirectIfPelamar::class,
            'nocache' => PreventBackHistory::class,
        ]);

        $middleware->web(append: [PaksaGantiPassword::class, CatatAktivitas::class]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('pelamar/*')) {
                return route('pelamar.login');
            }

            throw new NotFoundHttpException;
        });
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

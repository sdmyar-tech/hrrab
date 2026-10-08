<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
       web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin'    => \App\Http\Middleware\EnsureIsAdmin::class,
        'pegawai'  => \App\Http\Middleware\EnsureIsPegawai::class,
        'pelamar'  => \App\Http\Middleware\EnsureIsPelamar::class,
        'internal' => \App\Http\Middleware\EnsureIsInternal::class,
        'bukan-pelamar' => \App\Http\Middleware\RedirectIfPelamar::class,
        'nocache'  => \App\Http\Middleware\PreventBackHistory::class,
    ]);

    $middleware->web(append: [\App\Http\Middleware\CatatAktivitas::class]);

    $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
        if ($request->is('pelamar/*')) {
            return route('pelamar.login');
        }

        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    });
})

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

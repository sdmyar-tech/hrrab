<?php

use App\Http\Middleware\CatatAktivitas;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsInternal;
use App\Http\Middleware\EnsureIsPegawai;
use App\Http\Middleware\EnsureIsPelamar;
use App\Http\Middleware\PaksaGantiPassword;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RedirectIfPelamar;
use App\Support\PortalStaf;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            // Staf yang sesinya habis → portal SDM (URL tujuan diingat). Tamu lain tetap 404 agar alamat portal tidak terbuka.
            if (PortalStaf::dikenali($request)) {
                return route('login');
            }

            throw new NotFoundHttpException;
        });
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Token CSRF kedaluwarsa (halaman dibiarkan terbuka sampai sesi habis) saat login, keluar, atau ganti password:
        // arahkan ke halaman login yang tepat, bukan halaman 419 yang tombolnya berujung ke Lowongan.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $request->routeIs('logout') => redirect()->to(PortalStaf::halamanLogin($request))->with('success', 'Anda telah keluar.'),
                $request->routeIs('login.submit', 'akun.ganti-password.simpan') => redirect()->route('login')
                    ->withInput($request->only('email'))->with('error', 'Sesi halaman sudah berakhir. Silakan masuk lagi.'),
                $request->routeIs('pelamar.login.submit') => redirect()->route('pelamar.login')
                    ->withInput($request->only('email'))->with('error', 'Sesi halaman sudah berakhir. Silakan masuk lagi.'),
                default => null,
            };
        });
    })->create();

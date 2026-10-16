<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Akses ditolak: bila pengguna datang dari halaman lain di aplikasi ini,
        // kembalikan ke sana dengan toast. Selain itu tampil errors/403.
        $exceptions->render(function (AccessDeniedHttpException $galat, Request $request) {
            $sebelumnya = (string) $request->headers->get('referer');

            if ($request->expectsJson() || ! $request->user()
                || ! str_starts_with($sebelumnya, url('/').'/') || $sebelumnya === $request->fullUrl()) {
                return null;
            }

            return redirect()->to($sebelumnya)->with('gagal',
                'Akun Anda ('.$request->user()->label_role.') tidak memiliki izin untuk aksi ini.');
        });
    })->create();

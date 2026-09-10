<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAuth::class,
        ]);

        // Midtrans & Biteship memanggil endpoint ini langsung dari server mereka,
        // tidak punya token CSRF Laravel. Keamanannya dijaga masing-masing lewat
        // verifikasi signature (Midtrans) dan token acak di path URL (Biteship).
        $middleware->validateCsrfTokens(except: [
            'webhooks/midtrans',
            'webhooks/biteship/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

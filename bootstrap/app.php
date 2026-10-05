<?php

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
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'seller' => \App\Http\Middleware\EnsureApprovedSeller::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureNotSuspended::class,
            \App\Http\Middleware\TrackReferral::class,
        ]);

        // PayPal posts webhooks without a CSRF token; they are verified by signature instead.
        $middleware->validateCsrfTokens(except: ['webhooks/paypal']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'odoo.auth' => \App\Http\Middleware\OdooAuth::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

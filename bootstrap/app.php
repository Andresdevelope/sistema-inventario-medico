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
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias personalizados de middleware
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class, // por si queremos sobrescribir / asegurar
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'no.cache' => \App\Http\Middleware\NoCacheHeaders::class,
        ]);

        // Evitar cache para respuestas web autenticadas (ver middleware NoCacheHeaders)
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\NoCacheHeaders::class,
        ]);

        // Si en el futuro necesitas añadir a grupos:
        // $middleware->appendToGroup('web', [ ... ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

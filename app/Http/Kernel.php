<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Confianza en proxies (X-Forwarded-*) para despliegues detrás de balanceadores
        \App\Http\Middleware\TrustProxies::class,
        // ...middleware globales...
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // Reactivar protección CSRF para rutas web
            \App\Http\Middleware\VerifyCsrfToken::class,
            // ...otros middleware web...
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array<string, class-string|string>
     */
    // Compatibilidad con nuevas versiones (Laravel 11/12) usando middlewareAliases
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'is_admin' => \App\Http\Middleware\IsAdmin::class,
        // Límite de peticiones por usuario/IP
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];

    // Para compatibilidad si alguna parte del código aún referencia routeMiddleware
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'is_admin' => \App\Http\Middleware\IsAdmin::class,
        // Alias adicional por compatibilidad con código que usa routeMiddleware
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];
}

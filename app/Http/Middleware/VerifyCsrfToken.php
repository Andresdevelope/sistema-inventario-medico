<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * Mantener vacío para proteger todas las rutas relevantes.
     *
     * @var array<int, string>
     */
    protected $except = [
        // No exenciones: todas las solicitudes mutadoras deben incluir token CSRF
    ];
}

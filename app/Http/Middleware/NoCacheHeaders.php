<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class NoCacheHeaders
{
    /**
     * Evita cache del contenido autenticado para reducir exposición de información
     * confidencial en equipos compartidos (ej. botón atrás después de cerrar sesión).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Verificamos que el usuario esté autenticado.
        // 2. Aseguramos que la respuesta sea una instancia válida que maneje cabeceras (evita errores con descargas o respuestas atípicas).
        if (Auth::check() && $response instanceof Response) {
            
            // Usamos 'no-cache, no-store' juntos para cubrir tanto HTTP/1.1 como navegadores más rebeldes
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache'); // Para compatibilidad con HTTP/1.0
            
            // MEJORA CLAVE: Algunos navegadores estrictos ignoran '0' como fecha de expiración. 
            // Es mucho más seguro usar una fecha exacta en el pasado.
            $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        }

        return $response;
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $expectsJson = $request->expectsJson() || $request->isMethod('delete') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('post');

        if (!$user) {
            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión para continuar.',
                ], 401);
            }
            return redirect('/login')->with('error', 'Debes iniciar sesión para continuar.');
        }

        if (!$user->hasPermission($permission)) {
            $message = 'Acceso restringido: no tienes permisos para esta acción.';
            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }
            return redirect('/dashboard')->with('error', $message);
        }

        return $next($request);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTPS en producción para URLs generadas por el framework
        try {
            if (config('app.env') === 'production') {
                URL::forceScheme('https');
            }
        } catch (\Throwable $e) {}
        // ================= Rate limiting (login y recuperación) =================
        RateLimiter::for('login', function (Request $request) {
            $username = (string) ($request->input('username') ?? $request->input('email') ?? '');
            $key = strtolower($username).'|'.$request->ip();
            return [
                Limit::perMinute(5)->by($key)
                    ->response(function () {
                        return response()->json([
                            'success' => false,
                            'message' => 'Demasiados intentos de inicio de sesión. Intenta nuevamente en un minuto.'
                        ], 429);
                    })
            ];
        });

        RateLimiter::for('recover', function (Request $request) {
            $emailOrUser = (string) ($request->input('email') ?? $request->input('username') ?? '');
            $key = strtolower($emailOrUser).'|'.$request->ip().'|'.($request->path() ?? 'recover');
            return [
                Limit::perMinute(10)->by($key)
                    ->response(function () {
                        return response()->json([
                            'success' => false,
                            'message' => 'Demasiadas solicitudes de recuperación. Intenta nuevamente en breve.'
                        ], 429);
                    })
            ];
        });

        // Composer para la vista dashboard: comparte contadores siempre que se renderiza
        View::composer('dashboard', function ($view) {
            try {
                $view->with('totalCategorias', Categoria::count());
            } catch (\Throwable $e) {
                $view->with('totalCategorias', null);
            }
            try {
                $view->with('totalProductos', Producto::count());
            } catch (\Throwable $e) {
                $view->with('totalProductos', null);
            }

            // ================= OPCIONALES (descomenta cuando estén listos) =================
            // use App\Models\User;            // Asegúrate de importar arriba si lo usas
            // use App\Models\Proveedor;       // Si quieres contar proveedores
            // use App\Models\Movimiento;      // Si creas un modelo Movimiento
            // use App\Models\Reporte;         // Si creas un modelo Reporte

            // Usuarios registrados
            // try {
            //     $view->with('totalUsuarios', User::count());
            // } catch (\Throwable $e) {
            //     $view->with('totalUsuarios', null);
            // }

            // Total de ítems de inventario (ejemplo sumando stock de productos)
            // try {
            //     $view->with('totalInventario', Producto::sum('stock'));
            // } catch (\Throwable $e) {
            //     $view->with('totalInventario', null);
            // }

            // Movimientos (si existe tabla movimientos)
            // try {
            //     $view->with('totalMovimientos', Movimiento::count());
            // } catch (\Throwable $e) {
            //     $view->with('totalMovimientos', null);
            // }

            // Reportes generados (placeholder)
            // try {
            //     $view->with('totalReportes', Reporte::count());
            // } catch (\Throwable $e) {
            //     $view->with('totalReportes', null);
            // }
        });
    }
}

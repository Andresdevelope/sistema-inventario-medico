<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Categoria;
use App\Models\Producto;

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

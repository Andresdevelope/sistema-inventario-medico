<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Bitacora;
use App\Models\Movimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class InventarioController extends Controller
{
    /**
     * Muestra el inventario consolidado con filtros y KPIs.
     * Permite buscar por nombre, categoría, categoría de inventario y fecha de ingreso.
     * Muestra el stock real sumando los registros de inventario asociados a cada producto.
     * Paginación configurable.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Obtener filtros de la request
        $q = $request->input('search'); // Búsqueda por nombre
        $categoria = $request->input('categoria'); // Filtro por categoría
        $categoriaInventario = $request->input('categoria_inventario'); // Filtro por área (general / odontologia)
        $fecha = $request->input('fecha'); // Filtro por fecha de ingreso
        $perPage = (int) $request->input('per_page', 25); // Resultados por página
        $perPage = $perPage > 0 ? min(100, $perPage) : 25;

        // Listado de categorías para el filtro
        $categorias = Categoria::all();

        // Construir consulta base con relaciones
        $query = Producto::with(['categoria', 'subcategoria', 'proveedor', 'inventarios']);

        // Aplicar filtros si existen
        if ($q) {
            $query->where('nombre', 'like', "%{$q}%");
        }
        if ($categoria) {
            $query->where('categoria_id', $categoria);
        }
        if ($categoriaInventario) {
            $query->where('categoria_inventario', $categoriaInventario);
        }
        if ($fecha) {
            $query->whereDate('fecha_ingreso', $fecha);
        }

        // Obtener productos paginados
        $productos = $query->orderBy('nombre')->paginate($perPage)->appends($request->query());

        // Identificar qué productos ya tienen distribuciones registradas para habilitar el botón DISTR
        $productIds = $productos->pluck('id');
        $distribucionesPorProducto = $productIds->isNotEmpty()
            ? Movimiento::selectRaw('producto_id, COUNT(*) as total_distribuciones')
                ->whereIn('producto_id', $productIds)
                ->where('tipo', 'egreso')
                ->where('modalidad', 'distribucion')
                ->groupBy('producto_id')
                ->pluck('total_distribuciones', 'producto_id')
            : collect();

        $productos->getCollection()->transform(function ($producto) use ($distribucionesPorProducto) {
            $producto->has_distribuciones = $distribucionesPorProducto->has($producto->id);
            return $producto;
        });

        // KPIs básicos para mostrar en la vista
        $totalItems = Producto::count();
        // Stock bajo considerando transición: si no hay inventarios, usar producto.stock
        $stockBajo = Producto::with('inventarios')->get()->filter(function($p){
            // Tomar el mínimo entre: stock_minimo por inventario y, si no existe, el del producto
            $minInventarios = optional($p->inventarios->whereNotNull('stock_minimo'))
                ->min('stock_minimo');
            $stockMin = $minInventarios ?? $p->stock_minimo;
            if ($stockMin === null) return false; // Si no hay stock mínimo, nunca es bajo
            $tieneInventarios = $p->inventarios && $p->inventarios->count() > 0;
            $stockActual = $tieneInventarios ? ($p->inventarios->sum('cantidad')) : ($p->stock ?? 0);
            return $stockActual < $stockMin;
        })->count();

                // Próximos a vencer (≤ 30 días) considerando inventarios con fecha de vencimiento y cantidad > 0.
                // Se compara solo por fecha (sin hora) para evitar problemas de zonas horarias.
                $hoy = Carbon::today();
                $limite = Carbon::today()->addDays(30);
                $proximosAVencer = Producto::whereHas('inventarios', function ($q) use ($hoy, $limite) {
                        $q->whereNotNull('fecha_vencimiento')
                            ->where('cantidad', '>', 0)
                            ->whereDate('fecha_vencimiento', '>=', $hoy)
                            ->whereDate('fecha_vencimiento', '<=', $limite);
                })->count();

        // Registrar acceso en bitácora (ingreso al módulo inventario)
        try {
            if (Auth::check()) {
                Bitacora::create([
                    'user_id' => Auth::id(),
                    'accion' => 'inventario.index',
                    'detalles' => json_encode([
                        'filtros' => $request->query(),
                        'ip' => $request->ip(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora' => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        // Retornar la vista con los datos necesarios
        return view('inventario.index', compact('totalItems','stockBajo','proximosAVencer','productos','categorias'));
    }

}

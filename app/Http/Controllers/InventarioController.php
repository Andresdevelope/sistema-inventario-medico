<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use Carbon\Carbon;

class InventarioController extends Controller
{
    public function index()
    {
        // KPIs básicos de inventario para prototipo
        $totalItems = Producto::count();
        $stockBajo = Producto::where('stock', '<=', 5)->count();
        $proximosAVencer = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(30))
            ->count();

        // Listado resumido (limitado) para tabla demo
        $productos = Producto::with(['categoria', 'subcategoria', 'proveedor'])
            ->orderBy('nombre')
            ->limit(25)
            ->get();

        return view('inventario.index', compact('totalItems','stockBajo','proximosAVencer','productos'));
    }
}

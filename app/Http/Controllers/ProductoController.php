<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $q = $request->input('search');
        $sort = $request->input('sort', 'nombre');
        $dir  = $request->input('dir', 'asc');
        $categoria = $request->input('categoria');
        $perPage = (int) $request->input('per_page', 25);
        $perPage = $perPage > 0 ? min(100, $perPage) : 25;

        $allowed = ['nombre','codigo','presentacion','stock','categoria_id'];

        $query = Producto::with(['categoria', 'subcategoria', 'proveedor']);

        if ($q) {
            $query->where(function($qb) use ($q) {
                $qb->where('nombre', 'like', "%{$q}%")
                   ->orWhere('codigo', 'like', "%{$q}%")
                   ->orWhere('presentacion', 'like', "%{$q}%");
            });
        }

        if ($categoria) {
            $query->where('categoria_id', $categoria);
        }

        if (! in_array($sort, $allowed)) {
            $sort = 'nombre';
        }
        if (! in_array(strtolower($dir), ['asc','desc'])) {
            $dir = 'asc';
        }

        $productos = $query->orderBy($sort, $dir)
                           ->paginate($perPage)
                           ->appends($request->query());

        $categorias = \App\Models\Categoria::all();
        return view('productos.index', compact('productos','categorias'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categorias = \App\Models\Categoria::all();
        $subcategorias = \App\Models\Subcategoria::all();
        $proveedores = \App\Models\Proveedor::all();
        return view('productos.create', compact('categorias', 'subcategorias', 'proveedores'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|max:100|unique:productos,codigo',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'required|exists:subcategorias,id',
            'presentacion' => 'required|string|max:100',
            'unidad_medida' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_ingreso' => 'required|date',
            'fecha_vencimiento' => 'required|date',
        ]);

        $producto = Producto::create($request->all() + [
            'created_by' => Auth::user() ? Auth::user()->id : null,
            'updated_by' => Auth::user() ? Auth::user()->id : null,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    /**
     * Devuelve un código candidato basado en el nombre (AJAX)
     */
    public function generarCodigo(Request $request)
    {
        $request->validate([ 'nombre' => 'required|string' ]);
        $codigo = \App\Models\Producto::generateUniqueCodigo($request->input('nombre'));
        return response()->json(['codigo' => $codigo]);
    }

    /**
     * Display the specified product.
     */
    public function show(Producto $producto)
    {
        return view('productos.show', compact('producto'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Producto $producto)
    {
        $categorias = \App\Models\Categoria::all();
        $subcategorias = \App\Models\Subcategoria::all();
        $proveedores = \App\Models\Proveedor::all();
        return view('productos.edit', compact('producto', 'categorias', 'subcategorias', 'proveedores'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|max:100|unique:productos,codigo,' . $producto->id,
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'required|exists:subcategorias,id',
            'presentacion' => 'required|string|max:100',
            'unidad_medida' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_ingreso' => 'required|date',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        $producto->update($request->all() + [
            'updated_by' => Auth::user() ? Auth::user()->id : null,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    // El middleware de autenticación debe ser aplicado en el controlador base o en las rutas.
    // Si necesitas protección, usa Route::middleware(['auth']) en web.php o elimina este constructor.
}

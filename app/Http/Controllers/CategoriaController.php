<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use App\Models\Subcategoria;

class CategoriaController extends Controller
{
    /**
     * Devuelve las subcategorías de una categoría específica (AJAX).
     */
    public function subcategoriasPorCategoria($categoriaId)
    {
        $subcategorias = Subcategoria::where('categoria_id', $categoriaId)->get();
        return response()->json($subcategorias);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = Categoria::with('subcategorias')->get();
        return view('layouts.categoria.categoria', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_categoria' => 'required|string|max:255',
            'nombre_subcategoria' => 'nullable|string|max:255',
        ]);
        // Validar unicidad (case-insensitive) de categoría
        $nombreCat = trim($request->nombre_categoria);
        $existeCat = Categoria::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreCat)])->exists();
        if ($existeCat) {
            return response()->json(['success' => false, 'message' => 'Ya existe una categoría con ese nombre.']);
        }
    $categoria = Categoria::create(['nombre' => $nombreCat]);
    $this->logBitacora('categoria.crear', ['id' => $categoria->id, 'nombre' => $categoria->nombre]);
        $subcategoria = null;
        if ($request->filled('nombre_subcategoria')) {
            // Validar que no exista la misma subcategoría para esa categoría
            $subNombre = trim($request->nombre_subcategoria);
            $existe = $categoria->subcategorias()->whereRaw('LOWER(nombre) = ?', [mb_strtolower($subNombre)])->exists();
            if ($existe) {
                return response()->json(['success' => false, 'message' => 'Ya existe esa subcategoría para esta categoría.']);
            }
            $subcategoria = Subcategoria::create([
                'nombre' => $subNombre,
                'categoria_id' => $categoria->id
            ]);
            $this->logBitacora('subcategoria.crear', ['id'=>$subcategoria->id,'nombre'=>$subNombre,'categoria_id'=>$categoria->id]);
        }
        return response()->json([
            'success' => true,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_categoria' => 'required|string|max:255',
        ]);
        $categoria = Categoria::findOrFail($id);
        $nuevoNombre = trim($request->nombre_categoria);
        $existe = Categoria::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nuevoNombre)])
            ->where('id', '!=', $id)
            ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Ya existe otra categoría con ese nombre.']);
        }
        $old = ['id'=>$categoria->id,'nombre'=>$categoria->nombre];
        $categoria->nombre = $nuevoNombre;
        $categoria->save();
        $this->logBitacora('categoria.actualizar', ['antes'=>$old,'despues'=>['id'=>$categoria->id,'nombre'=>$categoria->nombre]]);
        return response()->json(['success' => true, 'categoria' => $categoria]);
    }

    /**
     * Actualizar una subcategoría individualmente
     */
    public function updateSubcategoria(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);
        $subcategoria = Subcategoria::findOrFail($id);
        // Validar que no exista otra subcategoría con ese nombre en la misma categoría
        $existe = Subcategoria::where('categoria_id', $subcategoria->categoria_id)
            ->where('nombre', $request->nombre)
            ->where('id', '!=', $id)
            ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Ya existe esa subcategoría para esta categoría.']);
        }
        $old = ['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id];
        $subcategoria->nombre = $request->nombre;
        $subcategoria->save();
        $this->logBitacora('subcategoria.actualizar', ['antes'=>$old,'despues'=>['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id]]);
        return response()->json(['success' => true, 'subcategoria' => $subcategoria]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $snapshot = ['id'=>$categoria->id,'nombre'=>$categoria->nombre];
        $categoria->delete();
        $this->logBitacora('categoria.eliminar', $snapshot);
        return response()->json(['success' => true]);
    }

    /**
     * Endpoint AJAX para obtener todas las categorías y subcategorías
     */
    public function listar()
    {
        $categorias = Categoria::with('subcategorias')->get();
        return response()->json($categorias);
    }
}

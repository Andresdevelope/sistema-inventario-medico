<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subcategoria;

class SubcategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subcategorias = Subcategoria::with('categoria')->get();
        return response()->json($subcategorias);
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
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
        ]);
        $nombre = trim($request->nombre);
        $existe = Subcategoria::where('categoria_id', $request->categoria_id)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Ya existe una subcategoría con ese nombre en esta categoría.']);
        }
        $subcategoria = Subcategoria::create([
            'nombre' => $nombre,
            'categoria_id' => $request->categoria_id
        ]);
        $this->logBitacora('subcategoria.crear', ['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id]);
        return response()->json(['success' => true, 'subcategoria' => $subcategoria]);
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
            'nombre' => 'required|string|max:255',
        ]);
        $subcategoria = Subcategoria::findOrFail($id);
        $nombre = trim($request->nombre);
        $existe = Subcategoria::where('categoria_id', $subcategoria->categoria_id)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->where('id', '!=', $id)
            ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Ya existe una subcategoría con ese nombre en esta categoría.']);
        }
        $old = ['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id];
        $subcategoria->nombre = $nombre;
        $subcategoria->save();
        $this->logBitacora('subcategoria.actualizar', ['antes'=>$old,'despues'=>['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id]]);
        return response()->json(['success' => true, 'subcategoria' => $subcategoria]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $snapshot = ['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id];
        $subcategoria->delete();
        $this->logBitacora('subcategoria.eliminar', $snapshot);
        return response()->json(['success' => true]);
    }
}

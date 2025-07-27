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
        $subcategoria = Subcategoria::create([
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id
        ]);
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
        $subcategoria->nombre = $request->nombre;
        $subcategoria->save();
        return response()->json(['success' => true, 'subcategoria' => $subcategoria]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $subcategoria->delete();
        return response()->json(['success' => true]);
    }
}

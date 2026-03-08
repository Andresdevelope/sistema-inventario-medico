<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subcategoria;
use App\Models\Producto;

class SubcategoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:categorias.ver')->only(['index', 'show', 'dependencias']);
        $this->middleware('permission:categorias.crear')->only(['create', 'store']);
        $this->middleware('permission:categorias.editar')->only(['edit', 'update']);
        $this->middleware('permission:categorias.eliminar')->only(['destroy']);
    }

    private function sanitizeLabel(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = trim($value);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    private function looksSuspiciousLabel(?string $value): bool
    {
        $text = $this->sanitizeLabel($value);
        if ($text === '') {
            return true;
        }

        $compact = preg_replace('/\s+/u', '', $text) ?? '';
        $lettersOnly = preg_replace('/[^\pL]/u', '', $text) ?? '';

        if (preg_match('/^\d+$/u', $compact)) {
            return true;
        }

        if (mb_strlen($lettersOnly) < 2) {
            return true;
        }

        if (preg_match('/(.)\1{4,}/u', $compact)) {
            return true;
        }

        if (preg_match('/[bcdfghjklmnñpqrstvwxyz]{8,}/iu', $lettersOnly)) {
            return true;
        }

        if (!str_contains($text, ' ') && mb_strlen($lettersOnly) > 14) {
            return true;
        }

        return false;
    }

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
        $request->merge([
            'nombre' => $this->sanitizeLabel($request->input('nombre')),
        ]);

        $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/&]+$/u',
                function ($attribute, $value, $fail) {
                    if ($this->looksSuspiciousLabel((string) $value)) {
                        $fail('El nombre de la subcategoría no parece válido. Usa texto real y evita patrones repetitivos.');
                    }
                },
            ],
            'categoria_id' => 'required|exists:categorias,id',
        ], [
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.string' => 'El nombre de la subcategoría debe ser texto válido.',
            'nombre.min' => 'El nombre de la subcategoría debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre de la subcategoría no puede superar 80 caracteres.',
            'nombre.regex' => 'El nombre de la subcategoría contiene caracteres no permitidos.',
            'categoria_id.required' => 'La categoría es obligatoria para crear una subcategoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
        ]);
        $nombre = $this->sanitizeLabel($request->nombre);
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
        $request->merge([
            'nombre' => $this->sanitizeLabel($request->input('nombre')),
        ]);

        $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/&]+$/u',
                function ($attribute, $value, $fail) {
                    if ($this->looksSuspiciousLabel((string) $value)) {
                        $fail('El nombre de la subcategoría no parece válido. Usa texto real y evita patrones repetitivos.');
                    }
                },
            ],
        ], [
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.string' => 'El nombre de la subcategoría debe ser texto válido.',
            'nombre.min' => 'El nombre de la subcategoría debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre de la subcategoría no puede superar 80 caracteres.',
            'nombre.regex' => 'El nombre de la subcategoría contiene caracteres no permitidos.',
        ]);
        $subcategoria = Subcategoria::findOrFail($id);
        $nombre = $this->sanitizeLabel($request->nombre);
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
        // Validar dependencias: productos
        $productosCount = Producto::where('subcategoria_id', $subcategoria->id)->count();
        if ($productosCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar. La subcategoría tiene {$productosCount} medicamento(s) asociados.",
                'dependencias' => ['medicamentos' => $productosCount]
            ], 422);
        }
        $snapshot = ['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id];
        $subcategoria->delete();
        $this->logBitacora('subcategoria.eliminar', $snapshot);
        return response()->json(['success' => true]);
    }

    /**
     * Endpoint AJAX: dependencias de una subcategoría (conteos)
     */
    public function dependencias($id)
    {
        $subcategoria = Subcategoria::findOrFail($id);
        $medicamentos = Producto::where('subcategoria_id', $id)->count();
        return response()->json([
            'success' => true,
            'dependencias' => compact('medicamentos')
        ]);
    }
}

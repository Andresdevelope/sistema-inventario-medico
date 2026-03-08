<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;

class CategoriaController extends Controller
{
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

        // No permitir entradas compuestas solo por números.
        if (preg_match('/^\d+$/u', $compact)) {
            return true;
        }

        // Debe contener al menos 2 letras reales.
        if (mb_strlen($lettersOnly) < 2) {
            return true;
        }

        // Repetición excesiva del mismo carácter: aaaaaaaa, 11111111.
        if (preg_match('/(.)\1{4,}/u', $compact)) {
            return true;
        }

        // Cadenas muy largas de consonantes suelen ser ruido.
        if (preg_match('/[bcdfghjklmnñpqrstvwxyz]{8,}/iu', $lettersOnly)) {
            return true;
        }

        // Secuencias alfabéticas sin vocales o muy largas sin separación.
        if (!str_contains($text, ' ') && mb_strlen($lettersOnly) > 14) {
            return true;
        }

        return false;
    }

    public function __construct()
    {
        $this->middleware('permission:categorias.ver')->only(['index','listar','dependencias','subcategoriasPorCategoria']);
        $this->middleware('permission:categorias.crear')->only(['store']);
        $this->middleware('permission:categorias.editar')->only(['update','updateSubcategoria']);
        $this->middleware('permission:categorias.eliminar')->only(['destroy']);
    }

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
        $request->merge([
            'nombre_categoria' => $this->sanitizeLabel($request->input('nombre_categoria')),
            'nombre_subcategoria' => $this->sanitizeLabel($request->input('nombre_subcategoria')),
        ]);

        $request->validate([
            'nombre_categoria' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/&]+$/u',
                function ($attribute, $value, $fail) {
                    if ($this->looksSuspiciousLabel((string) $value)) {
                        $fail('El nombre de la categoría no parece válido. Usa texto real y evita patrones repetitivos.');
                    }
                },
            ],
            'nombre_subcategoria' => [
                'nullable',
                'string',
                'min:2',
                'max:80',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/&]+$/u',
                function ($attribute, $value, $fail) {
                    if ((string) $value !== '' && $this->looksSuspiciousLabel((string) $value)) {
                        $fail('El nombre de la subcategoría no parece válido. Usa texto real y evita patrones repetitivos.');
                    }
                },
            ],
        ], [
            'nombre_categoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombre_categoria.string' => 'El nombre de la categoría debe ser texto válido.',
            'nombre_categoria.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
            'nombre_categoria.max' => 'El nombre de la categoría no puede superar 80 caracteres.',
            'nombre_categoria.regex' => 'El nombre de la categoría contiene caracteres no permitidos.',
            'nombre_subcategoria.string' => 'El nombre de la subcategoría debe ser texto válido.',
            'nombre_subcategoria.min' => 'El nombre de la subcategoría debe tener al menos 2 caracteres.',
            'nombre_subcategoria.max' => 'El nombre de la subcategoría no puede superar 80 caracteres.',
            'nombre_subcategoria.regex' => 'El nombre de la subcategoría contiene caracteres no permitidos.',
        ]);
        // Validar unicidad (case-insensitive) de categoría
        $nombreCat = $this->sanitizeLabel($request->nombre_categoria);
        $existeCat = Categoria::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreCat)])->exists();
        if ($existeCat) {
            return response()->json(['success' => false, 'message' => 'Ya existe una categoría con ese nombre.']);
        }
    $categoria = Categoria::create(['nombre' => $nombreCat]);
    $this->logBitacora('categoria.crear', ['id' => $categoria->id, 'nombre' => $categoria->nombre]);
        $subcategoria = null;
        if ($request->filled('nombre_subcategoria')) {
            // Validar que no exista la misma subcategoría para esa categoría
            $subNombre = $this->sanitizeLabel($request->nombre_subcategoria);
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
        $request->merge([
            'nombre_categoria' => $this->sanitizeLabel($request->input('nombre_categoria')),
        ]);

        $request->validate([
            'nombre_categoria' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/&]+$/u',
                function ($attribute, $value, $fail) {
                    if ($this->looksSuspiciousLabel((string) $value)) {
                        $fail('El nombre de la categoría no parece válido. Usa texto real y evita patrones repetitivos.');
                    }
                },
            ],
        ], [
            'nombre_categoria.required' => 'El nombre de la categoría es obligatorio.',
            'nombre_categoria.string' => 'El nombre de la categoría debe ser texto válido.',
            'nombre_categoria.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
            'nombre_categoria.max' => 'El nombre de la categoría no puede superar 80 caracteres.',
            'nombre_categoria.regex' => 'El nombre de la categoría contiene caracteres no permitidos.',
        ]);
        $categoria = Categoria::findOrFail($id);
        $nuevoNombre = $this->sanitizeLabel($request->nombre_categoria);
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
        // Validar que no exista otra subcategoría con ese nombre en la misma categoría
        $existe = Subcategoria::where('categoria_id', $subcategoria->categoria_id)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower((string) $request->nombre)])
            ->where('id', '!=', $id)
            ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Ya existe esa subcategoría para esta categoría.']);
        }
        $old = ['id'=>$subcategoria->id,'nombre'=>$subcategoria->nombre,'categoria_id'=>$subcategoria->categoria_id];
        $subcategoria->nombre = $this->sanitizeLabel($request->nombre);
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
        $productosCount = Producto::where('categoria_id', $categoria->id)->count();
        $subcategoriaIds = Subcategoria::where('categoria_id', $categoria->id)->pluck('id');
        $subcatsConMedicamentos = $subcategoriaIds->isEmpty()
            ? 0
            : Producto::whereIn('subcategoria_id', $subcategoriaIds)->distinct()->count('subcategoria_id');

        if ($productosCount > 0 || $subcatsConMedicamentos > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar. Reasigna o elimina primero los medicamentos asociados a esta categoría o a sus subcategorías.',
                'dependencias' => [
                    'medicamentos' => $productosCount,
                    'subcategorias_en_uso' => $subcatsConMedicamentos,
                    'subcategorias_totales' => $subcategoriaIds->count(),
                ]
            ], 422);
        }

        DB::transaction(function () use ($categoria, $subcategoriaIds) {
            if ($subcategoriaIds->isNotEmpty()) {
                Subcategoria::whereIn('id', $subcategoriaIds)->delete();
            }
            $snapshot = [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'subcategorias_eliminadas' => $subcategoriaIds->count(),
            ];
            $categoria->delete();
            $this->logBitacora('categoria.eliminar', $snapshot);
        });

        return response()->json([
            'success' => true,
            'subcategorias_eliminadas' => $subcategoriaIds->count(),
        ]);
    }

    /**
     * Endpoint AJAX para obtener todas las categorías y subcategorías
     */
    public function listar()
    {
        $categorias = Categoria::with('subcategorias')->get();
        return response()->json($categorias);
    }

    /**
     * Endpoint AJAX: dependencias de una categoría (conteos)
     */
    public function dependencias($id)
    {
        $categoria = Categoria::findOrFail($id);
        $medicamentos = Producto::where('categoria_id', $id)->count();
        $subcategoriasTotales = Subcategoria::where('categoria_id', $id)->count();
        $subcategoriaIds = Subcategoria::where('categoria_id', $id)->pluck('id');
        $subcategoriasEnUso = $subcategoriaIds->isEmpty()
            ? 0
            : Producto::whereIn('subcategoria_id', $subcategoriaIds)->distinct()->count('subcategoria_id');
        $subcategoriasVacias = max(0, $subcategoriasTotales - $subcategoriasEnUso);
        return response()->json([
            'success' => true,
            'dependencias' => [
                'medicamentos' => $medicamentos,
                'subcategorias_totales' => $subcategoriasTotales,
                'subcategorias_en_uso' => $subcategoriasEnUso,
                'subcategorias_vacias' => $subcategoriasVacias,
            ]
        ]);
    }
}

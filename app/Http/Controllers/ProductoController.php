<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Unidades de medida permitidas para productos.
     * mg/mcg → implica manejo por blíster (tabletas, cápsulas).
     * Resto → unidad directa (frascos, cremas, insumos).
     */
    // Las constantes fijas de UNIDADES_MEDIDA y UNIDADES_BLISTER han sido eliminadas para soportar texto libre y el checkbox usa_blister.

    public function __construct()
    {
        $this->middleware('permission:medicamentos.ver')->only(['index','show','buscarAjax']);
        $this->middleware('permission:medicamentos.crear')->only(['create','store']);
        $this->middleware('permission:medicamentos.editar')->only(['edit','update']);
        $this->middleware('permission:medicamentos.eliminar')->only(['destroy']);
    }

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
        $request->merge(['usa_blister' => $request->has('usa_blister')]);
        $request->merge($this->normalizarCamposTextoProducto($request));

        $rules = [
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/\+%]+$/u',
                function ($attribute, $value, $fail) {
                    if (! $this->nombreMedicamentoPareceValido((string) $value)) {
                        $fail('Ingresa un nombre real de medicamento (ej. Amoxicilina 500 mg). No se permiten solo números ni texto inválido.');
                    }

                    $totalDigitos = preg_match_all('/\pN/u', (string) $value);
                    if ($totalDigitos !== false && $totalDigitos > 4) {
                        $fail('El nombre puede contener máximo 4 números.');
                    }
                },
            ],
            'codigo' => 'required|string|min:3|max:30|regex:/^[A-Z0-9\-\.\/]+$/|unique:productos,codigo',
            'descripcion' => 'nullable|string|min:10|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'required|exists:subcategorias,id',
            'presentacion' => 'required|string|min:2|max:60|regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/\+%]+$/u',
            'unidad_medida' => 'required|string|min:1|max:20|regex:/^[\pL\pN\s\-\.%\/]+$/u',
            'usa_blister' => 'boolean',
            'tipo_producto' => 'required|string|in:medicamento,insumo',
            'categoria_inventario' => 'required|string|in:general,odontologia',
            'stock' => 'required|integer|min:1|max:9999|digits_between:1,4',
            'stock_minimo' => 'nullable|integer|min:1|max:9999|digits_between:1,4',
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_ingreso' => 'required|date|before_or_equal:today',
            'fecha_vencimiento' => 'required|date|after:fecha_ingreso|after:today'
        ];

        $request->validate($rules, $this->mensajesValidacion());

        $producto = Producto::create($request->all() + [
            'created_by' => Auth::user() ? Auth::user()->id : null,
            'updated_by' => Auth::user() ? Auth::user()->id : null,
        ]);
        $this->logBitacora('producto.crear', ['id'=>$producto->id,'nombre'=>$producto->nombre,'codigo'=>$producto->codigo]);

        // Crear inventario inicial para que el stock se refleje en detalle (stock_total) y movimientos futuros.
        if ($producto->stock > 0 && Inventario::where('producto_id',$producto->id)->count() === 0) {
            Inventario::create([
                'producto_id' => $producto->id,
                'lote' => null,
                'cantidad' => $producto->stock,
                'fecha_vencimiento' => $producto->fecha_vencimiento,
                'stock_minimo' => $producto->stock_minimo,
                'estado' => 'activo',
            ]);
        }

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    /**
     * Endpoint AJAX para búsquedas incrementales desde el módulo de movimientos.
     */
    public function buscarAjax(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $term = mb_substr($term, 0, 35);
        $tipo = $request->input('tipo');
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min(30, $perPage));

        $query = Producto::query()->select(['id','nombre','codigo','tipo_producto','unidad_medida','usa_blister']);

        if ($term !== '') {
            $query->where(function($qb) use ($term) {
                $qb->where('nombre', 'like', "%{$term}%")
                   ->orWhere('codigo', 'like', "%{$term}%");
            });
        }

        if (in_array($tipo, ['medicamento','insumo'], true)) {
            $query->where('tipo_producto', $tipo);
        }

        $productos = $query->orderBy('nombre')->simplePaginate($perPage);

        $items = collect($productos->items())->map(function (Producto $producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo,
                'tipo' => $producto->tipo_producto,
                'unidad_medida' => $producto->unidad_medida,
                'usa_blister' => (bool)$producto->usa_blister,
                'display' => sprintf('%s (%s)', $producto->nombre, $producto->codigo),
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $productos->currentPage(),
                'per_page' => $perPage,
                'has_more' => $productos->hasMorePages(),
                'next_page' => $productos->hasMorePages() ? $productos->currentPage() + 1 : null,
                'query' => $term,
                'tipo' => $tipo,
            ],
        ]);
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
        $request->merge(['usa_blister' => $request->has('usa_blister')]);
        $request->merge($this->normalizarCamposTextoProducto($request));

        $rules = [
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/\+%]+$/u',
                function ($attribute, $value, $fail) {
                    if (! $this->nombreMedicamentoPareceValido((string) $value)) {
                        $fail('Ingresa un nombre real de medicamento (ej. Amoxicilina 500 mg). No se permiten solo números ni texto inválido.');
                    }

                    $totalDigitos = preg_match_all('/\pN/u', (string) $value);
                    if ($totalDigitos !== false && $totalDigitos > 4) {
                        $fail('El nombre puede contener máximo 4 números.');
                    }
                },
            ],
            'codigo' => 'required|string|min:3|max:30|regex:/^[A-Z0-9\-\.\/]+$/|unique:productos,codigo,' . $producto->id,
            'descripcion' => 'nullable|string|min:10|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'required|exists:subcategorias,id',
            'presentacion' => 'required|string|min:2|max:60|regex:/^(?=.*\pL)[\pL\pN\s\-\.,\(\)\/\+%]+$/u',
            'unidad_medida' => 'required|string|min:1|max:20|regex:/^[\pL\pN\s\-\.%\/]+$/u',
            'usa_blister' => 'boolean',
            'tipo_producto' => 'required|string|in:medicamento,insumo',
            'categoria_inventario' => 'required|string|in:general,odontologia',
            'stock' => 'required|integer|min:1|max:9999|digits_between:1,4',
            'stock_minimo' => 'nullable|integer|min:1|max:9999|digits_between:1,4',
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_ingreso' => 'required|date|before_or_equal:today',
            'fecha_vencimiento' => 'nullable|date|after:fecha_ingreso|after:today',
        ];

        $request->validate($rules, $this->mensajesValidacion());

        $old = $producto->only(['id','nombre','codigo','categoria_id','subcategoria_id','presentacion','unidad_medida','usa_blister','categoria_inventario','stock','proveedor_id','stock_minimo','fecha_vencimiento']);
        $oldFechaVencimiento = $producto->fecha_vencimiento;
        $producto->update($request->all() + [
            'updated_by' => Auth::user() ? Auth::user()->id : null,
        ]);
        $this->logBitacora('producto.actualizar', ['antes'=>$old,'despues'=>$producto->only(array_keys($old))]);

        // Si tras la actualización no existen inventarios y stock > 0, crear inventario base para mantener consistencia con stock_total.
        $invCount = Inventario::where('producto_id',$producto->id)->count();
        if ($invCount === 0 && $producto->stock > 0) {
            Inventario::create([
                'producto_id' => $producto->id,
                'lote' => null,
                'cantidad' => $producto->stock,
                'fecha_vencimiento' => $producto->fecha_vencimiento,
                'stock_minimo' => $producto->stock_minimo,
                'estado' => 'activo',
            ]);
        } elseif ($request->filled('fecha_vencimiento') && $oldFechaVencimiento !== $producto->fecha_vencimiento) {
            Inventario::where('producto_id', $producto->id)
                ->where(function($q) use ($oldFechaVencimiento) {
                    $q->whereNull('lote')
                      ->orWhere('fecha_vencimiento', $oldFechaVencimiento);
                })
                ->update(['fecha_vencimiento' => $producto->fecha_vencimiento]);
        }

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Producto $producto)
    {
        // Validación previa: bloquear si existen movimientos; si solo hay inventarios "vírgenes", se eliminan automáticamente
        $inventariosQuery = \App\Models\Inventario::where('producto_id', $producto->id);
        $inventariosCount = $inventariosQuery->count();
        $movimientosCount = \App\Models\Movimiento::where('producto_id', $producto->id)->count();

        if ($movimientosCount > 0) {
            return redirect()->route('productos.index')
                ->with('error', "No se puede eliminar: tiene {$inventariosCount} inventario(s) y {$movimientosCount} movimiento(s) registrados.");
        }

        if ($inventariosCount > 0) {
            $inventariosQuery->delete();
        }

        $snapshot = $producto->only(['id','nombre','codigo']);
        $producto->delete();
        $this->logBitacora('producto.eliminar', $snapshot);
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    // El middleware de autenticación debe ser aplicado en el controlador base o en las rutas.
    // Si necesitas protección, usa Route::middleware(['auth']) en web.php o elimina este constructor.

    private function mensajesValidacion(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números y signos permitidos (.-,()/+%).',

            'codigo.required' => 'El código es obligatorio.',
            'codigo.string' => 'El código debe ser un texto válido.',
            'codigo.min' => 'El código debe tener al menos 3 caracteres.',
            'codigo.max' => 'El código no puede superar los 30 caracteres.',
            'codigo.regex' => 'El código solo puede contener letras, números y los símbolos - . /',
            'codigo.unique' => 'El código ingresado ya existe en otro producto.',

            'descripcion.string' => 'La descripción debe ser un texto válido.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres si se indica.',
            'descripcion.max' => 'La descripción no puede superar los 100 caracteres.',

            'categoria_id.required' => 'Debes seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',

            'subcategoria_id.required' => 'Debes seleccionar una subcategoría.',
            'subcategoria_id.exists' => 'La subcategoría seleccionada no es válida.',

            'presentacion.required' => 'La presentación es obligatoria.',
            'presentacion.string' => 'La presentación debe ser un texto válido.',
            'presentacion.min' => 'La presentación debe tener al menos 2 caracteres.',
            'presentacion.max' => 'La presentación no puede superar los 60 caracteres.',
            'presentacion.regex' => 'La presentación contiene caracteres no permitidos.',

            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida.string' => 'La unidad de medida debe ser un texto válido.',
            'unidad_medida.min' => 'La unidad de medida debe tener al menos 1 carácter.',
            'unidad_medida.max' => 'La unidad de medida no puede superar los 20 caracteres.',
            'unidad_medida.regex' => 'La unidad de medida contiene caracteres no permitidos.',

            'tipo_producto.required' => 'Debes seleccionar el tipo de producto.',
            'tipo_producto.string' => 'El tipo de producto debe ser un texto válido.',
            'tipo_producto.in' => 'El tipo de producto seleccionado no es válido.',

            'categoria_inventario.required' => 'Debes seleccionar la categoría de inventario.',
            'categoria_inventario.string' => 'La categoría de inventario debe ser un texto válido.',
            'categoria_inventario.in' => 'La categoría de inventario seleccionada no es válida.',

            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock debe ser mínimo 1.',
            'stock.max' => 'El stock no puede superar 9999 (4 dígitos).',
            'stock.digits_between' => 'El stock solo puede tener entre 1 y 4 dígitos.',

            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min' => 'El stock mínimo debe ser mínimo 1.',
            'stock_minimo.max' => 'El stock mínimo no puede superar 9999 (4 dígitos).',
            'stock_minimo.digits_between' => 'El stock mínimo solo puede tener entre 1 y 4 dígitos.',

            'proveedor_id.required' => 'Debes seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',

            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso debe tener un formato válido.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser posterior a hoy.',

            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento debe tener un formato válido.',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la fecha de ingreso y al día actual.',
        ];
    }

    private function normalizarCamposTextoProducto(Request $request): array
    {
        $campos = ['nombre', 'codigo', 'presentacion', 'descripcion', 'unidad_medida'];
        $normalizados = [];

        foreach ($campos as $campo) {
            if (! $request->has($campo)) {
                continue;
            }

            $valor = $request->input($campo);
            if (! is_string($valor)) {
                continue;
            }

            $texto = trim($valor);

            if ($campo === 'descripcion') {
                $texto = strip_tags($texto);
            }

            $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

            if ($campo === 'codigo') {
                $texto = mb_strtoupper($texto);
            }

            if ($campo === 'nombre') {
                $texto = $this->capitalizarPalabras($texto);
            }

            if ($campo === 'presentacion') {
                $texto = $this->capitalizarPrimeraLetra($texto);
            }

            if ($campo === 'unidad_medida') {
                // Las unidades de medida se guardan normalizadas en minúscula
                $texto = mb_strtolower($texto);
            }

            $normalizados[$campo] = $texto;
        }

        return $normalizados;
    }

    private function capitalizarPrimeraLetra(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return $texto;
        }

        $primera = mb_substr($texto, 0, 1, 'UTF-8');
        $resto = mb_substr($texto, 1, null, 'UTF-8');

        return mb_strtoupper($primera, 'UTF-8') . $resto;
    }

    private function capitalizarPalabras(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return $texto;
        }

        return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }

    private function nombreMedicamentoPareceValido(string $nombre): bool
    {
        $texto = trim(mb_strtolower($nombre));

        if ($texto === '') {
            return false;
        }

        // No permitir entradas compuestas solo por números.
        if (preg_match('/^\d+$/u', $texto)) {
            return false;
        }

        // Debe contener al menos una letra y una vocal.
        if (! preg_match('/\pL/u', $texto) || ! preg_match('/[aeiouáéíóú]/u', $texto)) {
            return false;
        }

        // Debe tener al menos 3 letras reales (sin contar espacios ni símbolos).
        $soloLetras = preg_replace('/[^\pL]/u', '', $texto) ?? '';
        if (mb_strlen($soloLetras) < 3) {
            return false;
        }

        // Evitar basura tipo "aaaaaa" o secuencias consonánticas excesivas.
        if (preg_match('/(.)\1{4,}/u', $texto) || preg_match('/[bcdfghjklmnñpqrstvwxyz]{6,}/u', $texto)) {
            return false;
        }

        return true;
    }
}

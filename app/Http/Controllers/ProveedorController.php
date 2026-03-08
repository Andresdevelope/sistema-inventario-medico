<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;

class ProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:medicamentos.crear')->only(['storeAjax']);
        $this->middleware('permission:medicamentos.editar')->only(['updateAjax']);
        $this->middleware('permission:medicamentos.eliminar')->only(['destroyAjax']);
    }

    // ...existing code...
    /**
     * Guarda un proveedor desde el modal (AJAX).
     */
    public function storeAjax(Request $request)
    {
        $request->merge($this->normalizarCamposTextoProveedor($request));

        $request->validate([
            'nombre' => 'required|string|min:3|max:120',
            'contacto' => 'nullable|string|min:3|max:80',
            'direccion' => 'nullable|string|min:5|max:180',
            'email' => 'nullable|email|max:120',
        ], $this->mensajesValidacionProveedor());
        $proveedor = Proveedor::create($request->all());
        $this->logBitacora('proveedor.crear', ['id'=>$proveedor->id,'nombre'=>$proveedor->nombre]);
        return response()->json(['success' => true, 'proveedor' => $proveedor]);
    }

    /**
     * Actualiza un proveedor desde el modal (AJAX).
     */
    public function updateAjax(Request $request, $id)
    {
        $request->merge($this->normalizarCamposTextoProveedor($request));

        $request->validate([
            'nombre' => 'required|string|min:3|max:120',
            'contacto' => 'nullable|string|min:3|max:80',
            'direccion' => 'nullable|string|min:5|max:180',
            'email' => 'nullable|email|max:120',
        ], $this->mensajesValidacionProveedor());
        $proveedor = Proveedor::findOrFail($id);
        $old = $proveedor->only(['id','nombre','contacto','direccion','email']);
        $proveedor->update($request->all());
        $this->logBitacora('proveedor.actualizar', ['antes'=>$old,'despues'=>$proveedor->only(array_keys($old))]);
        return response()->json(['success' => true, 'proveedor' => $proveedor]);
    }

    /**
     * Elimina un proveedor vía AJAX.
     */
    public function destroyAjax($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $snapshot = $proveedor->only(['id','nombre']);
        $proveedor->delete();
        $this->logBitacora('proveedor.eliminar', $snapshot);
        return response()->json(['success' => true]);
    }

    private function mensajesValidacionProveedor(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nombre.string' => 'El nombre del proveedor debe ser texto válido.',
            'nombre.min' => 'El nombre del proveedor debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre del proveedor no puede superar los 120 caracteres.',

            'contacto.string' => 'El contacto debe ser texto válido.',
            'contacto.min' => 'El contacto debe tener al menos 3 caracteres si se indica.',
            'contacto.max' => 'El contacto no puede superar los 80 caracteres.',

            'direccion.string' => 'La dirección debe ser texto válido.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres si se indica.',
            'direccion.max' => 'La dirección no puede superar los 180 caracteres.',

            'email.email' => 'El correo del proveedor debe tener un formato válido.',
            'email.max' => 'El correo del proveedor no puede superar los 120 caracteres.',
        ];
    }

    private function normalizarCamposTextoProveedor(Request $request): array
    {
        $campos = ['nombre', 'contacto', 'direccion', 'email'];
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
            $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

            if ($campo === 'email') {
                $texto = mb_strtolower($texto);
            }

            $normalizados[$campo] = $texto;
        }

        return $normalizados;
    }
  

}

<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;

class ProveedorController extends Controller
{
    // ...existing code...
    /**
     * Guarda un proveedor desde el modal (AJAX).
     */
    public function storeAjax(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);
        $proveedor = Proveedor::create($request->all());
        return response()->json(['success' => true, 'proveedor' => $proveedor]);
    }

    /**
     * Actualiza un proveedor desde el modal (AJAX).
     */
    public function updateAjax(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($request->all());
        return response()->json(['success' => true, 'proveedor' => $proveedor]);
    }

    /**
     * Elimina un proveedor vía AJAX.
     */
    public function destroyAjax($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();
        return response()->json(['success' => true]);
    }
  

}

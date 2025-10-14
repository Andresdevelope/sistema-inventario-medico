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
        $this->logBitacora('proveedor.crear', ['id'=>$proveedor->id,'nombre'=>$proveedor->nombre]);
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
  

}

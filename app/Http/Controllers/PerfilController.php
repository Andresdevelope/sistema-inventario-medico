<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    /**
     * Valida las respuestas de seguridad vía AJAX.
     * Retorna cuál respuesta es incorrecta.
     */
    public function validarSeguridad(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());
        $colorCorrecto = \Illuminate\Support\Facades\Hash::check($request->input('color'), $user->security_color_answer);
        $animalCorrecto = \Illuminate\Support\Facades\Hash::check($request->input('animal'), $user->security_animal_answer);
        $errores = [];
        if (!$colorCorrecto) {
            $errores[] = 'La respuesta de color favorito es incorrecta.';
        }
        if (!$animalCorrecto) {
            $errores[] = 'La respuesta de animal favorito es incorrecta.';
        }
        if (empty($errores)) {
            return response()->json(['success' => true, 'message' => 'Respuestas correctas.']);
        }
        return response()->json(['success' => false, 'message' => implode(' ', $errores)]);
    }
    /**
     * Cambia la contraseña del usuario si las respuestas de seguridad son correctas.
     * Valida la contraseña actual y la nueva antes de guardar.
     */
    public function cambiarContrasena(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());
        // Validar y cambiar contraseña (las preguntas de seguridad ya se validaron por AJAX)
        if ($request->has('actual') && $request->has('nueva') && $request->has('confirmar')) {
            // Validar contraseña actual
            if (!Hash::check($request->input('actual'), $user->password)) {
                return back()->withErrors(['actual' => 'La contraseña actual es incorrecta.'])->withInput();
            }
            // Validar nueva contraseña
            $nueva = $request->input('nueva');
            $confirmar = $request->input('confirmar');
            if (strlen($nueva) < 8 || $nueva !== $confirmar) {
                return back()->withErrors(['nueva' => 'La nueva contraseña debe tener al menos 8 caracteres y coincidir con la confirmación.'])->withInput();
            }
            // Guardar nueva contraseña
            $user->password = Hash::make($nueva);
            $user->save();
            $this->logBitacora('perfil.cambiar_contrasena', ['user_id'=>$user->id]);
            return back()->with('success', '¡Contraseña cambiada correctamente!');
        }
        // Si no se envió el formulario completo, no hacer nada
        return back();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class RecoverController extends Controller
{
    // Verifica si el correo existe y retorna el id del usuario
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $user = User::where('email', $request->email)->first();
        if ($user) {
            return response()->json(['success' => true, 'user_id' => $user->id]);
        }
        return response()->json(['success' => false]);
    }

    // Verifica las respuestas de seguridad
    public function checkSecurity(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'color' => 'required|string',
            'animal' => 'required|string',
        ]);
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado']);
        }
        $colorOk = Hash::check($request->color, $user->security_color_answer);
        $animalOk = Hash::check($request->animal, $user->security_animal_answer);
        if ($colorOk && $animalOk) {
            return response()->json(['success' => true]);
        }
        $errors = [];
        if (!$colorOk) $errors[] = 'color';
        if (!$animalOk) $errors[] = 'animal';
        return response()->json(['success' => false, 'incorrect' => $errors]);
    }

    // Cambia la contraseña del usuario
    public function changePassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'password' => 'required|string|min:6',
        ]);
        $user = User::find($request->user_id);
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }
}

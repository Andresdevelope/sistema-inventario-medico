<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'color' => 'required|string',
            'animal' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'security_color_answer' => Hash::make($request->color),
            'security_animal_answer' => Hash::make($request->animal),
        ]);

        return response()->json(['success' => true, 'user' => $user]);
    }

    // Inicio de sesión
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('name', $request->username)->first();
        $now = now();
        if ($user) {
            // Verificar si está bloqueado
            if ($user->locked_until && $user->locked_until > $now) {
                $minutos = $user->locked_until->diffInSeconds($now);
                return response()->json(['success' => false, 'message' => 'Usuario bloqueado. Intenta en ' . $minutos . ' segundos.'], 403);
            }
            // Verificar contraseña
            if (Hash::check($request->password, $user->password)) {
                $user->login_attempts = 0;
                $user->locked_until = null;
                $user->save();
                Auth::login($user);
                return response()->json(['success' => true, 'redirect' => url('/dashboard')]);
            } else {
                $user->login_attempts++;
                if ($user->login_attempts == 3) {
                    $user->locked_until = $now->addMinute();
                    $user->login_attempts = 0;
                    $user->save();
                    // Recalcular el usuario para obtener el locked_until actualizado
                    $user = User::where('name', $request->username)->first();
                    $segundos = $user->locked_until ? $user->locked_until->diffInSeconds(now()) : 60;
                    return response()->json(['success' => false, 'message' => 'Usuario bloqueado. Intenta en ' . $segundos . ' segundos.'], 403);
                }
                $user->save();
                return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
            }
        }
        return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }
}

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
            'password' => ['required','string','min:8','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'color' => 'required|string',
            'animal' => 'required|string',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe contener al menos una letra y un número.',
        ]);


        // Asignar rol: el primer usuario será admin, los demás operador
        $rol = User::count() === 0 ? 'admin' : 'operador';

        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'security_color_answer' => Hash::make($request->color),
            'security_animal_answer' => Hash::make($request->animal),
            'role' => $rol,
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
                $this->logBitacora('auth.login', ['user_id'=>$user->id,'name'=>$user->name]);
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
                $this->logBitacora('auth.login_fallido', ['username'=>$request->username]);
                return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
            }
        }
        $this->logBitacora('auth.login_fallido', ['username'=>$request->username]);
        return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }
}

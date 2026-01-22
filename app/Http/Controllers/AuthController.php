<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required','string','min:16','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'color' => 'required|string',
            'animal' => 'required|string',
            'padre' => 'required|string',
        ], [
            'password.min' => 'La contraseña debe tener al menos 16 caracteres.',
            'password.regex' => 'La contraseña debe contener al menos una letra y un número.',
        ]);

        // reCAPTCHA v2 para registro (si está habilitado y configurado)
        $enabled = (bool) config('services.recaptcha.enabled');
        $siteKey = config('services.recaptcha.site_key');
        $secret = config('services.recaptcha.secret');
        if ($enabled && $siteKey && $secret) {
            $captchaResponse = $request->input('g-recaptcha-response');
            if (!$captchaResponse) {
                return response()->json(['success' => false, 'message' => 'Por favor completa el reCAPTCHA.'], 422);
            }
            try {
                $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $captchaResponse,
                    'remoteip' => $request->ip(),
                ]);
                $result = $verify->json();
                if (!$verify->ok() || empty($result['success'])) {
                    return response()->json(['success' => false, 'message' => 'Verificación reCAPTCHA falló. Intenta nuevamente.'], 422);
                }
            } catch (\Throwable $e) {
                // En desarrollo/testing, permitir continuar si la red falla (por ejemplo sin internet)
                if (!app()->environment('production')) {
                    // Log opcional: \Log::warning('reCAPTCHA no disponible: '.$e->getMessage());
                } else {
                    return response()->json(['success' => false, 'message' => 'No se pudo verificar reCAPTCHA. Intenta más tarde.'], 500);
                }
            }
        }


        // Asignar rol: el primer usuario será admin, los demás operador
        $rol = User::count() === 0 ? 'admin' : 'operador';

        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Normalización de respuestas antes de hashear para comparaciones robustas
            'security_color_answer' => Hash::make(self::normalize($request->color)),
            'security_animal_answer' => Hash::make(self::normalize($request->animal)),
            'security_padre_answer' => Hash::make(self::normalize($request->padre)),
            'role' => $rol,
        ]);

        return response()->json(['success' => true, 'user' => $user]);
    }

    /**
     * Normaliza cadenas para almacenamiento/validación de preguntas de seguridad.
     * - trim, lowercase, quitar diacríticos básicos, colapsar espacios.
     */
    private static function normalize(?string $v): string
    {
        if ($v === null) return '';
        $v = trim(mb_strtolower($v));
        $v = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return $v;
    }

    // Inicio de sesión
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Validación reCAPTCHA v2 (si está habilitado y configurado)
        $enabled = (bool) config('services.recaptcha.enabled');
        $siteKey = config('services.recaptcha.site_key');
        $secret = config('services.recaptcha.secret');
        if ($enabled && $siteKey && $secret) {
            $captchaResponse = $request->input('g-recaptcha-response');
            if (!$captchaResponse) {
                return response()->json(['success' => false, 'message' => 'Por favor completa el reCAPTCHA.'], 422);
            }
            try {
                $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $captchaResponse,
                    'remoteip' => $request->ip(),
                ]);
                $result = $verify->json();
                if (!$verify->ok() || empty($result['success'])) {
                    return response()->json(['success' => false, 'message' => 'Verificación reCAPTCHA falló. Intenta nuevamente.'], 422);
                }
            } catch (\Throwable $e) {
                // En desarrollo/testing, permitir continuar si la red falla (por ejemplo sin internet)
                if (!app()->environment('production')) {
                    // Log opcional: \Log::warning('reCAPTCHA no disponible: '.$e->getMessage());
                } else {
                    return response()->json(['success' => false, 'message' => 'No se pudo verificar reCAPTCHA. Intenta más tarde.'], 500);
                }
            }
        }

        $user = User::where('name', $request->username)->first();
        if ($user) {
            // Verificar si está bloqueado (locked_until no es null)
            if ($user->locked_until !== null) {
                return response()->json(['success' => false, 'message' => 'Usuario bloqueado por intentos fallidos. Solo un administrador puede desbloquear tu cuenta para acceder al sistema.'], 403);
            }
            // Verificar contraseña
            if (Hash::check($request->password, $user->password)) {
                $user->login_attempts = 0;
                $user->save();
                Auth::login($user);
                $this->logBitacora('auth.login', ['user_id'=>$user->id,'name'=>$user->name]);
                return response()->json(['success' => true, 'redirect' => url('/dashboard')]);
            } else {
                // Manejar intentos fallidos
                $user->login_attempts++;
                if ($user->login_attempts >= 3) {
                    $user->locked_until = now(); // Bloqueo indefinido
                    $user->login_attempts = 0;
                    $user->save();
                    $this->logBitacora('auth.bloqueo', ['user_id'=>$user->id,'name'=>$user->name]);
                    return response()->json(['success' => false, 'message' => 'Usuario bloqueado por intentos fallidos. Solo un administrador puede desbloquear tu cuenta para acceder al sistema.'], 403);
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

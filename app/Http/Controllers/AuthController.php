<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $request->merge([
            'username' => self::sanitizeTextInput($request->input('username')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'password' => self::sanitizePasswordInput($request->input('password')),
            'color' => self::sanitizeTextInput($request->input('color')),
            'animal' => self::sanitizeTextInput($request->input('animal')),
            'padre' => self::sanitizeTextInput($request->input('padre')),
        ]);

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousText($value)) {
                        $fail('El nombre de usuario no parece válido. Usa solo texto real (máx. 40).');
                    }
                },
            ],
            'email' => [
                'required',
                'string',
                'max:60',
                'email:rfc',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousEmail($value)) {
                        $fail('El correo ingresado no parece válido. Verifica el formato y dominio.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:16',
                'max:30',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])\S+$/',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousPassword($value)) {
                        $fail('La contraseña no parece segura. Evita secuencias o patrones repetitivos.');
                    }
                },
            ],
            'color' => [
                'required',
                'string',
                'min:2',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousText($value)) {
                        $fail('La respuesta de seguridad no parece válida. Usa solo texto real (máx. 40).');
                    }
                },
            ],
            'animal' => [
                'required',
                'string',
                'min:2',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousText($value)) {
                        $fail('La respuesta de seguridad no parece válida. Usa solo texto real (máx. 40).');
                    }
                },
            ],
            'padre' => [
                'required',
                'string',
                'min:2',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousText($value)) {
                        $fail('La respuesta de seguridad no parece válida. Usa solo texto real (máx. 40).');
                    }
                },
            ],
        ], [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.string' => 'El nombre de usuario debe ser texto válido.',
            'username.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'username.max' => 'El nombre de usuario no puede superar 40 caracteres.',
            'username.regex' => 'El nombre de usuario solo debe contener letras y espacios.',
            'email.required' => 'El correo es obligatorio.',
            'email.string' => 'El correo debe ser texto válido.',
            'email.email' => 'El formato del correo no es válido.',
            'email.max' => 'El correo no puede superar 60 caracteres.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto válido.',
            'password.min' => 'La contraseña debe tener al menos 16 caracteres.',
            'password.max' => 'La contraseña no puede superar 30 caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una mayúscula, una minúscula, un número y un símbolo, sin espacios.',
            'color.required' => 'La respuesta de color favorito es obligatoria.',
            'color.string' => 'La respuesta de color favorito debe ser texto válido.',
            'color.min' => 'La respuesta de color favorito debe tener al menos 2 caracteres.',
            'color.max' => 'La respuesta no puede superar 40 caracteres.',
            'color.regex' => 'La respuesta solo debe contener letras y espacios.',
            'animal.required' => 'La respuesta de animal favorito es obligatoria.',
            'animal.string' => 'La respuesta de animal favorito debe ser texto válido.',
            'animal.min' => 'La respuesta de animal favorito debe tener al menos 2 caracteres.',
            'animal.max' => 'La respuesta no puede superar 40 caracteres.',
            'animal.regex' => 'La respuesta solo debe contener letras y espacios.',
            'padre.required' => 'La respuesta de nombre del padre es obligatoria.',
            'padre.string' => 'La respuesta de nombre del padre debe ser texto válido.',
            'padre.min' => 'La respuesta de nombre del padre debe tener al menos 2 caracteres.',
            'padre.max' => 'La respuesta no puede superar 40 caracteres.',
            'padre.regex' => 'La respuesta solo debe contener letras y espacios.',
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
        $maxAdmins = (int) config('inventario.max_admins', 2);
        if ($rol === 'admin') {
            $adminsActuales = User::where('role', 'admin')->count();
            if ($adminsActuales >= $maxAdmins) {
                return response()->json([
                    'success' => false,
                    'message' => "No se pueden registrar más administradores. Límite permitido: {$maxAdmins}."
                ], 422);
            }
        }

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

    /**
     * Limpia espacios al inicio/fin y colapsa múltiples espacios internos.
     */
    private static function sanitizeTextInput(?string $v): string
    {
        if ($v === null) return '';
        return preg_replace('/\s+/u', ' ', trim($v));
    }

    /**
     * Sanitiza contraseña quitando espacios al inicio/fin.
     */
    private static function sanitizePasswordInput(?string $v): string
    {
        if ($v === null) return '';
        return trim($v);
    }

    /**
     * Detecta texto sospechoso (ruido, secuencias irreales o entradas basura).
     */
    private static function isSuspiciousText(?string $v): bool
    {
        $value = self::sanitizeTextInput($v);
        if ($value === '') return true;

        $compact = preg_replace('/\s+/u', '', $value);

        // Repetición excesiva del mismo carácter: ej. aaaaaa
        if (preg_match('/(.)\1{3,}/u', $compact)) {
            return true;
        }

        // Palabra muy larga sin espacios suele ser ruido (ej. aiosdioajsdasldaodklalask)
        if (!str_contains($value, ' ') && mb_strlen($compact) > 12) {
            return true;
        }

        return false;
    }

    /**
     * Detecta correos sospechosos por dominio typo común y local-part basura.
     */
    private static function isSuspiciousEmail(?string $email): bool
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '' || !str_contains($email, '@')) {
            return true;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        $typoDomains = [
            'gmai.com',
            'gmial.com',
            'gmal.com',
            'hotnail.com',
            'yaho.com',
        ];

        if (in_array($domain, $typoDomains, true)) {
            return true;
        }

        if ($localPart === '' || preg_match('/^\d+$/', $localPart)) {
            return true;
        }

        if (preg_match('/(.)\1{4,}/', $localPart)) {
            return true;
        }

        // Local-part muy largo y sin separadores suele ser poco confiable.
        if (mb_strlen($localPart) > 18 && !preg_match('/[._-]/', $localPart)) {
            return true;
        }

        return false;
    }

    /**
     * Detecta contraseñas sospechosas por patrones triviales/repetitivos.
     */
    private static function isSuspiciousPassword(?string $password): bool
    {
        $password = self::sanitizePasswordInput($password);
        if ($password === '') return true;

        // No aceptar solo números (ej. 111111... o 124124...)
        if (preg_match('/^\d+$/', $password)) {
            return true;
        }

        // Repetición prolongada del mismo carácter (ej. aaaaaaaaaaaa)
        if (preg_match('/(.)\1{4,}/u', $password)) {
            return true;
        }

        // Poca variedad de caracteres (ej. patrón muy pobre)
        if (count(array_unique(str_split($password))) < 4) {
            return true;
        }

        return false;
    }

    // Inicio de sesión
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.string' => 'El nombre de usuario debe ser texto válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto válido.',
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
                $roleNoticeKey = 'role_notice_user_'.$user->id;
                $roleNotice = Cache::get($roleNoticeKey);
                if ($roleNotice) {
                    Cache::forget($roleNoticeKey);
                }
                return response()->json([
                    'success' => true,
                    'redirect' => url('/dashboard'),
                    'notice' => $roleNotice,
                ]);
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

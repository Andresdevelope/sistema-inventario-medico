<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RecoverController extends Controller
{
    // Verifica si el correo existe y retorna el id del usuario
    public function checkEmail(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => [
                'required',
                'string',
                'max:60',
                'email:rfc',
                function ($attribute, $value, $fail) {
                    if (self::isSuspiciousEmail($value)) {
                        $fail('El correo ingresado no parece válido. Verifica el formato y dominio.');
                    }
                },
            ],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.string' => 'El correo debe ser texto válido.',
            'email.max' => 'El correo no puede superar 60 caracteres.',
            'email.email' => 'El formato del correo no es válido.',
        ]);

        // reCAPTCHA v2 para recuperación (si está habilitado y configurado)
        $enabled = (bool) config('services.recaptcha.enabled');
        $siteKey = config('services.recaptcha.site_key');
        $secret = config('services.recaptcha.secret');
        if ($enabled && $siteKey && $secret) {
            $captchaResponse = $request->input('g-recaptcha-response');
            if (!$captchaResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor completa el reCAPTCHA.'
                ], 422);
            }
            try {
                $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $captchaResponse,
                    'remoteip' => $request->ip(),
                ]);
                $result = $verify->json();
                if (!$verify->ok() || empty($result['success'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Verificación reCAPTCHA falló. Intenta nuevamente.'
                    ], 422);
                }
            } catch (\Throwable $e) {
                // En desarrollo/testing, permitir continuar si la red falla (por ejemplo sin internet)
                if (!app()->environment('production')) {
                    // Log opcional: \Log::warning('reCAPTCHA no disponible: '.$e->getMessage());
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo verificar reCAPTCHA. Intenta más tarde.'
                    ], 500);
                }
            }
        }

        $user = User::where('email', $request->email)->first();

        // Token opaco temporal para evitar exponer IDs de usuario en el front.
        $flowToken = Str::random(64);
        Cache::put(
            $this->recoverFlowCacheKey($flowToken),
            [
                'user_id' => $user?->id,
                'valid' => $user !== null,
            ],
            now()->addMinutes(10)
        );

        // Respuesta uniforme para no facilitar enumeración de correos.
        return response()->json([
            'success' => true,
            'flow_token' => $flowToken,
            'message' => 'Si el correo existe en el sistema, podrás continuar con la verificación de seguridad.'
        ]);
    }

    /**
     * Verificación progresiva de seguridad.
     * Paso 1: verificar color y animal.
     * - Si ambos son correctos: success=true.
     * - Si cualquiera falla o está vacío: solicitar tercera pregunta (padre).
     * Paso 2 (opcional): si se envía 'padre', validar que sea correcto; si lo es,
     * permitir continuar aunque una de las primeras dos haya fallado.
     * Nota: se aplica normalización de entradas antes de comparar (lowercase, trim).
     */
    public function checkSecurity(Request $request)
    {
        $request->merge([
            'color' => self::emptyToNull(self::sanitizeTextInput($request->input('color'))),
            'animal' => self::emptyToNull(self::sanitizeTextInput($request->input('animal'))),
            'padre' => self::emptyToNull(self::sanitizeTextInput($request->input('padre'))),
        ]);

        $request->validate([
            'flow_token' => 'required|string|min:20',
            // En el primer intento color/animal son requeridos; para el segundo,
            // el front reenvía los mismos valores junto con 'padre'.
            'color' => [
                'nullable',
                'string',
                'min:2',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if ($value !== null && self::isSuspiciousText($value)) {
                        $fail('La respuesta de seguridad no parece válida. Usa solo texto real (máx. 40).');
                    }
                },
            ],
            'animal' => [
                'nullable',
                'string',
                'min:2',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if ($value !== null && self::isSuspiciousText($value)) {
                        $fail('La respuesta de seguridad no parece válida. Usa solo texto real (máx. 40).');
                    }
                },
            ],
            'padre' => [
                'nullable',
                'string',
                'min:2',
                'max:40',
                'regex:/^[\pL\s]+$/u',
                function ($attribute, $value, $fail) {
                    if ($value !== null && self::isSuspiciousText($value)) {
                        $fail('La respuesta de seguridad no parece válida. Usa solo texto real (máx. 40).');
                    }
                },
            ],
        ], [
            'flow_token.required' => 'El token de recuperación es obligatorio.',
            'flow_token.min' => 'El token de recuperación no es válido.',
            'color.min' => 'La respuesta de color favorito debe tener al menos 2 caracteres.',
            'color.max' => 'La respuesta de color favorito no puede superar 40 caracteres.',
            'color.regex' => 'La respuesta de color favorito solo debe contener letras y espacios.',
            'animal.min' => 'La respuesta de animal favorito debe tener al menos 2 caracteres.',
            'animal.max' => 'La respuesta de animal favorito no puede superar 40 caracteres.',
            'animal.regex' => 'La respuesta de animal favorito solo debe contener letras y espacios.',
            'padre.min' => 'La respuesta de nombre del padre debe tener al menos 2 caracteres.',
            'padre.max' => 'La respuesta de nombre del padre no puede superar 40 caracteres.',
            'padre.regex' => 'La respuesta de nombre del padre solo debe contener letras y espacios.',
        ]);

        $flowData = Cache::get($this->recoverFlowCacheKey($request->flow_token));
        $user = null;
        if (is_array($flowData) && !empty($flowData['valid']) && !empty($flowData['user_id'])) {
            $user = User::find((int) $flowData['user_id']);
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Las respuestas no coinciden con nuestros registros. Intenta nuevamente.'
            ]);
        }

        // Helper de normalización para comparar cadenas de manera robusta
        $colorInput = self::normalizeSecurityAnswer($request->color);
        $animalInput = self::normalizeSecurityAnswer($request->animal);
        $padreInput = self::normalizeSecurityAnswer($request->padre);

        // Hash::check necesita el valor tal cual se guardó; como guardamos normalizado
        // en el registro, también normalizamos antes de hashear en registro.
        $colorOk = ($colorInput !== '') && Hash::check($colorInput, $user->security_color_answer);
        $animalOk = ($animalInput !== '') && Hash::check($animalInput, $user->security_animal_answer);

        // Compatibilidad hacia atrás: si el almacenamiento previo no estaba normalizado,
        // intentamos con el valor sin normalizar (trim simple) para no romper usuarios existentes.
        if (!$colorOk && $request->color) {
            $colorOk = Hash::check(trim($request->color), $user->security_color_answer);
        }
        if (!$animalOk && $request->animal) {
            $animalOk = Hash::check(trim($request->animal), $user->security_animal_answer);
        }

        if ($colorOk && $animalOk) {
            return response()->json([
                'success' => true,
                'message' => 'Respuestas correctas. Puedes continuar.'
            ]);
        }

        // Si falta o falla alguna de las primeras dos, exigir la tercera
        $needPadre = (!$colorOk || !$animalOk);

        if ($needPadre) {
            // Si no se ha proporcionado 'padre', indicamos al front que la solicite
            if ($padreInput === '') {
                $incorrect = [];
                $msg = '';
                if (!$colorOk && !$animalOk) {
                    $incorrect = ['color', 'animal'];
                    $msg = 'Ambas respuestas son incorrectas. Para seguir adelante debes responder la tercera pregunta.';
                } elseif (!$colorOk) {
                    $incorrect = ['color'];
                    $msg = 'El color favorito es incorrecto. Para seguir adelante debes responder la tercera pregunta.';
                } elseif (!$animalOk) {
                    $incorrect = ['animal'];
                    $msg = 'El animal favorito es incorrecto. Para seguir adelante debes responder la tercera pregunta.';
                }
                return response()->json([
                    'success' => false,
                    'require_padre' => true,
                    'incorrect' => $incorrect,
                    'message' => $msg,
                ]);
            }

            // Validar la tercera respuesta (padre)
            $padreOk = Hash::check($padreInput, $user->security_padre_answer);
            if (!$padreOk && $request->padre) {
                $padreOk = Hash::check(trim($request->padre), $user->security_padre_answer);
            }
            if ($padreOk) {
                return response()->json([
                    'success' => true,
                    'message' => 'Respuestas correctas. Puedes continuar.'
                ]);
            }

            // Falla completa: reportar qué preguntas fallaron
            $incorrect = [];
            if (!$colorOk) $incorrect[] = 'color';
            if (!$animalOk) $incorrect[] = 'animal';
            $incorrect[] = 'padre';
            return response()->json([
                'success' => false,
                'incorrect' => $incorrect,
                'message' => 'Respuestas incorrectas. Intenta nuevamente o contacta a soporte si no recuerdas tus respuestas.',
            ]);
        }

        // Caso residual (no debería ocurrir):
        return response()->json([
            'success' => false,
            'message' => 'Verificación fallida. Intenta nuevamente.'
        ]);
    }

    // Cambia la contraseña del usuario
    public function changePassword(Request $request)
    {
        $request->merge([
            'password' => self::sanitizePasswordInput($request->input('password')),
        ]);

        $request->validate([
            'flow_token' => 'required|string|min:20',
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
        ], [
            'flow_token.required' => 'El token de recuperación es obligatorio.',
            'flow_token.min' => 'El token de recuperación no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto válido.',
            'password.min' => 'La contraseña debe tener al menos 16 caracteres.',
            'password.max' => 'La contraseña no puede superar 30 caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una mayúscula, una minúscula, un número y un símbolo, sin espacios.',
        ]);

        $flowData = Cache::get($this->recoverFlowCacheKey($request->flow_token));
        if (!is_array($flowData) || empty($flowData['valid']) || empty($flowData['user_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cambiar la contraseña. El proceso de recuperación expiró o es inválido.'
            ]);
        }

        $user = User::find((int) $flowData['user_id']);
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            Cache::forget($this->recoverFlowCacheKey($request->flow_token));
            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada correctamente. Ya puedes iniciar sesión.'
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'No se pudo cambiar la contraseña. Usuario no encontrado.'
        ]);
    }

    private function recoverFlowCacheKey(string $token): string
    {
        return 'recover_flow:' . $token;
    }

    private static function emptyToNull(?string $v): ?string
    {
        if ($v === null) return null;
        $trimmed = trim($v);
        return $trimmed === '' ? null : $trimmed;
    }

    private static function sanitizeTextInput(?string $v): string
    {
        if ($v === null) return '';
        return preg_replace('/\s+/u', ' ', trim($v));
    }

    private static function sanitizePasswordInput(?string $v): string
    {
        if ($v === null) return '';
        return trim($v);
    }

    private static function normalizeSecurityAnswer(?string $v): string
    {
        if ($v === null) return '';
        $v = trim(mb_strtolower($v));
        $v = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return $v;
    }

    private static function isSuspiciousText(?string $v): bool
    {
        $value = self::sanitizeTextInput($v);
        if ($value === '') return true;

        $compact = preg_replace('/\s+/u', '', $value);
        if (preg_match('/(.)\1{3,}/u', $compact)) return true;
        if (!str_contains($value, ' ') && mb_strlen($compact) > 12) return true;
        return false;
    }

    private static function isSuspiciousEmail(?string $email): bool
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '' || !str_contains($email, '@')) return true;

        [$localPart, $domain] = explode('@', $email, 2);
        $typoDomains = ['gmai.com', 'gmial.com', 'gmal.com', 'hotnail.com', 'yaho.com'];

        if (in_array($domain, $typoDomains, true)) return true;
        if ($localPart === '' || preg_match('/^\d+$/', $localPart)) return true;
        if (preg_match('/(.)\1{4,}/', $localPart)) return true;
        if (mb_strlen($localPart) > 18 && !preg_match('/[._-]/', $localPart)) return true;
        return false;
    }

    private static function isSuspiciousPassword(?string $password): bool
    {
        $password = self::sanitizePasswordInput($password);
        if ($password === '') return true;
        if (preg_match('/^\d+$/', $password)) return true;
        if (preg_match('/(.)\1{4,}/u', $password)) return true;
        if (count(array_unique(str_split($password))) < 4) return true;
        return false;
    }
}

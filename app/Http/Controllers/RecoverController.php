<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RecoverController extends Controller
{
    private const RECOVER_FLOW_TTL_MINUTES = 15;
    private const EMAIL_TOKEN_TTL_MINUTES = 2;
    private const EMAIL_TOKEN_MAX_ATTEMPTS = 3;
    private const EMAIL_TOKEN_RESEND_COOLDOWN_SECONDS = 45;

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
                'security_verified' => false,
                'email_token_verified' => false,
                'email_token_hash' => null,
                'email_token_expires_at' => null,
                'email_token_attempts' => 0,
                'email_token_sent_at' => null,
            ],
            now()->addMinutes(self::RECOVER_FLOW_TTL_MINUTES)
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
            ],
            'animal' => [
                'nullable',
                'string',
                'min:2',
                'max:40',
            ],
            'padre' => [
                'nullable',
                'string',
                'min:2',
                'max:40',
            ],
        ], [
            'flow_token.required' => 'El token de recuperación es obligatorio.',
            'flow_token.min' => 'El token de recuperación no es válido.',
            'color.min' => 'La respuesta de color favorito debe tener al menos 2 caracteres.',
            'color.max' => 'La respuesta de color favorito no puede superar 40 caracteres.',
            'animal.min' => 'La respuesta de animal favorito debe tener al menos 2 caracteres.',
            'animal.max' => 'La respuesta de animal favorito no puede superar 40 caracteres.',
            'padre.min' => 'La respuesta de nombre del padre debe tener al menos 2 caracteres.',
            'padre.max' => 'La respuesta de nombre del padre no puede superar 40 caracteres.',
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
            return $this->issueEmailTokenChallenge($request->flow_token, $flowData, $user);
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
                return $this->issueEmailTokenChallenge($request->flow_token, $flowData, $user);
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

    public function verifyEmailToken(Request $request)
    {
        $request->merge([
            'email_token' => preg_replace('/\D+/', '', (string) $request->input('email_token')),
        ]);

        $request->validate([
            'flow_token' => 'required|string|min:20',
            'email_token' => 'required|string|digits:6',
        ], [
            'flow_token.required' => 'El token de recuperación es obligatorio.',
            'flow_token.min' => 'El token de recuperación no es válido.',
            'email_token.required' => 'El código de verificación es obligatorio.',
            'email_token.digits' => 'El código de verificación debe contener 6 dígitos.',
        ]);

        $cacheKey = $this->recoverFlowCacheKey($request->flow_token);
        $flowData = Cache::get($cacheKey);

        if (!is_array($flowData) || empty($flowData['valid']) || empty($flowData['user_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'El proceso de recuperación expiró o es inválido. Inicia nuevamente.',
            ], 422);
        }

        if (empty($flowData['security_verified'])) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debes completar la verificación de preguntas de seguridad.',
            ], 422);
        }

        if (!empty($flowData['email_token_verified'])) {
            return response()->json([
                'success' => true,
                'message' => 'Código ya validado. Puedes cambiar tu contraseña.',
            ]);
        }

        if (empty($flowData['email_token_hash']) || empty($flowData['email_token_expires_at'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un código activo. Solicita un nuevo código.',
            ], 422);
        }

        $attempts = (int) ($flowData['email_token_attempts'] ?? 0);
        if ($attempts >= self::EMAIL_TOKEN_MAX_ATTEMPTS) {
            return response()->json([
                'success' => false,
                'message' => 'Has superado el número máximo de intentos. Solicita un nuevo código.',
            ], 429);
        }

        $expiresAt = strtotime((string) $flowData['email_token_expires_at']);
        if (!$expiresAt || $expiresAt < time()) {
            return response()->json([
                'success' => false,
                'message' => 'El código ha expirado. Solicita uno nuevo.',
            ], 422);
        }

        if (!Hash::check($request->email_token, (string) $flowData['email_token_hash'])) {
            $flowData['email_token_attempts'] = $attempts + 1;
            Cache::put($cacheKey, $flowData, now()->addMinutes(self::RECOVER_FLOW_TTL_MINUTES));

            $remaining = max(0, self::EMAIL_TOKEN_MAX_ATTEMPTS - (int) $flowData['email_token_attempts']);
            return response()->json([
                'success' => false,
                'message' => $remaining > 0
                    ? "Código incorrecto. Intentos restantes: {$remaining}."
                    : 'Código incorrecto. Has agotado los intentos; solicita un nuevo código.',
            ], 422);
        }

        $flowData['email_token_verified'] = true;
        $flowData['email_token_hash'] = null;
        $flowData['email_token_attempts'] = 0;
        $flowData['email_token_expires_at'] = null;
        Cache::put($cacheKey, $flowData, now()->addMinutes(self::RECOVER_FLOW_TTL_MINUTES));

        return response()->json([
            'success' => true,
            'message' => 'Código validado correctamente. Ahora puedes cambiar tu contraseña.',
        ]);
    }

    public function resendEmailToken(Request $request)
    {
        $request->validate([
            'flow_token' => 'required|string|min:20',
        ], [
            'flow_token.required' => 'El token de recuperación es obligatorio.',
            'flow_token.min' => 'El token de recuperación no es válido.',
        ]);

        $cacheKey = $this->recoverFlowCacheKey($request->flow_token);
        $flowData = Cache::get($cacheKey);

        if (!is_array($flowData) || empty($flowData['valid']) || empty($flowData['user_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'El proceso de recuperación expiró o es inválido. Inicia nuevamente.',
            ], 422);
        }

        if (empty($flowData['security_verified'])) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debes completar la verificación de preguntas de seguridad.',
            ], 422);
        }

        $sentAt = strtotime((string) ($flowData['email_token_sent_at'] ?? ''));
        if ($sentAt && (time() - $sentAt) < self::EMAIL_TOKEN_RESEND_COOLDOWN_SECONDS) {
            $wait = self::EMAIL_TOKEN_RESEND_COOLDOWN_SECONDS - (time() - $sentAt);
            return response()->json([
                'success' => false,
                'message' => "Espera {$wait} segundos para solicitar otro código.",
            ], 429);
        }

        $user = User::find((int) $flowData['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado para este proceso de recuperación.',
            ], 422);
        }

        return $this->issueEmailTokenChallenge($request->flow_token, $flowData, $user, true);
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
            ], 422);
        }

        if (empty($flowData['security_verified']) || empty($flowData['email_token_verified'])) {
            return response()->json([
                'success' => false,
                'message' => 'Debes completar la validación del código enviado a tu correo antes de cambiar la contraseña.',
            ], 422);
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
        ], 422);
    }

    private function issueEmailTokenChallenge(string $flowToken, array $flowData, User $user, bool $isResend = false)
    {
        $emailToken = (string) random_int(100000, 999999);

        try {
            $this->sendRecoverCodeByEmail($user, $emailToken);
        } catch (\Throwable $e) {
            Log::error('recover.email_token.send_failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el código de verificación al correo. Intenta nuevamente en unos minutos.',
            ], 500);
        }

        $flowData['security_verified'] = true;
        $flowData['email_token_verified'] = false;
        $flowData['email_token_hash'] = Hash::make($emailToken);
        $flowData['email_token_expires_at'] = now()->addMinutes(self::EMAIL_TOKEN_TTL_MINUTES)->toIso8601String();
        $flowData['email_token_attempts'] = 0;
        $flowData['email_token_sent_at'] = now()->toIso8601String();

        Cache::put(
            $this->recoverFlowCacheKey($flowToken),
            $flowData,
            now()->addMinutes(self::RECOVER_FLOW_TTL_MINUTES)
        );

        return response()->json([
            'success' => true,
            'require_email_token' => true,
            'message' => $isResend
                ? 'Se envió un nuevo código de verificación a tu correo.'
                : 'Respuestas correctas. Te enviamos un código de verificación a tu correo.',
            'email_hint' => $this->maskEmail($user->email),
            'token_expires_in_seconds' => self::EMAIL_TOKEN_TTL_MINUTES * 60,
        ]);
    }

    private function sendRecoverCodeByEmail(User $user, string $code): void
    {
        $data = [
            'userName' => $user->name,
            'code' => $code,
            'minutes' => self::EMAIL_TOKEN_TTL_MINUTES,
            'logoUrl' => url('/logouptag.png'),
            'appName' => (string) config('app.name', 'Sistema de Inventario Médico'),
        ];

        Mail::send('emails.recover-token', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Código de verificación para recuperar contraseña');
        });
    }

    private function maskEmail(string $email): string
    {
        $email = trim(mb_strtolower($email));
        if (!str_contains($email, '@')) {
            return 'correo oculto';
        }

        [$local, $domain] = explode('@', $email, 2);
        $localVisible = mb_substr($local, 0, 2);
        $maskedLocal = $localVisible . str_repeat('*', max(2, mb_strlen($local) - 2));

        return $maskedLocal . '@' . $domain;
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
        return trim($v);
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

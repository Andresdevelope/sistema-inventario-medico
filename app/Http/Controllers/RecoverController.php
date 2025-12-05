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
        $request->validate([
            'user_id' => 'required|integer',
            // En el primer intento color/animal son requeridos; para el segundo,
            // el front reenvía los mismos valores junto con 'padre'.
            'color' => 'nullable|string',
            'animal' => 'nullable|string',
            'padre' => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Usuario no encontrado']);
        }

        // Helper de normalización para comparar cadenas de manera robusta
        $normalize = function (?string $v): string {
            if ($v === null) return '';
            $v = trim(mb_strtolower($v));
            // quitar diacríticos básicos
            $v = str_replace(['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $v);
            // colapsar espacios múltiples
            $v = preg_replace('/\s+/', ' ', $v);
            return $v;
        };

        $colorInput = $normalize($request->color);
        $animalInput = $normalize($request->animal);
        $padreInput = $normalize($request->padre);

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
            return response()->json(['success' => true]);
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
                return response()->json(['success' => true]);
            }

            // Falla completa: reportar qué preguntas fallaron
            $incorrect = [];
            if (!$colorOk) $incorrect[] = 'color';
            if (!$animalOk) $incorrect[] = 'animal';
            $incorrect[] = 'padre';
            return response()->json([
                'success' => false,
                'incorrect' => $incorrect,
                'message' => 'Respuestas incorrectas',
            ]);
        }

        // Caso residual (no debería ocurrir):
        return response()->json(['success' => false, 'message' => 'Verificación fallida']);
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

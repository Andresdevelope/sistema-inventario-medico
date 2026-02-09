<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificacionesController extends Controller
{
    private const PANEL_LIMIT = 15;

    /**
     * Retorna JSON con la cantidad de movimientos no leídos y, si el panel se abre,
     * adjunta tarjetas ya resumidas para que el frontend no tenga que interpretar JSON crudos.
     */
    public function movimientos(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $isPanel = $request->boolean('panel');
        $lastSeenId = $this->resolveLastSeenId($user);
        $latestId = $this->getLatestMovimientoId();
        $unreadCount = $this->countUnreadForUser($user->id, $lastSeenId, $latestId);

        $items = $this->buildMovimientoCards($lastSeenId, $isPanel);

        $this->logIfNeeded($user, $unreadCount, $isPanel, $items);

        return response()->json([
            'unread' => $unreadCount,
            'last_seen' => $lastSeenId,
            'latest_id' => $latestId,
            'items' => $items,
        ]);
    }

    /**
     * Marca todos los movimientos como leídos avanzando el puntero "last seen" del usuario.
     * Evitamos inserciones masivas en la tabla pivote y sólo actualizamos un contador persistente.
     */
    public function leerMovimientos(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $latestId = $this->getLatestMovimientoId();
        $previous = (int) ($user->notif_mov_last_seen_id ?? 0);

        $this->persistLastSeen($user, $latestId);
        cache()->put($this->cacheKeyLastSeen($user->id), $latestId, 3600);

        try {
            Bitacora::create([
                'user_id' => $user->id,
                'accion' => 'notificaciones.movimientos.leer',
                'detalles' => json_encode([
                    'from' => $previous,
                    'to' => $latestId,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'fecha_hora' => now(),
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'ok',
            'last_seen' => $latestId,
        ]);
    }

    /**
     * Obtiene el último movimiento leído por el usuario, priorizando caché y persistiendo
     * el valor cuando venimos del esquema anterior basado en pivote por movimiento.
     */
    protected function resolveLastSeenId(User $user): int
    {
        $cacheKey = $this->cacheKeyLastSeen($user->id);
        $cached = cache()->get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        $value = (int) ($user->notif_mov_last_seen_id ?? 0);
        if ($value === 0) {
            $legacy = DB::table('movimiento_user_reads')
                ->where('user_id', $user->id)
                ->max('movimiento_id');
            if ($legacy) {
                $value = (int) $legacy;
                $this->persistLastSeen($user, $value);
            }
        }

        cache()->put($cacheKey, $value, 3600);
        return $value;
    }

    /**
     * Cuenta los movimientos pendientes comparando contra el último ID leído.
     */
    protected function countUnreadForUser(int $userId, int $lastSeenId, int $latestId): int
    {
        if ($latestId <= $lastSeenId) {
            return 0;
        }

        $cacheKey = $this->cacheKeyUnread($userId, $lastSeenId, $latestId);
        return cache()->remember($cacheKey, 15, function () use ($lastSeenId) {
            return Movimiento::where('id', '>', $lastSeenId)->count();
        });
    }

    /**
     * Obtiene el ID más reciente de movimientos, cacheado para evitar golpear la BD en cada polling.
     */
    protected function getLatestMovimientoId(): int
    {
        return cache()->remember('notif_mov_latest_id', 5, function () {
            return (int) (Movimiento::max('id') ?? 0);
        });
    }

    /**
     * Construye tarjetas humanas (headline + chips) para los últimos movimientos.
     */
    protected function buildMovimientoCards(int $lastSeenId, bool $isPanel): array
    {
        $limit = $isPanel ? self::PANEL_LIMIT : 5;
        return Movimiento::with([
                'producto:id,nombre,tipo_producto',
                'destino:id,nombre,codigo',
                'usuario:id,name',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (Movimiento $mov) use ($lastSeenId) {
                return $this->mapMovimientoToPayload($mov, $lastSeenId);
            })
            ->all();
    }

    /**
     * Simplifica la estructura de un movimiento en algo legible para el panel.
     */
    protected function mapMovimientoToPayload(Movimiento $mov, int $lastSeenId): array
    {
        $tipoLabel = $this->humanizeTipo($mov);
        $producto = $mov->producto?->nombre ?? 'Producto sin nombre';
        $unidad = $this->unidadOperativa($mov);
        $cantidad = number_format((int) $mov->cantidad, 0, ',', '.') . ' ' . $unidad;

        $chips = $this->buildChips($mov);
        $subtext = $this->buildSubtext($mov);

        $fechaMostrar = optional($mov->fecha)->format('d/m/Y') ?? optional($mov->created_at)->format('d/m/Y');

        return [
            'id' => $mov->id,
            'headline' => $tipoLabel . ' · ' . $producto,
            'resumen' => $cantidad,
            'subtext' => $subtext,
            'fecha' => $fechaMostrar,
            'leido' => $mov->id <= $lastSeenId,
            'chips' => $chips,
            'actor' => $mov->usuario?->name,
            // Campos legacy para compatibilidad con UI anterior
            'tipo' => $mov->tipo,
            'modalidad' => $mov->modalidad,
            'producto' => $producto,
            'cantidad' => (int) $mov->cantidad,
            'motivo' => $mov->motivo,
            'fecha_iso' => optional($mov->fecha)->format('Y-m-d') ?? optional($mov->created_at)->format('Y-m-d'),
        ];
    }

    protected function buildChips(Movimiento $mov): array
    {
        $chips = [];
        if ($mov->modalidad === 'distribucion') {
            $chips[] = 'Distribución';
        } elseif ($mov->modalidad === 'consumo') {
            $chips[] = 'Consumo';
        }

        if (in_array($mov->tipo, ['ajuste_pos', 'ajuste_neg'])) {
            $chips[] = 'Ajuste';
        }

        if ($mov->tipo === 'ingreso' && $this->esMedicamento($mov)) {
            $chips[] = 'Blíster';
        }

        if ($mov->destino && $mov->modalidad === 'distribucion') {
            $chips[] = $mov->destino->codigo ?? Str::limit($mov->destino->nombre, 10);
        }

        return array_values(array_unique(array_filter($chips)));
    }

    protected function buildSubtext(Movimiento $mov): string
    {
        $parts = [];
        if ($mov->modalidad === 'distribucion' && $mov->destino) {
            $parts[] = 'Destino: ' . $mov->destino->nombre;
        }
        if ($mov->modalidad === 'consumo' && $mov->salida) {
            $parts[] = 'Servicio: ' . $mov->salida;
        }
        if ($mov->tipo === 'ingreso' && $mov->entrada) {
            $parts[] = 'Origen: ' . $mov->entrada;
        }
        if ($mov->motivo) {
            $parts[] = 'Motivo: ' . $mov->motivo;
        }
        if ($mov->usuario) {
            $parts[] = 'Por: ' . $mov->usuario->name;
        }
        return implode(' · ', array_filter($parts));
    }

    protected function humanizeTipo(Movimiento $mov): string
    {
        return match ($mov->tipo) {
            'ingreso' => $this->esMedicamento($mov) ? 'Entrada por blíster' : 'Entrada',
            'egreso' => match ($mov->modalidad) {
                'distribucion' => 'Distribución',
                'consumo' => 'Consumo',
                default => 'Salida',
            },
            'ajuste_pos' => 'Ajuste positivo',
            'ajuste_neg' => 'Ajuste negativo',
            default => 'Movimiento',
        };
    }

    protected function unidadOperativa(Movimiento $mov): string
    {
        return $this->esMedicamento($mov) ? 'blísteres' : 'unidades';
    }

    protected function esMedicamento(Movimiento $mov): bool
    {
        return strtolower($mov->producto->tipo_producto ?? 'medicamento') === 'medicamento';
    }

    /**
     * Registra en bitácora sólo cuando el conteo cambia o cuando el usuario abre el panel,
     * evitando spam de logs.
     */
    protected function logIfNeeded(User $user, int $unreadCount, bool $openedPanel, array $items): void
    {
        try {
            $cacheKey = 'notif_mov_last_logged_' . $user->id;
            $previous = cache()->get($cacheKey);
            $shouldLog = $openedPanel || $previous === null || (int) $previous !== (int) $unreadCount;
            if (!$shouldLog) {
                return;
            }

            Bitacora::create([
                'user_id' => $user->id,
                'accion' => 'notificaciones.movimientos',
                'detalles' => json_encode([
                    'unread' => $unreadCount,
                    'panel' => $openedPanel,
                    'sample_ids' => array_slice(array_column($items, 'id'), 0, 5),
                    'chips' => collect($items)->pluck('chips')->flatten()->unique()->values(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'fecha_hora' => now(),
            ]);

            cache()->put($cacheKey, $unreadCount, 3600);
        } catch (\Throwable $e) {}
    }

    protected function cacheKeyUnread(int $userId, int $lastSeenId, int $latestId): string
    {
        return 'notif_mov_unread_' . $userId . '_' . $lastSeenId . '_' . $latestId;
    }

    protected function cacheKeyLastSeen(int $userId): string
    {
        return 'notif_mov_last_seen_' . $userId;
    }

    /**
     * Intenta persistir el puntero last_seen tanto si el guard usa modelos Eloquent
     * como si responde con GenericUser/Authenticatable sin método save().
     */
    protected function persistLastSeen($user, int $value): void
    {
        if ($user instanceof User) {
            $user->forceFill(['notif_mov_last_seen_id' => $value])->save();
            return;
        }

        if (method_exists($user, 'getAuthIdentifier')) {
            $id = $user->getAuthIdentifier();
            DB::table('users')->where('id', $id)->update([
                'notif_mov_last_seen_id' => $value,
                'updated_at' => now(),
            ]);
        }
    }
}

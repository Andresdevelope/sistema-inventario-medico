<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificacionesController extends Controller
{
    // Retorna JSON de movimientos recientes y contador no leídos
    public function movimientos(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $isPanel = $request->boolean('panel');
        // Movimientos recientes (últimos 15) y marcar si están leídos
        $movimientos = Movimiento::with(['producto'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(function($m) use ($user) {
                $leido = $m->lecturas()->where('user_id', $user->id)->whereNotNull('read_at')->exists();
                return [
                    'id' => $m->id,
                    'producto' => $m->producto?->nombre ?? 'N/A',
                    'tipo' => $m->tipo,
                    'cantidad' => $m->cantidad,
                    'motivo' => $m->motivo,
                    'fecha' => $m->fecha?->format('Y-m-d'),
                    'leido' => $leido,
                ];
            });
        // Contador no leídos (movimientos sin read_at para este usuario)
        $unreadCount = Movimiento::whereDoesntHave('lecturas', function($q) use ($user) {
                $q->where('user_id', $user->id)->whereNotNull('read_at');
            })
            ->count();
        // Bitácora: solo si cambia unread o si es apertura del panel
        try {
            $cacheKey = 'notif_last_unread_user_'.$user->id;
            $lastUnread = cache()->get($cacheKey);
            $shouldLog = $isPanel || ($lastUnread === null || (int)$lastUnread !== (int)$unreadCount);
            if ($shouldLog) {
                Bitacora::create([
                    'user_id'=>$user->id,
                    'accion'=>'notificaciones.movimientos',
                    'detalles'=>json_encode([
                        'unread'=>$unreadCount,
                        'panel'=>$isPanel,
                        'changed_from'=>$lastUnread,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora'=>now()
                ]);
                cache()->put($cacheKey, (int)$unreadCount, 3600);
            }
        } catch (\Throwable $e) {}
        return response()->json(['unread'=>$unreadCount,'items'=>$movimientos]);
    }

    // Marcar todas las notificaciones como leídas
    public function leerMovimientos(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        DB::beginTransaction();
        try {
            $ids = Movimiento::pluck('id');
            foreach ($ids as $id) {
                // Crear o actualizar pivot con read_at
                $existing = DB::table('movimiento_user_reads')
                    ->where('movimiento_id', $id)
                    ->where('user_id', $user->id)
                    ->first();
                if ($existing) {
                    DB::table('movimiento_user_reads')
                        ->where('id', $existing->id)
                        ->update(['read_at'=>now(), 'updated_at'=>now()]);
                } else {
                    DB::table('movimiento_user_reads')->insert([
                        'movimiento_id' => $id,
                        'user_id' => $user->id,
                        'read_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::commit();
            try { Bitacora::create(['user_id'=>$user->id,'accion'=>'notificaciones.movimientos.leer','detalles'=>null,'fecha_hora'=>now()]); } catch (\Throwable $e) {}
            return response()->json(['status'=>'ok']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error'=>'Error al marcar leídas'], 500);
        }
    }
}

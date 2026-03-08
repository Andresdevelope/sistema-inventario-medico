<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // Helper para registrar eventos en bitácora
    protected function logBitacora(string $accion, $detalles = null): void
    {
        try {
            $userId = \Illuminate\Support\Facades\Auth::id();
            \App\Models\Bitacora::create([
                'user_id' => $userId,
                'accion' => $accion,
                'detalles' => is_string($detalles) ? $detalles : json_encode($detalles, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'fecha_hora' => now(),
            ]);
        } catch (\Throwable $e) {
            // Evitar que un error en bitácora rompa el flujo
            Log::warning('No se pudo registrar bitácora: '.$e->getMessage());
        }
    }
}

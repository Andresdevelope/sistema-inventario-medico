<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bitacora;

class ReportesController extends Controller
{
    public function index(Request $request)
    {
        // Bitácora: ingreso a módulo reportes
        try {
            if (Auth::check()) {
                Bitacora::create([
                    'user_id' => Auth::id(),
                    'accion' => 'reportes.index',
                    'detalles' => json_encode([
                        'filtros' => $request->query(),
                        'ip' => $request->ip(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora' => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        return view('reportes.index');
    }
}

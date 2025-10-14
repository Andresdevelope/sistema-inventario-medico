<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $q = Bitacora::with('user')
            ->when($request->filled('user'), fn($qb)=>$qb->where('user_id', $request->input('user')))
            ->when($request->filled('accion'), fn($qb)=>$qb->where('accion', 'like', '%'.$request->input('accion').'%'))
            ->when($request->filled('desde'), fn($qb)=>$qb->where('fecha_hora', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn($qb)=>$qb->where('fecha_hora', '<=', $request->input('hasta')))
            ->orderBy('fecha_hora', 'desc');

        $bitacora = $q->paginate(25)->appends($request->query());
        $usuarios = \App\Models\User::select('id','name')->orderBy('name')->get();
        return view('bitacora.index', compact('bitacora','usuarios'));
    }
}

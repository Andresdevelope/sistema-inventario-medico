<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'tipo', // ingreso, egreso, ajuste_pos, ajuste_neg
        'salida', // area o punto de salida (general, odontologia, etc)
        'destino_id', // FK destinos (normalizado)
        'inventario_id',
        'entrada', // origen de entrada (compra, donacion, etc)
        'cantidad',
        'motivo',
        'fecha',
        'usuario_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    // Lecturas de notificación por usuario (pivot)
    public function lecturas()
    {
        return $this->belongsToMany(User::class, 'movimiento_user_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    // Scope para movimientos no leídos por un usuario
    public function scopeNoLeidosPor($query, $userId)
    {
        return $query->whereDoesntHave('lecturas', function($q) use ($userId) {
            $q->where('user_id', $userId)->whereNotNull('read_at');
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'lote',
        'cantidad',
        'fecha_vencimiento',
        'stock_minimo',
        'estado',
    ];

    /**
     * Relación: Un inventario pertenece a un producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}

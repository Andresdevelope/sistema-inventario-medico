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

    /**
     * Protege atributos críticos una vez creado el registro.
     * Evita cambiar 'lote' y 'fecha_vencimiento' para preservar trazabilidad.
     */
    protected static function booted()
    {
        static::updating(function (Inventario $model) {
            if ($model->isDirty('lote') || $model->isDirty('fecha_vencimiento')) {
                throw new \InvalidArgumentException('No se puede modificar el lote o la fecha de vencimiento de un inventario existente. Cree un nuevo lote si es necesario.');
            }
        });
    }
}

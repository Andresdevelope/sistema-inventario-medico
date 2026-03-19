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
        'um_operativa',
        'contenido_por_blister',
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
            // Siempre prohibimos cambiar lote y fecha de vencimiento una vez creado el registro
            if ($model->isDirty('lote') || $model->isDirty('fecha_vencimiento')) {
                throw new \InvalidArgumentException('No se puede modificar lote o fecha de vencimiento de un inventario existente. Cree un nuevo lote si es necesario.');
            }

            // Para unidad operativa y contenido por blíster permitimos sólo la PRIMERA definición
            // (ej.: registros antiguos que tenían null). Si ya tenían valor y se intenta cambiar, se bloquea.
            $originalUm = $model->getOriginal('um_operativa');
            if ($model->isDirty('um_operativa') && !is_null($originalUm) && $originalUm !== $model->um_operativa) {
                throw new \InvalidArgumentException('No se puede modificar la unidad operativa de un inventario existente. Cree un nuevo lote si es necesario.');
            }

            $originalContenido = $model->getOriginal('contenido_por_blister');
            if ($model->isDirty('contenido_por_blister') && !is_null($originalContenido) && (int)$originalContenido !== (int)$model->contenido_por_blister) {
                throw new \InvalidArgumentException('No se puede modificar el contenido por blíster de un inventario existente. Cree un nuevo lote si es necesario.');
            }
        });
    }
}

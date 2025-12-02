<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Producto extends Model
{
    use HasFactory;

    // Eliminado: No se autogenera el código, debe ser ingresado manualmente por el usuario.

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'categoria_id',
        'subcategoria_id',
        'presentacion',
        'unidad_medida',
        'categoria_inventario',
        'stock',
        'stock_minimo',
        'proveedor_id',
        'fecha_ingreso',
        'fecha_vencimiento',
        'created_by',
        'updated_by',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    /**
     * Accesor para obtener el stock total sumando todos los inventarios asociados
     */
    public function getStockTotalAttribute()
    {
        return $this->inventarios()->sum('cantidad');
    }
    
    /**
     * Genera un código único sugerido basado en el nombre del producto.
     * Mantiene mayúsculas, reemplaza espacios por guiones, limita a 20 caracteres
     * y agrega sufijo incremental si ya existe.
     */
    public static function generateUniqueCodigo(string $nombre): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', trim($nombre)));
        $base = trim(preg_replace('/-+/', '-', $base), '-');
        $base = substr($base, 0, 20);
        if ($base === '') { $base = 'PROD'; }
        $codigo = $base;
        $i = 1;
        while (self::where('codigo', $codigo)->exists()) {
            $codigo = $base . '-' . $i;
            $i++;
        }
        return $codigo;
    }
    // Eliminado: No se genera código automáticamente, el usuario debe ingresar el código real del medicamento.
}

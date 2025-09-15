<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Producto extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($producto) {
            if (empty($producto->codigo)) {
                $producto->codigo = self::generateUniqueCodigo($producto->nombre ?? 'PRD');
            }
        });
    }

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'categoria_id',
        'subcategoria_id',
        'presentacion',
        'unidad_medida',
        'stock',
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
    /**
     * Genera un código único basado en el nombre: BASE-0001
     */
    public static function generateUniqueCodigo(string $nombre): string
    {
        $base = Str::ascii(Str::upper(Str::slug($nombre, '')));
        $base = preg_replace('/[^A-Z0-9]/', '', $base);
        $base = substr($base, 0, 6) ?: 'PRD';

        $attempt = 1;
        do {
            $suffix = str_pad((string)$attempt, 4, '0', STR_PAD_LEFT);
            $codigo = $base . '-' . $suffix;
            if (!self::where('codigo', $codigo)->exists()) {
                return $codigo;
            }
            $attempt++;
        } while ($attempt <= 9999);

        // Fallback if agotamos secuencia
        return $base . '-' . time();
    }
}

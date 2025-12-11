<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;
    protected $fillable = ['nombre', 'categoria_id'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
        /**
         * Mutador para el atributo 'nombre'.
         * Transforma el valor para que siempre comience con mayúscula y el resto en minúsculas.
         * Ejemplo: "antibióticos" => "Antibióticos"
         *
         * @param string $value
         * @return void
         */
        public function setNombreAttribute($value)
        {
            $this->attributes['nombre'] = ucfirst(strtolower($value));
        }
}

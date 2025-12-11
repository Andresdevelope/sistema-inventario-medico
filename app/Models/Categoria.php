<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;
    protected $fillable = ['nombre'];

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class);
    }
        /**
         * Mutador para el atributo 'nombre'.
         * Transforma el valor para que siempre comience con mayúscula y el resto en minúsculas.
         * Ejemplo: "medicamentos" => "Medicamentos"
         *
         * @param string $value
         * @return void
         */
        public function setNombreAttribute($value)
        {   // Aplica la transformación al valor antes de asignarlo, asegurando el formato correcto.
            //this->attributes['nombre'] = ucfirst(strtolower($value)); que hace esto: 
            // Convierte la primera letra a mayúscula y el resto a minúsculas.
            $this->attributes['nombre'] = ucfirst(strtolower($value));
        }
}

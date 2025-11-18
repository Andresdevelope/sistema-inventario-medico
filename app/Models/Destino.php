<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo','nombre','activo','tipo'
    ];

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }
}

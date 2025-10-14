<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacora';
    public $timestamps = false; // usamos campo fecha_hora en migración

    protected $fillable = [
        'user_id',
        'accion',
        'detalles',
        'fecha_hora',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $primaryKey = 'cliente_id';
    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'cliente_frecuente',
        'fecha_registro',
    ];

    protected $casts = [
        'cliente_frecuente' => 'boolean',
        'fecha_registro' => 'date',
    ];

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
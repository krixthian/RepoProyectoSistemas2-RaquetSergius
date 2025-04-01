<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancha extends Model
{
    use HasFactory;

    protected $primaryKey = 'cancha_id';
    protected $fillable = [
        'nombre',
        'tipo',
        'disponible',
        'precio_hora',
        'capacidad',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'precio_hora' => 'decimal:2',
        'capacidad' => 'integer',
    ];

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
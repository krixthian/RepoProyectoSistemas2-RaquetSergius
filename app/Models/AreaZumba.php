<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaZumba extends Model
{
    use HasFactory;

    protected $primaryKey = 'area_id';
    protected $fillable = [
        'nombre',
        'capacidad',
        'disponible',
        'precio_clase',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'precio_clase' => 'decimal:2',
        'capacidad' => 'integer',
    ];

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
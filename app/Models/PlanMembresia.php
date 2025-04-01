<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMembresia extends Model
{
    use HasFactory;

    protected $primaryKey = 'plan_id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'duracion_dias',
        'descuento_reservas',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'duracion_dias' => 'integer',
        'descuento_reservas' => 'decimal:2',
    ];

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
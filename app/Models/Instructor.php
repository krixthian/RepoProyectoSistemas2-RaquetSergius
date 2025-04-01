<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $primaryKey = 'instructor_id';
    protected $fillable = [
        'nombre',
        'telefono',
        'especialidad',
        'tarifa_hora',
    ];

    protected $casts = [
        'tarifa_hora' => 'decimal:2',
    ];

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
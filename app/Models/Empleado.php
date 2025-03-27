<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $primaryKey = 'empleado_id';
    protected $fillable = [
        'nombre',
        'usuario',
        'contrasena',
        'rol',
        'telefono',
        'email',
        'activo',
    ];

    protected $hidden = [
        'contrasena',
        'remember_token', // Si estás utilizando autenticación de Laravel
    ];

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
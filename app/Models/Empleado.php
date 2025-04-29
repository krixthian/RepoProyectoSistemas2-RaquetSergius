<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Empleado extends Authenticatable
{
    use HasFactory;

    protected $table = 'empleados';
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
        'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->contrasena;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';
    protected $primaryKey = 'evento_id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'tipo',
        'precio_inscripcion',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'precio_inscripcion' => 'decimal:2',
    ];

    public function torneos() // Un evento puede tener múltiples torneos
    {
        return $this->hasMany(Torneo::class, 'evento_id', 'evento_id');
    }
}
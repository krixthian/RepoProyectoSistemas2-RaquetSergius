<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembresiaCliente extends Model
{
    use HasFactory;

    protected $primaryKey = 'membresia_id';
    protected $fillable = [
        'cliente_id',
        'plan_id',
        'fecha_inicio',
        'fecha_fin',
        'activa',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function plan()
    {
        return $this->belongsTo(PlanMembresia::class, 'plan_id', 'plan_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
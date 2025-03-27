<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    protected $primaryKey = 'equipo_id';
    protected $fillable = [
        'nombre',
        'torneo_id',
        'capitan_id',
    ];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'torneo_id', 'torneo_id');
    }

    public function capitan()
    {
        return $this->belongsTo(Cliente::class, 'capitan_id', 'cliente_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
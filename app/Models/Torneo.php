<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    use HasFactory;

    protected $primaryKey = 'torneo_id';
    protected $fillable = [
        'evento_id',
        'categoria',
        'num_equipos',
        'estado',
        'deporte',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id', 'evento_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
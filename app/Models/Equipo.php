<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    protected $table = 'equipos';
    protected $primaryKey = 'equipo_id';
    protected $fillable = [
        'nombre',
        'capitan_id',
        'torneo_id', // Es crucial que esté aquí
    ];

    public function capitan()
    {
        return $this->belongsTo(Cliente::class, 'capitan_id', 'cliente_id');
    }

    /**
     * Obtiene el torneo principal al que el equipo está directamente asignado.
     */
    public function torneoPrincipal()
    {
        // El primer argumento es el modelo relacionado (Torneo::class)
        // El segundo argumento es la clave foránea en la tabla 'equipos' (torneo_id)
        // El tercer argumento es la clave primaria en la tabla 'torneos' (torneo_id)
        return $this->belongsTo(Torneo::class, 'torneo_id', 'torneo_id');
    }

    /**
     * Obtiene todos los torneos en los que el equipo está inscrito
     * (a través de la tabla pivote torneo_equipo).
     */
    public function torneos()
    {
        return $this->belongsToMany(Torneo::class, 'torneo_equipo', 'equipo_id', 'torneo_id');
    }
}
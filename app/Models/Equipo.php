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
        'torneo_id', 
    ];

    public function capitan()
    {
        return $this->belongsTo(Cliente::class, 'capitan_id', 'cliente_id');
    }

    
    public function torneoPrincipal()
    {
    
        return $this->belongsTo(Torneo::class, 'torneo_id', 'torneo_id');
    }

    
    public function torneos()
    {
        return $this->belongsToMany(Torneo::class, 'torneo_equipo', 'equipo_id', 'torneo_id');
    }
}
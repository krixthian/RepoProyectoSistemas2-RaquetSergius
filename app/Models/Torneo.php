<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    use HasFactory;

    protected $table = 'torneos';
    protected $primaryKey = 'torneo_id';
    protected $fillable = [
        'evento_id',
        'categoria',
        'num_equipos',
        'estado',
        'deporte',
    ];
    protected $casts = [
        'evento_id' => 'integer',
        'num_equipos' => 'integer',
    ];
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id', 'evento_id');
    }

    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'torneo_equipo', 'torneo_id', 'equipo_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partido extends Model
{
    use HasFactory;

    protected $primaryKey = 'partido_id';
    protected $fillable = [
        'torneo_id',
        'equipo1_id',
        'equipo2_id',
        'cancha_id',
        'fecha_hora',
        'resultado',
        'estado',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'torneo_id', 'torneo_id');
    }

    public function equipo1()
    {
        return $this->belongsTo(Equipo::class, 'equipo1_id', 'equipo_id');
    }

    public function equipo2()
    {
        return $this->belongsTo(Equipo::class, 'equipo2_id', 'equipo_id');
    }

    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id', 'cancha_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionClase extends Model
{
    use HasFactory;

    protected $primaryKey = 'inscripcion_id';
    protected $fillable = [
        'clase_id',
        'cliente_id',
        'reserva_id',
        'fecha_inscripcion',
        'asistio',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'datetime',
        'asistio' => 'boolean',
    ];

    public function clase()
    {
        return $this->belongsTo(ClaseZumba::class, 'clase_id', 'clase_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id', 'reserva_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
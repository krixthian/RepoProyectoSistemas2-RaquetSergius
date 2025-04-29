<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntosLog extends Model
{
    use HasFactory;

    protected $table = 'puntos_log'; // Nombre de la tabla en la base de datos
    protected $primaryKey = 'log_id';
    public $timestamps = false; // Solo existe 'fecha' en la tabla SQL

    protected $fillable = [
        'cliente_id',
        'accion',
        'puntos_cambio',
        'puntos_antes',
        'puntos_despues',
        'reserva_id',
        'inscripcion_clase_id',
        'encuesta_id',
        'canje_premio_id',
        'detalle',
        'fecha', // Incluir si se maneja manualmente
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'puntos_cambio' => 'integer',
        'puntos_antes' => 'integer',
        'puntos_despues' => 'integer',
        'reserva_id' => 'integer',
        'inscripcion_clase_id' => 'integer',
        'encuesta_id' => 'integer',
        'canje_premio_id' => 'integer',
        'fecha' => 'datetime',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id', 'reserva_id');
    }

    public function inscripcionClase()
    {
        return $this->belongsTo(InscripcionClase::class, 'inscripcion_clase_id', 'inscripcion_id');
    }

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id', 'encuesta_id');
    }

    public function canjePremio()
    {
        return $this->belongsTo(CanjePremio::class, 'canje_premio_id', 'canje_id');
    }
}
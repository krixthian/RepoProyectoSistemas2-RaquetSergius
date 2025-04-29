<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanjePremio extends Model
{
    use HasFactory;

    protected $table = 'canjes_premios'; // Nombre de la tabla en la base de datos
    protected $primaryKey = 'canje_id';
    public $timestamps = false; // Deshabilitado por definición explícita en migración

    protected $fillable = [
        'cliente_id',
        'premio_id',
        'puntos_utilizados',
        'fecha_canje',
        'estado',
        'reserva_id',
        'inscripcion_clase_id',
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'premio_id' => 'integer',
        'puntos_utilizados' => 'integer',
        'fecha_canje' => 'datetime',
        'reserva_id' => 'integer',
        'inscripcion_clase_id' => 'integer',
        // 'created_at' => 'datetime', // Si $timestamps = true
        // 'updated_at' => 'datetime', // Si $timestamps = true
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function premio()
    {
        return $this->belongsTo(Premio::class, 'premio_id', 'premio_id');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id', 'reserva_id');
    }

    public function inscripcionClase()
    {
        return $this->belongsTo(InscripcionClase::class, 'inscripcion_clase_id', 'inscripcion_id');
    }

    public function puntosLog() // Un canje puede tener un log asociado
    {
        return $this->hasOne(PuntosLog::class, 'canje_premio_id', 'canje_id');
    }
}
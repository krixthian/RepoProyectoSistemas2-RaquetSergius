<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    use HasFactory;

    protected $table = 'encuestas';
    protected $primaryKey = 'encuesta_id';
    public $timestamps = false; // Deshabilitado porque la migración los define explícitamente

    protected $fillable = [
        'cliente_id',
        'reserva_id',
        'inscripcion_clase_id',
        'puntuacion_general',
        'comentario_general',
        'puntuacion_limpieza',
        'puntuacion_instructor',
        'fecha_envio_invitacion',
        'fecha_completada',
        // No incluir created_at/updated_at si $timestamps = false
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'reserva_id' => 'integer',
        'inscripcion_clase_id' => 'integer',
        'puntuacion_general' => 'integer',
        'puntuacion_limpieza' => 'integer',
        'puntuacion_instructor' => 'integer',
        'fecha_envio_invitacion' => 'datetime',
        'fecha_completada' => 'datetime',
        // Castear created_at/updated_at si $timestamps = true
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
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
        // Ojo: El nombre local 'inscripcion_clase_id' debe coincidir con la FK en la tabla ENCUESTA
        // El nombre de la clave dueña 'inscripcion_id' debe coincidir con la PK en INSCRIPCION_CLASE
        return $this->belongsTo(InscripcionClase::class, 'inscripcion_clase_id', 'inscripcion_id');
    }

    public function puntosLogs()
    {
        return $this->hasMany(PuntosLog::class, 'encuesta_id', 'encuesta_id');
    }
}
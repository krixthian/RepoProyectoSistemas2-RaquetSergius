<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuntosLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * El error SQL indica 'puntos_log' (singular), la migración 'puntos_logs' (plural).
     * Ajustar según el nombre real en tu BD. Usaré 'puntos_log' basado en el error.
     * @var string
     */
    protected $table = 'puntos_log'; // o 'puntos_logs' si ese es el nombre correcto

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'log_id';

    /**
     * Indicates if the model should be timestamped.
     * La tabla tiene una columna 'fecha' con default CURRENT_TIMESTAMP,
     * pero no parece usar las columnas 'created_at' y 'updated_at' de Eloquent.
     * @var bool
     */
    public $timestamps = false; // Si no tienes created_at/updated_at estándar de Eloquent

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'fecha', // Añadido si quieres setearla manualmente, aunque tiene default
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cliente_id' => 'integer',
        'puntos_cambio' => 'integer',
        'puntos_antes' => 'integer',
        'puntos_despues' => 'integer',
        'reserva_id' => 'integer',
        'inscripcion_clase_id' => 'integer',
        'encuesta_id' => 'integer',
        'canje_premio_id' => 'integer',
        'fecha' => 'datetime', // Para que Eloquent lo trate como objeto Carbon
    ];

    /**
     * Get the cliente that owns the log.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    // Define otras relaciones si es necesario (Reserva, InscripcionClase, etc.)
    // Ejemplo:
    // public function reserva()
    // {
    //     return $this->belongsTo(Reserva::class, 'reserva_id', 'reserva_id');
    // }
}
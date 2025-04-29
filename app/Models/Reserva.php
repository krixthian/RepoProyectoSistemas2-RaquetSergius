<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Opcional: Para usar Accessors/Mutators si quieres combinar fecha y hora virtualmente
// use Illuminate\Database\Eloquent\Casts\Attribute;
// use Carbon\Carbon;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';
    protected $primaryKey = 'reserva_id';


    protected $fillable = [
        'cancha_id',
        'cliente_id',
        'fecha',          // Columna para la fecha (ej: 2025-04-10)
        'hora_inicio',    // Columna para la hora de inicio (ej: 14:00:00)
        'hora_fin',       // Columna para la hora de fin (ej: 15:00:00)
        'monto',
        'estado',
        'metodo_pago',
        'pago_completo',
    ];

    /**
     * The attributes that should be cast.
     * Ajusta los casts para que coincidan con las columnas en $fillable y sus tipos de datos.
     */
    protected $casts = [
        'cliente_id' => 'integer',
        'cancha_id' => 'integer',
        'fecha' => 'date:Y-m-d',
        'monto' => 'decimal:2',
        'pago_completo' => 'boolean',


    ];

    /**
     * Relación con el modelo Cancha.
     */
    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id', 'cancha_id');
    }

    /**
     * Relación con el modelo Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }


    public function encuestas()
    {
        return $this->hasMany(Encuesta::class, 'reserva_id', 'reserva_id');
    }

    public function puntosLogs()
    {
        return $this->hasMany(PuntosLog::class, 'reserva_id', 'reserva_id');
    }

    public function canjesPremios() // Canjes aplicados a esta reserva (ej. descuento)
    {
        return $this->hasMany(CanjePremio::class, 'reserva_id', 'reserva_id');
    }

}
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
        'fecha' => 'date:Y-m-d', // Convierte 'fecha' a un objeto Carbon (solo fecha)
        // 'hora_inicio'   => 'datetime:H:i:s', // Opcional: Si guardas como TIME, puedes castear a Carbon solo con hora. A menudo se deja como string.
        // 'hora_fin'      => 'datetime:H:i:s', // Opcional: Ídem hora_inicio.
        'monto' => 'decimal:2',   // Correcto si la columna es DECIMAL/NUMERIC
        'pago_completo' => 'boolean',     // Correcto si la columna es BOOLEAN/TINYINT(1)

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


    // --- Opcional: Accessors para obtener fecha y hora combinadas ---
    // Si necesitas trabajar frecuentemente con la fecha y hora combinadas como un objeto DateTime/Carbon,
    // puedes definir accessors. Esto NO cambia tu base de datos, solo cómo accedes a la información.

    /*
    protected function fechaHoraInicio(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['fecha']) && isset($attributes['hora_inicio'])
                ? Carbon::parse($attributes['fecha'] . ' ' . $attributes['hora_inicio'])
                : null,
        );
    }

    protected function fechaHoraFin(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['fecha']) && isset($attributes['hora_fin'])
                ? Carbon::parse($attributes['fecha'] . ' ' . $attributes['hora_fin'])
                : null,
        );
    }
    */

}
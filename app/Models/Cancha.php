<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cancha extends Model
{
    use HasFactory;

    protected $table = 'canchas';
    protected $primaryKey = 'cancha_id';
    // ... (fillable, casts, etc. como los tenías) ...


    /**
     * Relación: Una Cancha puede estar en muchas Reservas (via tabla pivote).
     * (Corregida)
     */
    public function reservas(): BelongsToMany
    {
        // --- CORRECCIÓN ---
        return $this->belongsToMany(
            Reserva::class,
            'reservas_canchas', // Nombre correcto de la tabla pivote
            'cancha_id',        // Clave foránea en pivote para este modelo (Cancha)
            'reserva_id'
        )        // Clave foránea en pivote para modelo relacionado (Reserva)
            ->withPivot('precio_total', 'reserva_cancha_id') // Campos extra en pivote
            ->withTimestamps();      // Pivote tiene timestamps
    }
    protected $casts = [
        'disponible' => 'boolean',
        'precio_hora' => 'decimal:2',
        'capacidad' => 'integer',
    ];


}
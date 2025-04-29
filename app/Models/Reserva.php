<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    protected $primaryKey = 'reserva_id';

    protected $fillable = [
        'cancha_id',
        'cliente_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'monto',
        'estado',
        'metodo_pago',
        'pago_completo',
    ];

    /**
     * Convierte automáticamente estos campos a instancias de Carbon (o tipos nativos).
     */
    protected $casts = [
        'fecha'         => 'date',       // ahora $reserva->fecha es Carbon
        'monto'         => 'decimal:2',
        'pago_completo' => 'boolean',
        'created_at'    => 'datetime',   // ya vienen así por defecto, pero lo ponemos explícito
        'updated_at'    => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function cancha()
    {
        return $this->belongsTo(Cancha::class, 'cancha_id', 'cancha_id');
    }
}

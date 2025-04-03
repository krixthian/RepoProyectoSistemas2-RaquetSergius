<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';
    protected $primaryKey = 'reserva_id';

    protected $fillable = [
        'cliente_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'monto',
        'estado',
        'metodo_pago',
        'pago_completo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    // --- AÑADIR O MODIFICAR ESTA SECCIÓN ---
    protected $casts = [
        'fecha_hora_inicio' => 'datetime', // Convierte a objeto Carbon
        'fecha_hora_fin' => 'datetime',    // Convierte a objeto Carbon
        'pago_completo' => 'boolean',      // Es bueno mantener esto también
        'monto' => 'decimal:2',          // Si 'monto' es decimal/float, también es bueno castearlo
    ];
    // ----------------------------------------


    // --- Relaciones (como las tenías antes) ---
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function canchas()
    {
        return $this->belongsToMany(Cancha::class, 'reservas_canchas', 'reserva_id', 'cancha_id')
                    ->withPivot('precio_total', 'reserva_cancha_id')
                    ->withTimestamps();
    }
}
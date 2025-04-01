<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

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

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'monto' => 'decimal:2',
        'pago_completo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
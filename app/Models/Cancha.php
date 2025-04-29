<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancha extends Model
{
    use HasFactory;
    protected $table = 'canchas';
    protected $primaryKey = 'cancha_id';
    protected $fillable = [
        'nombre',
        'tipo',
        'disponible',
        'precio_hora',
        'capacidad',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'precio_hora' => 'decimal:2',
        'capacidad' => 'integer',
    ];

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'cancha_id', 'cancha_id');
    }
}
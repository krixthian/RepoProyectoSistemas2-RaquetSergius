<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importar

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes'; // Asumiendo que la tabla se llama así
    protected $primaryKey = 'cliente_id'; // Clave primaria correcta
    public $incrementing = true; // Asegúrate si es autoincremental
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'cliente_frecuente',
        'fecha_registro',
    ];

    protected $casts = [
        'cliente_frecuente' => 'boolean',
        'fecha_registro' => 'date',
    ];

     /**
      * Relación: Un Cliente puede tener muchas Reservas.
      * (Añadida/Confirmada)
      */
     public function reservas(): HasMany
     {
         // Clave foránea en la tabla 'reservas': cliente_id
         // Clave local (primaria) en esta tabla 'clientes': cliente_id
         return $this->hasMany(Reserva::class, 'cliente_id', 'cliente_id');
     }
}
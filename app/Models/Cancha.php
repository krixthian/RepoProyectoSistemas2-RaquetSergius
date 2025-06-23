<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancha extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'canchas';

    /**
     * La clave primaria para el modelo.
     *
     * @var string
     */
    protected $primaryKey = 'cancha_id';

    /**
     * Los atributos que se pueden asignar masivamente.
     * ¡Importante! Asegúrate de que estos campos coincidan con los de tu tabla 'canchas'.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_hora',
        'capacidad',
        'disponible',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'disponible' => 'boolean',
        'precio_hora' => 'decimal:2',
        'capacidad' => 'integer',
    ];

    /**
     * --- CORRECCIÓN APLICADA ---
     * Define la relación "uno a muchos" con el modelo Reserva.
     * Una Cancha puede tener muchas Reservas.
     */
    public function reservas()
    {
        // Esta es la relación correcta porque la tabla 'reservas' tiene una
        // clave foránea 'cancha_id' que apunta directamente a esta tabla.
        return $this->hasMany(Reserva::class, 'cancha_id', 'cancha_id');
    }
}
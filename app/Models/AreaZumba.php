<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaZumba extends Model
{
    use HasFactory;

    protected $table = 'areas_zumba';

    protected $primaryKey = 'area_id';
    protected $fillable = [
        'nombre',
        'capacidad',
        'disponible',
        'precio_clase',
        'img_horario', // Ajustado a SQL
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'precio_clase' => 'decimal:2',
        'capacidad' => 'integer',
    ];

    public function clases()
    {
        return $this->hasMany(ClaseZumba::class, 'area_id', 'area_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaseZumba extends Model
{
    use HasFactory;

    protected $primaryKey = 'clase_id';
    protected $fillable = [
        'area_id',
        'instructor_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'cupo_maximo',
        'cupo_actual',
        'precio',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'precio' => 'decimal:2',
        'cupo_maximo' => 'integer',
        'cupo_actual' => 'integer',
    ];

    public function area()
    {
        return $this->belongsTo(AreaZumba::class, 'area_id', 'area_id');
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id', 'instructor_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
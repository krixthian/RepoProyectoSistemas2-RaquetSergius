<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaseZumba extends Model
{
    use HasFactory;

    protected $table = 'clases_zumba'; // Ajustado a SQL
    protected $primaryKey = 'clase_id';
    protected $fillable = [
        'area_id',
        'instructor_id',
        'diasemama', // Ajustado a SQL
        'hora_inicio', // Ajustado a SQL
        'hora_fin', // Ajustado a SQL
        'precio', // Ajustado a SQL
        'cupo_maximo', // Ajustado a SQL
        'habilitado', // Ajustado a SQL

    ];

    protected $casts = [
        'area_id' => 'integer',
        'instructor_id' => 'integer',
        // No hay cast directo para 'time'
        'precio' => 'decimal:2',
        'cupo_maximo' => 'integer',
        'habilitado' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(AreaZumba::class, 'area_id', 'area_id');
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id', 'instructor_id');
    }

    // Relaciones
    public function inscripciones()
    {
        return $this->hasMany(InscripcionClase::class, 'clase_id', 'clase_id');
    }

    public function premiosDondeEsGratis() // Premios que regalan esta clase
    {
        return $this->hasMany(Premio::class, 'clase_gratis_id', 'clase_id');
    }
}
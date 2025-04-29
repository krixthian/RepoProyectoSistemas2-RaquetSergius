<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionClase extends Model
{
    use HasFactory;

    protected $table = 'inscripciones_clase'; // Nombre de la tabla en la base de datos
    protected $primaryKey = 'inscripcion_id';
    protected $fillable = [
        'clase_id',
        'cliente_id',
        'fecha_inscripcion', // Ajustado a SQL
        'estado', // Ajustado a SQL
        'monto_pagado', // Ajustado a SQL
        'metodo_pago', // Ajustado a SQL
        'fecha_pago', // Ajustado a SQL
    ];

    protected $casts = [
        'clase_id' => 'integer',
        'cliente_id' => 'integer',
        'fecha_inscripcion' => 'datetime',
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public function clase()
    {
        return $this->belongsTo(ClaseZumba::class, 'clase_id', 'clase_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    public function encuestas()
    {
        return $this->hasMany(Encuesta::class, 'inscripcion_clase_id', 'inscripcion_id');
    }

    public function puntosLogs()
    {
        return $this->hasMany(PuntosLog::class, 'inscripcion_clase_id', 'inscripcion_id');
    }

    public function canjesPremios() // Canjes aplicados a esta inscripción (ej. descuento)
    {
        return $this->hasMany(CanjePremio::class, 'inscripcion_clase_id', 'inscripcion_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionClase extends Model
{
    use HasFactory;

    protected $table = 'inscripciones_clase';
    protected $primaryKey = 'inscripcion_id';

    public $timestamps = false;

    protected $fillable = [
        'clase_id',
        'cliente_id',
        'fecha_inscripcion',
        'fecha_clase',
        'fecha_cancelacion',
        'estado',
        'monto_pagado',
        'metodo_pago',
        'fecha_pago',
    ];

    protected $casts = [
        'clase_id' => 'integer',
        'cliente_id' => 'integer',
        'fecha_inscripcion' => 'datetime',
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];
    protected $dates = [
        'fecha_inscripcion',
        'fecha_clase',
        'fecha_cancelacion',
        'fecha_pago',
        'created_at', // Eloquent ya las maneja si usas timestamps()
        'updated_at'  // pero no está de más ser explícito si no usas timestamps()
    ];
    public function claseZumba()
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

    public function canjesPremios()
    {
        return $this->hasMany(CanjePremio::class, 'inscripcion_clase_id', 'inscripcion_id');
    }
}
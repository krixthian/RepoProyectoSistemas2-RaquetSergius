<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'notificacion_id';
    protected $fillable = [
        'cliente_id',
        'tipo',
        'contenido',
        'fecha_envio',
        'enviada',
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'fecha_envio' => 'datetime',
        'enviada' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cliente_id');
    }

    // Puedes agregar aquí las relaciones con otras tablas si las hubiera
}
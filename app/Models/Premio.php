<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    use HasFactory;

    protected $table = 'premios';
    protected $primaryKey = 'premio_id';
    public $timestamps = false; // Deshabilitado por definición explícita en migración

    protected $fillable = [
        'nombre',
        'descripcion',
        'puntos_requeridos',
        'tipo',
        'valor_descuento',
        'porcentaje_descuento',
        'clase_gratis_id',
        'producto_nombre',
        'activo',
        'stock',
        'valido_desde',
        'valido_hasta',
    ];

    protected $casts = [
        'puntos_requeridos' => 'integer',
        'valor_descuento' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'clase_gratis_id' => 'integer',
        'activo' => 'boolean',
        'stock' => 'integer',
        'valido_desde' => 'date',
        'valido_hasta' => 'date',
        // 'created_at' => 'datetime', // Si $timestamps = true
        // 'updated_at' => 'datetime', // Si $timestamps = true
    ];

    // Relaciones
    public function claseGratis()
    {
        return $this->belongsTo(ClaseZumba::class, 'clase_gratis_id', 'clase_id');
    }

    public function canjes()
    {
        return $this->hasMany(CanjePremio::class, 'premio_id', 'premio_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ClaseZumba extends Model
{
    use HasFactory;

    protected $table = 'clases_zumba';
    protected $primaryKey = 'clase_id';
    protected $fillable = [
        'area_id',
        'instructor_id',
        'diasemama',
        'hora_inicio',
        'hora_fin',
        'precio',
        'cupo_maximo',
        'habilitado',

    ];

    protected $casts = [
        'area_id' => 'integer',
        'instructor_id' => 'integer',
        'precio' => 'decimal:2',
        'cupo_maximo' => 'integer',
        'habilitado' => 'boolean',
    ];

    public function getHoraInicioAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function getHoraFinAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }
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

    public function premiosDondeEsGratis()
    {
        return $this->hasMany(Premio::class, 'clase_gratis_id', 'clase_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;


    protected $table = 'clientes';
    protected $primaryKey = 'cliente_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'cliente_frecuente',
        'fecha_registro',

        'last_activity_at',
        'is_churned',
        'puntos',
    ];

    protected $casts = [
        'cliente_frecuente' => 'boolean',
        'fecha_registro' => 'date',

        'last_activity_at' => 'datetime',
        'is_churned' => 'boolean',
        'puntos' => 'integer',
    ];



    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'cliente_id', 'cliente_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(InscripcionClase::class, 'cliente_id', 'cliente_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'cliente_id', 'cliente_id');
    }

    public function equiposCapitan()
    {
        return $this->hasMany(Equipo::class, 'capitan_id', 'cliente_id');
    }

    public function encuestas()
    {
        return $this->hasMany(Encuesta::class, 'cliente_id', 'cliente_id');
    }

    public function puntosLogs()
    {
        return $this->hasMany(PuntosLog::class, 'cliente_id', 'cliente_id');
    }

    public function canjesPremios()
    {
        return $this->hasMany(CanjePremio::class, 'cliente_id', 'cliente_id');
    }
    public function inscripcionesClase()
    {
        return $this->hasMany(InscripcionClase::class);
    }

}
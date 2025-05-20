<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importar

class Cliente extends Model
{
    use HasFactory;


    protected $table = 'clientes'; // Asumiendo que la tabla se llama así
    protected $primaryKey = 'cliente_id'; // Clave primaria correcta
    public $incrementing = true; // Asegúrate si es autoincremental
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'cliente_frecuente',
        'fecha_registro',
        // Nuevas columnas
        'last_activity_at',
        'is_churned',
        'puntos',
        'streak_semanal_actual',
        'streak_semanal_maxima',
        'ultima_semana_actividad',
    ];

    protected $casts = [
        'cliente_frecuente' => 'boolean',
        'fecha_registro' => 'date',
        // Casts para nuevas columnas
        'last_activity_at' => 'datetime',
        'is_churned' => 'boolean',
        'puntos' => 'integer',
        'streak_semanal_actual' => 'integer',
        'streak_semanal_maxima' => 'integer',
        'ultima_semana_actividad' => 'integer',
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

    public function equiposCapitan() // Equipos donde es capitán
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;
    protected $table = 'instructores';
    protected $primaryKey = 'instructor_id';
    protected $fillable = [
        'nombre',
        'telefono',
        'especialidad',
        'tarifa_hora',
    ];

    protected $casts = [
        'tarifa_hora' => 'decimal:2',
    ];

    public function clases()
    {
        return $this->hasMany(ClaseZumba::class, 'instructor_id', 'instructor_id');
    }
}
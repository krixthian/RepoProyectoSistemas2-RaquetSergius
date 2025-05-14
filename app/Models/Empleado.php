<?php
namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Passwords\CanResetPassword;




class Empleado extends Authenticatable implements CanResetPasswordContract
{
    use Notifiable;
    use CanResetPassword;
    use HasFactory;

    protected $table = 'empleados';
    protected $primaryKey = 'empleado_id';

    protected $fillable = [
        'nombre',
        'usuario',
        'contrasena',
        'rol',
        'telefono',
        'email',
        'activo',
    ];

    protected $hidden = [
        'contrasena',
        'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->contrasena;
    }
    public function getAuthPasswordName()
    {
        return 'contrasena';
    }
    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        // Si creaste 'ResetPasswordNotification' en App\Notifications:
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
        // Si no la has creado aún, y para probar rápidamente, puedes usar la de Laravel por defecto
        // pero necesitarás asegurarte que el Empleado use el trait Notifiable.
        // Por ahora, mantenemos la personalizada que definimos.
    }


}
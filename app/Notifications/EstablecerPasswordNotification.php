<?php
// app/Notifications/EstablecerPasswordNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class EstablecerPasswordNotification extends Notification // Opcional: implements ShouldQueue
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $setUrl = url(route('password.reset', [ // Reutilizamos la ruta de reset
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject(Lang::get('Establece tu Contraseña - ' . config('app.name')))
            ->line(Lang::get('Bienvenido/a a ' . config('app.name') . '! Para completar tu registro, por favor establece tu contraseña.'))
            ->action(Lang::get('Establecer Contraseña'), $setUrl)
            ->line(Lang::get('Este enlace para establecer contraseña expirará en :count minutos.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
            ->line(Lang::get('Si no creaste esta cuenta, no se requiere ninguna acción adicional.'));
    }
}
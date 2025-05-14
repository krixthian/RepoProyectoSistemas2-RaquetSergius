<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Reserva;
use Illuminate\Mail\Mailables\Address;

class ConfirmacionReservaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Reserva $reserva;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\Reserva $reserva
     * @return void
     */
    public function __construct(Reserva $reserva)
    {
        $this->reserva = $reserva;
    }

    /**
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {

        return new Envelope(
            subject: 'Confirmación de tu Reserva en ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     * Especifica la vista Blade para el contenido del correo.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reservas.confirmacion',
            // with: [
            //     'nombreCliente' => $this->reserva->cliente->nombre,
            //     'fechaReserva' => $this->reserva->fecha->format('d/m/Y'),
            // ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
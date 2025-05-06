<?php

namespace App\Chatbot;

interface IntentHandlerInterface
{
    /**
     * Maneja la lógica para un intent específico.
     *
     * @param array $parameters Parámetros extraídos por Dialogflow.
     * @param string $senderId El ID del remitente (ej. número de WhatsApp).
     * @return string El texto de respuesta a enviar al usuario.
     */
    public function handle(array $parameters, string $senderId): string|array;
}
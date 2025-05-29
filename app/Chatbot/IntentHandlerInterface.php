<?php

namespace App\Chatbot;

interface IntentHandlerInterface
{
    /**
     * Maneja la lógica para un intent específico.
     *
     * @param array $parameters Parámetros extraidos Dialogflow.
     * @param string $senderId numero de whtsapp
     * @return string El texto de respuesta para enviar usuario
     */

    public function handle(array $parameters, string $senderId, ?string $action = null);
    // public function handle(array $parameters, string $senderId, ?string $action = null): string|array;
}
<?php // config/dialogflow.php
return [
    'project_id' => env('DIALOGFLOW_PROJECT_ID'),
    // Puedes añadir otras configuraciones de Dialogflow aquí
    // 'credentials_path' => base_path(env('GOOGLE_APPLICATION_CREDENTIALS')),
    'language_code' => env('DIALOGFLOW_LANGUAGE_CODE', 'es-ES'),
];
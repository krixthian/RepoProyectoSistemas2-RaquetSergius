<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class whatsappController extends Controller
{

    function escuchar(Request $request)
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {


            Log::info($_POST);
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            Log::info($data);


        } else {
            Log::info('No llegó nada');
        }
        // if ($request->isMethod('post')) {

        //     $data = $request->json()->all(); // Obtiene el JSON como array

        //     // Log::info($data);

        //     $this->extraerValoresEnviarRespuesta($data);

        // } else {
        //     Log::info('No llegó nada');
        // }

    }
    function token()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            if (isset($_GET['hub_mode']) && isset($_GET['hub_verify_token']) && isset($_GET['hub_challenge']) && $_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === 'andres') {

                Log::info($_GET['hub_mode']);
                Log::info($_GET['hub_verify_token']);
                Log::info($_GET['hub_challenge']);

                echo $_GET['hub_challenge'];
            } else {
                http_response_code(403);
                Log::info('Fallo...');
            }
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DateTime;

class AeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['throttle:api', 'auth:sanctum']);
    }

    private function getDates()
    {
        /*
            $apiUrl = "https://ejemplo.com/api/endpoint";
            $accessToken = "tu_token_aqui"; // Reemplaza esto con tu token real

            // Inicializar cURL
            $ch = curl_init($apiUrl);

            // Configurar opciones de cURL
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Configurar el token en la cabecera de la solicitud
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
            ]);

            // Realizar la solicitud GET a la API
            $response = curl_exec($ch);

            // Verificar si la solicitud fue exitosa
            if ($response === FALSE) {
                die('Error al realizar la solicitud a la API: ' . curl_error($ch));
            }

            // Cerrar la sesión de cURL
            curl_close($ch);

            // Decodificar la respuesta JSON
            $data = json_decode($response, true);

            // Procesar la respuesta
            if ($data !== null) {
                // La respuesta es un array asociativo
                print_r($data);
            } else {
                echo 'Error al decodificar la respuesta JSON';
            }
            */
        $startDay = new DateTime("8/1/2023");
        $fifthMonth = new DateTime($startDay->format('Y-m-d'));
        $sixthMonth = new DateTime($startDay->format('Y-m-d'));
        $lastMonth = new DateTime($startDay->format('Y-m-d'));

        $fifthMonth->modify('+5 months');
        $sixthMonth->modify('+6 months');
        $lastMonth->modify('+12 months');

        return [
            'startDay' => $startDay->format('Y-m-d H:i:s'),
            'fifthMonth' => $fifthMonth->format('Y-m-d H:i:s'),
            'sixthMonth' => $sixthMonth->format('Y-m-d H:i:s'),
            'lastMonth' => $lastMonth->format('Y-m-d H:i:s'),
        ];

    }


    public function getaedates(Request $request)
    {
        if (Auth::check()) {
            // Usuario autenticado, obtén sus datoss
            //$user = Auth::user();
            //TODO CONSULTAR API
            return response()->json($this->getDates());
        }
    }
}

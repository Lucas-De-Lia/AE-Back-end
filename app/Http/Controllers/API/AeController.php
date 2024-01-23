<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Http\Response;

class AE {
    const FINALIZABLE = 0;
    const FINALIZADA = 1;
    const NO_FINALIZABLE = 2;
}

class AeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['throttle:api', 'auth:sanctum']);
    }


    private function getCalendarDates()
    {
        // Retrieve API URL and token from environment variables
        if(Auth::check()){
            $url = env("API_AE");
            $token = env("TOKEN_AE");

            // Initialize cURL
            $ch = curl_init($url . '/fechas/' . $this.getDNI());
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                ],
            ]);

            // Make the GET request to the API
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                return response()->json('Error when making API request: ' . curl_error($ch), Response::HTTP_BAD_REQUEST);
            }

            curl_close($ch);

            // Decode the JSON response
            $data = json_decode($response);

            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json('Error decoding JSON response: ' . json_last_error_msg(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Build the response
            if (isset($data->error)) {
                return response()->json(['status' => 'AE not found'], Response::HTTP_ACCEPTED);
            }

            if (isset($data->fecha_renovacion_ae)) {
                $type = AE::FINALIZED;
                $dates = [
                    'startDay' => $data->fecha_ae,
                    'lastMonth' => $data->fecha_cierre_ae,
                    'fifthMonth' => $data->fecha_renovacion,
                    'sixthMonth' => $data->fecha_vencimiento,
                    'renewalDate' => $data->fecha_revocacion_ae,
                ];
            } elseif (isset($data->fecha_renovacion)) {
                $type = AE::FINISHABLE;
                $dates = [
                    'startDay' => $data->fecha_ae,
                    'lastMonth' => $data->fecha_cierre_ae,
                    'fifthMonth' => $data->fecha_renovacion,
                    'sixthMonth' => $data->fecha_vencimiento,
                ];
            } else {
                $type = AE::NON_FINISHABLE;
                $dates = [
                    'startDay' => $data->fecha_ae,
                    'lastMonth' => $data->fecha_cierre_ae,
                ];
            }

            $response = [
                'type' => $type,
                'dates' => $dates,
            ];

            return response()->json($response, Response::HTTP_OK);
        }
    }


    private function finalizeAE()
    {
        if(Auth::check()){
            $url = env("API_AE");
            $token = env("TOKEN_AE");


            // Initialize cURL
            $ch = curl_init($url . '/finalizar/'. $this.getDNI() );
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                ],
            ]);

            // Make the GET request to the API
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                return response()->json('Error when making API request: ' . curl_error($ch), Response::HTTP_BAD_REQUEST);
            }

            curl_close($ch);

            // Decode the JSON response
            $data = json_decode($response);

            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json('Error decoding JSON response: ' . json_last_error_msg(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if(isset($data->id_autoexcluido)){
                return response()->json(['status' => $data->id_autoexcluido], Response::HTTP_ACCEPTED);
            }
            else{
                return response()->json(['status' => $data,Response::HTTP_OK]);
            }

        }
    }

    private function getDNI()
    {
        $user = Auth::user();
        preg_match('/-(\d+)-/', $user->cuil, $matches);
        return $matches[1];
    }
    private function newAE(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'birthdate' => 'required',
            'gender' => 'required',
            'address' => 'required',
            'address_number' => 'required',
            'postalcode' => 'required',
            'city' => 'required',
            'state' => 'required',
            'phone' => 'required',
            'startdate' => 'required',
        ]);

        if(Auth::check()){;
            $user = Auth::user();
            $postData = [
                'nro_dni' =>  $this.getDNI(),
                'nombres' => $request->firstname,
                'apellido' => $request->lastname,
                'fecha_nacimiento' => $request->birthdate,
                'sexo' => $request->gender,
                'domicilio' => $request->address,
                'nro_domicilio' => $request->address_number,
                'codigo_postal' => $request->postalcode,
                'nombre_localidad' => $request->city,
                'nombre_provincia' => $request->state,
                'telefono' => $request->phone,
                'correo' => $user->email,
                'ae_estado' => ['fecha_ae' => $request->startdate],
            ];


            $url = env("API_AE");
            $token = env("TOKEN_AE");
            $ch = curl_init($url . '/agregar');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                ],
                CURLOPT_POST => true, // Set the request type to POST
                CURLOPT_POSTFIELDS => json_encode($postData), // Include data to be sent in the request
            ]);

            // Make the POST request to the API
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                return response()->json('Error when making API request: ' . curl_error($ch), Response::HTTP_BAD_REQUEST);
            }

            curl_close($ch);

            // Decode the JSON response
            $data = json_decode($response);
        }
    }

    private function getDates()
    {

        $startDay = new DateTime("8/1/2023");
        $fifthMonth = new DateTime($startDay->format('Y-m-d'));
        $sixthMonth = new DateTime($startDay->format('Y-m-d'));
        $lastMonth = new DateTime($startDay->format('Y-m-d'));

        $fifthMonth->modify('+5 months');
        $sixthMonth->modify('+6 months');
        $lastMonth->modify('+12 months');
        //return [];
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

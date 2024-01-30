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
        $this->middleware(['auth:sanctum'], ['except' => ['register_ae']]);
    }


    private function getCalendarDates()
    {
        // Retrieve API URL and token from environment variables
        if(Auth::check()){
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");

            // Initialize cURL
            $ch = curl_init($url . '/fechas/' . $this.getDNI());
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'API-Token' . $token,
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
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");


            // Initialize cURL
            $ch = curl_init($url . '/finalizar/'. $this.getDNI() );
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'API-Token' . $token,
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

    private function getDNI($cuil)
    {
        preg_match('/-(\d+)-/', $cuil, $matches);
        return intval($matches[1]);;
    }

    public static function register_ae(Request $request)
    {

        $postData = [
            'ae_datos' => [
                'nro_dni' =>  $request->dni,
                'nombres' => $request->firstname,
                'apellido' => $request->lastname,
                'fecha_nacimiento' => $request->birthdate,
                'sexo' => $request->gender,
                'domicilio' => $request->address,
                'nro_domicilio' => $request->address_number,
                'piso' => $request->floor,
                'dpto' => $request->apartament,
                'codigo_postal' => $request->postalcode,
                'nombre_localidad' => $request->city,
                'nombre_provincia' => $request->province,
                'ocupacion' => $request->occupation,
                'capacitacion' => $request->study,
                //'estado_civil' => $request,
                'telefono' => $request->phone,
                'correo' => $request->email,
            ],
            'ae_estado' => ['fecha_ae' => $request->startdate],
        ];
        $response = AeController::start($postData);
        return $response;

    }

    private function start_ae(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:150',
            'lastname' => 'required|string|max:100',
            'birthdate' => 'required|date',
            'gender' => 'required', //ver como hacerlo
            'address' => 'required|string|max:100',
            'address_number' => 'required|integer',
            'floor' => 'nullable|string|max:5',
            'apartament' => 'nullable|string|max:5',
            'postalcode' => 'required|string|max:10',
            'city' => 'required|string|max:200',
            'province' => 'required|string|max:200',
            'phone' => 'required|string|max:200',
            'startdate' => 'required|date',
            'occupation'        => 'nullable|string|max:4|exists:ae_ocupacion,codigo', // VER COMO HACERLO
            'study'     => 'nullable|string|max:4|exists:ae_capacitacion,codigo',  // VER COMO HACERLO
            //'ae_datos.estado_civil'     => 'nullable|string|max:4|exists:ae_estado_civil,codigo',  // VER COMO HACERLO
            'renewvaldate' => 'nullable|date'
        ]);

        if(Auth::check()){;
            $user = Auth::user();
            //TODO VER SI alguno tiene ya datos cargados y reutilizar si no existen
            $postData = [
                'ae_datos' => [
                    'nro_dni' =>  getDNI($request->cuil),
                    'nombres' => $request->firstname,
                    'apellido' => $request->lastname,
                    'fecha_nacimiento' => $request->birthdate,
                    'sexo' => $request->gender,
                    'domicilio' => $request->address,
                    'nro_domicilio' => $request->address_number,
                    'piso' => $request->floor,
                    'dpto' => $request->apartament,
                    'codigo_postal' => $request->postalcode,
                    'nombre_localidad' => $request->city,
                    'nombre_provincia' => $request->province,
                    'ocupacion' => $request->occupation,
                    'capacitacion' => $request->study,
                    //'estado_civil' => $request,
                    'telefono' => $request->phone,
                    'correo' => $user->email,
                ],
                'ae_estado' => ['fecha_ae' => $request->startdate],
            ];
            $response = start($postData);
            return $response;
        }
    }

    public static function start($postData){
        $url = env("API_URL_AE");
        $token = env("API_TOKEN_AE");

        $ch = curl_init($url . '/agregar');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'API-Token: ' . $token,
            ],
            CURLOPT_POST => true, // Set the request type to POST
            CURLOPT_POSTFIELDS => json_encode($postData), // Include data to be sent in the request
        ]);

        // Make the POST request to the API
        $response = curl_exec($ch);
        return $response;
    }
    private function getDates()
    {

        $startDay = new DateTime("8/5/2023");
        $fifthMonth = new DateTime($startDay->format('Y-m-d'));
        $sixthMonth = new DateTime($startDay->format('Y-m-d'));
        $lastMonth = new DateTime($startDay->format('Y-m-d'));

        $fifthMonth->modify('+5 months');
        $sixthMonth->modify('+5 months');
        $sixthMonth->modify('+29 days');
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

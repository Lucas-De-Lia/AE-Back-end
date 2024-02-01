<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class AE {
    const NON_AE = -1;
    const FINALIZED = 0;
    const FINISHABLE = 1;
    const NON_FINISHABLE = 2;
}

class AeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['throttle:api', 'auth:sanctum']);
        $this->middleware(['auth:sanctum'], ['except' => ['register_ae']]);
    }

    public function get_calendar_dates()
    {
        // Retrieve API URL and token from environment variables
        if(Auth::check()){
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $DNI = AeController::getDNI($user->cuil);

            // Make the GET request to the API
            $response = Http::withHeaders([
                'API-Token' => $token,
            ])->get($url . '/fechas/' . (string)$DNI);

            // Check for any HTTP errors
            if ($response->failed()) {
                return response()->json('Error when making API request: ' . $response->status(), Response::HTTP_BAD_REQUEST);
            }

            // Decode the JSON response
            $data = $response->json();

            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json('Error decoding JSON response: ' . json_last_error_msg(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Build the response
            if (isset($data['error'])) {
                return response()->json(['type' => AE::NON_AE], Response::HTTP_ACCEPTED);
            }

            $type = AE::NON_FINISHABLE;
            $dates = [
                'startDay' => $data['fecha_ae'],
                'lastMonth' => $data['fecha_cierre_ae'],
            ];

            if (isset($data['fecha_renovacion'])) {
                $type = AE::FINISHABLE;
                $dates['fifthMonth'] = $data['fecha_renovacion'];
                $dates['sixthMonth'] = $data['fecha_vencimiento'];
            }

            if (isset($data['fecha_renovacion_ae'])) {
                $type = AE::FINALIZED;
                $dates['renewalMonth'] = $data['fecha_revocacion_ae'];
            }

            $response = [
                'type' => $type,
                'dates' => $dates,
            ];

            //Log::info(json_encode($dates));

            return response()->json($response, Response::HTTP_OK);
        }
    }

    public function finalize_ae(Request $request)
    {
        if (Auth::check()) {

            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $DNI = AeController::getDNI($user->cuil);

            if (!Hash::check($request->input('password'), $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
            }

            // Realizar la solicitud al API utilizando Guzzle
            $response = Http::withHeaders([
                'API-Token' => $token,
            ])->get($url . '/finalizar/' . (string)$DNI);


            // Decodificar la respuesta JSON
            $data = $response->json();

            // Verificar si la decodificación JSON fue exitosa
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json('Error decoding JSON response: ' . json_last_error_msg(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Verificar si existe la propiedad 'id_autoexcluido' en la respuesta
            if (isset($data['id_autoexcluido'])) {
                return response()->json(['status' => $data['id_autoexcluido']], Response::HTTP_OK);
            } else {
                return response()->json(['status' => $data], Response::HTTP_OK);
            }
        }
    }

    private static function getDNI($cuil)
    {
        preg_match('/-(\d+)-/', $cuil, $matches);
        return (int)($matches[1]);;
    }

    public static function register_ae(Request $request)
    {

        $postData = [
            'ae_datos' => [
                'nro_dni' => AeController::getDNI($request->cuil),
                'nombres' => $request->firstname,
                'apellido' => $request->lastname,
                'fecha_nacimiento' => $request->birthdate,
                'sexo' => $request->gender,
                'domicilio' => $request->address,
                'nro_domicilio' => (int) $request->address_number,
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
                    'nro_dni' =>  AeController::getDNI($request->cuil),
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

        //Log::info(json_encode($postData));

        $response = Http::withHeaders([
            'API-Token' => $token,
            'Content-Type' => 'application/json',
        ])->post($url . '/agregar', $postData);

        return $response->body();
    }

}

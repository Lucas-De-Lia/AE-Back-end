<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Exception;
use Intervention\Image\ImageManager;

class AeController extends Controller
{
    public static $AE = [
        'NON_AE' => -1,
        'FINALIZED' => 0,
        'FINISHABLE' => 1,
        'NON_FINISHABLE' => 2
    ];
    public function __construct()
    {
        $this->middleware(['throttle:api', 'auth:sanctum', 'api']);
        $this->middleware(['auth:sanctum'], ['except' => ['register_ae']]);
    }

    public function get_calendar_dates()
    {
        // Retrieve API URL and token from environment variables
        if (Auth::check()) {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $DNI = AeController::getDNI($user->cuil);

            // Make the GET request to the API
            $response = Http::withHeaders([
                'API-Token' => $token,
                'X-API-Key' => env('APP_SISTEMON_KEY'),
            ])->get($url . '/fechas/' . (string) $DNI);

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
            if (isset ($data['error'])) {
                return response()->json(['type' => self::$AE['NON_AE']], Response::HTTP_ACCEPTED);
            }

            $type = self::$AE['NON_FINISHABLE'];
            $dates = [
                'startDay' => $data['fecha_ae'],
                'lastMonth' => $data['fecha_cierre_ae'],
            ];

            if (isset ($data['fecha_renovacion'])) {
                $type = self::$AE['FINISHABLE'];
                $dates['fifthMonth'] = $data['fecha_renovacion'];
                $dates['sixthMonth'] = $data['fecha_vencimiento'];
            }

            if (isset ($data['fecha_revocacion_ae'])) {
                $type = self::$AE['FINALIZED'];
                $dates['renewalMonth'] = $data['fecha_revocacion_ae'];
            }

            $response = [
                'type' => $type,
                'dates' => $dates,
            ];

            //Log::info(json_encode($data));

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
                'X-API-Key' => env('APP_SISTEMON_KEY'),
            ])->get($url . '/finalizar/' . (string) $DNI);


            // Decodificar la respuesta JSON
            $data = $response->json();

            // Verificar si la decodificación JSON fue exitosa
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json('Error decoding JSON response: ' . json_last_error_msg(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Verificar si existe la propiedad 'id_autoexcluido' en la respuesta
            if (isset ($data['id_autoexcluido'])) {
                return response()->json(['status' => $data['id_autoexcluido']], Response::HTTP_OK);
            } else {
                return response()->json(['status' => $data], Response::HTTP_OK);
            }
        }
    }

    private static function getDNI($cuil)
    {
        preg_match('/-(\d+)-/', $cuil, $matches);
        return (int) ($matches[1]);
        ;
    }
    public static function start($postData)
    {
        try {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $response = Http::withHeaders([
                'API-Token' => $token,
                'X-API-Key' => env('APP_SISTEMON_KEY'),
            ])->post($url . '/agregar', $postData);
            Log::info(json_encode($response->json()));
            return $response;

        } catch (Exception $e) {
            return response()->json('Error al registrar AE', 500);
        }
    }

    public static function merge_dni_photos($image_list)
    {
        $manager = ImageManager::gd();
        $resized = [];
        foreach ($image_list as $photo) {
            $resized[] = $manager->read($photo)->resize(1280, 720, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        $img_merged = $manager->create(1280, 1440);
        $img_merged->place($resized[0], 'top-left');
        $img_merged->place($resized[1], 'bottom-left');
        return $img_merged->toWebp();
    }


    public static function register_ae(Request $request)
    {
        $image = AeController::merge_dni_photos([$request->dni1, $request->dni2]);
        $nro_dni = AeController::getDNI($request->cuil);
        $postData = [
            'ae_datos' => [
                'nro_dni' => $nro_dni,
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
                'telefono' => $request->phone,
                'correo' => $request->email, // ES EL DEL USER
            ],
            'ae_estado' => ['fecha_ae' => $request->startdate],
        ];
        $response = AeController::start($postData);
        $url = env("API_URL_AE");
        $token = env("API_TOKEN_AE");

        $response2 = Http::withHeaders([
            'API-Token' => $token,
            'X-APP-KEY' => env('APP_SISTEMON_KEY'),
        ])
            ->attach('file', $image, 'dni_' . $nro_dni . '.webp')
            ->post($url . '/importacion/archivos', [['name' => 'dni', 'contents' => $nro_dni]]);

        return ['1' => $response->body(), '2' => $response2->body()];
    }


    public function start_ae_n(Request $request)
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
            'state' => 'required|string|max:200',
            'phone' => 'required|string|max:200',
            'startdate' => 'required|date',
            'occupation' => 'nullable|string|max:4', // VER COMO HACERLO
            'study' => 'nullable|string|max:4',
            'dni1' => 'file|max:2048',
            'dni2' => 'file|max:2048',
        ]);

        if (Auth::check()) {

            $user = Auth::user();
            //TODO VER SI alguno tiene ya datos cargados y reutilizar si no existen
            $newName = $request->firstname . ", " . $request->lastname;
            if ($user->name !== $newName) {
                // El nombre cambió
                $partes = explode(", ", $user->name);
                Log::info($partes);
                $newName = $request->firstname;

                if ($partes[0] !== $request->firstname) {
                    // El primer nombre no es igual
                    $newName = $partes[0];
                }

                // Verificar si el apellido ha cambiado
                $newLastName = $request->lastname;
                if (count($partes) > 1 && $partes[1] !== $request->lastname) {
                    $newLastName = $partes[1];
                }

                // Combinar el nuevo primer nombre y el nuevo apellido
                $newName .= ", " . $newLastName;

                $user->name = $newName;
                $user->save();
            }
            $postData = [
                'ae_datos' => [
                    'nro_dni' => AeController::getDNI($user->cuil),
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
                    'nombre_provincia' => $request->state,
                    'ocupacion' => $request->occupation,
                    'capacitacion' => $request->study,
                    'telefono' => $request->phone,
                    'correo' => $user->email, // ES EL DEL USER
                ],
                'ae_estado' => ['fecha_ae' => $request->startdate],
            ];
            $response = AeController::start($postData);
            return $response;
        }
    }


    public function fetch_user_data(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $dni = AeController::getDNI($user->cuil);
            try {
                $response = Http::withHeaders([
                    'API-Token' => $token,
                    'Content-Type' => 'application/json',
                    'X-API-Key' => env('APP_SISTEMON_KEY'),
                ])->get($url . '/ultima/' . $dni);

                if ($response->successful()) {
                    $data = $response->json();
                } else {
                    Log::error("Error al hacer la solicitud HTTP: " . $response);
                    throw new Exception("Error al obtener los datos: " . $response->status());
                }
            } catch (Exception $e) {
                Log::error("Error al hacer la solicitud HTTP: " . $e->getMessage());
                return response()->json(['error' => 'Ha ocurrido un error al obtener los datos.'], 500);
            }
            return response()->json(
                [
                    'cuil' => $user->cuil,
                    'lastname' => $data['apellido'], // Acceder al apellido como elemento del array
                    'name' => $data['nombres'], // Acceder al nombre como elemento del array
                    'birthdate' => $data['fecha_nacimiento'], // Supongo que $ae es definido en algún lugar, de lo contrario tendrías que obtener su valor
                    'gender' => $data['sexo'],
                    'address' => $data['domicilio'],
                    'nro_address' => $data['nro_domicilio'],
                    'floor' => $data['piso'],
                    'apartment' => $data['dpto'] === null ? "" : $data['dpto'],
                    'postalCode' => $data['codigo_postal'],
                    'city' => $data['nombre_localidad'],
                    'state' => $data['nombre_provincia'],
                    'phone' => $data['telefono'],
                    'email' => $data['correo'], // Supongo que $ae es definido en algún lugar, de lo contrario tendrías que obtener su valor
                    'occupation' => $data['ocupacion'] === null ? "" : $data['ocupacion'],
                    'study' => $data['capacitacion'] === null ? "" : $data['capacitacion'],
                ]
            );
        }
    }

    public function fetch_end_pdf(Request $request)
    {
        if (Auth::check()) {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $dni = AeController::getDNI($user->cuil);
            //Log::info(json_encode($postData));
            $response = Http::withHeaders([
                'X-API-Key' => env('APP_SISTEMON_KEY'),
                'API-Token' => $token,
                'Content-Type' => 'application/json',
            ])->get($url . '/constancia/reingreso/' . $dni);
            //Log::info(json_encode($response.data.content));
            $content = $response->json()["content"];
            return response()->json(["content" => $content]);
        }

    }

    public function fetch_start_pdf(Request $request)
    {
        if (Auth::check()) {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $dni = AeController::getDNI($user->cuil);
            //Log::info(json_encode($postData));
            $response = Http::withHeaders([
                'X-API-Key' => env('APP_SISTEMON_KEY'),
                'API-Token' => $token,
                'Content-Type' => 'application/json',
            ])->get($url . '/constancia/alta/' . $dni);
            Log::info(json_encode($response));
            $content = $response->json()["content"];
            return response()->json(["content" => $content]);
        }

    }
}

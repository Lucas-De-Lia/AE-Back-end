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
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;

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
    // Obtiene las fechas de AE para el usuario 
    public function get_calendar_dates(){
        // Retrieve API URL and token from environment variables
        if (Auth::check()) {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
          
            $user = Auth::user();
            $DNI = $user->dni;

            // Make the GET request to the API
            $response = Http::withHeaders([
                'API-Token' => $token,
                'X-API-Key' => env('APP_SISTEMON_KEY'),
            ])->get($url . '/fechas/' . (string) $DNI);
            Log::info("url " .$url . '/fechas/' . (string) $DNI);
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


            return response()->json($response, Response::HTTP_OK);
        }
    }
    // Gestiona la baja de la AE para el usuario
    public function finalize_ae(Request $request){
        if (Auth::check()) {

            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $DNI = $user->dni;

            if (!Hash::check($request->input('password'), (string) $user->password)) {
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
    // Función encargada de hacer la request de alta de AE
    public static function start($postData){
        try {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $response = Http::withHeaders([
                'API-Token' => $token,
                'X-API-Key' => env('APP_SISTEMON_KEY'),
            ])->post($url . '/agregar', $postData);
            return $response;
        } catch (Exception $e) {
            return response()->json('Error al registrar AE', 500);
        }
    }
    // Une las imagenes de los dni en una.
    public static function merge_dni_photos($photo){
        $manager = ImageManager::gd();
        $resized = $manager->read($photo)->resize(1280, 720, function ($constraint) {
            $constraint->aspectRatio();
        });
        return $resized->toWebp();
    }
    // Gestiona el registro del usuario como un nuevo AE
    //! REVISAR PORQUE EL TEMA DE LA IMAGEN PUEDE LLEGAR A FALLAR!!!
    public static function register_ae(Request $request){   
        $nro_dni = $request->dni;
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
                'ocupacion' => 'NC', 
                'capacitacion' => 'NC',
                'telefono' => $request->phone,
                'correo' => $request->email, // ES EL DEL USER
            ],
            'ae_estado' => ['fecha_ae' => $request->startdate],
        ];
        $response = AeController::start($postData);
        $url = env("API_URL_AE");
        $token = env("API_TOKEN_AE");
        $image = AeController::merge_dni_photos($request->dniImg);
        
        $response2 = Http::withHeaders([
            'API-Token' => $token,
            ])
            ->attach('file', $image, 'dni_' . $nro_dni . '.webp')
            ->post("{$url}" . '/importacion/archivos', [['name' => 'dni', 'contents' => $nro_dni]]);

        return ['1' => $response->body() , '2' => $response2->body()];
    }
    // Gestiona el alta de un usuario ya AE almenos 1 vez para una nueva AE 
    public function start_ae_n(Request $request){
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
        ]);
        if (Auth::check()) {

            $user = Auth::user();
            //TODO VER SI alguno tiene ya datos cargados y reutilizar si no existen
            $newName = $request->firstname . ", " . $request->lastname;
            if ($user->name !== $newName) {
                // El nombre cambió
                $partes = explode(", ", $user->name);
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
                    'nro_dni' => $user->dni,
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
                    'ocupacion' => 'NC', 
                    'capacitacion' => 'NC',
                    'telefono' => $request->phone,
                    'correo' => $user->email, // ES EL DEL USER
                ],
                'ae_estado' => ['fecha_ae' => $request->startdate],
            ];
            $response = AeController::start($postData);
            return response()->json(["content" => $response->json()]);
        }
    }
    // Obtiene de la API de AE los datos del usuario
    public function fetch_user_data(Request $request){
        if (Auth::check()) {
            $user = Auth::user();
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $dni = $user->dni;
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
                    'dni' => $user->dni,
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
    // Obtiene de la API el  certificado en pdf de fin de AE.
    public function fetch_end_pdf(Request $request){
        if (Auth::check()) {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $dni = $user->dni;          
            $response = Http::withHeaders([
                'X-API-Key' => env('APP_SISTEMON_KEY'),
                'API-Token' => $token,
                'Content-Type' => 'application/json',
            ])->get($url . '/constancia/reingreso/' . $dni);
            
            $content = $response->json()["content"];
            return response()->json(["content" => $content]);
        }

    }
    // Obtiene de la API el certificado en pdf de inicio de AE
    public function fetch_start_pdf(Request $request){
        if (Auth::check()) {
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $user = Auth::user();
            $dni = $user->dni;
            $response = Http::withHeaders([
                'X-API-Key' => env('APP_SISTEMON_KEY'),
                'API-Token' => $token,
                'Content-Type' => 'application/json',
            ])->get($url . '/constancia/alta/' . $dni);
            $content = $response->json()["content"];
            return response()->json(["content" => $content]);
        }

    }
    //
    public function fetch_history(Request $request){
        if (Auth::check()) {
            $user = Auth::user();
            $url = env("API_URL_AE");
            $token = env("API_TOKEN_AE");
            $dni = $user->dni;
            $page_size = 10;
            //Si envie datos concretos de paginado los seteo
            if(!empty($request->page_size)){
                $page_size = $request->page_size;
            }

            try {
                $response = Http::withHeaders([
                    'API-Token' => $token,
                    'Content-Type' => 'application/json',
                    'X-API-Key' => env('APP_SISTEMON_KEY'),
                ])->post($url . '/historial/' . $dni . '?page=' . $request->query('page'), [
                    'page_size' => $page_size,
                ]);

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
            return response()->json($data);
        }
    }

public function loadSurvey(Request $request){
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    try {
        $request->request->remove('data');
        // Validación de datos
        $validatedData = $request->validate([
            'frecuencia' => 'required|string|in:Diaria,Semanal,Mensual',
            'asistencia' => 'required|string|in:Solo,Acompaniado',
            'horas' => 'required|integer|between:1,24',
            'maquinasTradicionales' => 'required|boolean',
            'ruletaElectronica' => 'required|boolean',
            'carteados' => 'required|boolean',
            'ruletaAmericana' => 'required|boolean',
            'dados' => 'required|boolean',
            'bingo' => 'required|boolean',
            'socioClubJugadores' => 'required|boolean',
            'conocePlataformasOnline' => 'required|boolean',
            'utilizaPlataformasOnline' => 'required|boolean',
            'problemasAutocontrol' => 'required|boolean',
            'deseaRecibirInfo' => 'required|boolean',
        ]);
        $validatedData['dni'] = Auth::user()->dni;
        // Solicitud HTTP
        $response = Http::withHeaders([
            'X-API-Key' => env('APP_SISTEMON_KEY'),
            'API-Token' => env("API_TOKEN_AE"),
            'Content-Type' => 'application/json',
        ])->post(env("API_URL_AE") . '/encuesta', $validatedData);
        if ($response->successful()) {
            $user = Auth::user();
            DB::table('users')
            ->where('id', $user->id)
            ->update(['respondio_encuesta' => true]);
            return response()->json($response->json());
        } else {
        // Manejar error específico
        return response()->json([
            'message' => 'Error desde microservicio',
            'status' => $response->status(),
            'body' => $response->body(),
        ], $response->status());
        }
    } catch (ValidationException $e) {
        Log::error($e->getMessage());
        return response()->json([
            'message' => 'Errores de validación',
            'errors' => $e->errors()
        ], 422);

    } catch (RequestException $e) {
        Log::error($e->getMessage());
        return response()->json([
            'message' => 'Error al comunicarse con el microservicio',
            'error' => $e
        ], 500);

    } catch (Exception $e) {
        Log::error($e->getMessage());
        return response()->json([
            'message' => 'Ocurrió un error inesperado',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
//somos.casino
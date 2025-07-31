<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller {
    public function __construct(){
        $this->middleware('throttle:api'); // Limita las request a 1000 por seg
        // Funciones a las cuales no es necesario estar verificado para realizar.
        $this->middleware(['verified'], ['except' => ['login', 'refresh', 'logout', 'register', 'email_send_code', 'forgot_password', 'verify_code_email', 'verify_link_email', 'merge_dni_photos']]);
        // Funciones a las cuales no se debe estar logeado para realizar
        $this->middleware(['auth:sanctum'], ['except' => ['login', 'register', 'verify_link_email', 'forgot_password', 'reset_password', 'merge_dni_photos']]);
    }
    // Gestiona el inició de seccion
    public function login(Request $request){
        $request->validate([
            'dni' => 'required|string',
            'password' => 'required|string'
        ]);
        if (Auth::attempt(['dni' => $request->dni, 'password' => $request->password])) {
            $user = $request->user(); // Obtengo el usuario
            $user->tokens()->delete(); // botto el token viejo
            $token = $user->createToken('token-name')->plainTextToken;
            //check encuesta
            $url = env("API_URL_AE");
            $apiToken = env("API_TOKEN_AE");
            $respondioEncuesta = true;
            try{
                $response = Http::withHeaders([
                    'API-Token' => $apiToken,
                    'X-API-Key' => env('APP_SISTEMON_KEY'),
                    ])->get("$url/respondio-encuesta",[
                        'dni' => $user->dni,
                    ]);
                
                if($response->successful()){
                    $respondioEncuesta = $response->json('respondioEncuesta');
                }
            }catch (Exception $e){
                Log::error('Error al consultar microservicio de encuesta: ' . $e->getMessage());
            }
            return response()->json([
                'user' => [ // devuelvo datos del usuario
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'dni' => $user->dni,
                    'ae' => AeController::$AE['NON_AE'],
                    'respondioEncuesta' => $respondioEncuesta,
                ],
                'authorization' => [ // devuelvo datos de auth
                    'token' => $token,
                    'type' => 'Bearer ',
                    'X_CSRF_TOKEN' => csrf_token()
                ]
            ], Response::HTTP_CREATED);
        }
        return response()->json([
            'message' => 'Invalid credentials',
        ], Response::HTTP_UNAUTHORIZED);
    }

    // Registra un nuevo usuario   
    //?YA ME MANDA EL DNI EL FRONT, LO DEBE OBTENER ANTES DE HACER LA SOLICITUD, PERO DEBO QUITAR EL CUIL Y QUE ME MANDE SOLO EL DNI
    public function register(Request $request){
        // Validate the incoming request data
        $request->validate([
            'dni' => [
                'required',
                'string',
                'unique:users',
            ],
            'firstname' => 'required|string|max:150',
            'lastname' => 'required|string|max:100',
            'birthdate' => 'required|date',
            'gender' => 'required',
            'address' => 'required|string|max:100',
            'address_number' => 'required|integer',
            'floor' => 'nullable|string|max:5',
            'apartament' => 'nullable|string|max:5',
            'postalcode' => 'required|string|max:10',
            'city' => 'required|string|max:200',
            'province' => 'required|string|max:200',
            'phone' => 'required|string|max:200',
            'startdate' => 'required|date',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'renewvaldate' => 'nullable|date',
        ]);
        //verificar los datos
        
        // $token = $this->getTokenRENAPER();
        // $datosRenaper = $this->sendImagenDNIRENAPER($request->dni, $token);
        // $check = $this->checkData($request,$datosRenaper);
        // if(!$check->empty()){
        //    return response()->json(['error' => $check], 500);
        // }

        // Create user data
        $data = [
            'name' => implode(', ', [$request->firstname, $request->lastname]),
            'dni' => $request->dni,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];
        DB::beginTransaction();
        try {
            // Create a new user
            $user = User::create($data);
            // Envia una requeset de regisro
            $ae = AeController::register_ae($request);
            Log::info($ae);
            event(new Registered($user));
            // Commit
            DB::commit();
            //crea el token
            $token = $user->createToken('token-name')->plainTextToken;

            // Return a JSON response
            return response()->json([ //devuelvo resutlado
                'message' => 'User created successfully',
                'authorization' => [
                    'token' => $token,
                    'type' => 'Bearer ',
                    'X_CSRF_TOKEN' => csrf_token()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Rollback the database transaction and return an error response
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Gestiona el fin de la sección
    public function logout(Request $request){
        // verifica si esta auth
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
            return response()->json([
                'message' => 'Successfully logged out',
            ], Response::HTTP_OK);
        }
        return response()->json([
            'message' => 'Unauthenticated',
        ], Response::HTTP_UNAUTHORIZED);
    }

    // Gestiona el mantener la sección abierta
    public function refresh(){
        // Sin uso actualmente
        if (Auth::check()) {
            $user = Auth::user();
            $url = env("API_URL_AE");
            $apiToken = env("API_TOKEN_AE");
            $respondioEncuesta = true;
            try{
                $response = Http::withHeaders([
                    'API-Token' => $apiToken,
                    'X-API-Key' => env('APP_SISTEMON_KEY'),
                    ])->get("$url/respondio-encuesta",[
                        'dni' => $user->dni,
                    ]);
                
                if($response->successful()){
                    $respondioEncuesta = $response->json('respondioEncuesta');
                }
            }catch (Exception $e){
                Log::error('Error al consultar microservicio de encuesta: ' . $e->getMessage());
            }
            return response()->json([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'dni' => $user->dni,
                    'ae' => AeController::$AE['NON_AE'],
                    'respondioEncuesta' => $respondioEncuesta,
                ],
            ], Response::HTTP_CREATED);
        }
        return response()->json([
            'message' => 'Unauthenticated',
        ], Response::HTTP_UNAUTHORIZED);
    }

    static private function str_random($length = 10){
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    static private function sendImagenDNIRENAPER($img,$token){
        $response = Http::withHeaders([
            'Authorization' => 'Bearer' . $token
        ])->post('https://apirenaper.idear.gov.ar/CHUTROFINAL/servicios/prod/documento.php', [
            'front' => $img,
        ]);
        $data = null;
        if ($response->successful()) {
            $data = $response->json();
        } else {
            Log::error("Error al hacer la solicitud HTTP: " . $response);
            throw new Exception("Error al obtener los datos: " . $response->status());
        }
        return $data;
    }

    static private function getTokenRENAPER(){
        $response = Http::asForm()->post('https://apirenaper.idear.gov.ar/CHUTROFINAL/servicios/prod/Autorizacion/token.php',[
            'username' => env('RENAPER_USERNAME'),
            'password'=> env('RENAPER_PASSWORD')
        ]);
        if ($response->successful() && $response->codigo == 200  ) {
            return $response->token;
        } else {
            Log::error("Error al hacer la solicitud HTTP: " . $response);
            throw new Exception("Error al obtener los datos: " . $response->status());
        }
    }

    static private function checkData($data , $renaper){
        // esto se puede hacer mejor con un for y usando keys, tengo ganas? No..
        $document = $renaper->document;
        $errors = Array();
        if(!$document->documentNumber->value == $this->getDNI($data->cuil) && !$this->check($document->documentNumber)){
            $errors[] = "DNI, no coincide";
        }
        if(!$document->Name->value == $data->firstname && !$this->check($document->Name)){
            $errors[] = "Nombre, no coincide";
        }
        if(!$document->Surname->value == $data->lastname && !$this->check($document->Surname)){
            $errors[] = "Apellido, no coincide";
        }
        if(!$document->Sex->value == $data->gender && !$this->check($document->Sex)){
            $errors[] = "Genero, no coincide";
        }
        if(!$document->BirthDate->value ==formatDate($data->birthdate) && !$this->check($document->BirthDate)){
            $errors[] = "Fecha de nacimiento, no coincide";
        }
        return $errors;
    }

    private static function getDNI($cuil){
        preg_match('/-(\d+)-/', $cuil, $matches);
        return (int) ($matches[1]);
    }

    private static function formatDate($dateString) {
        $date = new DateTime($dateString);
        return $date->format('d/m/Y');
    }

    private static function check($field){
        return ( $field->check == 1 ||  $field== -1);
    }
}
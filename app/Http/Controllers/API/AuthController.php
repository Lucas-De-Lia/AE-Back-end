<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;

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
            'cuil' => 'required|string',
            'password' => 'required|string'
        ]);

        if (Auth::attempt($request->only(['cuil', 'password']))) {
            $user = $request->user(); // Obtengo el usuario
            $user->tokens()->delete(); // botto el token viejo
            $token = $user->createToken('token-name')->plainTextToken;
            return response()->json([
                'user' => [ // devuelvo datos del usuario
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'cuil' => $user->cuil,
                    'ae' => AeController::$AE['NON_AE']
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
    public function register(Request $request){
        // Validate the incoming request data
        $request->validate([
            'cuil' => [
                'required',
                'string',
                'unique:users',
                'regex:/^\d{2}-\d{8}-\d{1}$/',
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
            //'dni1' => 'required|file|max:2048',
            //'dni2' => 'required|file|max:2048',
        ]);
        // Create user data
        $data = [
            'name' => implode(', ', [$request->firstname, $request->lastname]),
            'cuil' => $request->cuil,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];
        DB::beginTransaction();
        try {
            // Create a new user
            $user = User::create($data);
            // Envia una requeset de regisro
            $ae = AeController::register_ae($request);
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
            return response()->json([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'cuil' => $user->cuil,
                    'ae' => AeController::$AE['NON_AE']
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
}

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

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware('throttle:api');

        // Apply 'verified' middleware to all methods except the specified ones.
        $this->middleware(['verified'], ['except' => ['login', 'refresh', 'logout', 'register', 'email_send_code', 'forgot_password', 'verify_code_email', 'verify_link_email', 'merge_dni_photos']]);

        // Apply 'auth:sanctum' middleware to all methods except the specified ones.
        $this->middleware(['auth:sanctum'], ['except' => ['login', 'register', 'verify_link_email', 'forgot_password', 'reset_password', 'merge_dni_photos']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'cuil' => 'required|string',
            'password' => 'required|string'
        ]);

        if (Auth::attempt($request->only(['cuil', 'password']))) {
            $user = $request->user();
            $user->tokens()->delete();
            $token = $user->createToken('token-name')->plainTextToken;

            return response()->json([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'cuil' => $user->cuil,
                    'ae' => AeController::$AE['NON_AE']
                ],
                'authorization' => [
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

    /**
     * Register a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
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
            'gender' => 'required', // Consider providing more specific validation
            'address' => 'required|string|max:100',
            'address_number' => 'required|integer',
            'floor' => 'nullable|string|max:5',
            'apartament' => 'nullable|string|max:5',
            'postalcode' => 'required|string|max:10',
            'city' => 'required|string|max:200',
            'province' => 'required|string|max:200',
            'phone' => 'required|string|max:200',
            'startdate' => 'required|date',
            'occupation' => 'nullable|string|max:4', // Consider providing more specific validation
            'study' => 'nullable|string|max:4',  // Consider providing more specific validation
            'email' => 'required|string|email|max:255|unique:users|unique:email_to_verify',
            'password' => 'required|string|min:8',
            'renewvaldate' => 'nullable|date',
            'dni1' => 'required|file|max:2048',
            'dni2' => 'required|file|max:2048',
        ]);
        // Create user data
        $data = [
            'name' => implode(', ', [$request->firstname, $request->lastname]),
            'cuil' => $request->cuil,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];

        // Begin a database transaction
        DB::beginTransaction();
        try {
            // Create a new user
            $user = User::create($data);

            // Create a new email verification record
            /*
            $emailToVerify = new EmailToVerify([
                'email' => $request->email,
                'code' => self::str_random(10),
            ]);
            $user->emailToVerify()->save($emailToVerify);
            */
            // Send the verification email event(register)->email
            // Register an AE (whatever that stands for) - consider providing more information in the comment
            $ae = AeController::register_ae($request);
            event(new Registered($user));
            // Commit the database transaction
            DB::commit();

            // Return a JSON response
            return response()->json([
                'message' => 'User created successfully',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Rollback the database transaction and return an error response
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function logout(Request $request)
    {
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


    public function refresh()
    {
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

    static private function str_random($length = 10)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}

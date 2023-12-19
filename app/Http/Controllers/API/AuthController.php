<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;


function str_random($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $randomString;
}
class AuthController extends Controller
{
    public function __construct(){
        $this->middleware('ThrottleRequestsByIP:100,1');
        $this->middleware( ['expired','auth:sanctum'] , ['except' => ['login', 'register']]);
    }
        
    public function login(Request $request){
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string'
        ]);
        $credentials = $request->only('name', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            Auth::user()->tokens()->delete();
            return response()->json([
                'user' => $user,
                'authorization' => [
                    'token' => $user->createToken('ApiToken',['expires' => now()->addMinutes(60)])->plainTextToken,
                    'type' => 'bearer',
                ]
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    public function register(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'email' => 'required|string|email|max:255|unique:users'
        ]);

        $data = [
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'email' => $request->email
             //'confirmation_code' => str_random(10)
        ];
        
        $user = User::create($data);
        
        /*
        Mail::send('emails.confirmation_code',$data, function($message) use ($data) {
            $message->to($data['email'], $data['name'])->subject('Por favor confirma tu correo');
        });
        */        
        
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    public function logout(){
        Auth::user()->tokens()->delete();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh(){
        return response()->json([
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }



    public function verifyCode(Request $request){
        $request->validate([
            'code' => 'required|string',
        ]);
        $user = User::where('confirmation_code', $request->code)->first();

        if (! $user)
            return  response()->json([
            'message' => 'Invalid confirmation code',
        ], 404);

        $user->confirmed = true;
        $user->confirmation_code = null;
        $user->save();

        // Lógica para verificar el código aquí

        return response()->json([
            'message' => 'Code verified successfully',
        ]);
    }
}


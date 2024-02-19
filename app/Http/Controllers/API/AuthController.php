<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmationCode;
use App\Models\EmailToVerify;
use App\Mail\ConfirmationLink;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api'); // throttle , mata las peticiones de cualquier usuario que quiera colmar el server de consultas, si mal no recuerdo eran 100 request/min el maximo permitido.
        $this->middleware(['verified'], ['except' => ['login', 'register', 'email_send_code', 'verify_code_email','verify_link_email']]);
        $this->middleware(['auth:sanctum'], ['except' => ['login', 'register','verify_link_email','forgot_password','reset_password']]);
        //$this->middleware(['signed'], ['except' => ['login', 'register', 'logout', 'refresh']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'cuil' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = $request->only(['cuil', 'password']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user->tokens()->delete();
            $token = $user->createToken('token-name')->plainTextToken;
            //Mail::to($user->email)->send(new ConfirmationCode("2131", "name"));
            return response()->json([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'cuil' => $user->cuil
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

    // Esta parte es el registro
    public function register(Request $request)
    {
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
            'occupation'        => 'nullable|string|max:4', // VER COMO HACERLO
            'study'     => 'nullable|string|max:4',  // VER COMO HACERLO
            'email' => 'required|string|email|max:255|unique:users|unique:email_to_verify',
            'password' => 'required|string|min:8',
            'renewvaldate' => 'nullable|date'
        ]);

        //creo usuario
        $data = [
            'name' => $request->firstname . ", " . $request->lastname,
            'cuil' => $request->cuil,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];

        $user = User::create($data);

        // creo abst de emailverify

        $emailToVerify = new EmailToVerify([
        'email' => $request->email,
        'code' => self::str_random(10),
        ]);
        $user->emailToVerify()->save($emailToVerify);

        //envio el mail de verificacion
        Mail::to($request->email)->send(new ConfirmationLink($user->name,$emailToVerify->id,  $emailToVerify->code));

        $ae = AeController::register_ae($request);
        //send verify email but with other function
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'ae' =>  $ae
        ], Response::HTTP_CREATED);
    }

    public function logout()
    {
        if (Auth::check()) {
            //Auth::logout();
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
                'user' => $user,
                'authorization' => [
                    'token' => Auth::refresh(),
                    'type' => 'bearer',
                    'expires_in' => Auth::factory()->getTTL() * 60, // Tiempo de expiración del nuevo token en segundos
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


    //Envio de codigos de verificacion
    // Crea una peticion de cambio de email , enviandole un mail a la nueva direccion. (esto es si el usuario esta logeado y registrado)
    public function email_send_code(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string'
        ]);
        if (Auth::check()) {
            $user = Auth::user();
            if (Hash::check($request->input('password'), $user->password)) {

                $existingUser = User::where('email', $request->email)->first();

                if ($existingUser && $existingUser->id !== $user->id) {
                    // Esto sirve tanto si el email es de otro como si es del mismo usuarios
                    return response()->json([
                        'message' => 'Email already registered',
                    ], Response::HTTP_CONFLICT);
                }


                if (!$user->emailToVerify) {
                    // no tiene
                    $emailToVerify = new EmailToVerify([
                    'email' => $request->email,
                    'code' => self::str_random(10),
                    ]);
                    $user->emailToVerify()->save($emailToVerify);
                } else {
                    // El usuario ya tiene un modelo EmailToVerify asociado.
                    $emailToVerify = $user->emailToVerify;
                    $emailToVerify->email = $request->email; ;
                    $emailToVerify->code = self::str_random(10);
                    $emailToVerify->save();
                }

                Mail::to($request->email)->send(new ConfirmationCode($emailToVerify->code, $user->name));

                return response()->json([
                    'message' => 'Email sent',
                ], Response::HTTP_OK);
            }
        }
        return response()->json([
            'message' => 'Unauthenticated',
        ], Response::HTTP_UNAUTHORIZED);
    }
    // verifica el email con el codigo obtenido.
    public function verify_code_email(Request $request)
    {
        $request->validate(['code' => 'required|regex:/^[A-Z0-9]{10}$/', 'email' => 'required|string|email|max:255']);
        $code = $request->code;
        if (Auth::check()) {
            $user = Auth::user();
            $emailToVerify = $user->emailToVerify;

            if (!$emailToVerify || $emailToVerify->code !== $request->code) {
                return response()->json(['error' => 'Invalid code'], Response::HTTP_BAD_REQUEST);
            }

            $expirationTime = now()->subMinutes(5);
            if ($emailToVerify->updated_at < $expirationTime) {
                return response()->json(['error' => 'Verification code has expired'], Response::HTTP_BAD_REQUEST);
            }

            if ($request->user()->markEmailAsVerified()) {
                $user->email = $emailToVerify->email;
                $emailToVerify->delete();
                $user->save();
                //event(new Verified($user));
                return response()->json(['message' => 'Email verified'], Response::HTTP_OK);
            }else {
                return response()->json(['error' => 'Failed to mark email as verified'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } else {
            return response()->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }
    }
    //esta verificacion hace lo mismo pero envia un link ( esta es para el registro )
    public function verify_link_email(Request $request)
    {
        $request->validate([
            'hash' => 'required|regex:/^[A-Z0-9]{10}$/',
            'id' => 'required|string|max:255']);

        $code = $request->query('hash');
        $id = $request->query('id');

        //$user = Auth::user();
        $emailToVerify = EmailToVerify::find($id);

        if(!$emailToVerify){
            // no existe una verifycacion de email para este email o ya se verifico o nunca se creo la verificacion para este.
            return response()->json(['error' => 'Invalid email verification link'], Response::HTTP_BAD_REQUEST);
        }
        if ($code !== $emailToVerify->code) {
            return response()->json(['error' => 'Invalid code'], Response::HTTP_BAD_REQUEST);
        }

        $user = $emailToVerify->user;

        if ($user->markEmailAsVerified()) {
            $user->email = $emailToVerify->email;
            $emailToVerify->delete();
            $user->save();
            //event(new Verified($user));
            return response()->json(['message' => 'Email verified'], Response::HTTP_OK);
        }
        return response()->json(['error' => 'Email verification failed'], Response::HTTP_INTERNAL_SERVER_ERROR);

    }


    // genera una peticion de cambio de contraseña , solo 1 cada 3 minutos
    public function forgot_password(Request $request)
    {
        $request->validate([
            'cuil' => [
                'required',
                'string',
                'regex:/^\d{2}-\d{8}-\d{1}$/',
                'exists:users,cuil'
            ]
        ]);

        $user = User::where('cuil', $request->cuil)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid cuil provided.'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->forgotpassword && $user->forgotpassword->created_at->gt(now()->subMinutes(5))) {
            return response()->json(['message' => 'Wait 5 minutes and try again'], Response::HTTP_BAD_REQUEST);
        }

        $TOKEN = Str::random(20);
        $passwordReset = $user->forgotpassword()->updateOrCreate(
            ['email' => $request->email],
            ['token' => Hash::make($TOKEN)]
        );

        // Send email with the new password
        Mail::to($user->email)->send(new ForgotPassMail($user->name, $TOKEN));

        return response()->json(['message' => 'Password reset successful. Check your email.'], Response::HTTP_OK);
    }
    // el commit de la peticion de cambio de contraseña , asigna la nueva y borra la paticion.
    public function reset_password(Request $request)
    {
        $request->validate([
            'cuil' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('cuil', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $passwordReset = $user->forgotpassword();

        if (!$passwordReset) {
            return response()->json(['message' => 'Password reset token not found.'], Response::HTTP_NOT_FOUND);
        }

        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json(['message' => 'Invalid token.'], Response::HTTP_UNAUTHORIZED);
        }

        // Update user's password
        $user->password = Hash::make($$request->password);
        $user->save();

        // Delete the password reset token
        $passwordReset->delete();

        return response()->json(['message' => 'Password reset successful. Check your email for the new password.'], Response::HTTP_OK);
    }
    //Change password , si estas logado y conoces tu  ocntraseña anterior
    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
        ]);
        if (Auth::check()) {
            $user = Auth::user();
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
            }
            $user->forceFill([
                'password' => Hash::make($request->input('new_password')),
            ]);
            $user->save();
            Auth::logout();
            $user->tokens()->delete();
            return response()->json(['message' => 'Password changed successfully'], Response::HTTP_OK);
        }

        return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}

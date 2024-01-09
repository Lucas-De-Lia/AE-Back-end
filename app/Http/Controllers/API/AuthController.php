<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmationCode;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Password;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api');
        $this->middleware(['verified'], ['except' => ['login', 'register']]);
        $this->middleware(['auth:sanctum'], ['except' => ['login', 'register']]);
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
            Mail::to($user->email)->send(new ConfirmationCode("2131"));
            return response()->json([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'cuil' => $user->cuil
                ],
                'authorization' => [
                    'token' => $token,
                    'type' => 'Bearer ',
                    'X-CSRF-TOKEN' => csrf_token()
                ]
            ]);
        }


        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:users',
            'cuil' => [
                'required',
                'string',
                'unique:users',
                'regex:/^\d{2}-\d{8}-\d{1}$/',
            ],
            'password' => 'required|string|min:8',
            'email' => 'required|string|email|max:255|unique:users'
        ]);

        $data = [
            'name' => $request->name,
            'cuil' => $request->cuil,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];
        // TODO ver que hacer si el usuario ya existe
        $user = User::create($data);

        /**Mail::send('emails.confirmation_code', $data, function ($message) use ($data) {
            $message->to($data['email'], $data['name'])->subject('Por favor confirma tu correo');
        });*/


        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    public function logout()
    {
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
            return response()->json([
                'message' => 'Successfully logged out',
            ]);
        }
    }

    public function refresh()
    {
        return response()->json([
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    static private function str_random($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    //verify 2
    public function email_send_code(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $code = AuthController::str_random(6);
        Session::put('verifycode', $code);
        Mail::to($request->email)->send(new ConfirmationCode($code));
        return response()->json(["msg" => 'code send']);
    }
    public function verify_code_email(Request $request)
    {
        $request->validate(['code' => 'required|regex:/^[a-zA-Z0-9]{6}$/']);
        $code = $request->code;
        $realcode = Session::get('verifycode', '');
        if ($code == $realcode) {
            return response()->json(['msg' => 'Código válido']);
        } else {
            return response()->json(['msg' => 'Código no válido']);
        }
        Session::forget('verifycode');
    }


    //verify 1
    public function email_verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return response()->json([
            'success' => "Email verified"
        ]);
    }
    public function verification_notification(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return response()->json([
            'message' => 'Verification link sent!'
        ]);
    }



    public function forgot_password(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT ? response()->json(['status' => __($status)]) : response()->json(['status' => 'Password reset email send error']);
    }

    public function reset_password(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|min:8|confirmed']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFIll(['password' => Hash::make($password)]);
                $user->save();
                event(new PasswordReset(($user)));
            }

        );

        return $status === Password::PASSWORD_RESET ? response()->json(['status' => __($status)]) : response()->json(['status' => 'Password reset error']);
    }

    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password|confirmed',
        ]);
        if (Auth::check()) {
            $user = Auth::user();
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json(['status' => 'Current password is incorrect'], 401);
            }
            $user->forceFill([
                'password' => Hash::make($request->input('new_password')),
            ]);

            $user->save();
            //cierro session

            Auth::user()->tokens()->delete();
            return response()->json(['status' => 'Password changed successfully']);
        }
        return response()->json(['status' => 'Auth check error'], 401);
    }
}

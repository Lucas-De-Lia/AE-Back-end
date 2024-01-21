<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmationCode;
use App\Models\EmailToVerify;
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
        $this->middleware('throttle:api');
        $this->middleware(['verified'], ['except' => ['login', 'register', 'email_send_code', 'verify_code_email']]);
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

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cuil' => [
                'required',
                'string',
                'unique:users',
                'regex:/^\d{2}-\d{8}-\d{1}$/',
            ],
            'password' => 'required|string|min:8',
            'email' => 'required|string|email|max:255|unique:users|unique:email_to_verify'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'name' => $request->name,
            'cuil' => $request->cuil,
            'password' => Hash::make($request->password),
            'email' => $request->email,
        ];

        $user = User::create($data);
        //send verify email but with other function
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
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
                    return response()->json([
                        'message' => 'Email already registered',
                    ], Response::HTTP_CONFLICT);
                }
                $newEmail = EmailToVerify::firstOrNew(['email' => $request->email]);
                if (!$newEmail->exists) {
                    $newEmail->code = self::str_random(6);
                    $newEmail->save();
                }
                Mail::to($request->email)->send(new ConfirmationCode($newEmail->code, $user->name));
                return response()->json([
                    'message' => 'Email sent',
                ], Response::HTTP_OK);
            }
        }
        return response()->json([
            'message' => 'Unauthenticated',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function verify_code_email(Request $request)
    {
        $request->validate(['code' => 'required|regex:/^[A-Z0-9]{6}$/', 'email' => 'required|string|email|max:255']);
        $code = $request->code;
        if (Auth::check()) {
            $newEmail = EmailToVerify::where('email', $request->email)->first();
            if ($code == $newEmail->code) {
                $user = Auth::user();
                if ($request->user()->hasVerifiedEmail()) {
                    return response()->json(['error' => 'Email already verified'], Response::HTTP_BAD_REQUEST);
                }
                if ($request->user()->markEmailAsVerified()) {
                    $user->email = $newEmail->email;
                    $user->save();
                    $newEmail->delete();
                    //event(new Verified($user));
                    return response()->json(['message' => 'Email verified'], Response::HTTP_OK);
                }
            } else {
                return response()->json(['error' => 'Invalid code'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }
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

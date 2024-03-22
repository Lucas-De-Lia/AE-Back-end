<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class PasswordsController extends Controller
{
    public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware(['throttle:api']);
        $this->middleware(['auth:sanctum'], ['except' => ['forgot_password', 'forgot_password']]);

    }
    public function forgot_password(Request $request)
    {
        $request->validate(['cuil' => 'exists:users,cuil']);
        $user = User::whereCuil($request->cuil)->first();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['email' => __('Error')], Response::HTTP_BAD_REQUEST);
        }
        if ($user) {
            $status = Password::sendResetLink($user->only('email'));
            Log::info($status);
            return $status == Password::RESET_LINK_SENT
                ? response()->json(['status' => __($status)], Response::HTTP_OK)
                : response()->json(['status' => __($status)], Response::HTTP_BAD_REQUEST);
        }


    }

    public function reset_password(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'cuil' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('cuil', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
                //->setRememberToken(Str::random(100));
    
                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => __($status)], Response::HTTP_OK)
            : response()->json(['email' => [__($status)]], Response::HTTP_BAD_REQUEST);
    }

    //Change password , si estas logado y conoces tu  ocntraseña anterior
    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
        }
        $user->forceFill([
            'password' => Hash::make($request->input('new_password')),
        ]);
        $user->save();
        //Auth::logout();
        $user->tokens()->delete();
        return response()->json(['message' => 'Password changed successfully'], Response::HTTP_OK);

    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

class PasswordsController extends Controller
{
    public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware(['throttle:api']);
        // Apply 'verified' middleware to all methods except the specified ones
        //$this->middleware(['verified']);
        //$this->middleware(['auth:sanctum'], ['except' => ['forgot_password', 'forgot_password']]);

    }
    public function forgot_password(Request $request)
    {
        $request->validate(['cuil' => 'exists:users,cuil']);
        $user = User::whereCuil($request->cuil)->first();
        if ($user && $user->hasVerifiedEmail()) {
            $status = Password::sendResetLink($user->only('email'));
            return $status == Password::RESET_LINK_SENT
                ? response()->json(['status' => __($status)], Response::HTTP_OK)
                : response()->json(['email' => __($status)], Response::HTTP_BAD_REQUEST);
        } elseif (!$user) {
            return response()->json(['cuil' => __('Error')], Response::HTTP_BAD_REQUEST);
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
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => __($status)], Response::HTTP_OK)
            : response()->json(['email' => [__($status)]], Response::HTTP_BAD_REQUEST);
    }
}

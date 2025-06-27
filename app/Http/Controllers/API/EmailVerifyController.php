<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailToVerify;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmailVerifyController extends Controller
{
    public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware(['throttle:api'], ['except' => ["email_send_by_root"]]);
        // Apply 'verified' middleware to all methods except the specified ones.
        $this->middleware(['verified'], ['except' => ['email_verify', 'email_send','email_send_by_root']]);
        $this->middleware('auth:sanctum', ['except' => ['email_send_by_root']]);

    }

    // Verifica el email del usuario logeado
    public function email_verify(EmailVerificationRequest $request){
        try {
            DB::beginTransaction();
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'message' => 'Email already Verified'
                ]);
            }
            $request->fulfill();
            $request->user()->markEmailAsVerified();
            $request->user()->save();
            // El evento es muy importante
            event(new Verified($request->user()));
            DB::commit();
            return response()->json([
                'message' => "Email verified"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }

    }
    // Envia los emails de verificación de email
    public function email_send(Request $request){
        $user = $request->user();
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' =>'User already have verified email'], Response::HTTP_BAD_REQUEST);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification send'], Response::HTTP_OK);
    }
    // gestiona el cambio de email con un email nuevo y su password, envia un email.
    public function email_change(Request $request){
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'current_password' => 'required'
        ]);
        $user = $request->user();
        if (!Hash::check($request->input('current_password'), $user->password)) {
            // Si la contraseña es erronea
            return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
        }
        if ($user->email != $request->input('email')) {
            //TODO: MODIFICAR, PARA QUE SE ENVIE UN MAIL DE CONFIRMACION DE CAMBIO Y QUE SOLO SI SE VERIFICA ESE EMAIL SE PRODUCE EL CAMBIO
            //? aca creo que solo deberia enviar el mail de notificacion, pero con una funcion distinta
            //? porque esta usa el email actual del user, la mia deberia recibir como parametro el email y hacer los cambios luego de la validación
            //? en el front end deberia agregar una nueva ruta o ver si la actual me funciona
            $user->forceFill(["email" => $request->input('email'), 'email_verified_at' => null]);
            $user->save();
            $user->sendEmailVerificationNotification();
        }else{
            return response()->json(['message' => 'Can not use your currect email '],Response::HTTP_BAD_REQUEST);
        }
        return response()->json(['message' => 'Verification send successfully'], Response::HTTP_OK);
    }
    // Envia el email de verificación, solo gestionado por el root
    public function email_send_by_root(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' =>'User already have verified email'], Response::HTTP_BAD_REQUEST);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification send'], Response::HTTP_OK);
    }
}
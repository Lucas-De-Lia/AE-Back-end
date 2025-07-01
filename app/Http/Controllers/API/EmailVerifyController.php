<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\EmailChangeVerification;
use App\Mail\VerifyNewEmail;
use App\Models\EmailToVerify;
use App\Models\PendingEmailChange;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailVerifyController extends Controller
{
    public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware(['throttle:api'], ['except' => ["email_send_by_root"]]);
        // Apply 'verified' middleware to all methods except the specified ones.
        $this->middleware(['verified'], ['except' => ['email_verify', 'email_send','email_send_by_root','email_change','confirmEmailChange']]);
        $this->middleware('auth:sanctum', ['except' => ['email_send_by_root']]);

    }

    // Verifica el email del usuario logeado
    public function email_verify(EmailVerificationRequest $request){
        try {
            DB::beginTransaction();
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'message' => 'Email already Verified'
                ], Response::HTTP_BAD_REQUEST);
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
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $newEmail = $request->input('email');
        if (!Hash::check($request->input('current_password'), $user->password)) {
            // Si la contraseña es erronea
            return response()->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
        }
        if ($user->email != $newEmail) {
            //TODO: MODIFICAR, PARA QUE SE ENVIE UN MAIL DE CONFIRMACION DE CAMBIO Y QUE SOLO SI SE VERIFICA ESE EMAIL SE PRODUCE EL CAMBIO
            
            if(User::where('email', $newEmail)->exists()){
                return response()->json(['message' => 'Email already exists'], Response::HTTP_BAD_REQUEST);
            }
            PendingEmailChange::where('user_id', $user->id)->delete();
            $token = Str::uuid()->toString();
            $tokenHash = hash('sha256', $token);
            PendingEmailChange::create([
                'user_id' => $user->id,
                'new_email' => $newEmail,
                'token' => $tokenHash,
                'expires_at' => Carbon::now()->addHour(),
                ]);
            Mail::to($newEmail)->send(new VerifyNewEmail($token));
        }else{
            return response()->json(['message' => 'Can not use your currect email '],Response::HTTP_BAD_REQUEST);
        }
        return response()->json(['message' => 'Verification send successfully'], Response::HTTP_OK);
    }

    public function confirmEmailChange(Request $request){
        $token = $request->input('token');
        $tokenHash = hash('sha256', $token);
        $pending = PendingEmailChange::where('token', $tokenHash)->where('expires_at','>',now())->first();
        if(!$pending){
            return response()->json(['message'=> 'Invalid or expired token'], Response::HTTP_BAD_REQUEST);
        }
        $user = User::find($pending->user_id);
        if(!$user){
            return response()->json(['message'=> 'User not found'], Response::HTTP_BAD_REQUEST);
        }
        $user->email = $pending->new_email;
        $user->markEmailAsVerified();
        $user->save();
        $pending->delete();
        return response()->json(['message'=> 'Email changed successfully'],Response::HTTP_OK);
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
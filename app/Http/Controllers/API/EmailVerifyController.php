<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class EmailVerifyController extends Controller
{
        public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware(['throttle:api']);
        // Apply 'verified' middleware to all methods except the specified ones.
        $this->middleware('signed', ['except' => 'email_send']);
        $this->middleware(['verified'], ['except' => ['email_verify','email_send']]);
        $this->middleware('auth:sanctum');

    }
    public function email_verify (EmailVerificationRequest $request){
        try{
            DB::beginTransaction();
            if($request->user()->hasVerifiedEmail()){
                return response()->json([
                    'message' => 'Email already Verified'
                ]);
            }
            $request->fulfill();
            $request->user()->markEmailAsVerified();
            $request->user()->emailToVerify->delete();
            $request->user()->save();
            event(new Verified($request->user()));
            DB::commit();
            return response()->json([
                'message' => "Email verified"
            ]);
        }
        catch (\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }

    }

    public function email_send (Request $request){
        $user = $request->user();
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json('User already have verified email', Response::HTTP_BAD_REQUEST);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification send'], Response::HTTP_OK);
    }
}

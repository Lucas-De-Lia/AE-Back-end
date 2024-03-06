<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


class EmailVerifyController extends Controller
{
        public function __construct()
    {
        // Rate limit the number of requests from any user to prevent server overload, with a maximum of 100 requests per minute.
        $this->middleware(['throttle:api','auth','signed']);

        // Apply 'verified' middleware to all methods except the specified ones.
        $this->middleware(['verified'], ['except' => ['verify_email']]);

    }
    public function verify_email (EmailVerificationRequest $request){
        try{
            DB::beginTransaction();
            $request->fulfill();
            if($request->user()->hasVerifiedEmail()){
                return response()->json([
                    'message' => 'Email already Verified'
                ]);
            }
            $request->user()->markEmailAsVerified();
            event(new Verified($request->user()));
            DB::commit();
            return response()->json([
                'message' => 'Email  Verified'
            ]);
        }
        catch (\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }

    }

    public function email_send (Request $request){
         $request->user()->sendEmailVerificationNotification();
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DateTime;

class AeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['throttle:api', 'auth:sanctum', 'verified']);
    }

    private function getDates()
    {
        $startDay = new DateTime("1/1/2024");
        $fifthMonth = new DateTime($startDay->format('Y-m-d'));
        $sixthMonth = new DateTime($startDay->format('Y-m-d'));
        $lastMonth = new DateTime($startDay->format('Y-m-d'));

        $fifthMonth->modify('+5 months');
        $sixthMonth->modify('+6 months');
        $lastMonth->modify('+12 months');

        return [
            'startDay' => $startDay->format('Y-m-d H:i:s'),
            'fifthMonth' => $fifthMonth->format('Y-m-d H:i:s'),
            'sixthMonth' => $sixthMonth->format('Y-m-d H:i:s'),
            'lastMonth' => $lastMonth->format('Y-m-d H:i:s'),
        ];
    }


    public function getaedates(Request $request)
    {
        if (Auth::check()) {
            // Usuario autenticado, obtén sus datoss
            //$user = Auth::user();
            //TODO CONSULTAR API
            return response()->json($this->getDates());
        }
    }
    public function getaeuserdata(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json([
                'user' => [
                    'name' => decrypt($user->name),
                    'email' => decrypt($user->email),
                    'cuil' => $user->cuil
                ],
            ]);
        }
    }
    public function send(Request $request)
    {
        $data = [
            'name' => "Romang Ignacio",
            'cuil' => "20-37396357-8",
            'email' => "ignacio21496@gmail.com",
            'confirmation_code' => AuthController::str_random(10)
        ];

        Mail::send('emails.confirmation_code', $data, function ($message) use ($data) {
            $message->to($data['email'], $data['name'])->subject('Por favor confirma tu correo');
        });
    }
}

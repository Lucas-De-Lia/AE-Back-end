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
        $this->middleware(['throttle:api', 'auth']);
    }

    private function getDates()
    {
        $startDay = new DateTime();
        $fifthMonth = new DateTime($startDay->format('Y-m-d'));
        $sixthMonth = new DateTime($startDay->format('Y-m-d'));
        $lastMonth = new DateTime($startDay->format('Y-m-d'));

        $fifthMonth->modify('+5 months');
        $sixthMonth->modify('+6 months');
        $lastMonth->modify('+12 months');

        return [
            'startDay' => $startDay,
            'fifthMonth' => $fifthMonth,
            'sixthMonth' => $sixthMonth,
            'lastMonth' => $lastMonth,
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
}

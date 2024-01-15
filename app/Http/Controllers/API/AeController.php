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
        $this->middleware(['throttle:api', 'auth:sanctum']);
    }

    private function getDates()
    {
        $startDay = new DateTime("8/1/2023");
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
}

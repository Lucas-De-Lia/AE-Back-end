<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\MailVerifyMenssage;
use Illuminate\Support\Facades\Mail;
class EmailController extends Controller
{
    public function index()
    {
        $data =array('email' => 'us@example.com');
        Mail::to('ignacio21496@gmail.com')->send(new MailVerifyMenssage("ignacio","url url url"));
        print('Enviado');
    }
}

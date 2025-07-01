<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyNewEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $greeting;
    public $introLines;
    public $actionText;
    public $actionUrl;
    public $outroLines;
    public $salutation;
    public $displayableActionUrl;
    public $level;
    public function __construct($token)
    {
        $baseUrl = rtrim(env('FRONT_END_URL'), '/');
        $this->token = $token;
        $this->level = 'info';
        $this->greeting = 'Hola!';
        $this->introLines = [
            '¡Gracias por confiar en nosotros!',
            'Para verificar tu correo electrónico correctamente, por favor ingresa a tu cuenta y realiza la verificación en el mismo navegador web.',
            'Haz clic en el botón de abajo para verificar tu correo.'
        ];
        $this->actionText = 'Verificar Email';
        $this->actionUrl = rtrim(env('FRONT_END_URL'), '/') . '/email'.'/verify-new-email'.'/' . $token;
        $this->displayableActionUrl = rtrim(env('FRONT_END_URL'), '/') . '/email'.'/verify-new-email'.'/' . $token;
        $this->outroLines = [
            'Ten en cuenta que el cambio de correo no se concretará hasta que verifiques tu correo.',
            'Recuerda que este enlace es válido por un tiempo limitado por razones de seguridad.',
            'Si no has intentado verificar tu dirección de correo electrónico, por favor ignora este correo o contáctanos de inmediato.',
            'Gracias por tu atención y comprensión.'
        ];
        $this->salutation = 'Atentamente, Departamento de Casinos';
    }

    public function build()
    {
        return $this->markdown('vendor.notifications.verify-new-email')
                    ->subject('Verificación de nuevo correo electrónico');
    }
}
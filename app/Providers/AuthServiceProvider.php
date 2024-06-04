<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $dom = env('APP_URL') . "/api/";
            $newUrl = str_replace($dom, env('FRONT_END_URL'), $url);
            $mail = (new MailMessage)
                ->greeting('Hola! ' . $notifiable->name)
                ->subject('Verificá dirección de correo electrónico')
                ->line("¡Gracias por confiar en nosotros!")
                ->line("Para garantizar la seguridad de tu cuenta, necesitamos verificar tu dirección de correo electrónico.")
                ->line('Para proceder, haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
                ->action('Verificar Email', $newUrl)
                ->line('Recuerda que este enlace es válido por un tiempo limitado por razones de seguridad.')
                ->line("Si no has intentado verificar tu dirección de correo electrónico, por favor ignora este correo o contáctanos de inmediato.")
                ->line("Gracias por tu atención y comprensión.")
                ->salutation("Atentamente, Departamento de Casinos");
            return $mail;
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = env('FRONT_END_URL') . 'password/reset?token=' . $token;
            $mail = (new MailMessage)
                ->greeting('Hola! ' . $notifiable->name)
                ->subject('Cambio de Contraseña')
                ->line("¡Gracias por confiar en nosotros!")
                ->line("Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Para proceder, haz clic en el botón de abajo para restablecer tu contraseña de correo electrónico.")
                ->action('Cambiar Contraseña', $url)
                ->line('Recuerda que este enlace es válido por un tiempo limitado por razones de seguridad.')
                ->line("Si no solicitaste restablecer tu contraseña, por favor ignora este correo o contáctanos de inmediato.")
                ->line("Gracias por tu atención y comprensión.")
                ->salutation("Atentamente, Departamento de Casinos");
            return $mail;
        });
    }
}
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
            $dom = env('APP_URL') . "api/";
            $newUrl = str_replace($dom, env('FRONT_END_URL'), $url);
            $mail = (new MailMessage)
                ->greeting('Hola! ' . $notifiable->name)
                ->subject('Verificá dirección de correo electrónico')
                ->line('¡Gracias por confiar en nosotros!, Recivimos tu solicitud. Haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
                ->action('Verificar Email', $newUrl)
                ->salutation("Saludos, Departamento de Casinos");
            return $mail;
        });

        /*
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return env('FRONT_END_URL') . 'reset-password?token=' . $token;
        });*/

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = env('FRONT_END_URL') . 'password/reset?token=' . $token;
            $mail = (new MailMessage)
                ->greeting('Hola! ' . $notifiable->name)
                ->subject('Cambio de Contraseña')
                ->line('¡Gracias por confiar en nosotros!, Recivimos tu solicitud. Haz clic en el botón de abajo para poder cambiar de contraseña.')
                ->action('Cambiar Contraseña', $url)
                ->salutation("Saludos, Departamento de Casinos");
            return $mail;
        });
    }
}

<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

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
            $newUrl = str_replace($dom, env('FRONT_END_URL') . 'user/', $url);
            return (new MailMessage)
                ->subject('Verificá dirección de correo electrónico')
                ->line('Hola! ' . $notifiable->name . ', recivimos tu solicitud. Haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
                ->action('Verificar Email', $newUrl);
        });
    }
}

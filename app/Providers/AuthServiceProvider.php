<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
            return (new MailMessage)
                ->subject('Verificá dirección de correo electrónico')
                ->line('Hola!, recivimos tu solicitud de auto excluson,Haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
                ->action('Verificar Email', $url);
        });
    }
}

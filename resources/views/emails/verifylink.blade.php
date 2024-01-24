@component('mail::message')
# ¡Hola, {{ $username }}!

Gracias por registrarte en nuestra plataforma. Para completar la verificación de tu correo electrónico, por favor haz clic en el siguiente enlace:

@component('mail::button', ['url' => url("http:/localhost:3000/user/verify-email/{$id}/{$hash}")])
Verificar Correo Electrónico
@endcomponent

Si no has intentado registrarte en nuestro sitio, puedes ignorar este mensaje.

Gracias,<br>
{{ config('app.name') }}
@endcomponent

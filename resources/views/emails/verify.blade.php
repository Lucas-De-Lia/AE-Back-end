@component('mail::message')
# ¡Hola, {{ $username }}!

Gracias por registrarte en nuestra plataforma. Para completar la verificación de tu correo electrónico, por favor ingresa el siguiente código en la página de verificación:

**Código de Verificación: {{ $confirmation_code }}**

*Puedes copiar manualmente este código utilizando las funciones estándar de tu sistema operativo.*

Si no has intentado registrarte en nuestro sitio, puedes ignorar este mensaje.

Gracias,<br>
{{ config('app.name') }}
@endcomponent


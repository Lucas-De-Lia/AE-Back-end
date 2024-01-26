<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmación de Correo Electrónico</title>
</head>
<body>

<h1>¡Hola, {{ $username }}!</h1>

<p>Gracias por registrarte en nuestra plataforma. Para completar la verificación de tu correo electrónico, por favor ingresa el siguiente código en la página de verificación:</p>

<p><strong>Código de Verificación:</strong> {{ $confirmation_code }}</p>

<p><em>Puedes copiar manualmente este código utilizando las funciones estándar de tu sistema operativo.</em></p>

<p>Si no has intentado registrarte en nuestro sitio, puedes ignorar este mensaje.</p>

<p>Gracias,<br>
{{ config('app.name') }}</p>

</body>
</html>

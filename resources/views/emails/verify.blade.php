<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
</head>
<style>
.center {
    display: grid;
    justify-content: center;
    align-items: center;
}
.codediv {
    border: 2px solid #999999;
    border-radius: 5px;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 4px;
    width: 200px;
    background-color: rgba(0, 0, 0, .125)
}
body{
    font-family: 'Roboto';
    font-size: 23px
}
</style>
<body>
    <p>Estimado/a {{$name}}, recivimos tu solicitud de <strong>Auto-exclusion</strong>!</p>
    <p>Para garantizar la seguridad de su cuenta, le solicitamos que complete el proceso de verificación adjuntando el siguiente código:</p>
    <div class="center">
        <div class="codediv" >
            <h2>{{$confirmation_code}}</h2>
        </div>
    </div>
    <p>Agradecemos su confianza y estamos a su dispocision. Recuerde que este mail fue enviado automaticamente, no debe responder a este mail</p>
    <p> Para cualquier duda debe comunicarse con email@fasdf.com</p>
    <p>Atentamente, Direcion de Casinos</p>
</body>
</html>

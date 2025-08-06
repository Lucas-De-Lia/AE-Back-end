<!DOCTYPE html>
<html>
<style>
    .centrar {
        text-align: center;
        font-family: Arial, sans-serif;
        font-weight: bold;
    }

    @page {
        margin-top: 6.25%;
        margin-left: 16.3%;
        margin-right: 16.3%;
    }
</style>

<head>
    <meta charset="utf-8">
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
</head>

<body>
    <!-- SOLICITUD DE AUTOEXCLUSIÓN -->
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">

    <p
        style="margin-left: 27.5%;width: 45.5%;margin-right: 27.5%;
    text-align: center;font-family: Arial, sans-serif;font-weight: bold;border-bottom: 1px solid black;">
        SOLICITUD DE AUTOEXCLUSIÓN
    </p>

    <div class="primerEncabezado" style="margin-left: 74%;border: 1px solid black;text-align: center;font-size:12px;">
        Fecha: {{ $fechaHoy }}
    </div>

    <div class="primerEncabezado" style="font-size:13px">
        <p>El Programa de Autoexclusión de los Casinos y Bingos de la Provincia de Santa Fe, de la CAS - Lotería de
            Santa Fe,
            se encuentra destinado a proveer ayuda a quienes consideren de su mayor interés, no participar en Salas de
            juegos de azar.</p>
        <p>Para ello, la C.A.S. Lotería de Santa Fe, puede asistirlo en su decisión de autoexcluirse,
            a través de la suscripción de la presente solicitud.</p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
        <p style="font-size:15px"><b>ACUERDO:</b></p>
        <p>
            Yo <b>{{ $nombres }} {{ $apellido }}</b>, DNI
            <b>{{ $dni }}</b>,
            constituyendo domicilio a los efectos del presente en calle <b>{{ $domicilio }}</b> Nº
            <b>{{ $nroDomicilio }}</b>
            {!! is_null($piso) ? '' : ', Piso <b>' . $piso . '</b>' !!}
            {!! is_null($dpto) ? '' : ', Dpto <b>' . $dpto . '</b>' !!}
            de la localidad de <b>{{ $localidad }}</b>, Provincia de
            <b>{{ $provincia }}</b>
            {!! is_null($cp) ? '' : ', C.P. <b>' . $cp . '</b>' !!}
            {!! is_null($telefono) ? '' : ', Teléfono <b>' . $telefono . '</b>' !!}; manifiesto voluntariamente, que no ingresaré a ninguna Sala de Juego de los Casinos
            y
            Bingos, ni accederé a las Plataformas de Juego Online
            (<a href="https://www.bplay.bet.ar">www.bplay.bet.ar</a> y/o <a
                href="https://www.citycenteronline.bet.ar">www.citycenteronline.bet.ar</a>) de la Provincia de Santa Fe,
            durante el plazo de duración del
            @if ($esPrimerAe)
                presente, que se extiende por seis (6) meses desde su suscripción y
                cuyo primer vencimiento operará en la siguiente fecha:
            @else
                presente:
            @endif
        </p>
    </div>

    @if ($esPrimerAe)
        <p
            style="margin-left: 40%;width: 20%;margin-right: 40%;text-align: center;font-size:18px;border: 1px solid black;">
            <b>{{ $fechaVencimiento }}</b>
        </p>

        <p class="primerEncabezado" style="font-size:13px">
            Que, asimismo, si dentro de los treinta días anteriores al primer vencimiento del plazo de duración del
            presente acuerdo,
            no expreso en forma fehaciente y documentada mi voluntad de dar por finalizada la autoexclusión (*), la
            misma se renovará
            automáticamente por otros seis (6) meses, a cuyo término operará el vencimiento definitivo, el día:
        </p>
    @endif

    <table style="table-layout: fixed;width: 100%;">
        <tr>
            <td width="40%" style="text-align: center;">
                @if (!$esPrimerAe)
                    <b>VENCIMIENTO (*)</b>
                @endif
            </td>
            <td width="20%" style="font-size:18px;text-align: center;border: 1px solid black;">
                <b>{{ $fechaCierre }}</b>
            </td>
            <td width="40%"></td>
        </tr>
    </table>

    @if (!$esPrimerAe)
        <br>
        <p class="primerEncabezado"><b>(*) R.V.E. N° 983/19 –cito en Art. 1, último párrafo
                “Para las personas que ya fueron parte del pro-grama, cuando soliciten ingresar
                nuevamente al mismo, el tiempo de vigencia será de un (1) año.
            </b></p>
    @endif

    <div class="primerEncabezado" style="font-size:13px">
        <p><b>Que la presente solicitud tiene carácter de IRREVOCABLE.</b></p>
        <p> Que en tal sentido, y durante el período de vigencia de la Autoexclusión, solicito me sea
            rechazado la entrada a todos los Casinos de la Provincia de Santa Fe, y a las plataformas de
            Juego Online ut-supra mencionadas y se me prohíba, en la medida de lo posible, la
            permanencia en los mismos. Que si intentara, o lograra ingresar a cualquier Sala de las
            aludidas, me sea requerido el retiro del lugar.</p>
        <p>También autorizo a que me sean tomadas las imágenes necesarias con el fin de mi
            identificación, aceptando que las mismas sean remitidas a las restantes Salas de Juego, al
            único efecto del cumplimiento del presente.</p>
    </div>

    <div style="page-break-after: always;"></div>
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">

    <div class="primerEncabezado" style="font-size:13px">
        <p>Asimismo, expreso:</p>
        <p>Que el ingreso al presente Programa de Autoexclusión, es voluntario, resultando exclusivamente responsable de
            su cumplimiento,
            para lo cual eximo expresamente de toda responsabilidad al respecto a la C.A.S.-Lotería de Santa Fe y los
            Concesionarios.
            Que comprendo y consiento que ni los Casinos y Bingos habilitados en la Pcia., ni la C.A.S.-Lotería de Santa
            Fe pueden garantizar
            totalmente el cumplimiento del presente.</p>
    </div>

    <div class="primerEncabezado"
        style="font-size:13px; border: 1px solid black; padding:7px;background-color: #f3f3f3;">
        <p><b>IMPORTANTE - LEER CUIDADOSAMENTE:</b> Entiendo que el ingresar a este Programa, no resulta obligación ni
            responsabilidad de terceros,
            por lo que expresamente renuncio a iniciar cualquier acción legal contra los concesionarios de las Salas de
            Juegos, la C.A.S.- Lotería
            de Santa Fe y/ o el Estado Provincial, por violación o incumplimiento del presente. Reconozco que las Salas
            de Juego y sus concesionarios,
            ni la CAS - Lotería de Santa Fe ni el Estado Provincial, resultan responsables de las pérdidas o daños que
            por mi propio accionar se
            produzcan en mi patrimonio y/o persona y/o en la de terceros.</p>
        <p>Linea telefónica de atención gratuita y confidencial. Si considera que tiene algún inconveniente con su
            manera de jugar puede informarse al numero 0800-345-5640.</p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
        <p>Los datos contenidos en el presente formulario de autoexclusión, se encuentran bajo los
            términos expresados en la Ley Nacional 25326/00 de protección de los datos
            personales, particularmente en los artículos 5, 6, 8, 9 y 10 y la Ley Provincial 8525,
            Capitulo IV, Art. 13, inc. f, deberes del personal.</p>
    </div>
    @if (isset($qrBase64) && isset($qrUrl))
        <div class="centrar" style="text-align: center; margin-top: 40px;">
            <p style="font-size: 14px; font-family: Arial, sans-serif;">
                <b>Verificación del Certificado</b>
            </p>
            <p style="font-size: 12px; font-family: Arial, sans-serif; max-width: 75%; margin: 0 auto;">
                Escanee el siguiente código QR para validar la autenticidad de este certificado de autoexclusión.
                También puede ingresar manualmente al enlace que figura debajo.
            </p>
            <div style="margin-top: 10px;">
                <img src="{{ $qrBase64 }}" alt="Código QR de Verificación" width="140">
            </div>
            <p
                style="font-size: 11px;
                font-family: Arial, sans-serif;
                margin-top: 10px;
                word-break: break-word;
                max-width: 70%;
                margin-left: auto;
                margin-right: auto;
                text-align: center;
                white-space: normal;
                overflow-wrap: break-word;">
                <a href="{{ $qrUrl }}" target="_blank"
                    style="text-decoration: underline;">{{ $qrUrl }}</a>
            </p>
        </div>
    @endif
</body>

</html>

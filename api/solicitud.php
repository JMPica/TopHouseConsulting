<?php
/* =====================================================================
   LAS SOLICITUDES, TAMBIEN POR CORREO
   =====================================================================

   POR QUE EXISTE ESTE FICHERO

   Hasta ahora el formulario solo abria WhatsApp con el mensaje escrito.
   Eso funciona, pero tiene un agujero que no se ve hasta que duele: si
   el visitante no le da a enviar en WhatsApp, o lo abre en un ordenador
   sin sesion iniciada, o cambia de idea a medio camino, esa solicitud NO
   EXISTE para nadie. Se ha perdido un cliente y ni siquiera se sabe.

   Este fichero recoge la solicitud en el servidor y la manda por correo
   a la inmobiliaria en el momento en que el visitante le da al boton.
   WhatsApp se sigue abriendo igual, porque es lo que el visitante espera
   ver y porque cierra la conversacion en caliente. Los dos caminos a la
   vez: el correo asegura que la solicitud llega, WhatsApp asegura que la
   conversacion empieza.

   QUE NO HACE

   No guarda las solicitudes en ningun sitio. Ni base de datos, ni
   fichero, ni registro. Se componen, se envian por correo y se olvidan.
   El unico rastro que queda en disco es un contador de ritmo con la IP
   PICADA (sha1 con una sal propia de la instalacion), que no permite
   recuperar la IP y que se limpia solo. Eso es lo que se ha prometido en
   la politica de privacidad y es lo que hace el codigo.

   CONFIGURACION

   No hace falta ninguna: los valores de abajo ya son los correctos. Si
   algun dia hay que cambiar la direccion de aviso, se pone en el mismo
   fichero de configuracion que ya usa cartera.php, FUERA de public_html:

       /home/<usuario>/domains/<dominio>/mobilia-config.php

       'aviso_a'  => 'info@tophouserealestate.es',
       'aviso_de' => 'no-reply@tophouserealestate.es',

       // Copia a mas destinatarios, uno o varios separados por comas.
       // Esta puesto pensando en el CRM: si Mobilia recoge solicitudes
       // de una direccion de correo, como hacen casi todos, se pone
       // aqui y entran solas, sin tocar codigo.
       'aviso_copia' => '',

   El remitente TIENE que ser una direccion de este mismo dominio. Si se
   pone la del visitante, el correo lo firma un dominio que no es el
   nuestro, el SPF del servidor no cuadra y acaba en la carpeta de spam
   justo el dia que llega la solicitud buena.
   ===================================================================== */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$PRIVADO = dirname(dirname(__DIR__));

/* La hora del correo tiene que ser la de aqui. El servidor va en UTC, asi
   que una visita pedida a las nueve de la noche llegaria fechada a las
   siete, y quien devuelva la llamada al dia siguiente no sabe si es de
   ayer o de hoy. */
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Europe/Madrid');
}

function apuntar($motivo) {
    error_log('solicitud.php: ' . $motivo);
}

/* Al visitante se le contesta siempre lo mismo y sin detalle. El detalle
   dice si una direccion existe o como esta montado el servidor, y eso es
   justo lo que busca quien esta probando. Va al registro, no a la calle. */
function fin($ok, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(array('ok' => (bool) $ok), JSON_UNESCAPED_UNICODE);
    exit;
}

/* Recorta sin partir un caracter por la mitad. Un acento son dos bytes y
   substr() los separa: el correo llegaria con un rombo negro al final. */
function recortar($t, $max) {
    $t = trim((string) $t);
    if (function_exists('mb_substr')) {
        return mb_strlen($t, 'UTF-8') > $max ? mb_substr($t, 0, $max, 'UTF-8') : $t;
    }
    if (strlen($t) <= $max) { return $t; }
    $c = substr($t, 0, $max);
    while ($c !== '' && (ord($c[strlen($c) - 1]) & 0xC0) === 0x80) {
        $c = substr($c, 0, -1);
    }
    return $c;
}

/* Todo lo que va a una CABECERA del correo pasa por aqui. Un salto de
   linea dentro del nombre convierte el nombre en una cabecera nueva, y
   con eso se puede anadir un Bcc y usar este formulario para mandar
   correo a quien sea. Es el fallo clasico de los formularios de contacto
   y se cierra en un sitio, no en cinco. */
function limpiarCabecera($t) {
    return trim(preg_replace('/[\r\n\t]+/', ' ', (string) $t));
}

/* Un asunto con acentos tiene que ir codificado o el cliente de correo
   lo ensena en crudo: "Solicitud de visita =?" y cosas peores. */
function asunto($t) {
    $t = limpiarCabecera($t);
    return preg_match('/[^\x20-\x7e]/', $t)
        ? '=?UTF-8?B?' . base64_encode($t) . '?='
        : $t;
}

/* Las cabeceras del correo. En una funcion a proposito: la prueba de mas
   abajo y el aviso de verdad tienen que salir EXACTAMENTE iguales, o la
   prueba dejaria de probar lo que se cree que prueba. */
function cabeceras($de, $para = null, $titulo = null) {
    $h = array();

    /* Por SMTP el mensaje se manda entero, asi que el destinatario y el
       asunto tienen que ir DENTRO. Con mail() no: esos dos los pone ella
       a partir de sus argumentos, y repetirlos daria dos veces cada uno. */
    if ($para !== null)   { $h[] = 'To: ' . $para; }
    if ($titulo !== null) { $h[] = 'Subject: ' . asunto($titulo); }

    $h[] = 'From: ' . asunto('Web Top House') . ' <' . $de . '>';
    $h[] = 'Reply-To: ' . $de;

    /* La fecha y el identificador, solo en el camino de SMTP. Por mail()
       los pone el servidor de correo al recogerlo, y si los pusiesemos
       aqui tambien saldrian dos veces, que es justo de las cosas que
       miran los filtros de spam. */
    if ($para !== null) {
        $dominio = substr(strrchr($de, '@'), 1);
        if ($dominio === false || $dominio === '') { $dominio = 'tophouserealestate.es'; }
        $h[] = 'Date: ' . date('r');
        $h[] = 'Message-ID: <' . bin2hex(function_exists('random_bytes')
            ? random_bytes(8)
            : pack('N*', mt_rand(), mt_rand())) . '.' . time() . '@' . $dominio . '>';
    }

    $h[] = 'MIME-Version: 1.0';
    $h[] = 'Content-Type: text/plain; charset=UTF-8';
    $h[] = 'Content-Transfer-Encoding: 8bit';
    $h[] = 'X-Mailer: tophouserealestate.es';
    return $h;
}

/* =================================================================
   COMO SALE EL CORREO
   =================================================================

   Hay dos caminos y se usan en este orden:

     1. SMTP, hablando con el servidor de correo como lo haria un cliente
        de correo cualquiera, con usuario y contrasena.
     2. mail(), la funcion de php, que es como salia antes.

   POR QUE NO BASTA CON mail()

   Porque el correo salia SIN FIRMAR. Se comprobo en la cabecera de un
   correo recibido: 'dkim=none'. El DKIM del dominio firma lo que sale
   del servicio de correo (el webmail, el movil, el Outlook), y mail() no
   pasa por ahi: va por un relay aparte. Ese relay si cumple el SPF, por
   eso los avisos llegaban a la bandeja de entrada y no a spam, pero la
   firma no se la pone nadie.

   Entrando por SMTP como un cliente mas, el correo sale por donde sale
   el vuestro y lo firma el servidor.

   POR QUE SE DEJA mail() DETRAS

   Porque esto son solicitudes de clientes. Si el servidor de correo esta
   caido, si cambia la contrasena o si el alojamiento cierra el puerto,
   la solicitud NO SE PUEDE PERDER por una mejora de reputacion. Si el
   SMTP falla por lo que sea, se cae a mail() y el aviso sale igual, sin
   firmar pero sale. El motivo del fallo queda en el registro.

   Sin configuracion de smtp, esto se comporta exactamente como antes.

   QUE HAY QUE PONER EN LA CONFIGURACION

   En el fichero de fuera de public_html, junto a lo demas:

       'smtp' => array(
         'host'     => 'smtp.hostinger.com',
         'puerto'   => 465,
         'seguridad'=> 'ssl',      // 'ssl' en el 465, 'tls' en el 587
         'usuario'  => 'jordi@tophouserealestate.es',
         'clave'    => 'LA CONTRASENA DEL BUZON',
       );

   El usuario es un BUZON de verdad. 'no-reply@' es un alias y los alias
   no tienen contrasena propia: se entra como el buzon y se manda DESDE
   el alias, que es lo que hace este codigo.
   ================================================================= */

/** Lee una respuesta del servidor, que puede venir en varias lineas. */
function smtpLeer($f) {
    $texto = '';
    while (($linea = fgets($f, 1024)) !== false) {
        $texto .= $linea;
        /* En una respuesta de varias lineas el codigo va seguido de '-';
           la ultima lleva un espacio. Sin esto se lee media respuesta y
           todo lo siguiente va desfasado una orden. */
        if (strlen($linea) >= 4 && $linea[3] === ' ') { break; }
        if (strlen($linea) < 4) { break; }
    }
    return $texto;
}

/** Manda una orden y comprueba que el servidor conteste lo esperado. */
function smtpDecir($f, $orden, $esperado, &$fallo, $secreto = false) {
    if ($orden !== null) {
        if (fwrite($f, $orden . "\r\n") === false) {
            $fallo = 'no se ha podido escribir en la conexion';
            return false;
        }
    }
    $r = smtpLeer($f);
    $codigo = (int) substr($r, 0, 3);
    if (!in_array($codigo, (array) $esperado, true)) {
        /* La contrasena NO va al registro ni aunque falle: si el error se
           apunta con la orden entera, una clave acaba en un fichero de
           log que no la tendria que ver nadie. */
        $que = $secreto ? '(orden con credenciales)' : (string) $orden;
        $fallo = 'el servidor ha contestado ' . $codigo . ' a ' . $que;
        return false;
    }
    return true;
}

/**
 * Manda el correo por SMTP. Devuelve true si el servidor lo ha aceptado.
 * El motivo del fallo se devuelve por $fallo para que quede en el
 * registro; al visitante no se le cuenta nunca.
 */
function porSmtp($cfg, $para, $titulo, $cuerpo, $de, &$fallo) {
    $fallo = '';
    $host = isset($cfg['host']) ? (string) $cfg['host'] : '';
    $usuario = isset($cfg['usuario']) ? (string) $cfg['usuario'] : '';
    $clave = isset($cfg['clave']) ? (string) $cfg['clave'] : '';
    if ($host === '' || $usuario === '' || $clave === '') {
        $fallo = 'configuracion de smtp incompleta';
        return false;
    }
    $seg = isset($cfg['seguridad']) ? strtolower((string) $cfg['seguridad']) : 'ssl';
    $puerto = isset($cfg['puerto']) ? (int) $cfg['puerto'] : ($seg === 'tls' ? 587 : 465);
    $espera = isset($cfg['espera']) ? (int) $cfg['espera'] : 12;

    /* Se comprueba el certificado del servidor. Si no se comprobase,
       cualquiera que se metiese en medio de la conexion se llevaria la
       contrasena del buzon. 'ca' solo hace falta para probar en local. */
    $ssl = array('verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true);
    if (!empty($cfg['ca'])) { $ssl['cafile'] = $cfg['ca']; }
    $ctx = stream_context_create(array('ssl' => $ssl));

    $destino = ($seg === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $puerto;
    $f = @stream_socket_client($destino, $errno, $errstr, $espera, STREAM_CLIENT_CONNECT, $ctx);
    if (!$f) {
        $fallo = 'no se ha podido conectar con ' . $host . ':' . $puerto . ' (' . $errstr . ')';
        return false;
    }
    stream_set_timeout($f, $espera);

    $yo = isset($cfg['saludo']) ? (string) $cfg['saludo'] : 'tophouserealestate.es';
    $ok = smtpDecir($f, null, 220, $fallo)
       && smtpDecir($f, 'EHLO ' . $yo, 250, $fallo);

    if ($ok && $seg === 'tls') {
        $ok = smtpDecir($f, 'STARTTLS', 220, $fallo);
        if ($ok) {
            $metodo = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $metodo |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!@stream_socket_enable_crypto($f, true, $metodo)) {
                $fallo = 'el cifrado starttls ha fallado';
                $ok = false;
            }
        }
        /* Despues de STARTTLS hay que volver a saludar: lo de antes se
           dijo en claro y el servidor lo descarta. */
        if ($ok) { $ok = smtpDecir($f, 'EHLO ' . $yo, 250, $fallo); }
    }

    if ($ok) {
        $ok = smtpDecir($f, 'AUTH LOGIN', 334, $fallo)
           && smtpDecir($f, base64_encode($usuario), 334, $fallo, true)
           && smtpDecir($f, base64_encode($clave), 235, $fallo, true);
    }

    if ($ok) {
        $ok = smtpDecir($f, 'MAIL FROM:<' . $de . '>', 250, $fallo)
           && smtpDecir($f, 'RCPT TO:<' . $para . '>', array(250, 251), $fallo)
           && smtpDecir($f, 'DATA', 354, $fallo);
    }

    if ($ok) {
        $mensaje = implode("\r\n", cabeceras($de, $para, $titulo)) . "\r\n\r\n" . $cuerpo;
        /* Una linea que empiece por un punto marca el final del mensaje.
           Si el visitante escribe una linea que empieza por un punto, el
           correo se corta ahi. Se dobla el punto, que es lo que manda la
           norma y lo que el otro lado deshace. */
        $mensaje = preg_replace('/^\./m', '..', $mensaje);
        fwrite($f, $mensaje . "\r\n.\r\n");
        $ok = smtpDecir($f, null, 250, $fallo);
    }

    @smtpDecir($f, 'QUIT', array(221, 250), $sinUsar);
    @fclose($f);
    return $ok;
}

/**
 * Manda el correo por el mejor camino disponible.
 * Devuelve 'smtp', 'mail', o '' si no ha salido por ninguno.
 */
function enviar($para, $titulo, $cuerpo, $de) {
    global $PRIVADO;
    $cfg = configuracion($PRIVADO);

    if (!empty($cfg['smtp']) && is_array($cfg['smtp'])) {
        $fallo = '';
        if (porSmtp($cfg['smtp'], $para, $titulo, $cuerpo, $de, $fallo)) {
            return 'smtp';
        }
        /* No se corta aqui: se apunta y se prueba el otro camino. Una
           solicitud de un cliente no se pierde por esto. */
        apuntar('smtp ha fallado (' . $fallo . '); se prueba con mail()');
    }

    /* El quinto argumento de mail() pone el remitente del SOBRE, que es el
       que mira el servidor que recibe para comprobar el SPF. Sin el,
       algunos alojamientos ponen el usuario del sistema. */
    $ok = @mail($para, asunto($titulo), $cuerpo, implode("\r\n", cabeceras($de)), '-f' . $de);
    return $ok ? 'mail' : '';
}

/* A quien mas se le manda una copia, aparte del buzon de la oficina.

   Existe para el CRM. Los portales meten sus solicitudes en Mobilia y
   hay que averiguar por donde entran; si resulta que Mobilia las recoge
   de una direccion de correo, como hacen casi todos los CRM, esto ya
   esta hecho y solo hay que poner esa direccion aqui:

       'aviso_copia' => 'loquesea@tophouseconsulting.mobiliagestion.es',

   Vale una direccion o varias separadas por comas. Va como envio
   APARTE, no como Cc: los que recogen correo automaticamente suelen
   mirar solo el destinatario directo, y ademas asi una direccion que
   falle no se lleva por delante el aviso a la oficina. */
function copias($privado) {
    $cfg = configuracion($privado);
    $v = isset($cfg['aviso_copia']) ? $cfg['aviso_copia'] : array();
    if (is_string($v)) { $v = preg_split('/[\s,;]+/', $v); }
    if (!is_array($v)) { return array(); }
    $fuera = array();
    foreach ($v as $d) {
        $d = trim((string) $d);
        if ($d !== '' && filter_var($d, FILTER_VALIDATE_EMAIL)) { $fuera[] = $d; }
    }
    return array_slice(array_values(array_unique($fuera)), 0, 5);
}

/** Lo que haya en el fichero de configuracion de fuera de public_html. */
function configuracion($privado) {
    $ruta = $privado . '/mobilia-config.php';
    if (is_readable($ruta)) {
        $leido = include $ruta;
        if (is_array($leido)) { return $leido; }
    }
    return array();
}

/** A donde va el aviso y desde donde sale. Vacias si estan mal puestas. */
function direcciones($privado) {
    $cfg  = configuracion($privado);
    $para = !empty($cfg['aviso_a'])  ? $cfg['aviso_a']  : 'info@tophouserealestate.es';
    $de   = !empty($cfg['aviso_de']) ? $cfg['aviso_de'] : 'no-reply@tophouserealestate.es';
    if (!filter_var($para, FILTER_VALIDATE_EMAIL) || !filter_var($de, FILTER_VALIDATE_EMAIL)) {
        return array('', '');
    }
    return array($para, $de);
}

/* ---------------------------------------------------------------
   0. Que version esta publicada
   ---------------------------------------------------------------
   Hace falta por un fallo de diseno que se vio en la primera prueba de
   verdad: cuando la prueba de mas abajo contesta {"ok":false} porque la
   clave no vale, contesta EXACTAMENTE lo mismo que contestaba la version
   anterior de este fichero, que no tenia prueba ninguna. Asi que ante un
   {"ok":false} no habia forma de saber si la clave estaba mal o si el
   despliegue todavia no habia llegado al servidor. Dos causas muy
   distintas y la misma respuesta.

   Esto lo corta: no lleva clave, no dice nada que no se pueda saber
   mirando la web, y contesta la unica pregunta que no se podia contestar.

       https://www.tophouserealestate.es/api/solicitud.php?version
   --------------------------------------------------------------- */

define('VERSION_SOLICITUD', '2026-09-21.5');
define('CLAVE_MINIMA', 16);

if (isset($_GET['version'])) {
    /* Tambien dice si la clave sirve, y por el mismo motivo que existe
       todo esto. Una clave de menos de CLAVE_MINIMA letras hace que el
       diagnostico se comporte como si no existiera, que es lo correcto,
       pero contesta lo mismo que una clave equivocada: otra vez dos
       causas y una sola respuesta. Paso de verdad, con una clave de 13.

       Decir esto no regala nada: no sale la clave, ni su longitud, ni
       nada que se pueda probar. Solo si el diagnostico esta utilizable.
       La misma clave vale para cartera.php, asi que esto los cubre a los
       dos. */
    $c = configuracion($PRIVADO);
    $clave = isset($c['clave']) ? (string) $c['clave'] : '';

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'version'      => VERSION_SOLICITUD,
        'tiene_prueba' => true,
        'clave_util'   => (strlen($clave) >= CLAVE_MINIMA),
        'clave_minimo' => CLAVE_MINIMA,
        'nota'         => (strlen($clave) >= CLAVE_MINIMA)
            ? 'La clave del fichero de configuracion sirve.'
            : 'La clave del fichero de configuracion falta o es demasiado corta: ponga una de ' . CLAVE_MINIMA . ' letras o mas. Hasta entonces, ni esta prueba ni el diagnostico de la cartera responden.',
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------------------------------------------------------
   0 bis. La prueba
   ---------------------------------------------------------------
   Para comprobar que los correos llegan SIN tener que rellenar el
   formulario y sin dejar una solicitud falsa en el buzon cada vez que se
   quiere mirar algo. Se abre en el navegador:

       https://www.tophouserealestate.es/api/solicitud.php?prueba=CLAVE

   La CLAVE es la misma que ya usa el diagnostico de cartera.php, la del
   fichero de configuracion de fuera de public_html. Sin clave puesta, o
   con una clave corta, esto no existe: contesta como cualquier otra
   peticion mal hecha.

   Dice dos cosas distintas que conviene no confundir:

     - 'aceptado': el servidor ha cogido el correo. Eso es todo lo que
       puede saber este fichero.
     - Si llega al buzon o no, eso ya depende del correo, y por eso la
       respuesta trae las direcciones: para poder mirar si existen.
   --------------------------------------------------------------- */

if (isset($_GET['prueba'])) {
    $cfg   = configuracion($PRIVADO);
    $clave = isset($cfg['clave']) ? (string) $cfg['clave'] : '';

    /* Sin clave larga no se responde nada distinto de lo normal: si esto
       contestase 'clave incorrecta' ya estaria diciendo que existe. */
    if (strlen($clave) < CLAVE_MINIMA || !hash_equals($clave, (string) $_GET['prueba'])) {
        fin(false, 405);
    }

    list($para, $de) = direcciones($PRIVADO);
    if ($para === '' || $de === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'las direcciones de aviso estan mal puestas'));
        exit;
    }

    /* Tambien cuenta para el limite: si la clave se escapase, esto no
       puede convertirse en una manera de mandar correo sin freno. */
    if (!ritmoOk($PRIVADO, 5, 80)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'demasiadas pruebas seguidas, espere un rato'));
        exit;
    }

    $cuerpo = "Esto es una prueba.\r\n\r\n"
        . "Si lee esto, los avisos del formulario de la web llegan bien.\r\n"
        . 'Enviada el ' . date('d/m/Y H:i') . " (hora de aqui).\r\n";

    $camino = enviar($para, 'Prueba de los avisos de la web', $cuerpo, $de);
    $aceptado = ($camino !== '');

    /* Las copias tambien se prueban: si no, se pondria una direccion de
       CRM en la configuracion y no se sabria si funciona hasta que
       llegase una solicitud de verdad, que es el peor momento. */
    $copias = array();
    foreach (copias($PRIVADO) as $otro) {
        $copias[$otro] = (bool) enviar($otro, 'Prueba de los avisos de la web', $cuerpo, $de);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'aceptado'     => (bool) $aceptado,
        'camino'       => $camino !== '' ? $camino : 'ninguno',
        'firmado'      => $camino === 'smtp'
            ? 'si: ha salido por el servidor de correo, que lo firma con el DKIM del dominio'
            : 'no: ha salido por mail(), que no firma. Mire el registro de errores para ver por que ha fallado el smtp.',
        'enviado_a'    => $para,
        'copias'       => empty($copias) ? 'ninguna configurada' : $copias,
        'enviado_desde'=> $de,
        'hay_mail'     => function_exists('mail'),
        'hora'         => date('d/m/Y H:i'),
        'nota'         => $aceptado
            ? 'El servidor ha cogido el correo. Mire el buzon, y la carpeta de spam.'
            : 'El servidor NO ha podido enviarlo. El motivo esta en el registro de errores de php.',
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------------------------------------------------------
   1. De donde viene
   --------------------------------------------------------------- */

if (strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') !== 'POST') {
    fin(false, 405);
}

/* Esto no es seguridad de verdad (una cabecera se falsifica en dos
   lineas), pero para de golpe a los robots que recorren internet
   disparando a cualquier .php que encuentran, que son el 99% de lo que
   va a llamar a esta direccion sin ser un visitante.

   Ojo con el puerto: HTTP_HOST lo trae ('sitio.es:8080') y Origin no lo
   trae en la parte del anfitrion. Comparados en crudo NO coinciden nunca
   y se rechazarian TODAS las solicitudes. Paso en la primera prueba. */
$anfitrion = isset($_SERVER['HTTP_HOST'])
    ? preg_replace('/:\d+$/', '', strtolower($_SERVER['HTTP_HOST']))
    : '';
$venido    = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $venido = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $venido = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
}
if ($venido !== '' && $anfitrion !== '' && strtolower($venido) !== $anfitrion) {
    apuntar('origen ajeno: ' . preg_replace('/[^\x20-\x7e]/', '', $venido));
    fin(false, 403);
}

/* ---------------------------------------------------------------
   2. Que trae
   --------------------------------------------------------------- */

$crudo = file_get_contents('php://input');
$d = json_decode((string) $crudo, true);
if (!is_array($d)) { $d = $_POST; }
if (!is_array($d)) { fin(false, 400); }

function campo($d, $n, $max) {
    return isset($d[$n]) && is_scalar($d[$n]) ? recortar($d[$n], $max) : '';
}

/* El campo que ningun humano rellena porque no lo ve. Si viene lleno, es
   un robot rellenando todo lo que encuentra. Se le contesta que si y no
   se envia nada: si se le contestase que no, el robot lo reintentaria
   cambiando cosas hasta acertar. */
if (campo($d, 'empresa', 80) !== '') { fin(true); }

/* El nombre y el telefono son de una linea: si traen saltos, se juntan.
   Asi no queda un 'Bcc: ...' colgando debajo del nombre que parezca una
   cabecera del correo aunque no lo sea. El mensaje SI conserva los
   suyos: ahi los ha puesto el visitante a proposito. */
$nombre  = limpiarCabecera(campo($d, 'nombre', 80));
$tel     = limpiarCabecera(campo($d, 'telefono', 40));
$mensaje = campo($d, 'mensaje', 2000);
$acepta  = !empty($d['consentimiento']);

if ($nombre === '' || $tel === '') { fin(false, 422); }

/* Sin consentimiento no hay base legal para tratar sus datos. El
   navegador ya lo comprueba, pero el navegador se puede saltar: la
   comprobacion que cuenta es la del servidor, que es la que decide si el
   correo sale. */
if (!$acepta) { fin(false, 422); }

/* Un telefono tiene que parecer un telefono. No se valida a rajatabla
   (hay prefijos raros y gente que escribe "605 27 31 50 (tardes)"), solo
   se exige un minimo de digitos para que no entre texto suelto. */
if (preg_replace('/\D/', '', $tel) === '' || strlen(preg_replace('/\D/', '', $tel)) < 6) {
    fin(false, 422);
}

/* Lo del inmueble, cuando la solicitud sale de una ficha. */
$ref     = campo($d, 'ref', 40);
$titulo  = campo($d, 'titulo', 200);
$pagina  = campo($d, 'pagina', 300);
$idioma  = campo($d, 'idioma', 5);
$origen  = campo($d, 'origen', 20);

/* La direccion de la pagina la manda el navegador, asi que podria traer
   cualquier cosa. Solo se acepta si apunta a este mismo sitio: asi el
   correo no puede llevar un enlace a otro sitio puesto desde fuera. */
if ($pagina !== '') {
    $h = parse_url($pagina, PHP_URL_HOST);
    if (!$h || ($anfitrion !== '' && strtolower($h) !== $anfitrion)) { $pagina = ''; }
}

/* Los campos propios del formulario de la portada. */
$zona     = campo($d, 'zona', 120);
$necesita = campo($d, 'necesita', 60);
$extra    = campo($d, 'extra', 400);

/* ---------------------------------------------------------------
   3. El ritmo
   ---------------------------------------------------------------
   Sin esto, cualquiera con un bucle de diez lineas deja el buzon con
   veinte mil correos y la solicitud de verdad se pierde entre ellos.

   La IP no se guarda: se guarda su picadura con una sal que solo existe
   en este servidor. Sirve para contar y no sirve para saber quien es. */

function ritmoOk($privado, $limitePorIp, $limiteTotal) {
    $fichero = is_writable($privado)
        ? $privado . '/solicitudes-ritmo.json'
        : sys_get_temp_dir() . '/solicitudes-ritmo-' . substr(sha1(__DIR__), 0, 12) . '.json';

    $f = @fopen($fichero, 'c+');
    if (!$f) { return true; }          /* si no se puede contar, no se bloquea a nadie */
    if (!flock($f, LOCK_EX)) { fclose($f); return true; }

    $texto = stream_get_contents($f);
    $datos = json_decode((string) $texto, true);
    if (!is_array($datos)) { $datos = array('sal' => '', 'visitas' => array()); }
    if (empty($datos['sal'])) {
        $datos['sal'] = bin2hex(function_exists('random_bytes')
            ? random_bytes(16)
            : pack('N*', mt_rand(), mt_rand(), mt_rand(), mt_rand()));
    }

    $ahora = time();
    $hora  = $ahora - 3600;
    $lista = isset($datos['visitas']) && is_array($datos['visitas']) ? $datos['visitas'] : array();

    /* Fuera lo viejo antes de contar, o el fichero crece sin fin. */
    $vivas = array();
    foreach ($lista as $v) {
        if (is_array($v) && isset($v[0], $v[1]) && $v[1] > $hora) { $vivas[] = $v; }
    }

    $ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $picada = substr(hash('sha256', $datos['sal'] . '|' . $ip), 0, 16);

    $mias = 0;
    foreach ($vivas as $v) { if ($v[0] === $picada) { $mias++; } }

    $cabe = ($mias < $limitePorIp) && (count($vivas) < $limiteTotal);
    if ($cabe) { $vivas[] = array($picada, $ahora); }

    $datos['visitas'] = $vivas;
    ftruncate($f, 0);
    rewind($f);
    fwrite($f, json_encode($datos));
    fflush($f);
    flock($f, LOCK_UN);
    fclose($f);
    return $cabe;
}

if (!ritmoOk($PRIVADO, 5, 80)) {
    apuntar('ritmo superado, solicitud descartada');
    /* Se le contesta que si: el visitante de verdad que le ha dado dos
       veces al boton no tiene que ver un error, y a quien esta abusando
       no se le da la pista de que hay un limite. El WhatsApp se le abre
       igual, asi que aunque esta se pierda, sigue teniendo por donde
       escribir. */
    fin(true);
}

/* ---------------------------------------------------------------
   4. El correo
   --------------------------------------------------------------- */

list($para, $de) = direcciones($PRIVADO);
if ($para === '' || $de === '') {
    apuntar('direcciones de aviso mal puestas en la configuracion');
    fin(false, 500);
}

$esFicha = ($ref !== '' || $origen === 'ficha');
$titLinea = $esFicha
    ? 'Visita: ' . ($ref !== '' ? 'ref ' . $ref : 'inmueble') . ' · ' . $nombre
    : 'Valoración · ' . $nombre;

$lineas = array();
$lineas[] = $esFicha ? 'PETICIÓN DE VISITA' : 'PETICIÓN DESDE LA WEB';
$lineas[] = str_repeat('=', 40);
$lineas[] = '';
$lineas[] = 'Nombre:    ' . $nombre;
$lineas[] = 'Teléfono:  ' . $tel;
if ($ref !== '')      { $lineas[] = 'Referencia: ' . $ref; }
if ($titulo !== '')   { $lineas[] = 'Inmueble:  ' . $titulo; }
if ($zona !== '')     { $lineas[] = 'Zona:      ' . $zona; }
if ($necesita !== '') { $lineas[] = 'Necesita:  ' . $necesita; }
if ($extra !== '')    { $lineas[] = 'Valoración web: ' . $extra; }
if ($mensaje !== '') {
    $lineas[] = '';
    $lineas[] = 'Mensaje:';
    $lineas[] = $mensaje;
}
$lineas[] = '';
$lineas[] = str_repeat('-', 40);
if ($pagina !== '') { $lineas[] = 'Página:  ' . $pagina; }
if ($idioma !== '') { $lineas[] = 'Idioma:  ' . $idioma; }
$lineas[] = 'Recibido: ' . date('d/m/Y H:i');
$lineas[] = '';
$lineas[] = 'Ha aceptado la política de privacidad al enviar el formulario.';
$lineas[] = 'Este aviso lo manda la web. No se responde a esta dirección:';
$lineas[] = 'llame al teléfono de arriba.';

/* Las lineas de un correo no pueden pasar de 998 caracteres y el mensaje
   del visitante puede venir de una sola tirada.

   wordwrap() con corte forzado NO sirve aqui: cuenta bytes, y un acento
   son dos, asi que puede partir una letra por la mitad y dejar un rombo
   negro en el correo. Se parte por palabras (que nunca corta letras) y
   solo si queda alguna linea absurdamente larga se corta a lo bruto,
   pero contando LETRAS y no bytes. */
function plegar($texto) {
    $fuera = array();
    foreach (explode("\n", str_replace("\r\n", "\n", $texto)) as $linea) {
        $linea = wordwrap($linea, 78, "\n", false);
        foreach (explode("\n", $linea) as $trozo) {
            while (function_exists('mb_strlen') && mb_strlen($trozo, 'UTF-8') > 400) {
                $fuera[] = mb_substr($trozo, 0, 400, 'UTF-8');
                $trozo   = mb_substr($trozo, 400, null, 'UTF-8');
            }
            $fuera[] = $trozo;
        }
    }
    return implode("\r\n", $fuera);
}

$cuerpo = plegar(implode("\n", $lineas));


$enviado = enviar($para, $titLinea, $cuerpo, $de);

/* Las copias van despues y no deciden nada: si el CRM no las coge, la
   solicitud NO se pierde, que ya esta en el buzon de la oficina. Al
   visitante no se le hace esperar por ellas ni se le cuenta un error
   que no es suyo. */
foreach (copias($PRIVADO) as $otro) {
    if (!enviar($otro, $titLinea, $cuerpo, $de)) {
        apuntar('la copia a ' . $otro . ' no ha salido');
    }
}

if (!$enviado) {
    apuntar('mail() ha devuelto falso; la solicitud no ha salido por correo');
    fin(false, 502);
}

fin(true);

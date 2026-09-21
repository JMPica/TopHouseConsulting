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

$cfg = array();
$ruta = $PRIVADO . '/mobilia-config.php';
if (is_readable($ruta)) {
    $leido = include $ruta;
    if (is_array($leido)) { $cfg = $leido; }
}

$para = !empty($cfg['aviso_a']) ? $cfg['aviso_a'] : 'info@tophouserealestate.es';
$de   = !empty($cfg['aviso_de']) ? $cfg['aviso_de'] : 'no-reply@tophouserealestate.es';
if (!filter_var($para, FILTER_VALIDATE_EMAIL) || !filter_var($de, FILTER_VALIDATE_EMAIL)) {
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

$cabeceras = array(
    'From: ' . asunto('Web Top House') . ' <' . $de . '>',
    'Reply-To: ' . $de,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'X-Mailer: tophouserealestate.es',
);

/* El quinto argumento pone el remitente del SOBRE, que es el que mira el
   servidor que recibe para comprobar el SPF. Sin el, algunos alojamientos
   ponen el usuario del sistema y el correo entra directo en spam. */
$enviado = @mail(
    $para,
    asunto($titLinea),
    $cuerpo,
    implode("\r\n", $cabeceras),
    '-f' . $de
);

if (!$enviado) {
    apuntar('mail() ha devuelto falso; la solicitud no ha salido por correo');
    fin(false, 502);
}

fin(true);

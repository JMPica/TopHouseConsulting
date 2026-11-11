<?php
/* =====================================================================
   Las cifras de la web, sin cookies y sin seguir a nadie
   =====================================================================

   QUE CUENTA. Cuantas veces pasa cada una de estas cosas, por dia:
     llegada      alguien entra en la web desde fuera (y de que TIPO de
                  sitio viene: un buscador, ChatGPT, un portal...)
     tel          pulsa un telefono
     whatsapp     pulsa un enlace de WhatsApp
     correo       pulsa el correo
     calculadora  usa la calculadora de precio de la portada
     solicitud    envia un formulario y el servidor confirma que ha llegado
     fallo        envia un formulario y NO llega (para detectar averias)

   QUE NO GUARDA. Ni la IP, ni cookies, ni ningun identificador, ni la
   direccion completa de donde viene: solo una categoria ("chatgpt",
   "google", "portal"...). No se puede saber quien hizo que, ni juntar
   dos acciones de la misma persona. Por eso no hace falta pedir permiso
   de cookies y la politica de privacidad lo explica tal cual.

   DONDE. Fuera de public_html, junto a la configuracion: un fichero por
   mes (medida-AAAA-MM.json) con los totales por dia.

   COMO SE LEE. /api/evento.php?informe=CLAVE, con la 'clave' de
   mobilia-config.php (la misma del diagnostico de la cartera).
   ===================================================================== */

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$PRIVADO = dirname(dirname(__DIR__));
$DIR = is_writable($PRIVADO) ? $PRIVADO : sys_get_temp_dir();

$EVENTOS = array('llegada', 'tel', 'whatsapp', 'correo', 'calculadora', 'solicitud', 'fallo');
$FUENTES = array('directa', 'interna', 'google', 'bing', 'duckduckgo', 'otro-buscador',
                 'chatgpt', 'perplexity', 'copilot', 'gemini', 'claude', 'otra-ia',
                 'portal', 'social', 'qr', 'correo', 'otra');
$MAX_CLAVES_DIA = 3000;   /* tope por dia: nadie puede llenar el disco a base de eventos */

function fichero_mes($dir, $ym) { return $dir . '/medida-' . $ym . '.json'; }

/* ---------------------------------------------------------------------
   El informe
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cfg = array();
    $ruta = $PRIVADO . '/mobilia-config.php';
    if (is_readable($ruta)) {
        try { $leido = include $ruta; if (is_array($leido)) { $cfg = $leido; } } catch (\Throwable $e) { }
    }
    $clave = isset($cfg['clave']) ? (string) $cfg['clave'] : '';
    $pedida = isset($_GET['informe']) ? (string) $_GET['informe'] : '';
    if (strlen($clave) < 16 || !hash_equals($clave, $pedida)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No disponible.';
        exit;
    }
    $dias = isset($_GET['dias']) ? max(1, min(400, (int) $_GET['dias'])) : 90;
    informe($DIR, $dias, $EVENTOS);
    exit;
}

/* ---------------------------------------------------------------------
   Apuntar un evento
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

/* Solo desde esta misma web: el navegador manda Origin en las peticiones
   POST, y una pagina ajena no puede falsificarlo. */
$anfitrion = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', strtolower($_SERVER['HTTP_HOST'])) : '';
$venido = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) { $venido = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST); }
elseif (!empty($_SERVER['HTTP_REFERER'])) { $venido = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST); }
if ($venido === '' || $venido === null || strtolower($venido) !== $anfitrion) { http_response_code(403); exit; }

$crudo = file_get_contents('php://input', false, null, 0, 2048);
$d = json_decode((string) $crudo, true);
if (!is_array($d)) { http_response_code(400); exit; }

$e = isset($d['e']) ? (string) $d['e'] : '';
$f = isset($d['f']) ? (string) $d['f'] : '';
$p = isset($d['p']) ? (string) $d['p'] : '';
$o = isset($d['o']) ? (string) $d['o'] : '';

if (!in_array($e, $EVENTOS, true)) { http_response_code(400); exit; }
if (!in_array($f, $FUENTES, true)) { $f = 'otra'; }
/* La pagina: solo la ruta, sin parametros, y solo con caracteres de las
   rutas de esta web. Una ficha se cuenta como /es/inmueble/1401. */
$p = strtolower(parse_url($p, PHP_URL_PATH) ?: '/');
if (!preg_match('~^/[a-z0-9/_.-]{0,80}$~', $p)) { $p = '/otra'; }
if (!preg_match('~^[a-z-]{0,24}$~', $o)) { $o = ''; }

$hoy = gmdate('Y-m-d');
$ruta = fichero_mes($DIR, gmdate('Y-m'));
$fh = @fopen($ruta, 'c+');
if (!$fh) { http_response_code(204); exit; }
if (flock($fh, LOCK_EX)) {
    $txt = stream_get_contents($fh);
    $datos = json_decode((string) $txt, true);
    if (!is_array($datos)) { $datos = array(); }
    if (!isset($datos[$hoy])) { $datos[$hoy] = array(); }
    $k = $e . '|' . $f . '|' . $p . '|' . $o;
    if (isset($datos[$hoy][$k]) || count($datos[$hoy]) < $MAX_CLAVES_DIA) {
        $datos[$hoy][$k] = (isset($datos[$hoy][$k]) ? $datos[$hoy][$k] : 0) + 1;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($datos));
        fflush($fh);
    }
    flock($fh, LOCK_UN);
}
fclose($fh);
http_response_code(204);
exit;

/* ---------------------------------------------------------------------
   El cuadro de mando
   --------------------------------------------------------------------- */
function informe($dir, $dias, $EVENTOS) {
    header('Content-Type: text/html; charset=utf-8');
    $desde = gmdate('Y-m-d', time() - ($dias - 1) * 86400);
    $filas = array();
    foreach (glob($dir . '/medida-*.json') ?: array() as $f) {
        $datos = json_decode((string) @file_get_contents($f), true);
        if (!is_array($datos)) { continue; }
        foreach ($datos as $dia => $claves) {
            if ($dia < $desde) { continue; }
            foreach ($claves as $k => $n) {
                $partes = explode('|', $k) + array('', '', '', '');
                $filas[] = array('dia' => $dia, 'e' => $partes[0], 'f' => $partes[1], 'p' => $partes[2], 'o' => $partes[3], 'n' => (int) $n);
            }
        }
    }
    $suma = function ($filtro, $por) use ($filas) {
        $r = array();
        foreach ($filas as $x) {
            if (!$filtro($x)) { continue; }
            $c = is_callable($por) ? $por($x) : $x[$por];
            $r[$c] = (isset($r[$c]) ? $r[$c] : 0) + $x['n'];
        }
        arsort($r);
        return $r;
    };
    $h = function ($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); };
    $tabla = function ($titulo, $datos, $col) use ($h) {
        $o = '<h2>' . $h($titulo) . '</h2>';
        if (!count($datos)) { return $o . '<p class="v">Todavía sin datos.</p>'; }
        $o .= '<table><tr><th>' . $h($col) . '</th><th>Veces</th></tr>';
        foreach ($datos as $k => $n) { $o .= '<tr><td>' . $h($k === '' ? '—' : $k) . '</td><td>' . (int) $n . '</td></tr>'; }
        return $o . '</table>';
    };
    $IA = array('chatgpt', 'perplexity', 'copilot', 'gemini', 'claude', 'otra-ia');
    $totales = $suma(function () { return true; }, 'e');
    $contactos = (isset($totales['solicitud']) ? $totales['solicitud'] : 0) + (isset($totales['tel']) ? $totales['tel'] : 0)
               + (isset($totales['whatsapp']) ? $totales['whatsapp'] : 0) + (isset($totales['correo']) ? $totales['correo'] : 0);
    $llegadas = isset($totales['llegada']) ? $totales['llegada'] : 0;
    $desdeIA = array_sum($suma(function ($x) use ($IA) { return $x['e'] === 'llegada' && in_array($x['f'], $IA, true); }, 'f'));
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex,nofollow"><title>Cifras de la web · Top House</title>'
       . '<link rel="stylesheet" href="/assets/informe.css"></head><body>';
    echo '<h1>Cifras de la web</h1><p class="s">Últimos ' . (int) $dias . ' días (desde ' . $h($desde) . '). Recuentos anónimos: sin cookies, sin IP, sin identificar a nadie. '
       . 'Cambiar el periodo: añada <code>&amp;dias=30</code> a la dirección.</p>';
    echo '<div class="k">'
       . '<div><b>' . (int) $llegadas . '</b>entradas a la web</div>'
       . '<div><b>' . (int) (isset($totales['solicitud']) ? $totales['solicitud'] : 0) . '</b>solicitudes recibidas</div>'
       . '<div><b>' . (int) $contactos . '</b>contactos (solicitudes + llamadas + WhatsApp + correo)</div>'
       . '<div><b>' . ($llegadas ? round(100 * $contactos / $llegadas, 1) : 0) . '%</b>contactos por entrada</div>'
       . '<div><b>' . (int) $desdeIA . '</b>entradas desde asistentes de IA</div>'
       . '<div><b>' . (int) (isset($totales['fallo']) ? $totales['fallo'] : 0) . '</b>formularios que NO llegaron</div>'
       . '</div>';
    echo $tabla('Qué ha pasado', $totales, 'Acción');
    echo $tabla('De dónde llegan', $suma(function ($x) { return $x['e'] === 'llegada'; }, 'f'), 'Origen');
    echo $tabla('Solicitudes por formulario', $suma(function ($x) { return $x['e'] === 'solicitud'; }, 'o'), 'Formulario');
    echo $tabla('Solicitudes por página', $suma(function ($x) { return $x['e'] === 'solicitud'; }, 'p'), 'Página');
    echo $tabla('Llamadas y WhatsApp por página', $suma(function ($x) { return $x['e'] === 'tel' || $x['e'] === 'whatsapp'; }, function ($x) { return $x['e'] . ' · ' . $x['p']; }), 'Acción · página');
    echo $tabla('Páginas de entrada', $suma(function ($x) { return $x['e'] === 'llegada'; }, 'p'), 'Página');
    echo $tabla('Entradas desde IA, por página', $suma(function ($x) use ($IA) { return $x['e'] === 'llegada' && in_array($x['f'], $IA, true); }, function ($x) { return $x['f'] . ' · ' . $x['p']; }), 'Asistente · página');
    echo $tabla('Por día', $suma(function () { return true; }, function ($x) { return $x['dia'] . ' · ' . $x['e']; }), 'Día · acción');
    echo '</body></html>';
}

<?php
/* =====================================================================
   LA FICHA DE UN INMUEBLE

   Cada inmueble tiene aqui su propia direccion: /immoble/1418 en catalan,
   /es/inmueble/1418, /en/property/1418. Las reescribe el .htaccess hacia
   este fichero con ?ref=1418.

   POR QUE ES PHP Y NO UNA PAGINA MAS DEL GENERADOR

   Porque una ficha se comparte. Cuando alguien manda un piso por WhatsApp
   quiere que salga la foto y el precio, y los robots que hacen esas vistas
   previas NO EJECUTAN JAVASCRIPT: si la ficha se pintase en el navegador,
   como hace la lista de la cartera, WhatsApp veria una pagina vacia y
   Google tambien. Asi que sale hecha del servidor.

   DE DONDE SALEN LOS DATOS

   De la misma copia que mantiene api/cartera.php, que ya trae la galeria
   entera y la descripcion. Aqui no se habla con Mobilia: si la copia esta
   vieja, se le pide a cartera.php que la refresque y se vuelve a leer. Asi
   hay un solo sitio que sabe hablar con Mobilia, y es el que esta probado.
   ===================================================================== */

$L       = 'es';
$CARPETA = 'es';

/* Donde vive la copia. Esta pagina existe en la raiz (catalan) y una
   carpeta mas adentro (castellano e ingles), asi que la profundidad no es
   fija: se sube buscando hasta encontrarla, y no se cuentan carpetas. */
$PRIVADO = null;
$dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    if (is_readable($dir . '/cartera-cache.json') || is_readable($dir . '/mobilia-config.php')) {
        $PRIVADO = $dir;
        break;
    }
    $padre = dirname($dir);
    if ($padre === $dir) { break; }
    $dir = $padre;
}

/* La referencia se compara sin acentos, sin mayusculas y sin guiones, por
   lo mismo que en el buscador de la cartera: quien la teclea a mano casi
   nunca la escribe igual que el CRM. */
function llana($v) {
    $v = strtolower((string) $v);
    return preg_replace('/[^a-z0-9]/', '', $v);
}

function h($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** El texto en el idioma de la pagina, y si no lo hay, el castellano. */
function porIdioma($n, $campo, $L) {
    if ($L !== 'es' && isset($n['i18n'][$L][$campo]) && $n['i18n'][$L][$campo] !== '') {
        return $n['i18n'][$L][$campo];
    }
    return isset($n[$campo]) ? $n[$campo] : '';
}

function leerCopia($PRIVADO) {
    if ($PRIVADO === null) { return array(); }
    $ruta = $PRIVADO . '/cartera-cache.json';
    if (!is_readable($ruta)) { return array(); }
    $d = json_decode(file_get_contents($ruta), true);
    return is_array($d) ? $d : array();
}

$pedida = isset($_GET['ref']) ? llana($_GET['ref']) : '';
$lista  = leerCopia($PRIVADO);

/* Si la copia no esta o se ha quedado vieja, se le pide a cartera.php que
   la rehaga. Una sola vez y sin bloquear mas de unos segundos: si Mobilia
   no contesta, mejor una ficha de hace un rato que una pagina de error. */
$vieja = true;
if ($PRIVADO !== null && is_readable($PRIVADO . '/cartera-cache.json')) {
    $minutos = 15;
    if (is_readable($PRIVADO . '/mobilia-config.php')) {
        /* Si el fichero tiene un error de sintaxis, la ficha se pinta
           igual con los 15 minutos de siempre. Solo se lee de aqui cada
           cuanto refrescar: no vale una pagina en blanco. */
        try {
            $cfg = include $PRIVADO . '/mobilia-config.php';
            if (is_array($cfg) && isset($cfg['minutos'])) { $minutos = (int) $cfg['minutos']; }
        } catch (\Throwable $e) {
            error_log('immoble.php: mobilia-config.php tiene un error en la linea ' . $e->getLine());
        }
    }
    $vieja = (time() - filemtime($PRIVADO . '/cartera-cache.json')) > max(60, $minutos * 60);
}
if ((!count($lista) || $vieja) && function_exists('curl_init')) {
    $base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
          . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
    $ch = curl_init($base . '/api/cartera.php');
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_USERAGENT      => 'TopHouseRealEstate/ficha',
    ));
    curl_exec($ch);
    curl_close($ch);
    $lista = leerCopia($PRIVADO);
}

$n = null;
foreach ($lista as $ficha) {
    if (is_array($ficha) && isset($ficha['ref']) && llana($ficha['ref']) === $pedida && $pedida !== '') {
        $n = $ficha;
        break;
    }
}

/* Un inmueble que ya no esta en la cartera -vendido, retirado- no es un
   error del visitante: se le dice que no esta y se le lleva a la lista,
   pero con un 404 de verdad para que Google lo saque del indice en vez de
   seguir mandando gente a una pagina que no existe. */
if ($n === null) {
    http_response_code(404);
}

$LISTAS = array(
    'ca' => array('venta' => 'comprar.html', 'alquiler' => 'llogar.html'),
    'es' => array('venta' => 'comprar.html', 'alquiler' => 'alquilar.html'),
    'en' => array('venta' => 'buy.html',     'alquiler' => 'rent.html'),
);
$RUTAS = array('ca' => 'immoble', 'es' => 'es/inmueble', 'en' => 'en/property');

$DOMINIO  = 'https://www.tophouserealestate.es';
$operacio = $n && isset($n['operacion']) ? $n['operacion'] : 'venta';
$volver   = isset($LISTAS[$L][$operacio]) ? $LISTAS[$L][$operacio] : $LISTAS[$L]['venta'];
/* Con la ruta entera: la ficha vive en /immoble/1432, y "comprar.html" a
   secas apuntaria a /immoble/comprar.html. */
$volver   = '/' . ($CARPETA !== '' ? $CARPETA . '/' : '') . $volver;

/* Los textos de Mobilia vienen a veces con restos del editor de su panel:
   "&nbsp;", "&amp;", alguna <br> o <p>. Como aqui se escapan para que nadie
   pueda colar codigo, esos restos salian tal cual en la ficha ("pasos.&nbsp;
   Este inmueble"). Se convierten primero en texto llano: los saltos de
   linea se quedan, las etiquetas se van y las entidades se traducen a su
   letra. Dos pasadas, porque alguno llega escapado dos veces
   ("&amp;nbsp;"). Despues h() lo escapa como siempre. */
function textoLlano($v) {
    $t = (string) $v;
    $t = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $t);
    $t = preg_replace('~<\s*/?\s*p\b[^>]*>~i', "\n\n", $t);
    $t = strip_tags($t);
    for ($i = 0; $i < 2; $i++) {
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $t = str_replace("\xC2\xA0", ' ', $t);           /* el espacio duro, como espacio */
    $t = preg_replace('/[ \t]+/', ' ', $t);
    $t = preg_replace('/ *\n */', "\n", $t);
    return trim($t);
}

/* =====================================================================
   LA DESCRIPCION, LEGIBLE
   =====================================================================
   En Mobilia la descripcion se escribe de una tirada: un bloque de veinte
   lineas sin un punto y aparte, con frases pegadas ("pasos.Este"),
   exclamaciones en serie y, al final, la letra legal (gastos, zona
   tensionada, gran tenedor, Ley 3/2017) mezclada con el texto comercial.
   En pantalla salia un muro gris que nadie lee, y Google tampoco le saca
   nada.

   Aqui se pule y se ordena SIN reescribir: no se inventa ni se quita una
   sola afirmacion del texto de la agencia. Solo:
     - puntuacion: espacio tras punto, signos repetidos, m2 -> m²;
     - parrafos cortos (dos o tres frases), el primero como entradilla;
     - las listas que ya vengan con guiones, como lista;
     - la letra legal, aparte y en pequeno, con los "Dato: valor" en lista.
   ===================================================================== */
function pulirTexto($t) {
    $t = preg_replace('/(?<!\b\p{Lu})([.!?…])(?=[¡¿"«(\p{Lu}])/u', '$1 ', $t);
    $t = preg_replace(array('/!{2,}/u', '/\?{2,}/u', '/¡{2,}/u', '/¿{2,}/u', '/\.{4,}/u'),
                      array('!', '?', '¡', '¿', '…'), $t);
    $t = preg_replace('/(\d)\s*m2\b/u', '$1 m²', $t);
    $t = preg_replace('/[ \t]+([,.;:!?])/u', '$1', $t);
    $t = preg_replace('/([,;])(?=\p{L})/u', '$1 ', $t);
    return $t;
}

function frases($t) {
    $t = trim(preg_replace('/\s+/u', ' ', $t));
    if ($t === '') { return array(); }
    return preg_split('/(?<=[.!?…])\s+(?=[¡¿"«(\p{Lu}\d])/u', $t);
}

/* Frases de dos o tres en dos o tres: un parrafo se lee de un vistazo. */
function agrupar($frases, $max = 260) {
    $fuera = array(); $actual = ''; $n = 0;
    foreach ($frases as $f) {
        $actual = $actual === '' ? $f : $actual . ' ' . $f;
        $n++;
        $largo = function_exists('mb_strlen') ? mb_strlen($actual, 'UTF-8') : strlen($actual);
        if ($largo >= $max || $n >= 3) { $fuera[] = $actual; $actual = ''; $n = 0; }
    }
    if ($actual !== '') { $fuera[] = $actual; }
    return $fuera;
}

function partesDescripcion($texto) {
    $legalRx = '/gastos\s+(de\s+)?(notar|registr|compraventa|de\s+la\s+compra)|no\s+est[aá]n\s+incluid|honorarios'
             . '|impuestos\s+aplicables|zona\s+tensionada|gran\s+tenedor|informaci[oó]n\s+precontractual'
             . '|ley\s+3\/2017|decreto\s+515|c[oó]digo\s+civil|derecho\s+a\s+recibir/iu';
    $t = pulirTexto($texto);

    /* Los bloques que ya trae el texto, y dentro las listas con guiones. */
    $piezas = array();   // cada pieza: array('lista', items) o array('frases', frases)
    foreach (preg_split('/\n\s*\n/u', $t) as $bloque) {
        $lineas = array_values(array_filter(array_map('trim', explode("\n", $bloque)), 'strlen'));
        $items = array(); $suelto = array();
        foreach ($lineas as $l) {
            if (preg_match('/^[-•*·–]\s*(.+)$/u', $l, $m)) { $items[] = $m[1]; }
            else { $suelto[] = $l; }
        }
        if ($suelto) { $piezas[] = array('frases', frases(implode(' ', $suelto))); }
        if (count($items) >= 2) { $piezas[] = array('lista', $items); }
        elseif ($items) { $piezas[] = array('frases', frases($items[0])); }
    }

    /* Donde empieza la letra legal: la primera frase que la nombra. Si la
       primerisima frase ya es legal, no se separa nada (raro, y mejor un
       texto entero que una ficha sin descripcion). */
    $todas = array();
    foreach ($piezas as $i => $p) {
        if ($p[0] === 'frases') { foreach ($p[1] as $j => $f) { $todas[] = array($i, $j, $f); } }
    }
    $corte = null;
    foreach ($todas as $k => $x) {
        if ($k > 0 && preg_match($legalRx, $x[2])) { $corte = array($x[0], $x[1]); break; }
    }

    $cuerpo = array(); $legal = array();
    foreach ($piezas as $i => $p) {
        if ($p[0] === 'lista') {
            if ($corte !== null && $i > $corte[0]) { continue; }   // una lista despues de lo legal se queda fuera del texto comercial
            $cuerpo[] = $p; continue;
        }
        $antes = $p[1]; $despues = array();
        if ($corte !== null && $i >= $corte[0]) {
            $antes   = $i === $corte[0] ? array_slice($p[1], 0, $corte[1]) : array();
            $despues = $i === $corte[0] ? array_slice($p[1], $corte[1]) : $p[1];
        }
        foreach (agrupar($antes) as $par) { $cuerpo[] = array('p', $par); }
        foreach ($despues as $f) { $legal[] = $f; }
    }

    /* La entradilla, corta: si el primer parrafo pasa de unas dos lineas,
       se queda con su primera frase y el resto baja al parrafo siguiente.
       Una entradilla de cinco lineas en letra grande pesa mas que ayuda. */
    if (isset($cuerpo[0]) && $cuerpo[0][0] === 'p') {
        $fs = frases($cuerpo[0][1]);
        $l0 = function_exists('mb_strlen') ? mb_strlen($cuerpo[0][1], 'UTF-8') : strlen($cuerpo[0][1]);
        if ($l0 > 200 && count($fs) > 1) {
            array_splice($cuerpo, 0, 1, array(array('p', array_shift($fs)), array('p', implode(' ', $fs))));
        }
    }

    /* La letra legal: los "Zona tensionada: Si." como dato, el resto en
       parrafos. "Si" pasa a "Sí", que es lo que quiere decir. */
    $datos = array(); $resto = array();
    foreach ($legal as $f) {
        if (preg_match('/^([^:.]{3,40}):\s*([^.:]{1,40})\.?$/u', trim($f), $m)) {
            $v = trim($m[2]);
            if (preg_match('/^si$/iu', $v)) { $v = 'Sí'; }
            $datos[] = array(trim($m[1]), $v);
        } else {
            $resto[] = $f;
        }
    }
    return array('cuerpo' => $cuerpo, 'datos' => $datos, 'legal' => agrupar($resto, 400));
}

/* La primera frase o dos, para Google y para la vista previa. */
function entradilla($partes, $max = 155) {
    $len = function ($s) { return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s); };
    foreach ($partes['cuerpo'] as $p) {
        if ($p[0] !== 'p') { continue; }
        /* Frases enteras mientras quepan: un corte a media frase se lee peor
           en Google que una frase corta y completa. */
        $t = '';
        foreach (frases($p[1]) as $f) {
            $prueba = $t === '' ? $f : $t . ' ' . $f;
            if ($len($prueba) > $max) { break; }
            $t = $prueba;
        }
        if ($t !== '') { return $t; }
        $t = $p[1];
        $corto = function_exists('mb_substr') ? mb_substr($t, 0, $max, 'UTF-8') : substr($t, 0, $max);
        $corto = preg_replace('/\s+\S*$/u', '', $corto);
        return rtrim($corto, ' ,;:') . '…';
    }
    return '';
}

$titulo = $n ? textoLlano(porIdioma($n, 'titulo', $L)) : '';
$desc   = $n ? textoLlano(porIdioma($n, 'descripcio', $L)) : '';
$partes = partesDescripcion($desc);
$fotos  = $n && isset($n['fotos']) && is_array($n['fotos']) ? $n['fotos'] : array();
if (!count($fotos) && $n && !empty($n['foto'])) { $fotos = array($n['foto']); }

$precio = $n && isset($n['precio']) ? (float) $n['precio'] : 0;
$sitio  = $n ? trim($n['poblacio'] . (!empty($n['zona']) ? ' · ' . $n['zona'] : '')) : '';

/* Para la vista previa de WhatsApp y de Google: el titulo con el precio y
   el sitio, que es lo que hace que alguien abra el enlace. */
$fmt = $L === 'en' ? 'en-GB' : 'es-ES';
$precioTexto = $precio > 0
    ? number_format($precio, 0, ',', $L === 'en' ? ',' : '.') . ' €'
    : '';
$tituloPagina = $n
    ? trim($titulo . ($precioTexto !== '' ? ' · ' . $precioTexto : '')) . ' · Top House Real Estate'
    : 'Top House Real Estate';
$resumen = $n
    ? trim($sitio . ($n['m2'] ? ' · ' . $n['m2'] . ' m²' : '') . ($n['hab'] ? ' · ' . $n['hab'] . ' hab.' : ''))
    : '';
$canonica = $n ? $DOMINIO . '/' . $RUTAS[$L] . '/' . rawurlencode($n['ref']) : $DOMINIO . '/';

/* La descripcion para Google y para la vista previa: primero los datos
   (donde, metros, habitaciones), que es lo que se busca, y detras la
   entradilla del texto en frases enteras, hasta unos 155 caracteres. */
$metaDesc = $resumen;
if ($n) {
    $sitioLibre = 155 - (function_exists('mb_strlen') ? mb_strlen($resumen, 'UTF-8') : strlen($resumen)) - 2;
    $ent = $sitioLibre > 40 ? entradilla($partes, $sitioLibre) : '';
    if ($ent !== '') { $metaDesc = ($resumen !== '' ? rtrim($resumen, '.') . '. ' : '') . $ent; }
}
if ($metaDesc === '') { $metaDesc = 'Top House Real Estate'; }

/* Datos estructurados (schema.org) para que Google entienda que la pagina
   es un inmueble en venta o en alquiler, con su precio, su superficie y
   quien lo vende. No es texto que vea nadie: es la ficha contada en el
   idioma de los buscadores. JSON_HEX_TAG para que un "</script>" dentro de
   una descripcion no pueda cerrar el bloque. */
$jsonLd = '';
if ($n) {
    /* El tipo puede venir en castellano o en catalan, y con mayuscula
       acentuada ("Ático"): por eso mb_strtolower y no strtolower, que deja
       la "Á" como esta y el ático acababa sin reconocer. */
    $tipoTxt = isset($n['tipo']) ? textoLlano($n['tipo']) : '';
    $tipoTxt = function_exists('mb_strtolower') ? mb_strtolower($tipoTxt, 'UTF-8') : strtolower($tipoTxt);
    $tipoLd = preg_match('/casa|chalet|torre|adosad|unifamiliar|mas[ií]a/u', $tipoTxt) ? 'SingleFamilyResidence'
            : (preg_match('/piso|pis\b|[aàá]tic|apartament|d[uú]plex|estudi|planta baja|planta baixa|loft/u', $tipoTxt) ? 'Apartment' : 'Place');
    $item = array('@type' => $tipoLd, 'name' => $titulo);
    if ($tipoLd !== 'Place') {
        if (!empty($n['m2']))    { $item['floorSize'] = array('@type' => 'QuantitativeValue', 'value' => (int) $n['m2'], 'unitCode' => 'MTK'); }
        if (!empty($n['hab']))   { $item['numberOfRooms'] = (int) $n['hab']; }
        if (!empty($n['banys'])) { $item['numberOfBathroomsTotal'] = (int) $n['banys']; }
    }
    if (!empty($n['poblacio'])) {
        $item['address'] = array('@type' => 'PostalAddress', 'addressLocality' => $n['poblacio'], 'addressCountry' => 'ES');
    }
    $oferta = array(
        '@type' => 'Offer',
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock',
        'businessFunction' => $operacio === 'alquiler' ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
        'offeredBy' => array(
            '@type' => 'RealEstateAgent',
            'name' => 'Top House Real Estate',
            'telephone' => '+34605273150',
            'url' => $DOMINIO . '/',
            'address' => array('@type' => 'PostalAddress', 'streetAddress' => 'Riera del Bisbe Pol, 56',
                               'postalCode' => '08350', 'addressLocality' => 'Arenys de Mar', 'addressCountry' => 'ES'),
        ),
    );
    if ($precio > 0) { $oferta['price'] = $precio; }
    $ld = array(
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $titulo,
        'url' => $canonica,
        'description' => $metaDesc,
        'mainEntity' => $item,
        'offers' => $oferta,
    );
    if (count($fotos)) {
        /* Google quiere direcciones completas: las de Mobilia ya lo son, las
           del fichero local (/assets/...) se completan con el dominio. */
        $ld['image'] = array_map(function ($f) use ($DOMINIO) {
            return (strpos($f, '/') === 0 && strpos($f, '//') !== 0) ? $DOMINIO . $f : $f;
        }, array_values(array_slice($fotos, 0, 6)));
    }
    $jsonLd = json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($tituloPagina); ?></title>
<meta name="description" content="<?php echo h($metaDesc); ?>">
<meta name="theme-color" content="#0B1417">
<meta name="author" content="Top House Real Estate">
<?php if ($n === null) { echo '<meta name="robots" content="noindex, follow">' . "\n"; } ?>
<link rel="canonical" href="<?php echo h($canonica); ?>">
<?php
/* Las tres versiones de la MISMA ficha, para que Google sepa que son la
   misma cosa en tres idiomas y no las tome por copias. */
if ($n) {
    foreach (array('ca' => 'ca-ES', 'es' => 'es-ES', 'en' => 'en-GB') as $cod => $etiqueta) {
        printf('<link rel="alternate" hreflang="%s" href="%s/%s/%s">' . "\n",
               $etiqueta, $DOMINIO, $RUTAS[$cod], rawurlencode($n['ref']));
    }
    printf('<link rel="alternate" hreflang="x-default" href="%s/%s/%s">' . "\n",
           $DOMINIO, $RUTAS['ca'], rawurlencode($n['ref']));
}
?>
<meta property="og:url" content="<?php echo h($canonica); ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="es_ES">
<meta property="og:site_name" content="Top House Real Estate">
<meta property="og:title" content="<?php echo h($tituloPagina); ?>">
<meta property="og:description" content="<?php echo h($metaDesc); ?>">
<?php if (count($fotos)) { ?>
<meta property="og:image" content="<?php echo h($fotos[0]); ?>">
<meta name="twitter:card" content="summary_large_image">
<?php } ?>

<?php if ($jsonLd !== '') { echo '<script type="application/ld+json">' . $jsonLd . '</script>' . "\n"; } ?>
<link rel="icon" type="image/png" href="/assets/favicon.png?v=90aac445">
<link rel="preload" href="/assets/fonts/fraunces-var-latin.woff2?v=48642176" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/manrope-var-latin.woff2?v=b4295101" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/fonts.css?v=69dae886">
<link rel="stylesheet" href="/assets/site.css?v=6ab3917a">
</head>
<body id="top">
<a class="skip" href="#main">Saltar al contenido</a>
<div class="env" aria-hidden="true"><div class="env__glow"></div><div class="env__dust"></div></div>
<nav class="nav" id="nav" aria-label="Principal">
  <a class="nav__brand" href="/es/index.html" aria-label="Top House Real Estate, inicio">
    <img class="mark" src="/assets/logo.png?v=e80d112c" width="256" height="256" alt="" aria-hidden="true" decoding="async">
    <span class="nav__word">
      <span class="nav__name">Top House</span>
      <span class="nav__kicker">Real Estate</span>
    </span>
  </a>
  <div class="nav__links">
    <a href="/es/index.html#vender">Vender</a>
    <a href="/es/comprar.html">Comprar</a>
    <a href="/es/alquilar.html">Alquilar</a>
    <a href="/es/index.html#valorar">Valoración</a>
    <a href="/es/index.html#servicios">Servicios</a>
    <a href="/es/nautica.html">Marine</a>
    <a href="/es/index.html#contacto">Contacto</a>
  </div>
  <div class="nav__actions">
    <div class="lang" role="group" aria-label="Idioma"><a href="/immoble.php" hreflang="ca" lang="ca"><abbr title="Català">CA</abbr></a><span class="lang__on" aria-current="true"><abbr title="Castellano">ES</abbr></span><a href="/en/property.php" hreflang="en" lang="en"><abbr title="English">EN</abbr></a></div>
    <a class="btn btn--ghost nav__tel" href="tel:+34605273150">605 27 31 50</a>
    <a class="btn btn--solid" href="/es/index.html#contacto">Pedir mi valoración</a>
  </div>
</nav>

<main id="main">
<?php if ($n === null) { ?>

<section class="cart__head">
  <p class="kicker">Inmueble</p>
  <h1 class="d1">Este inmueble ya no está publicado</h1>
  <p class="lede">Puede que se haya vendido o que lo hayamos retirado. La gran mayoría de lo que vendemos se cierra antes de publicarse, así que llámenos y le decimos qué tenemos parecido.</p>
  <p class="fitxa__acciones">
    <a class="btn btn--solid btn--lg" href="<?php echo h($volver); ?>">Ver lo que tenemos</a>
    <a class="btn btn--ghost btn--lg" href="tel:+34605273150">605 27 31 50</a>
  </p>
</section>

<?php } else { ?>

<article class="fitxa" data-ref="<?php echo h($n['ref']); ?>"
         data-operacion="<?php echo h($operacio); ?>"
         data-extras="<?php echo h(implode(',', isset($n['extras']) ? $n['extras'] : array())); ?>">

  <p class="fitxa__volver"><a href="<?php echo h($volver); ?>">Volver a la lista</a></p>

  <header class="fitxa__cap">
    <p class="kicker"><?php echo h($sitio); ?></p>
    <h1 class="d1"><?php echo h($titulo); ?></h1>
    <p class="fitxa__precio"><?php echo h($precioTexto !== '' ? $precioTexto : 'A consultar'); ?><?php
      if ($operacio === 'alquiler' && $precioTexto !== '') { echo '<span class="fitxa__mes">/mes</span>'; }
    ?></p>
  </header>

  <?php if (count($fotos)) { ?>
  <section class="fitxa__galeria" aria-label="Fotografías">
    <img class="fitxa__grande" id="fitxa-grande" src="<?php echo h($fotos[0]); ?>" alt="" decoding="async">
    <?php if (count($fotos) > 1) { ?>
    <ul class="fitxa__tiras">
      <?php foreach ($fotos as $i => $f) { ?>
      <li><button type="button" class="fitxa__tira<?php echo $i === 0 ? ' is-on' : ''; ?>"
                  data-foto="<?php echo h($f); ?>">
            <img src="<?php echo h($f); ?>" alt="" loading="lazy" decoding="async">
          </button></li>
      <?php } ?>
    </ul>
    <?php } ?>
  </section>
  <?php } ?>

  <section class="fitxa__cos">
    <div class="fitxa__principal">
      <ul class="fitxa__datos">
        <?php if ($n['m2']) { ?><li><span>Superficie</span> <b><?php echo (int) $n['m2']; ?> m²</b></li><?php } ?>
        <?php if ($n['hab']) { ?><li><span>Habitaciones</span> <b><?php echo (int) $n['hab']; ?></b></li><?php } ?>
        <?php if ($n['banys']) { ?><li><span>Baños</span> <b><?php echo (int) $n['banys']; ?></b></li><?php } ?>
        <li><span>Referencia</span> <b><?php echo h($n['ref']); ?></b></li>
        <?php $en = porIdioma($n, 'energia', $L); if ($en !== '') { ?>
        <li><span>Calificación energética</span> <b><?php echo h($en); ?></b></li>
        <?php } ?>
      </ul>

      <ul class="fitxa__tags inm__tags" id="fitxa-tags"></ul>

      <?php if ($desc !== '') { ?>
      <div class="fitxa__desc">
        <h2 class="d3">Descripción</h2>
        <?php
        $primero = true;
        foreach ($partes['cuerpo'] as $p) {
            if ($p[0] === 'lista') {
                echo '<ul class="fitxa__lista">';
                foreach ($p[1] as $it) { echo '<li>' . h($it) . '</li>'; }
                echo '</ul>';
                continue;
            }
            echo '<p' . ($primero ? ' class="fitxa__lead"' : '') . '>' . h($p[1]) . '</p>';
            $primero = false;
        } ?>
        <?php if ($partes['datos'] || $partes['legal']) { ?>
        <div class="fitxa__legal">
          <h3>Condiciones de la operación</h3>
          <?php if ($partes['datos']) { ?>
          <ul class="fitxa__kv">
            <?php foreach ($partes['datos'] as $kv) { echo '<li><span>' . h($kv[0]) . '</span> <b>' . h($kv[1]) . '</b></li>'; } ?>
          </ul>
          <?php } ?>
          <?php foreach ($partes['legal'] as $par) { echo '<p>' . h($par) . '</p>'; } ?>
        </div>
        <?php } ?>
      </div>
      <?php } ?>
    </div>

    <aside class="fitxa__lateral">
      <form class="form fitxa__form" id="form" novalidate data-gracias="/es/gracias.html">
        <p class="kicker">Pedir visita</p>
        <h2 class="d3">¿Quiere verlo?</h2>
        <p class="fitxa__formnota">Déjenos su teléfono y le llamamos hoy para acordar el día.</p>

        <div class="field field--wide">
          <label for="f-nombre">Nombre</label>
          <input id="f-nombre" name="nombre" type="text" autocomplete="name" required placeholder="Su nombre">
        </div>
        <div class="field field--wide">
          <label for="f-tel">Teléfono</label>
          <input id="f-tel" name="telefono" type="tel" autocomplete="tel" required placeholder="Para llamarle hoy">
        </div>
        <div class="field field--wide">
          <label for="f-msg">Mensaje <span class="opt">(opcional)</span></label>
          <textarea id="f-msg" name="mensaje" rows="3" placeholder="Cuéntenos lo que quiera"></textarea>
        </div>
        <div class="field field--wide field--consent">
          <label class="consent" for="f-ok">
            <input id="f-ok" name="consentimiento" type="checkbox" required>
            <span>He leído y acepto la política de privacidad. Al enviar, mis datos llegan a Top House por correo.</span>
          </label>
        </div>
        <div class="field field--trampa" aria-hidden="true">
          <input id="f-empresa" name="empresa" type="text" tabindex="-1" autocomplete="off">
        </div>
        <p class="form__err" id="form-err" role="alert" hidden></p>
        <button class="btn btn--solid btn--lg form__go" type="submit">Pedir visita</button>
        <p class="form__note mono">Su solicitud nos llega por correo. Después, si lo prefiere, podrá escribirnos también por WhatsApp.</p>
      </form>

      <p class="fitxa__tel">
        O llámenos ahora: <a href="tel:+34605273150">605 27 31 50</a>
      </p>
    </aside>
  </section>
</article>

<?php } ?>
</main>

<footer class="foot">
  <div class="foot__top">
    <a class="nav__brand" href="/es/index.html" aria-label="Top House Real Estate, inicio">
      <img class="mark" src="/assets/logo.png?v=e80d112c" width="256" height="256" alt="" aria-hidden="true" decoding="async">
      <span class="nav__word">
        <span class="nav__name">Top House</span>
        <span class="nav__kicker">Real Estate</span>
      </span>
    </a>
    <div class="foot__cols">
      <div>
        <p class="mono foot__l">Dónde</p>
        <p>Riera del Bisbe Pol, 56<br>08350 Arenys de Mar<br>Barcelona</p>
      </div>
      <div>
        <p class="mono foot__l">Hablar</p>
        <p><a href="tel:+34605273150">605 27 31 50</a><br>
           <a href="https://wa.me/34605273150" rel="noopener">WhatsApp</a></p>
      </div>
      <div>
        <p class="mono foot__l">Legal</p>
        <p><a href="/es/legal.html#aviso-legal">Aviso legal</a><br>
           <a href="/es/legal.html#privacidad">Privacidad</a><br>
           <a href="/es/legal.html#cookies">Cookies</a></p>
      </div>
    </div>
  </div>
  <div class="foot__bar">
    <p>© <span id="year">2026</span> Top House Real Estate</p>
    <p class="foot__note">Precios y superficies orientativos. La información definitiva de cada inmueble se entrega por escrito antes de cualquier reserva.</p>
  </div>
</footer>
<script src="/assets/idioma.js?v=4350aba3" defer></script>
<script src="/assets/menu.js?v=dde41b9f" defer></script>
<script src="/assets/base.js?v=aa4a05d7" defer></script>
<script src="/assets/extras.js?v=da2cdee0" defer></script>
<script src="/assets/aviso.js?v=cc278b17" defer></script>
<script src="/assets/fitxa.js?v=54a58d8e" defer></script>
</body>
</html>

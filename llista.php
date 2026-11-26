<?php
/* =====================================================================
   LAS LISTAS DE COMPRAR Y ALQUILAR, HECHAS EN EL SERVIDOR

   QUE PROBLEMA RESUELVE
   La lista de inmuebles la pintaba solo el navegador (assets/cartera.js).
   Un robot que no ejecuta javascript -los buscadores de los asistentes de
   IA, Bing a menudo, las vistas previas- pedia /comprar.html y encontraba
   una lista vacia: cero inmuebles y cero enlaces a las fichas.

   QUE HACE
   El .htaccess manda aqui /comprar.html, /llogar.html y sus versiones en
   castellano e ingles. Este fichero coge la pagina ya generada (la misma
   de siempre, ya traducida) y le mete dentro, antes de enviarla:
     - las tarjetas de los inmuebles, iguales que las de cartera.js;
     - el recuento y el buscador ya a la vista;
     - los datos estructurados de la lista (ItemList).
   El navegador recibe la pagina con la lista puesta; cartera.js ve que ya
   esta pintada (data-ssr) y no la rehace hasta que alguien filtra.

   LAS PAGINAS POR POBLACION
   /comprar/arenys-de-mar, /es/comprar/arenys-de-mar, /en/buy/arenys-de-mar:
   la misma lista con solo los inmuebles de ese pueblo, y con su titulo,
   su descripcion y su direccion propios. Solo existen para los pueblos
   con MIN_POBLACION inmuebles o mas: con menos seria una pagina casi
   vacia, y se manda al visitante a la lista general filtrada.

   SI ALGO FALLA
   Cualquier error aqui dentro acaba enviando la pagina tal como estaba
   (sin lista, y cartera.js la pinta como siempre). Nunca una pagina en
   blanco: ver el try/catch del final.
   ===================================================================== */

$MIN_POBLACION = 3;
$DOMINIO = 'https://www.tophouserealestate.es';

/* Que pagina es cada nombre, en cada idioma: operacion y fichero. */
$PAGS = array(
    'ca' => array('comprar' => array('venta', 'comprar.html'), 'llogar'   => array('alquiler', 'llogar.html')),
    'es' => array('comprar' => array('venta', 'comprar.html'), 'alquilar' => array('alquiler', 'alquilar.html')),
    'en' => array('buy'     => array('venta', 'buy.html'),     'rent'     => array('alquiler', 'rent.html')),
);
$FICHAS = array('ca' => '/immoble/', 'es' => '/es/inmueble/', 'en' => '/en/property/');

$L   = isset($_GET['l']) && isset($PAGS[$_GET['l']]) ? $_GET['l'] : 'ca';
$pag = isset($_GET['pag']) ? (string) $_GET['pag'] : '';
$pob = isset($_GET['pob']) ? (string) $_GET['pob'] : '';
$pideDirecto = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'llista.php') !== false;

if ($pideDirecto || !isset($PAGS[$L][$pag]) || ($pob !== '' && !preg_match('/^[a-z0-9-]{1,60}$/', $pob))) {
    /* Este fichero no es una direccion: solo se llega por el .htaccess. */
    header('Location: /comprar.html', true, 301);
    exit;
}

$OPER    = $PAGS[$L][$pag][0];
$NOMBRE  = $PAGS[$L][$pag][1];
$PREFIJO = $L === 'ca' ? '/' : '/' . $L . '/';
$FICHERO = __DIR__ . $PREFIJO . $NOMBRE;

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');

if (!is_readable($FICHERO)) {
    http_response_code(404);
    if (is_readable(__DIR__ . '/404.php')) { include __DIR__ . '/404.php'; }
    exit;
}
$HTML = file_get_contents($FICHERO);

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

/** "Arenys de Mar" -> "arenys-de-mar". La misma en los tres idiomas. */
function slug($nombre) {
    $t = function_exists('mb_strtolower') ? mb_strtolower((string) $nombre, 'UTF-8') : strtolower((string) $nombre);
    $t = strtr($t, array('à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'í' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
                         'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', '·' => '', '’' => '', "'" => ''));
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
}

/** Un texto que va de reemplazo en preg_replace, sin que un "$" o una
    barra suya se tomen por una referencia. */
function lit($s) { return addcslashes((string) $s, '\\$'); }

function numero($v) { $n = is_numeric($v) ? (float) $v : 0; return $n > 0 ? $n : 0; }

/** El precio como lo escribe el navegador en cada idioma (Intl.NumberFormat):
    en castellano los miles no se separan hasta las cinco cifras (1200,
    12.000); en catalan si (1.200); en ingles, con coma. */
function miles($n, $L) {
    $n = (float) $n;
    if ($L === 'en') { return number_format($n, 0, '.', ','); }
    if ($L === 'es' && $n < 10000) { return number_format($n, 0, ',', ''); }
    return number_format($n, 0, ',', '.');
}

try {
    /* ---------- los textos, los mismos de cartera.js ---------- */
    $TXT = array('ca' => array(), 'en' => array(), 'extras' => array());
    $rutaTxt = __DIR__ . '/api/textos-llista.json';
    if (is_readable($rutaTxt)) {
        $leido = json_decode(file_get_contents($rutaTxt), true);
        if (is_array($leido)) { $TXT = $leido + $TXT; }
    }
    $T = function ($s) use ($TXT, $L) {
        return ($L !== 'es' && isset($TXT[$L][$s])) ? $TXT[$L][$s] : $s;
    };

    /* ---------- la cartera ---------- */
    $PRIVADO = null;
    $dir = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        if (is_readable($dir . '/cartera-cache.json')) { $PRIVADO = $dir; break; }
        $padre = dirname($dir);
        if ($padre === $dir) { break; }
        $dir = $padre;
    }
    $lista = array();
    if ($PRIVADO !== null) {
        $d = json_decode(file_get_contents($PRIVADO . '/cartera-cache.json'), true);
        if (is_array($d)) { $lista = $d; }
    }
    /* Aqui NO se le pide a Mobilia que refresque: si la copia esta vieja,
       se usa igual. El navegador pide despues api/cartera.php, que es
       quien la renueva, y repinta si algo ha cambiado. Una lista de hace
       un rato vale mas que hacer esperar al visitante. */

    $todos = array();
    foreach ($lista as $n) {
        if (!is_array($n) || empty($n['ref'])) { continue; }
        if (isset($n['seccion']) && $n['seccion'] === 'marine') { continue; }
        if (!isset($n['operacion']) || $n['operacion'] !== $OPER) { continue; }
        $todos[] = $n;
    }
    /* El mismo orden que cartera.js: destacados, lo mas reciente, y a
       igualdad el mas caro. */
    usort($todos, function ($a, $b) {
        $da = !empty($a['destacado']); $db = !empty($b['destacado']);
        if ($da !== $db) { return $db ? 1 : -1; }
        $fa = numero(isset($a['fecha']) ? $a['fecha'] : 0); $fb = numero(isset($b['fecha']) ? $b['fecha'] : 0);
        if ($fa != $fb) { return $fb > $fa ? 1 : -1; }
        $pa = numero(isset($a['precio']) ? $a['precio'] : 0); $pb = numero(isset($b['precio']) ? $b['precio'] : 0);
        return $pb == $pa ? 0 : ($pb > $pa ? 1 : -1);
    });

    /* ---------- los pueblos con pagina propia ---------- */
    $porPueblo = array();
    foreach ($todos as $n) {
        $p = isset($n['poblacio']) ? trim((string) $n['poblacio']) : '';
        if ($p === '') { continue; }
        $s = slug($p);
        if (!isset($porPueblo[$s])) { $porPueblo[$s] = array('nombre' => $p, 'n' => 0); }
        $porPueblo[$s]['n']++;
    }
    uasort($porPueblo, function ($a, $b) { return $b['n'] - $a['n']; });
    $conPagina = array();
    foreach ($porPueblo as $s => $info) { if ($info['n'] >= $MIN_POBLACION) { $conPagina[$s] = $info; } }

    $pueblo = null;
    if ($pob !== '') {
        if (!isset($conPagina[$pob])) {
            /* Pocos inmuebles (o ninguno) en ese pueblo: no hay pagina
               propia. Temporal (302): si entran mas, la pagina vuelve. */
            header('Location: ' . $PREFIJO . $NOMBRE . '?poblacio=' . rawurlencode($pob), true, 302);
            exit;
        }
        $pueblo = $conPagina[$pob]['nombre'];
    }

    $ver = array();
    foreach ($todos as $n) {
        if ($pueblo === null || (isset($n['poblacio']) && trim((string) $n['poblacio']) === $pueblo)) { $ver[] = $n; }
    }

    if (!count($todos)) {
        /* Sin cartera a mano: la pagina de siempre, y que la pinte el navegador. */
        echo $HTML;
        exit;
    }

    /* ---------- las tarjetas ---------- */
    $nDest = 0;
    foreach ($todos as $n) { if (!empty($n['destacado'])) { $nDest++; } }
    $DESTACA = $nDest > 0 && $nDest < count($todos) / 2;

    $texto = function ($n, $campo) use ($L) {
        if ($L !== 'es' && isset($n['i18n'][$L][$campo]) && $n['i18n'][$L][$campo] !== '') { return $n['i18n'][$L][$campo]; }
        return isset($n[$campo]) ? (string) $n[$campo] : '';
    };

    $tarjetas = '';
    $refs = array();
    $elementos = array();
    foreach ($ver as $pos => $n) {
        $ref = (string) $n['ref'];
        $refs[] = $ref;
        $dest = !empty($n['destacado']) && $DESTACA;
        $titulo = $texto($n, 'titulo');
        if ($titulo === '') { $titulo = $T('Inmueble'); }
        $url = $FICHAS[$L] . rawurlencode($ref);

        $foto = isset($n['foto']) ? (string) $n['foto'] : '';
        if (!preg_match('~^https?://~i', $foto)) { $foto = ''; }
        $flag = $dest ? '<span class="inm__flag mono">' . h($T('Destacado')) . '</span>' : '';
        $cajaFoto = $foto !== ''
            ? '<div class="inm__foto"><img src="' . h($foto) . '" alt="" loading="lazy" decoding="async">' . $flag . '</div>'
            : '<div class="inm__foto inm__foto--sin"><span class="mono">' . h($T('Sin foto todavía')) . '</span>' . $flag . '</div>';

        $sitio = (isset($n['poblacio']) ? $n['poblacio'] : '') . (!empty($n['zona']) ? ' · ' . $n['zona'] : '');
        $datos = array();
        $m2 = numero(isset($n['m2']) ? $n['m2'] : 0); $hab = numero(isset($n['hab']) ? $n['hab'] : 0); $banys = numero(isset($n['banys']) ? $n['banys'] : 0);
        if ($m2)    { $datos[] = ($m2 + 0) . ' m²'; }
        if ($hab)   { $datos[] = ($hab + 0) . ($hab == 1 ? $T(' habitación') : $T(' habitaciones')); }
        if ($banys) { $datos[] = ($banys + 0) . ($banys == 1 ? $T(' baño') : $T(' baños')); }

        $tags = '';
        $extras = isset($n['extras']) && is_array($n['extras']) ? array_slice(array_values(array_filter($n['extras'])), 0, 5) : array();
        foreach ($extras as $e) {
            $nombre = isset($TXT['extras'][$e]) ? $T($TXT['extras'][$e]) : (string) $e;
            $tags .= '<li>' . h($nombre) . '</li>';
        }
        $energia = $texto($n, 'energia');
        $precio = numero(isset($n['precio']) ? $n['precio'] : 0);
        $pPrecio = $precio
            ? h(miles($precio, $L) . ' €') . ($OPER === 'alquiler' ? '<span class="inm__mes">' . h($T('/mes')) . '</span>' : '')
            : h($T('A consultar'));

        $tarjetas .= '<li class="inm' . ($dest ? ' inm--dest' : '') . '">' . $cajaFoto
            . '<div class="inm__cuerpo">'
            . '<p class="inm__sitio mono">' . h($sitio) . '</p>'
            . '<h3 class="inm__t">' . h($titulo) . '</h3>'
            . (count($datos) ? '<p class="inm__datos">' . h(implode(' · ', $datos)) . '</p>' : '')
            . ($tags !== '' ? '<ul class="inm__tags">' . $tags . '</ul>' : '')
            . ($energia !== '' ? '<p class="inm__energia mono">' . h($T('Energía') . ': ' . $energia) . '</p>' : '')
            . '<p class="inm__precio">' . $pPrecio . '</p>'
            . '<a class="btn btn--ghost inm__cta" href="' . h($url) . '">' . h($T('Quiero verlo'))
            . '<span class="sr-only">' . h($T(' (referencia ') . $ref . ')') . '</span></a>'
            . '</div></li>' . "\n";

        $elementos[] = array('@type' => 'ListItem', 'position' => $pos + 1, 'url' => $DOMINIO . $url, 'name' => $titulo);
    }

    /* ---------- los textos propios de la pagina de un pueblo ---------- */
    $F = array(
        'es' => array('venta' => ' en venta en ', 'alquiler' => ' en alquiler en ', 'y' => ' y ',
                      'inm' => 'Inmuebles', 'ahora' => ' ahora mismo.',
                      'ofi' => ', inmobiliaria con oficina en Arenys de Mar', 'por' => 'Por población:', 'todo' => 'Todo el Maresme',
                      'cats' => array('Pisos', 'casas', 'locales', 'terrenos', 'plazas de aparcamiento')),
        'ca' => array('venta' => ' en venda a ', 'alquiler' => ' de lloguer a ', 'y' => ' i ',
                      'inm' => 'Immobles', 'ahora' => ' ara mateix.',
                      'ofi' => ', immobiliària amb oficina a Arenys de Mar', 'por' => 'Per població:', 'todo' => 'Tot el Maresme',
                      'cats' => array('Pisos', 'cases', 'locals', 'terrenys', 'places d’aparcament')),
        'en' => array('venta' => ' for sale in ', 'alquiler' => ' to rent in ', 'y' => ' and ',
                      'inm' => 'Properties', 'ahora' => ' right now.',
                      'ofi' => ', estate agents with an office in Arenys de Mar', 'por' => 'By town:', 'todo' => 'All the Maresme',
                      'cats' => array('Flats', 'houses', 'commercial premises', 'land', 'parking spaces')),
    );
    $f = $F[$L];
    $canonica = $DOMINIO . $PREFIJO . $NOMBRE;
    $h1 = '';

    if ($pueblo !== null) {
        /* Que hay de verdad en ese pueblo: no se anuncian "pisos y casas"
           donde solo hay plazas de aparcamiento. */
        $grupos = array('~^(piso|atico|bajo|duplex|apart|estudi|loft)~', '~^(casa|chalet|adosad|torre|masia|villa)~',
                        '~^(local|oficina|nave)~', '~^(terren|solar|parcel)~', '~^(garaj|parking|aparcam)~');
        $hay = array();
        foreach ($ver as $n) {
            $tipo = isset($n['tipo']) ? (string) $n['tipo'] : '';
            foreach ($grupos as $g => $patron) { if (preg_match($patron, $tipo)) { $hay[$g] = true; break; } }
        }
        ksort($hay);
        $nombres = array();
        foreach (array_slice(array_keys($hay), 0, 3) as $g) { $nombres[] = $f['cats'][$g]; }
        if (!count($nombres)) { $nombres = array($f['inm']); }
        $enMinuscula = $nombres;
        if ($enMinuscula[0] === $f['inm'] || $L !== 'en') {
            $enMinuscula[0] = function_exists('mb_strtolower') ? mb_strtolower($enMinuscula[0], 'UTF-8') : strtolower($enMinuscula[0]);
        } else {
            $enMinuscula[0] = strtolower($enMinuscula[0]);
        }
        $catsMin = count($enMinuscula) > 1
            ? implode(', ', array_slice($enMinuscula, 0, -1)) . $f['y'] . $enMinuscula[count($enMinuscula) - 1]
            : $enMinuscula[0];
        $nombres[0] = function_exists('mb_strtoupper')
            ? mb_strtoupper(mb_substr($nombres[0], 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($nombres[0], 1, null, 'UTF-8')
            : ucfirst($nombres[0]);
        $cats = count($nombres) > 1
            ? implode(', ', array_slice($nombres, 0, -1)) . $f['y'] . $nombres[count($nombres) - 1]
            : $nombres[0];

        $nVer = count($ver);
        $nInm = $nVer . $T($nVer === 1 ? ' inmueble' : ' inmuebles');

        $h1 = $cats . $f[$OPER] . $pueblo;
        $tituloPag = $h1 . ' · Top House';
        $frase1 = $nInm . $f[$OPER] . $pueblo;
        /* Sin "desde X euros": el mas barato suele ser una plaza de
           aparcamiento, y al lado de "pisos y casas" daria a entender otra
           cosa. */
        $desc = $frase1 . ': ' . $catsMin . '. Top House Real Estate' . $f['ofi'] . '.';
        $largo = function_exists('mb_strlen') ? mb_strlen($desc, 'UTF-8') : strlen($desc);
        if ($largo > 160) { $desc = $frase1 . ': ' . $catsMin . '. Top House Real Estate.'; }

        $tramo = array('ca' => array('venta' => 'comprar', 'alquiler' => 'llogar'),
                       'es' => array('venta' => 'es/comprar', 'alquiler' => 'es/alquilar'),
                       'en' => array('venta' => 'en/buy', 'alquiler' => 'en/rent'));
        $urls = array();
        foreach ($tramo as $cod => $t) { $urls[$cod] = $DOMINIO . '/' . $t[$OPER] . '/' . $pob; }
        $canonicaLista = $canonica;
        $canonica = $urls[$L];

        /* cabecera */
        $HTML = preg_replace('~<title>.*?</title>~s', lit('<title>' . h($tituloPag) . '</title>'), $HTML, 1);
        $HTML = preg_replace('~<meta name="description" content="[^"]*">~', lit('<meta name="description" content="' . h($desc) . '">'), $HTML, 1);
        $HTML = preg_replace('~<meta property="og:title" content="[^"]*">~', lit('<meta property="og:title" content="' . h($tituloPag) . '">'), $HTML, 1);
        $HTML = preg_replace('~<meta property="og:description" content="[^"]*">~', lit('<meta property="og:description" content="' . h($desc) . '">'), $HTML, 1);
        $HTML = preg_replace('~<meta property="og:url" content="[^"]*">~', lit('<meta property="og:url" content="' . h($canonica) . '">'), $HTML, 1);
        $HTML = preg_replace('~<link rel="alternate" hreflang="[^"]*" href="[^"]*">\n?~', '', $HTML);
        $alternas = '';
        foreach ($urls as $cod => $u) { $alternas .= '<link rel="alternate" hreflang="' . $cod . '" href="' . h($u) . '">' . "\n"; }
        $alternas .= '<link rel="alternate" hreflang="x-default" href="' . h($urls['ca']) . '">';
        $HTML = preg_replace('~<link rel="canonical" href="[^"]*">~', lit('<link rel="canonical" href="' . h($canonica) . '">' . "\n" . $alternas), $HTML, 1);

        /* el selector de idioma: a este mismo pueblo en el otro idioma */
        $HTML = preg_replace_callback('~(<a href=")[^"]*(" hreflang="(ca|es|en)" lang=")~', function ($m) use ($urls, $DOMINIO) {
            return $m[1] . h(substr($urls[$m[3]], strlen($DOMINIO))) . $m[2];
        }, $HTML);

        /* el titular y la entradilla */
        $HTML = preg_replace('~(<h1 class="d1">).*?(</h1>)~s', '${1}' . lit(h($h1)) . '${2}', $HTML, 1);
        $HTML = preg_replace_callback('~(<p class="lede">)(.*?)(</p>)~s', function ($m) use ($frase1, $f) {
            /* Se conserva la segunda parte de la entradilla general, que
               ya esta escrita y traducida, detras del dato del pueblo. */
            $resto = strpos($m[2], '. ') !== false ? substr($m[2], strpos($m[2], '. ') + 2) : '';
            return $m[1] . h($frase1 . $f['ahora']) . ($resto !== '' ? ' ' . $resto : '') . $m[3];
        }, $HTML, 1);

        /* cartera.js: que arranque con el pueblo ya elegido */
        $HTML = preg_replace('~(<section class="cart" data-cartera="[a-z]+")~', '${1} data-poblacio="' . lit(h($pueblo)) . '"', $HTML, 1);
        $HTML = preg_replace('~(<button type="button" class="f-limpiar") hidden>~', '${1}>', $HTML, 1);
        $HTML = preg_replace('~<span class="cart__plegar-n" hidden></span>~',
            lit('<span class="cart__plegar-n"><span aria-hidden="true">1</span><span class="sr-only">, 1' . h($T(' filtro activo')) . '</span></span>'), $HTML, 1);

        /* los datos estructurados de la pagina: los mismos, con su direccion */
        $HTML = preg_replace_callback('~(<script type="application/ld\+json">)(.*?)(</script>)~s', function ($m) use ($canonica, $canonicaLista, $tituloPag, $desc, $pueblo) {
            $j = json_decode($m[2], true);
            if (!is_array($j) || !isset($j['@graph'])) { return $m[0]; }
            foreach ($j['@graph'] as &$nodo) {
                if (!isset($nodo['@type'])) { continue; }
                if ($nodo['@type'] === 'WebPage') {
                    $nodo['@id'] = $canonica . '#pagina'; $nodo['url'] = $canonica;
                    $nodo['name'] = $tituloPag; $nodo['description'] = $desc;
                    $nodo['breadcrumb'] = array('@id' => $canonica . '#migas');
                } elseif ($nodo['@type'] === 'BreadcrumbList') {
                    $nodo['@id'] = $canonica . '#migas';
                    $nodo['itemListElement'][] = array('@type' => 'ListItem', 'position' => count($nodo['itemListElement']) + 1,
                                                       'name' => $pueblo, 'item' => $canonica);
                }
            }
            unset($nodo);
            return $m[1] . str_replace('</', '<\\/', json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . $m[3];
        }, $HTML, 1);
    }

    /* ---------- los enlaces por poblacion ---------- */
    $enlaces = array();
    if ($pueblo !== null) {
        $enlaces[] = '<a href="' . h($PREFIJO . $NOMBRE) . '">' . h($f['todo']) . ' (' . count($todos) . ')</a>';
    }
    $base = $PREFIJO . preg_replace('/\.html$/', '', $NOMBRE) . '/';
    foreach ($conPagina as $s => $info) {
        if ($pueblo !== null && $s === $pob) { continue; }
        $enlaces[] = '<a href="' . h($base . $s) . '">' . h($info['nombre']) . ' (' . (int) $info['n'] . ')</a>';
    }
    if (count($enlaces)) {
        $linea = '<p class="cart__pobles mono">' . h($f['por']) . ' ' . implode(' · ', $enlaces) . '</p>';
        $HTML = preg_replace('~(<section class="cart__head">.*?)(</section>)~s', '${1}  ' . lit($linea) . "\n" . '${2}', $HTML, 1);
    }

    /* ---------- la lista, el recuento y el buscador ---------- */
    $nVer = count($ver);
    $cuenta = $nVer . $T($nVer === 1 ? ' inmueble' : ' inmuebles');
    $HTML = preg_replace_callback('~<ul class="cart__grid"></ul>~', function () use ($tarjetas, $refs) {
        return '<ul class="cart__grid" data-ssr="' . h(implode('|', $refs)) . '">' . "\n" . $tarjetas . '</ul>';
    }, $HTML, 1);
    $HTML = preg_replace_callback('~(<p class="cart__cuenta mono"[^>]*>)(</p>)~', function ($m) use ($cuenta) {
        return $m[1] . h($cuenta) . $m[2];
    }, $HTML, 1);
    /* El buscador nace escondido y lo destapaba cartera.js; con la lista ya
       puesta, llega destapado (y plegado en el movil) para que nada salte. */
    $HTML = preg_replace('~<div class="cart__filtros"([^>]*?) hidden>~', '<div class="cart__filtros plegable"${1}>', $HTML, 1);

    /* ---------- datos estructurados de la lista ---------- */
    $itemList = array('@context' => 'https://schema.org', '@type' => 'ItemList', '@id' => $canonica . '#llista',
                      'url' => $canonica, 'numberOfItems' => count($elementos), 'itemListElement' => $elementos);
    if ($h1 !== '') { $itemList['name'] = $h1; }
    $ld = '<script type="application/ld+json">'
        . json_encode($itemList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
        . '</script>' . "\n";
    $corte = strpos($HTML, '</head>');
    if ($corte !== false) { $HTML = substr($HTML, 0, $corte) . $ld . substr($HTML, $corte); }

    /* Si alguna sustitucion ha fallado y ha dejado la pagina a medias, la
       de siempre. */
    if (!is_string($HTML) || strlen($HTML) < strlen(file_get_contents($FICHERO))) {
        throw new \RuntimeException('la pagina ha quedado mas corta que la original');
    }
    echo $HTML;
} catch (\Throwable $e) {
    error_log('llista.php: ' . $e->getMessage() . ' en la linea ' . $e->getLine());
    echo file_get_contents($FICHERO);
}

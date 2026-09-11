<?php
/* =====================================================================
   El puente entre Mobilia y la web
   =====================================================================

   POR QUE EXISTE ESTE FICHERO

   Lo natural seria que la web pidiese los inmuebles a Mobilia
   directamente desde el navegador del visitante. No se puede dar por
   hecho: el navegador solo deja leer datos de otro dominio si ese
   dominio envia una cabecera de permiso (CORS). Si Mobilia no la envia,
   no hay nada que hacer desde el codigo de la web.

   Este fichero lo evita. Corre en NUESTRO servidor, que no tiene esa
   limitacion, pide los datos a Mobilia y se los sirve a la web desde el
   mismo dominio. De paso resuelve otras tres cosas:

     - Las credenciales no viajan al navegador. Si la llamada a Mobilia
       lleva usuario, clave o token, aqui se queda.
     - Se guarda el resultado un rato, asi que mil visitas no son mil
       llamadas a Mobilia.
     - Si Mobilia falla o tarda, se sirve la ultima copia buena en vez de
       dejar la cartera vacia.

   DONDE VA LA CONFIGURACION

   NO en este fichero, y no en git. Se crea un fichero aparte FUERA de
   public_html, asi que los despliegues nunca lo tocan ni acaba en el
   repositorio:

       /home/<usuario>/mobilia-config.php

       <?php
       return array(
         'url'       => 'https://...',  // el feed que de Mobilia
         'cabeceras' => array(),        // p.ej. array('Authorization: Bearer xxx')
         'minutos'   => 15,             // cada cuanto se refresca
         'operacion' => '',             // 'venta' o 'alquiler' si el feed
                                        // es de un solo tipo y no lo dice
         'campos'    => array(),        // solo si hace falta forzar un
                                        // nombre: array('precio'=>'PVP')
         'clave'     => '',             // secreto del modo diagnostico
       );

   COMO SE AVERIGUAN LOS NOMBRES DE LOS CAMPOS

   No se adivinan. El traductor de abajo reconoce los nombres habituales
   (precio, price, preu, pvp, importe...) y, si algo no lo reconoce, NO
   se lo inventa: descarta el inmueble y lo cuenta. Para ver que llega de
   verdad, se pone una 'clave' en la configuracion y se abre:

       https://www.tophouserealestate.es/api/cartera.php?diagnostico=CLAVE

   Eso devuelve los nombres de campo que manda Mobilia, con cual se ha
   emparejado cada uno y cuales se han quedado sin emparejar. Con esa
   respuesta se cierra el mapeo en un minuto y sin adivinar nada.
   ===================================================================== */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/* La copia de seguridad va junto a la configuracion, FUERA de public_html:
   nadie la puede pedir por web, y en un alojamiento compartido no se cruza
   con la de otra cuenta como pasaria en /tmp. Si esa carpeta no dejase
   escribir, se cae a la temporal con un nombre propio de esta instalacion. */
$PRIVADO = dirname(dirname(__DIR__));
$CACHE = is_writable($PRIVADO)
    ? $PRIVADO . '/cartera-cache.json'
    : sys_get_temp_dir() . '/cartera-' . substr(sha1(__DIR__), 0, 12) . '.json';

/* Los mensajes de fallo detallados van al registro del servidor, no al
   visitante: el detalle suele nombrar el servidor de Mobilia y esa no es
   informacion que tenga que salir a la calle. */
function apuntar($motivo) {
    error_log('cartera.php: ' . $motivo);
}

function responder($datos, $origen, $cachear = true) {
    /* La CDN puede guardar la respuesta buena un rato: es exactamente lo
       que hace este fichero de todos modos. Los errores y el diagnostico
       no se guardan nunca, o se quedaria pegado el fallo. */
    header('Cache-Control: ' . ($cachear ? 'public, max-age=300' : 'no-store'));
    header('X-Origen: ' . preg_replace('/[^\x20-\x7e]/', ' ', $origen));
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function servir_copia($cache, $motivo) {
    apuntar($motivo);
    if (is_readable($cache)) {
        $viejo = json_decode(file_get_contents($cache), true);
        if (is_array($viejo) && count($viejo)) {
            /* Mejor una cartera de hace un rato que una pagina vacia. */
            responder($viejo, 'copia guardada');
        }
    }
    http_response_code(503);
    /* Al visitante, nada: el motivo esta en el registro y en ?diagnostico. */
    responder(array('error' => 'cartera no disponible'), 'sin datos', false);
}

/* ---------- 1. la configuracion, que vive fuera de public_html ---------- */
$rutaCfg = dirname(dirname(__DIR__)) . '/mobilia-config.php';
if (!is_readable($rutaCfg)) {
    servir_copia($CACHE, 'falta mobilia-config.php');
}
$cfg = include $rutaCfg;
if (!is_array($cfg) || empty($cfg['url'])) {
    servir_copia($CACHE, 'mobilia-config.php no trae url');
}

$clave  = isset($cfg['clave']) ? (string) $cfg['clave'] : '';
$pedida = isset($_GET['diagnostico']) ? (string) $_GET['diagnostico'] : '';
/* hash_equals compara en tiempo constante: asi la clave no se puede
   adivinar a base de medir cuanto tarda en contestar. */
/* Una clave corta se adivina a fuerza bruta, y detras de esta puerta hay
   datos de propietarios. Menos de 16 caracteres y el diagnostico no existe. */
$DIAG = (strlen($clave) >= 16 && $pedida !== '' && hash_equals($clave, $pedida));

if ($pedida !== '' && !$DIAG) {
    http_response_code(403);
    apuntar('diagnostico rechazado (clave incorrecta, ausente o de menos de 16 caracteres)');
    responder(array('error' => 'no disponible'), 'diagnostico', false);
}

/* ---------- 2. si la copia es reciente, no se molesta a Mobilia ---------- */
$minutos = isset($cfg['minutos']) ? (int) $cfg['minutos'] : 15;
if (!$DIAG && is_readable($CACHE) && (time() - filemtime($CACHE)) < $minutos * 60) {
    $viejo = json_decode(file_get_contents($CACHE), true);
    if (is_array($viejo) && count($viejo)) {
        responder($viejo, 'copia reciente');
    }
}

/* ---------- 3. pedir a Mobilia ---------- */
if (!function_exists('curl_init')) {
    servir_copia($CACHE, 'el servidor no tiene curl');
}
$ch = curl_init($cfg['url']);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_CONNECTTIMEOUT => 6,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_HTTPHEADER     => isset($cfg['cabeceras']) ? $cfg['cabeceras'] : array(),
    CURLOPT_USERAGENT      => 'TopHouseRealEstate/1.0',
));
$cuerpo = curl_exec($ch);
$codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$fallo  = curl_error($ch);
curl_close($ch);

if ($cuerpo === false || $codigo >= 400) {
    servir_copia($CACHE, 'Mobilia responde ' . ($codigo ? $codigo : $fallo));
}

$formato = 'json';
$crudo = json_decode($cuerpo, true);
if (!is_array($crudo)) {
    /* Muchos CRM inmobiliarios sirven XML, que es lo que piden los
       portales. Se convierte a la misma forma que el json y sigue todo
       igual a partir de aqui. */
    $antes = libxml_use_internal_errors(true);
    /* Sin LIBXML_NOENT (no se expanden entidades) y con LIBXML_NONET (no
       sale a la red a buscar un dtd): asi un xml malicioso no puede leer
       ficheros del servidor ni usarnos de puente. */
    $xml = simplexml_load_string($cuerpo, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_clear_errors();
    libxml_use_internal_errors($antes);
    if ($xml === false) {
        servir_copia($CACHE, 'la respuesta no es json ni xml');
    }
    $crudo = json_decode(json_encode($xml), true);
    $formato = 'xml';
}

/* =====================================================================
   4. traducir al formato que espera la web

   La web necesita de cada inmueble:
     ref, operacion ('venta' o 'alquiler'), tipo, titulo, poblacio, zona,
     precio, m2, hab, banys, extras (lista), foto (direccion)

   Los campos se emparejan POR NOMBRE contra la lista de abajo. Nunca por
   posicion ni por parecido: si un nombre no esta en la lista, no se usa.
   Es la diferencia entre no publicar un inmueble y publicarlo con el
   precio de otra cosa.
   ===================================================================== */

$SINONIMOS = array(
    'ref'       => array('ref', 'referencia', 'reference', 'codigo', 'codi', 'code', 'idinmueble', 'idpropiedad', 'propertyid', 'id'),
    'operacion' => array('operacion', 'operacio', 'operation', 'tipooperacion', 'tipusoperacio', 'transaccion', 'oferta', 'accion'),
    'tipo'      => array('tipo', 'tipus', 'type', 'tipoinmueble', 'tipopropiedad', 'propertytype', 'subtipo', 'categoria'),
    'titulo'    => array('titulo', 'titol', 'title', 'nombre', 'nom', 'name', 'descripcioncorta', 'encabezado'),
    'poblacio'  => array('poblacion', 'poblacio', 'municipio', 'municipi', 'ciudad', 'ciutat', 'localidad', 'town', 'city', 'localidadnombre'),
    'zona'      => array('zona', 'barrio', 'barri', 'distrito', 'area', 'district', 'neighbourhood', 'neighborhood'),
    'precio'    => array('precio', 'preu', 'price', 'pvp', 'importe', 'precioventa', 'preciovenda', 'precioinmueble', 'saleprice', 'precioactual'),
    'm2'        => array('m2', 'metros', 'metrosconstruidos', 'superficie', 'superficieconstruida', 'superficieutil', 'built', 'builtarea', 'buildingarea', 'area', 'size', 'msuperficie'),
    'hab'       => array('habitaciones', 'habitacions', 'dormitorios', 'dormitoris', 'bedrooms', 'rooms', 'nhabitaciones', 'numhabitaciones', 'habs'),
    'banys'     => array('banos', 'banyos', 'banys', 'aseos', 'bathrooms', 'nbanos', 'numbanos', 'wc'),
    'extras'    => array('extras', 'caracteristicas', 'caracteristiques', 'features', 'equipamiento', 'servicios', 'amenities', 'etiquetas'),
    'foto'      => array('foto', 'fotos', 'imagen', 'imagenes', 'imatges', 'image', 'images', 'photo', 'photos', 'foto1', 'fotoprincipal', 'urlfoto', 'thumbnail', 'multimedia'),
);

/* El precio del alquiler suele venir en su propio campo, y publicar un
   alquiler con el precio de venta seria el peor error posible. */
$SINONIMOS_ALQUILER = array('precioalquiler', 'preualquiler', 'preulloguer', 'preciolloguer', 'preciomes', 'preciomensual', 'rentprice', 'rent', 'mensualidad', 'renta');

/**
 * Deja un nombre de campo en su forma comparable: sin mayusculas, sin
 * acentos y sin guiones ni barrasbajas. Asi 'Precio_Venta', 'precioVenta'
 * y 'PRECIO VENTA' son el mismo nombre.
 */
function normalizar($nombre) {
    $n = strtolower((string) $nombre);
    $n = strtr($n, array('á'=>'a','à'=>'a','ä'=>'a','â'=>'a','é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
                         'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i','ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o',
                         'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u','ñ'=>'n','ç'=>'c'));
    return preg_replace('/[^a-z0-9]/', '', $n);
}

/**
 * Aplana un inmueble a un indice nombre-normalizado => valor. Los CRM
 * anidan mucho ({datos:{precio:...}}), asi que se baja dos niveles. Gana
 * siempre el campo mas superficial: es el que el CRM considera el bueno.
 */
function aplanar($registro, $profundidad = 0, &$plano = null) {
    if ($plano === null) { $plano = array(); }
    if (!is_array($registro)) { return $plano; }
    foreach ($registro as $clave => $valor) {
        $k = normalizar($clave);
        if ($k !== '' && !array_key_exists($k, $plano)) {
            $plano[$k] = $valor;
        }
        if (is_array($valor) && $profundidad < 2 && !isset($valor[0])) {
            aplanar($valor, $profundidad + 1, $plano);
        }
    }
    return $plano;
}

/** Devuelve el primer sinonimo que exista de verdad, y cual ha sido. */
function buscar($plano, $nombres, &$usado = null) {
    foreach ($nombres as $n) {
        if (array_key_exists($n, $plano) && $plano[$n] !== null && $plano[$n] !== '') {
            $usado = $n;
            return $plano[$n];
        }
    }
    $usado = null;
    return null;
}

/** Un numero de verdad, aguantando '285.000 €', '285000,50' y 285000. */
function numero($valor) {
    if (is_array($valor)) { return 0; }
    $s = trim((string) $valor);
    if ($s === '') { return 0; }
    $s = preg_replace('/[^0-9,.\-]/', '', $s);
    /* Si hay coma y punto, el ultimo que aparece es el decimal. */
    $ultimaComa  = strrpos($s, ',');
    $ultimoPunto = strrpos($s, '.');
    if ($ultimaComa !== false && $ultimoPunto !== false) {
        $dec = $ultimaComa > $ultimoPunto ? ',' : '.';
        $mil = $dec === ',' ? '.' : ',';
        $s = str_replace($mil, '', $s);
        $s = str_replace($dec, '.', $s);
    } elseif ($ultimaComa !== false) {
        /* Una coma sola: decimal si deja 1 o 2 cifras detras, si no es de miles. */
        $s = (strlen($s) - $ultimaComa - 1) <= 2 ? str_replace(',', '.', $s) : str_replace(',', '', $s);
    } elseif ($ultimoPunto !== false) {
        $s = (strlen($s) - $ultimoPunto - 1) <= 2 ? $s : str_replace('.', '', $s);
    }
    return is_numeric($s) ? (float) $s : 0;
}

/** Un texto plano, venga como venga (los XML traen {'0'=>'texto'}). */
function texto($valor) {
    if (is_array($valor)) {
        if (isset($valor[0]) && !is_array($valor[0])) { return trim((string) $valor[0]); }
        foreach ($valor as $v) { if (!is_array($v) && (string) $v !== '') { return trim((string) $v); } }
        return '';
    }
    return trim((string) $valor);
}

/** La primera foto, venga como cadena, como lista o como lista de objetos. */
function primeraFoto($valor, $hondo = 0) {
    if ($hondo > 3) { return ''; }
    $candidatos = is_array($valor) ? $valor : array($valor);
    foreach ($candidatos as $c) {
        /* Una lista dentro de la lista (tipico del xml) se mira por dentro. */
        if (is_array($c) && isset($c[0])) {
            $dentro = primeraFoto($c, $hondo + 1);
            if ($dentro !== '') { return $dentro; }
            continue;
        }
        if (is_array($c)) {
            $plano = aplanar($c);
            foreach (array('url', 'src', 'href', 'imagen', 'image', 'foto', 'ruta', 'file') as $k) {
                if (!empty($plano[$k]) && !is_array($plano[$k])) { $c = $plano[$k]; break; }
            }
        }
        if (is_array($c)) { continue; }
        $s = trim((string) $c);
        /* Solo http(s): asi una direccion rara del feed no puede acabar
           siendo un javascript: en el navegador del visitante. */
        if (preg_match('#^https?://#i', $s)) { return $s; }
    }
    return '';
}

/** Los extras como lista de textos, sin objetos ni vacios. */
function listaExtras($valor, $hondo = 0) {
    if ($hondo > 3) { return array(); }
    if (!is_array($valor)) {
        $s = trim((string) $valor);
        return $s === '' ? array() : array_values(array_filter(array_map('trim', explode(',', $s))));
    }
    $fuera = array();
    foreach ($valor as $v) {
        /* El xml agrupa (<features><feature>..</feature></features>), asi que
           una lista dentro de la lista son mas extras, no un extra raro. */
        if (is_array($v) && isset($v[0])) {
            foreach (listaExtras($v, $hondo + 1) as $sub) { $fuera[] = $sub; }
            continue;
        }
        $s = texto($v);
        if ($s !== '') { $fuera[] = $s; }
    }
    return array_slice(array_values(array_unique($fuera)), 0, 12);
}

/**
 * Encuentra la lista de inmuebles dentro de la respuesta, se llame como
 * se llame el envoltorio ({propiedades:[...]}, {properties:{property:[]}}...).
 */
function localizarLista($crudo, $profundidad = 0) {
    if (!is_array($crudo)) { return array(); }
    if (isset($crudo[0]) && is_array($crudo[0])) { return $crudo; }
    if ($profundidad > 3) { return array(); }
    foreach ($crudo as $valor) {
        if (!is_array($valor)) { continue; }
        $hallado = localizarLista($valor, $profundidad + 1);
        if (count($hallado)) { return $hallado; }
    }
    /* Un solo inmueble sin envolver en lista tambien es una lista de uno. */
    $plano = aplanar($crudo);
    if (isset($plano['precio']) || isset($plano['referencia']) || isset($plano['ref'])) {
        return array($crudo);
    }
    return array();
}

/**
 * Traduce la respuesta de Mobilia al formato de la web.
 *
 * Descarta sin contemplaciones el inmueble al que no le encuentra precio
 * u operacion: una ficha con el precio en blanco es peor que una ficha
 * que no esta. Lo descartado se cuenta y sale en el diagnostico.
 */
function traducir($crudo, $cfg, $SINONIMOS, $SINONIMOS_ALQUILER, &$informe) {
    $lista = localizarLista($crudo);
    $forzados = isset($cfg['campos']) && is_array($cfg['campos']) ? $cfg['campos'] : array();
    $porDefecto = isset($cfg['operacion']) ? strtolower((string) $cfg['operacion']) : '';

    $informe = array(
        'registros'    => count($lista),
        'publicados'   => 0,
        'descartados'  => array(),
        'emparejados'  => array(),
        'sinemparejar' => array(),
        'camposvistos' => array(),
    );

    $fuera = array();
    $indice = 0;
    foreach ($lista as $p) {
        $indice++;
        if (!is_array($p)) { continue; }
        $plano = aplanar($p);
        $tocados = array();

        $lee = function ($campo) use ($plano, $SINONIMOS, $forzados, &$tocados, &$informe) {
            $nombres = isset($SINONIMOS[$campo]) ? $SINONIMOS[$campo] : array();
            if (isset($forzados[$campo])) {
                array_unshift($nombres, normalizar($forzados[$campo]));
            }
            $usado = null;
            $valor = buscar($plano, $nombres, $usado);
            if ($usado !== null) {
                $tocados[$usado] = true;
                $informe['emparejados'][$campo] = $usado;
            }
            return $valor;
        };

        /* La operacion primero: de ella depende que precio es el bueno. */
        $bruta = texto($lee('operacion'));
        $n = normalizar($bruta);
        if ($n !== '' && (strpos($n, 'alq') !== false || strpos($n, 'llog') !== false ||
                          strpos($n, 'rent') !== false || strpos($n, 'arrend') !== false)) {
            $operacion = 'alquiler';
        } elseif ($n !== '' && (strpos($n, 'vent') !== false || strpos($n, 'venda') !== false ||
                                strpos($n, 'sale') !== false || strpos($n, 'sell') !== false ||
                                strpos($n, 'compra') !== false)) {
            $operacion = 'venta';
        } else {
            /* Sin campo de operacion no se inventa: o lo dice la
               configuracion (feed de un solo tipo) o el inmueble no sale. */
            $operacion = ($porDefecto === 'venta' || $porDefecto === 'alquiler') ? $porDefecto : '';
        }

        $nombresPrecio = $SINONIMOS['precio'];
        if ($operacion === 'alquiler') {
            $nombresPrecio = array_merge($SINONIMOS_ALQUILER, $nombresPrecio);
        }
        $usadoPrecio = null;
        if (isset($forzados['precio'])) { array_unshift($nombresPrecio, normalizar($forzados['precio'])); }
        $precio = numero(buscar($plano, $nombresPrecio, $usadoPrecio));
        if ($usadoPrecio !== null) {
            $tocados[$usadoPrecio] = true;
            $informe['emparejados']['precio'] = $usadoPrecio;
        }

        $ref = texto($lee('ref'));

        if ($operacion === '') {
            $informe['descartados'][] = array('n' => $indice, 'ref' => $ref, 'motivo' => 'sin operacion (venta/alquiler)');
            continue;
        }
        if ($precio <= 0) {
            $informe['descartados'][] = array('n' => $indice, 'ref' => $ref, 'motivo' => 'sin precio reconocible');
            continue;
        }

        /* 'area' vale por barrio en unos feeds y por superficie en otros.
           Un barrio nunca es un numero pelado, asi que si llega un numero se
           descarta: mas vale sin barrio que con '105' de nombre de barrio. */
        $zona = texto($lee('zona'));
        if ($zona !== '' && preg_match('/^[0-9.,]+$/', $zona)) { $zona = ''; }

        $ficha = array(
            'ref'       => $ref !== '' ? $ref : ('THR-' . $indice),
            'operacion' => $operacion,
            'tipo'      => strtolower(texto($lee('tipo'))),
            'titulo'    => texto($lee('titulo')),
            'poblacio'  => texto($lee('poblacio')),
            'zona'      => $zona,
            'precio'    => $precio,
            'm2'        => (int) numero($lee('m2')),
            'hab'       => (int) numero($lee('hab')),
            'banys'     => (int) numero($lee('banys')),
            'extras'    => listaExtras($lee('extras')),
            'foto'      => primeraFoto($lee('foto')),
        );

        /* Sin titulo la ficha sigue siendo util: se compone uno con lo
           que si se sabe, que es lo que hace el propio portal. */
        if ($ficha['titulo'] === '') {
            $partes = array_filter(array($ficha['tipo'], $ficha['poblacio']));
            $ficha['titulo'] = count($partes) ? ucfirst(implode(' en ', $partes)) : $ficha['ref'];
        }

        $fuera[] = $ficha;
        $informe['publicados']++;

        /* Para el diagnostico: que nombres han llegado y cuales no se han
           usado. Ahi es donde se ve si falta un sinonimo. */
        if ($indice === 1) {
            $informe['camposvistos'] = array_keys($plano);
        }
        foreach ($plano as $k => $v) {
            if (!isset($tocados[$k]) && !in_array($k, $informe['sinemparejar'], true)) {
                $informe['sinemparejar'][] = $k;
            }
        }
    }
    return $fuera;
}

$informe = array();
$limpio = traducir($crudo, $cfg, $SINONIMOS, $SINONIMOS_ALQUILER, $informe);

/* ---------- 5. el modo diagnostico ---------- */
/**
 * Tapa los datos personales de la muestra del diagnostico. Del propietario
 * de un piso no hace falta saber nada para emparejar campos: basta con ver
 * COMO SE LLAMA el campo y de que tipo es. Se conserva el nombre y se
 * sustituye el contenido.
 */
function tapar($registro, $hondo = 0) {
    if (!is_array($registro) || $hondo > 3) { return $registro; }
    $delicados = array('propietario', 'telefono', 'movil', 'mobil', 'email', 'correo',
                       'nif', 'dni', 'cif', 'nombrepropietario', 'contacto', 'observaciones',
                       'notas', 'notasinternas', 'direccion', 'calle', 'numero', 'piso',
                       'portal', 'catastro', 'referenciacatastral', 'iban', 'titular');
    $fuera = array();
    foreach ($registro as $clave => $valor) {
        if (in_array(normalizar($clave), $delicados, true)) {
            $fuera[$clave] = '(tapado: ' . gettype($valor) . ')';
        } elseif (is_array($valor)) {
            $fuera[$clave] = tapar($valor, $hondo + 1);
        } else {
            /* Un texto largo puede ser la descripcion, y ahi la gente escribe
               de todo. Se recorta: para ver el nombre del campo sobra. */
            $t = (string) $valor;
            $fuera[$clave] = strlen($t) > 80 ? substr($t, 0, 80) . '...' : $valor;
        }
    }
    return $fuera;
}

if ($DIAG) {
    $lista = localizarLista($crudo);
    $muestra = array();
    if (isset($lista[0])) { $muestra = tapar($lista[0]); }
    responder(array(
        'formato'       => $formato,
        'http'          => $codigo,
        'registros'     => $informe['registros'],
        'publicados'    => $informe['publicados'],
        'descartados'   => array_slice($informe['descartados'], 0, 20),
        'emparejados'   => $informe['emparejados'],
        'sin_emparejar' => $informe['sinemparejar'],
        'campos_vistos' => $informe['camposvistos'],
        'primer_registro_tal_cual' => $muestra,
    ), 'diagnostico', false);
}

if (!count($limpio)) {
    /* Traducir a cero inmuebles casi siempre significa que los nombres de
       los campos no coinciden, no que la cartera este vacia de verdad. */
    servir_copia($CACHE, 'traducidos 0 de ' . $informe['registros'] . ' registros: usar ?diagnostico=CLAVE');
}

/* Escritura atomica: si el proceso muere a medias, la copia buena sigue
   entera y no se queda un json cortado que romperia la siguiente visita. */
$tmp = $CACHE . '.' . getmypid();
if (file_put_contents($tmp, json_encode($limpio, JSON_UNESCAPED_UNICODE)) !== false) {
    @rename($tmp, $CACHE);
} else {
    @unlink($tmp);
}

responder($limpio, 'recien traido de Mobilia (' . $informe['publicados'] . ' de ' . $informe['registros'] . ')');

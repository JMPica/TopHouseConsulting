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
         // la direccion que devuelve los inmuebles
         'url'   => 'https://api.mobiliagestion.es/api/v1/inmuebles',

         // Mobilia usa OAuth 2.0: el client_id y el client_secret salen
         // de Mobilia > Configuracion > Integraciones > API
         // Desarrolladores > Anadir aplicacion cliente.
         'oauth' => array(
           'url_token'     => 'https://api.mobiliagestion.es/api/v1/token',
           'client_id'     => '...',
           'client_secret' => '...',
           'grant_type'    => 'client_credentials',
           'scope'         => '',       // solo si Mobilia lo pide
         ),

         'cabeceras' => array(),        // cabeceras extra, casi nunca hacen falta
         'minutos'   => 15,             // cada cuanto se refresca la cartera
         'operacion' => '',             // 'venta' o 'alquiler' si la respuesta
                                        // es de un solo tipo y no lo dice
         'campos'    => array(),        // solo si hace falta forzar un
                                        // nombre: array('precio'=>'PVP')
         'clave'     => '',             // secreto del diagnostico, 16+ letras
       );

   El token que devuelve Mobilia se guarda en mobilia-token.json, en esa
   misma carpeta de fuera de public_html, y se reutiliza hasta que
   caduca. Ese fichero se puede borrar en cualquier momento: se vuelve a
   pedir solo.

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

    /* json_encode devuelve FALSE si en los datos hay un solo byte que no
       sea UTF-8 valido, y 'echo false' no escribe nada: la respuesta salia
       vacia, con codigo 200 y sin un mal error en ningun sitio. Una pagina
       en blanco sin motivo es lo mas caro de diagnosticar que existe, y
       aqui se llevaria por delante la cartera entera por culpa de un
       acento mal codificado en la descripcion de un piso. */
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        apuntar('json_encode ha fallado (' . json_last_error_msg() . '), se reintenta sustituyendo lo que no es utf-8');
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
    if ($json === false) {
        /* Ni asi. Se contesta algo honesto en vez de nada. */
        apuntar('json_encode ha fallado incluso sustituyendo');
        $json = '{"error":"la respuesta no se ha podido codificar"}';
    }
    echo $json;
    exit;
}

/**
 * Quita de la lista lo que solo necesita la ficha de un inmueble.
 *
 * La galeria entera y la descripcion se GUARDAN en la copia -de ahi las
 * lee la pagina de cada inmueble- pero no se MANDAN a la pagina de la
 * cartera, que ensena una foto por tarjeta y ninguna descripcion. Con
 * treinta y cuatro inmuebles a treinta fotos cada uno, mandarlas seria
 * cargarle al visitante cien kilobytes que no va a mirar, y la mayoria
 * entra desde el movil.
 */
function aligerar($lista) {
    $fuera = array();
    foreach ($lista as $ficha) {
        if (!is_array($ficha)) { continue; }
        unset($ficha['fotos'], $ficha['descripcio']);
        if (isset($ficha['i18n']) && is_array($ficha['i18n'])) {
            foreach ($ficha['i18n'] as $idioma => $campos) {
                unset($campos['descripcio']);
                if (count($campos)) { $ficha['i18n'][$idioma] = $campos; }
                else { unset($ficha['i18n'][$idioma]); }
            }
            if (!count($ficha['i18n'])) { unset($ficha['i18n']); }
        }
        $fuera[] = $ficha;
    }
    return $fuera;
}

function servir_copia($cache, $motivo) {
    apuntar($motivo);
    if (is_readable($cache)) {
        $viejo = json_decode(file_get_contents($cache), true);
        if (is_array($viejo) && count($viejo)) {
            /* Mejor una cartera de hace un rato que una pagina vacia. */
            responder(aligerar($viejo), 'copia guardada');
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
$oauth = isset($cfg['oauth']) && is_array($cfg['oauth']) ? $cfg['oauth'] : null;

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
        responder(aligerar($viejo), 'copia reciente');
    }
}

/* =====================================================================
   3. El billete: OAuth 2.0

   Mobilia no publica un fichero que se pueda pedir y ya. Tiene una API
   con puerta: primero se cambian un client_id y un client_secret por un
   token, y ese token es el que abre la cartera. El token caduca, asi
   que hay que pedir uno nuevo de vez en cuando.

   El token se guarda en disco junto a la configuracion, FUERA de
   public_html, y se reutiliza mientras le quede vida. Sin eso cada
   visita a la web serian dos llamadas a Mobilia en vez de ninguna.

   El estandar de OAuth dice que la peticion va como formulario, pero no
   todas las implementaciones lo cumplen, asi que si el formulario falla
   se reintenta en json. Cual de las dos ha funcionado sale en el
   diagnostico, para no tener que adivinarlo nunca mas.
   ===================================================================== */
if (!function_exists('curl_init')) {
    servir_copia($CACHE, 'el servidor no tiene curl');
}

$notaOAuth = 'sin oauth';

function pedir($url, $opciones) {
    $ch = curl_init($url);
    curl_setopt_array($ch, $opciones + array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_USERAGENT      => 'TopHouseRealEstate/1.0',
    ));
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $fallo  = curl_error($ch);
    curl_close($ch);
    return array($cuerpo, $codigo, $fallo);
}

function token_guardado($ruta) {
    if (!is_readable($ruta)) { return null; }
    $t = json_decode(file_get_contents($ruta), true);
    /* Con un minuto de margen: un token que caduca mientras viaja la
       peticion da un 401 que no se entiende al leer el registro. */
    if (is_array($t) && !empty($t['token']) && isset($t['caduca']) && $t['caduca'] > time() + 60) {
        return $t['token'];
    }
    return null;
}

function guardar_token($ruta, $token, $segundos) {
    $tmp = $ruta . '.' . getmypid();
    $dato = json_encode(array('token' => $token, 'caduca' => time() + max(60, (int) $segundos)));
    if (file_put_contents($tmp, $dato) !== false) {
        @chmod($tmp, 0600);
        @rename($tmp, $ruta);
    } else { @unlink($tmp); }
}

function conseguir_token($oauth, $ruta, &$nota) {
    $guardado = token_guardado($ruta);
    if ($guardado) { $nota = 'token reutilizado'; return $guardado; }

    $campos = array(
        'grant_type'    => isset($oauth['grant_type']) ? $oauth['grant_type'] : 'client_credentials',
        'client_id'     => isset($oauth['client_id']) ? $oauth['client_id'] : '',
        'client_secret' => isset($oauth['client_secret']) ? $oauth['client_secret'] : '',
    );
    if (!empty($oauth['scope'])) { $campos['scope'] = $oauth['scope']; }

    $intentos = array(
        array('como' => 'formulario',
              'op' => array(CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => http_build_query($campos),
                            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded',
                                                        'Accept: application/json'))),
        array('como' => 'json',
              'op' => array(CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => json_encode($campos),
                            CURLOPT_HTTPHEADER => array('Content-Type: application/json',
                                                        'Accept: application/json'))),
    );

    foreach ($intentos as $i) {
        list($cuerpo, $codigo, $fallo) = pedir($oauth['url_token'], $i['op']);
        if ($cuerpo === false || $codigo >= 400) {
            $nota = 'token rechazado (' . $i['como'] . '): ' . ($codigo ? $codigo : $fallo);
            continue;
        }
        $r = json_decode($cuerpo, true);
        if (!is_array($r)) { $nota = 'token: respuesta ilegible (' . $i['como'] . ')'; continue; }
        /* Cada implementacion lo llama a su manera. */
        $token = '';
        foreach (array('access_token', 'accessToken', 'token', 'bearer') as $k) {
            if (!empty($r[$k]) && is_string($r[$k])) { $token = $r[$k]; break; }
        }
        if ($token === '') { $nota = 'token: la respuesta no trae access_token (' . $i['como'] . ')'; continue; }
        $vida = 3600;
        foreach (array('expires_in', 'expiresIn', 'expira_en') as $k) {
            if (!empty($r[$k])) { $vida = (int) $r[$k]; break; }
        }
        guardar_token($ruta, $token, $vida);
        $nota = 'token nuevo por ' . $i['como'] . ', vale ' . $vida . 's';
        return $token;
    }
    return null;
}

$cabeceras = isset($cfg['cabeceras']) && is_array($cfg['cabeceras']) ? $cfg['cabeceras'] : array();

if ($oauth && !empty($oauth['url_token'])) {
    $rutaToken = $PRIVADO . '/mobilia-token.json';
    $token = conseguir_token($oauth, $rutaToken, $notaOAuth);
    if (!$token) {
        servir_copia($CACHE, 'no se ha podido conseguir el token: ' . $notaOAuth);
    }
    $cabeceras[] = 'Authorization: Bearer ' . $token;
    $cabeceras[] = 'Accept: application/json';
}
/* LA MARCA DE AGUA.

   Las fotos de Mobilia salen con el logotipo de TOP HOUSE CONSULTING
   incrustado, que es la marca vieja. En la web nueva eso es una marca que
   ya no existe, puesta encima de cada foto de cada ficha.

   El manual de la exportacion documenta un parametro para pedirlas sin
   marca: marcaAgua=0. Afecta solo a ESTA descarga, asi que lo que Mobilia
   publica en Idealista o en Fotocasa sigue saliendo como saliera. No hay
   que pedirle nada a nadie ni tocar nada del lado de Mobilia.

   Se anade solo si la direccion no lo trae ya, para que quien escriba la
   configuracion pueda decidir otra cosa: con 'marca_agua' => true en
   mobilia-config.php vuelven a venir con marca. */
$url = $cfg['url'];
if (strpos($url, 'marcaAgua=') === false && stripos($url, 'ExportarInmuebles') !== false) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . 'marcaAgua=' . (!empty($cfg['marca_agua']) ? '1' : '0');
}

list($cuerpo, $codigo, $fallo) = pedir($url, array(CURLOPT_HTTPHEADER => $cabeceras));

/* Un 401 con token casi siempre significa que el guardado ya no vale
   aunque la fecha dijera que si. Se tira y se pide uno nuevo, una vez. */
if ($oauth && $codigo == 401) {
    @unlink($PRIVADO . '/mobilia-token.json');
    $token = conseguir_token($oauth, $PRIVADO . '/mobilia-token.json', $notaOAuth);
    if ($token) {
        /* Se quita la cabecera del token caducado antes de poner la
           nueva. Anadirla sin mas manda dos Authorization en la misma
           peticion, y hay servidores que ante eso responden 400. */
        $limpias = array();
        foreach ($cabeceras as $c) {
            if (stripos($c, 'authorization:') !== 0) { $limpias[] = $c; }
        }
        $limpias[] = 'Authorization: Bearer ' . $token;
        $cabeceras = $limpias;
        list($cuerpo, $codigo, $fallo) = pedir($url, array(CURLOPT_HTTPHEADER => $cabeceras));
        $notaOAuth .= ' (tras un 401)';
    }
}

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
    /* Mobilia marca en su propio panel los inmuebles que quiere empujar.
       Ese es el unico dato de 'popularidad' que manda: no hay visitas ni
       contactos en el feed, asi que el criterio lo pone la agencia. */
    'destacado' => array('destacado', 'destacada', 'destaque', 'featured', 'highlight', 'resaltado'),
    'fecha'     => array('fechamodificacion', 'fecha', 'fechacreacion', 'fechaalta', 'updated', 'modified', 'date'),
    /* La calificacion energetica es OBLIGATORIA en la publicidad de un
       inmueble en venta o alquiler, y el propio manual de Mobilia la
       describe como 'a spanish mandatory value'. La web no la ensenaba. */
    'energia'   => array('calificacionenergetica', 'certificadoenergetico', 'eficienciaenergetica', 'energia'),
    /* La ampliada primero: es la que escribe el agente. La corta la genera
       Mobilia sola ('Piso en venta en Arenys, 4 habitaciones') y no aporta
       nada que la ficha no ensene ya. */
    'descripcio'=> array('descripcionampliada', 'descripcion', 'descripcio', 'description', 'observacionespublicas', 'textoweb'),
);

/* El precio del alquiler suele venir en su propio campo, y publicar un
   alquiler con el precio de venta seria el peor error posible. */
$SINONIMOS_ALQUILER = array('precioalquiler', 'preualquiler', 'preulloguer', 'preciolloguer', 'preciomes', 'preciomensual', 'rentprice', 'rent', 'mensualidad', 'renta');

/* Los idiomas de la web y el sufijo con que Mobilia nombra cada uno.
   El castellano es el campo sin sufijo, asi que no esta en la tabla. */
$IDIOMAS = array('ca' => 'Ca', 'en' => 'En');

/* El nombre de cada tipo en cada idioma.

   Hace falta AQUI, y no solo en la web, porque Mobilia rellena el titulo
   en castellano y deja el catalan y el ingles vacios casi siempre. Ese
   titulo hay que componerlo, y tiene que salir igual en la tarjeta de la
   cartera y en la ficha del inmueble: una se pinta en el navegador y la
   otra sale hecha del servidor, asi que si cada una lo compusiera por su
   cuenta acabarian diciendo cosas distintas del mismo piso.

   No se usan los que manda Mobilia porque los manda en plural: saldria
   'Pisos a Arenys de Mar' en vez de 'Pis a Arenys de Mar'. */
$NOMBRE_TIPO = array(
    'piso'     => array('ca' => 'Pis',            'es' => 'Piso',                 'en' => 'Flat'),
    'atico'    => array('ca' => 'Àtic',           'es' => 'Ático',                'en' => 'Penthouse'),
    'casa'     => array('ca' => 'Casa',           'es' => 'Casa',                 'en' => 'House'),
    'bajo'     => array('ca' => 'Planta baixa',   'es' => 'Planta baja',          'en' => 'Ground floor'),
    'local'    => array('ca' => 'Local',          'es' => 'Local',                'en' => 'Commercial unit'),
    'terreno'  => array('ca' => 'Terreny',        'es' => 'Terreno',              'en' => 'Land'),
    'garaje'   => array('ca' => 'Plaça d’aparcament', 'es' => 'Plaza de aparcamiento', 'en' => 'Parking space'),
    'trastero' => array('ca' => 'Traster',        'es' => 'Trastero',             'en' => 'Storage room'),
);

/* LAS CARACTERISTICAS.

   Mobilia no manda una lista de extras: manda un centenar de casillas
   sueltas, una por cosa (Ascensor 1, PiscinaPrivada 0, Trasteros 2...).
   Por eso las fichas salian sin ni una sola caracteristica teniendo toda
   la informacion delante.

   Aqui se eligen las que de verdad mira quien compra, y en el orden en
   que las mira: primero lo que decide una visita (playa, vistas, piscina,
   ascensor) y al final la letra pequena. Van como CLAVE, no como texto,
   por lo mismo que el tipo: las traduce la web y asi salen iguales en los
   tres idiomas.

   La regla es una sola para todas: cuenta si el campo trae algo que no
   sea vacio ni cero. Vale igual para un si/no ('1'), para un contador
   ('2' trasteros) y para un texto ('calefaccion central'). */
$EXTRAS_MOBILIA = array(
    'primeralineaplaya'    => 'primera-linia',
    'segundalineaplaya'    => 'segona-linia',
    'vistas'               => 'vistes',
    'piscinaprivada'       => 'piscina',
    'piscinacomunitaria'   => 'piscina-comunitaria',
    'ascensor'             => 'ascensor',
    'terrazas'             => 'terrassa',
    'metrosjardin'         => 'jardi',
    'patio'                => 'pati',
    'plazasgaraje'         => 'parquing',
    'plazasparking'        => 'parquing',
    'trasteros'            => 'traster',
    'calefaccion'          => 'calefaccio',
    'aireacondicionado'    => 'aire',
    'chimeneas'            => 'llar-de-foc',
    'amueblado'            => 'moblat',
    'cocinaamueblada'      => 'cuina-equipada',
    'armarios'             => 'armaris',
    'exterior'             => 'exterior',
    'zonascomunes'         => 'zones-comunes',
    'zonasverdes'          => 'zones-verdes',
    'barbacoa'             => 'barbacoa',
    'solarium'             => 'solarium',
    'lavadero'             => 'safareig',
    'bodega'               => 'celler',
    'buhardilla'           => 'golfes',
    'gimnasio'             => 'gimnas',
    'pistapadel'           => 'padel',
    'pistatenis'           => 'tenis',
    'conserje'             => 'conserge',
    'vigilancia24h'        => 'vigilancia',
    'alarma'               => 'alarma',
    'puertablindad'        => 'porta-blindada',
    'adaptado'             => 'adaptat',
    'accesodiscapacitados' => 'adaptat',
    'admitemascotas'       => 'mascotes',
);

/* La CLAVE del tipo tiene que ser la misma en los tres idiomas: es lo que
   compara el filtro, y un desplegable que en catalan filtra por 'Pisos' y
   en ingles por 'Flats' no encuentra nada. Asi que el tipo viaja como
   clave estable y el nombre visible lo pone la web, que ya sabe traducir.
   Mobilia los manda en plural ('Pisos'), de ahi la tabla. */
$TIPOS = array(
    'piso' => 'piso', 'pisos' => 'piso', 'apartamento' => 'piso', 'apartamentos' => 'piso',
    'atico' => 'atico', 'aticos' => 'atico',
    'casa' => 'casa', 'casas' => 'casa', 'chalet' => 'casa', 'chalets' => 'casa',
    'torre' => 'casa', 'torres' => 'casa', 'villa' => 'casa', 'villas' => 'casa',
    'adosado' => 'casa', 'adosados' => 'casa', 'unifamiliar' => 'casa', 'unifamiliares' => 'casa',
    'bajo' => 'bajo', 'bajos' => 'bajo', 'plantabaja' => 'bajo', 'plantasbajas' => 'bajo',
    'local' => 'local', 'locales' => 'local', 'localcomercial' => 'local',
    'terreno' => 'terreno', 'terrenos' => 'terreno', 'solar' => 'terreno', 'solares' => 'terreno',
    'parcela' => 'terreno', 'parcelas' => 'terreno',
    'garaje' => 'garaje', 'garajes' => 'garaje', 'parking' => 'garaje', 'parkings' => 'garaje',
    'plazadeaparcamiento' => 'garaje', 'plazasdeaparcamiento' => 'garaje', 'aparcamiento' => 'garaje',
    'trastero' => 'trastero', 'trasteros' => 'trastero',
);

/**
 * Deja un nombre de campo en su forma comparable: sin mayusculas, sin
 * acentos y sin guiones ni barrasbajas. Asi 'Precio_Venta', 'precioVenta'
 * y 'PRECIO VENTA' son el mismo nombre.
 */
function normalizar($nombre) {
    /* strtolower solo baja la A-Z de toda la vida: una 'A con tilde' la deja
       entera, y el filtro de la ultima linea se la lleva por delante. Asi es
       como 'Aticos' con tilde acababa convertido en 'ticos', no encontraba su
       sitio en la tabla de tipos y salia sin traducir en las tres webs. */
    $n = (string) $nombre;
    $n = function_exists('mb_strtolower') ? mb_strtolower($n, 'UTF-8') : strtolower($n);
    /* Las mayusculas acentuadas siguen en la tabla por si el servidor no
       trae mbstring: entonces la linea de arriba no las ha bajado. */
    $n = strtr($n, array('á'=>'a','à'=>'a','ä'=>'a','â'=>'a','é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
                         'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i','ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o',
                         'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u','ñ'=>'n','ç'=>'c',
                         'Á'=>'a','À'=>'a','Ä'=>'a','Â'=>'a','É'=>'e','È'=>'e','Ë'=>'e','Ê'=>'e',
                         'Í'=>'i','Ì'=>'i','Ï'=>'i','Î'=>'i','Ó'=>'o','Ò'=>'o','Ö'=>'o','Ô'=>'o',
                         'Ú'=>'u','Ù'=>'u','Ü'=>'u','Û'=>'u','Ñ'=>'n','Ç'=>'c'));
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

/**
 * TODAS las fotos, no solo la primera.
 *
 * La pagina de la cartera solo necesita una, pero la ficha de un inmueble
 * necesita la galeria entera, y de Mobilia vienen las dos cosas en el
 * mismo sitio. Se recogen aqui una vez y cada pagina coge lo que usa.
 *
 * Tope de 40: hay inmuebles con mas de treinta fotos y ninguna galeria
 * ensena la numero cuarenta y uno.
 */
function todasLasFotos($valor, $hondo = 0, &$fuera = null) {
    if ($fuera === null) { $fuera = array(); }
    if ($hondo > 3 || count($fuera) >= 40) { return $fuera; }
    foreach (is_array($valor) ? $valor : array($valor) as $c) {
        if (count($fuera) >= 40) { break; }
        if (is_array($c)) {
            /* Un objeto de foto ({url:...}) o una lista dentro de la lista. */
            if (!isset($c[0])) {
                $plano = aplanar($c);
                foreach (array('url', 'src', 'href', 'imagen', 'image', 'foto', 'ruta', 'file') as $k) {
                    if (!empty($plano[$k]) && !is_array($plano[$k])) { $c = $plano[$k]; break; }
                }
            }
            if (is_array($c)) { todasLasFotos($c, $hondo + 1, $fuera); continue; }
        }
        $t = trim((string) $c);
        /* Solo http(s), por lo mismo que en primeraFoto: una direccion
           rara del feed no puede acabar siendo un javascript: en el
           navegador de un visitante. */
        if (preg_match('#^https?://#i', $t) && !in_array($t, $fuera, true)) { $fuera[] = $t; }
    }
    return $fuera;
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

    /* UN INMUEBLE SUELTO SE RECONOCE ANTES DE BAJAR A SUS HIJOS.

       Antes se bajaba primero y se preguntaba despues, y con un feed de UN
       SOLO inmueble eso salia mal: el nodo Operaciones > Operacion de
       Mobilia lleva un precio dentro, asi que se hacia pasar por el
       inmueble y la cartera entera acababa siendo una lista de precios
       sueltos sin referencia ni poblacion.

       Con treinta y cuatro inmuebles no se nota, porque entonces vienen ya
       en lista y se cogen por el primer camino. Se notaria el dia que Top
       House tuviese un unico inmueble publicado, que es justo el dia en
       que menos falta hace que la web falle.

       La senal es la REFERENCIA, no el precio: un precio lo tiene tambien
       un nodo hijo, una referencia solo la tiene el inmueble. Se mira a un
       nivel (aplanar desde 1) para que cuente igual si el CRM la manda
       como atributo, que es como la mandan algunos. */
    if ($profundidad >= 1) {
        $propio = aplanar($crudo, 1);
        if (isset($propio['referencia']) || isset($propio['ref'])) {
            return array($crudo);
        }
    }

    foreach ($crudo as $valor) {
        if (!is_array($valor)) { continue; }
        $hallado = localizarLista($valor, $profundidad + 1);
        if (count($hallado)) { return $hallado; }
    }

    /* Sin referencia por ningun lado, el precio vale de senal; pero solo
       DESPUES de haber mirado dentro, para que gane el inmueble de verdad
       y no el primer nodo hijo que lleve una cifra. */
    $plano = aplanar($crudo);
    if (isset($plano['precio'])) { return array($crudo); }
    return array();
}

/**
 * Compone un titulo cuando el CRM no lo trae en ese idioma. Mobilia
 * rellena el castellano y deja el catalan y el ingles vacios mas veces que
 * no, y una ficha sin titulo en la pagina catalana acabaria ensenando el
 * castellano, que es justo lo que se quiere evitar.
 */
function componerTitulo($tipo, $poblacio, $idioma) {
    $tipo = trim((string) $tipo);
    $poblacio = trim((string) $poblacio);
    if ($tipo === '') { return $poblacio; }
    if ($poblacio === '') { return $tipo; }
    $enlace = array('ca' => ' a ', 'en' => ' in ', 'es' => ' en ');
    return $tipo . (isset($enlace[$idioma]) ? $enlace[$idioma] : ' en ') . $poblacio;
}

/**
 * Una fecha de Mobilia ('11-09-2026 14:24:25', dia primero) convertida en
 * un numero con el que se pueda ordenar. Se acepta tambien el orden
 * internacional por si otro CRM lo manda asi. Lo que no se entiende vale
 * cero, que es lo mismo que decir "el mas viejo de todos".
 */
function fechaMobilia($v) {
    $t = trim((string) $v);
    if ($t === '') { return 0; }
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $t, $m)) {
        return (int) mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
    }
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $t, $m)) {
        return (int) mktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]);
    }
    return 0;
}

/**
 * Mobilia manda las poblaciones como 'Arenys De Mar', con la preposicion
 * en mayuscula. Sale en todas las fichas y en el desplegable, asi que se
 * arregla aqui una vez: las palabras de enlace van en minuscula, salvo la
 * primera.
 */
function poblacionBonita($t) {
    $t = trim((string) $t);
    if ($t === '') { return ''; }
    $enlaces = array('de', 'del', 'la', 'las', 'les', 'los', 'el', 'i', 'y', 'd', 'da', 'dels');
    $partes = preg_split('/\s+/', $t);
    foreach ($partes as $n => $palabra) {
        $baja = function_exists('mb_strtolower') ? mb_strtolower($palabra, 'UTF-8') : strtolower($palabra);
        if ($n > 0 && in_array($baja, $enlaces, true)) { $partes[$n] = $baja; }
    }
    return implode(' ', $partes);
}

/**
 * La lista de destacados PROPIA de la web, si la hay.
 *
 * En Mobilia, la casilla 'Destacado' no es un mando de esta web: alimenta
 * tambien lo que se destaca en Idealista y Fotocasa, que se paga aparte.
 * Tocarla para ordenar la web propia saldria caro en el sitio equivocado.
 *
 * Por eso existe este fichero, al lado de la configuracion y fuera de
 * public_html: un texto con una referencia por linea. Lo que este ahi sale
 * primero en la web y con su distintivo, y en Mobilia no cambia nada.
 *
 *     # los que empujamos esta semana
 *     1418
 *     1500
 *
 * Si el fichero no existe o esta vacio, manda la casilla de Mobilia, que
 * es como estaba antes.
 */
function destacadosPropios($ruta) {
    if (!is_readable($ruta)) { return array(); }
    $fuera = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) file_get_contents($ruta)) as $linea) {
        $linea = trim($linea);
        /* Una almohadilla delante es un comentario: asi se pueden dejar
           referencias apuntadas sin que cuenten. */
        if ($linea === '' || $linea[0] === '#') { continue; }
        $clave = normalizar($linea);
        if ($clave !== '') { $fuera[$clave] = true; }
    }
    return $fuera;
}

/**
 * Las caracteristicas de un inmueble, a partir de las casillas de Mobilia.
 *
 * Cuenta lo que trae algo que no sea vacio ni cero. Un '0' en una casilla
 * de si/no es un NO, y un cero colado como caracteristica ('0 trasteros')
 * seria peor que no ensenar nada.
 */
function extrasMobilia($plano, $tabla) {
    $fuera = array();
    foreach ($tabla as $campo => $clave) {
        if (isset($fuera[$clave]) || !array_key_exists($campo, $plano)) { continue; }
        $v = $plano[$campo];
        if (is_array($v)) { continue; }
        $t = trim((string) $v);
        if ($t === '') { continue; }
        if (is_numeric($t) && (float) $t == 0) { continue; }
        $fuera[$clave] = true;
    }
    return array_keys($fuera);
}

/**
 * Saca las operaciones de un inmueble de Mobilia, ya aplanadas.
 *
 * Mobilia no deja la operacion y el precio sueltos en el inmueble: los
 * mete juntos y anidados, Operaciones > Operacion > {Tipo, Precio}. Y
 * cuando una finca esta a la vez en venta y en alquiler, Operacion es una
 * LISTA de dos. Aplanar eso a ciegas no encontraba ni tipo ni precio, y el
 * inmueble entero se caia: eran los dos que el diagnostico contaba como
 * 'sin operacion'.
 *
 * Leerlo aqui da ademas lo que de verdad importa: que el tipo y el precio
 * salgan SIEMPRE de la misma operacion. Cruzar el alquiler de una con el
 * precio de la otra es el unico error que esta pagina no se puede permitir.
 */
function operacionesMobilia($p) {
    $dentro = null;
    foreach ($p as $k => $v) {
        if (normalizar($k) === 'operaciones' && is_array($v)) { $dentro = $v; break; }
    }
    if ($dentro === null) { return array(); }

    $ops = null;
    foreach ($dentro as $k => $v) {
        if (normalizar($k) === 'operacion' && is_array($v)) { $ops = $v; break; }
    }
    if ($ops === null) { return array(); }
    /* Una sola operacion llega como objeto; varias, como lista. */
    if (!isset($ops[0])) { $ops = array($ops); }

    $fuera = array();
    foreach ($ops as $o) {
        if (is_array($o)) { $fuera[] = aplanar($o); }
    }
    return $fuera;
}

/**
 * Traduce la respuesta de Mobilia al formato de la web.
 *
 * Descarta sin contemplaciones el inmueble al que no le encuentra precio
 * u operacion: una ficha con el precio en blanco es peor que una ficha
 * que no esta. Lo descartado se cuenta y sale en el diagnostico.
 */
function traducir($crudo, $cfg, $SINONIMOS, $SINONIMOS_ALQUILER, $IDIOMAS, $TIPOS, $NOMBRE_TIPO, $EXTRAS_MOBILIA, $PROPIOS, &$informe) {
    $lista = localizarLista($crudo);
    $forzados = isset($cfg['campos']) && is_array($cfg['campos']) ? $cfg['campos'] : array();
    $porDefecto = isset($cfg['operacion']) ? strtolower((string) $cfg['operacion']) : '';

    $informe = array(
        'destacados_propios' => count($PROPIOS),
        'inmueblessinpublicar' => array(),
        'destacados_sin_encontrar' => array(),
        'registros'    => count($lista),
        'publicados'   => 0,
        'descartados'  => array(),
        'emparejados'  => array(),
        'sinemparejar' => array(),
        'camposvistos' => array(),
    );

    $fuera = array();
    $vistas = array();
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
                $informe['emparejados'][$campo][$usado] = true;
            }
            return $valor;
        };

        /* Un inmueble puede estar a la vez en venta y en alquiler. Cuando
           pasa, sale una ficha por operacion: en la web son dos paginas
           distintas, Comprar y Llogar, y tiene que aparecer en las dos.
           Sin operaciones reconocibles se pasa una sola vez por el camino
           generico, que es el que sirve para cualquier otro CRM. */
        $variantes = operacionesMobilia($p);
        if (!count($variantes)) { $variantes = array(null); }

        /* Cuantas fichas ha dado ESTE inmueble. Contar solo operaciones
           descartadas no dice lo que de verdad importa: un local en venta y
           en traspaso sale igual en la web -su venta- y solo pierde una
           operacion, mientras que uno que solo esta en traspaso no aparece
           en ningun sitio. La primera situacion no urge; la segunda es
           cartera invisible. */
        $dadas = 0;
        $refFicha = '';

        foreach ($variantes as $op) {
            /* La operacion primero: de ella depende que precio es el bueno. */
            $bruta = ($op !== null && isset($op['tipo'])) ? texto($op['tipo']) : texto($lee('operacion'));
            if ($op !== null && isset($op['tipo'])) {
                $tocados['operaciones'] = true;
                $informe['emparejados']['operacion']['operaciones>operacion>tipo'] = true;
            }
            $n = normalizar($bruta);
            if ($n !== '' && (strpos($n, 'alq') !== false || strpos($n, 'llog') !== false ||
                              strpos($n, 'rent') !== false || strpos($n, 'arrend') !== false)) {
                $operacion = 'alquiler';
            } elseif ($n !== '' && (strpos($n, 'vent') !== false || strpos($n, 'venda') !== false ||
                                    strpos($n, 'sale') !== false || strpos($n, 'sell') !== false ||
                                    strpos($n, 'compra') !== false)) {
                $operacion = 'venta';
            } elseif ($n !== '' && strpos($n, 'traspas') !== false) {
                /* El manual de Mobilia da TRES tipos de operacion: venta,
                   alquiler y traspaso. Un traspaso no es ninguna de las dos
                   cosas -no se compra el local, se releva un contrato- y la
                   web no tiene esa seccion, asi que no se publica. Pero se
                   reconoce, para que salga NOMBRADO en el diagnostico en vez
                   de caer en el saco de 'sin operacion' y perderse de vista.
                   Si Top House tiene traspasos en cartera, esto lo dira. */
                $operacion = 'traspaso';
            } else {
                /* Sin campo de operacion no se inventa: o lo dice la
                   configuracion (feed de un solo tipo) o el inmueble no sale. */
                $operacion = ($porDefecto === 'venta' || $porDefecto === 'alquiler') ? $porDefecto : '';
            }

            /* Si la operacion trae su propio precio, ese y no otro: es el precio
               DE ESTA operacion, que es justo lo que hay que publicar. */
            $precio = 0;
            if ($op !== null) {
                $suyo = null;
                $nombresSuyos = $operacion === 'alquiler'
                    ? array_merge($SINONIMOS_ALQUILER, $SINONIMOS['precio'])
                    : $SINONIMOS['precio'];
                $precio = numero(buscar($op, $nombresSuyos, $suyo));
                if ($suyo !== null) {
                    $tocados['operaciones'] = true;
                    $informe['emparejados']['precio']['operaciones>operacion>' . $suyo] = true;
                }
            }
            if ($precio <= 0) {
                $nombresPrecio = $SINONIMOS['precio'];
                if ($operacion === 'alquiler') {
                    $nombresPrecio = array_merge($SINONIMOS_ALQUILER, $nombresPrecio);
                }
                $usadoPrecio = null;
                if (isset($forzados['precio'])) { array_unshift($nombresPrecio, normalizar($forzados['precio'])); }
                $precio = numero(buscar($plano, $nombresPrecio, $usadoPrecio));
                if ($usadoPrecio !== null) {
                    $tocados[$usadoPrecio] = true;
                    $informe['emparejados']['precio'][$usadoPrecio] = true;
                }
            }

            $ref = texto($lee('ref'));
            /* Para avisar de las erratas: una referencia mal escrita en la
               lista de destacados no da ningun error, simplemente no
               destaca nada, y eso se pasa por alto durante semanas. */
            if ($ref !== '') { $vistas[normalizar($ref)] = true; $refFicha = $ref; }

            if ($operacion === 'traspaso') {
                $informe['descartados'][] = array('n' => $indice, 'ref' => $ref, 'motivo' => 'traspaso: la web no tiene esa seccion todavia');
                continue;
            }
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

            /* El tipo viaja como CLAVE estable, igual en los tres idiomas:
               es lo que compara el filtro. El nombre visible lo pone la web,
               que ya sabe traducir 'piso' a 'Pis' y a 'Flat'. */
            $tipoCru   = texto($lee('tipo'));
            $tipoClave = normalizar($tipoCru);
            if (isset($TIPOS[$tipoClave])) { $tipoClave = $TIPOS[$tipoClave]; }

            $poblacio = poblacionBonita(texto($lee('poblacio')));

            $ficha = array(
                'ref'       => $ref !== '' ? $ref : ('THR-' . $indice),
                'operacion' => $operacion,
                'tipo'      => $tipoClave,
                'titulo'    => texto($lee('titulo')),
                'poblacio'  => $poblacio,
                'zona'      => $zona,
                'precio'    => $precio,
                'm2'        => (int) numero($lee('m2')),
                'hab'       => (int) numero($lee('hab')),
                'banys'     => (int) numero($lee('banys')),
                /* Primero la lista de extras si el CRM trae una -otros si lo
                   hacen-, y despues las casillas sueltas de Mobilia. */
                'extras'    => array_slice(array_values(array_unique(array_merge(
                                   listaExtras($lee('extras')),
                                   extrasMobilia($plano, $EXTRAS_MOBILIA)))), 0, 12),
                'energia'   => texto($lee('energia')),
                'foto'      => primeraFoto($lee('foto')),
                /* La galeria entera y la descripcion no viajan a la pagina
                   de la cartera -abultan y no se usan-, pero si se guardan
                   en la copia, que es de donde bebe la ficha del inmueble. */
                'fotos'     => todasLasFotos($lee('foto')),
                'descripcio'=> texto($lee('descripcio')),
                /* La lista propia manda sobre la casilla de Mobilia: esa
                   es de los portales, no de esta web. */
                'destacado' => count($PROPIOS)
                    ? isset($PROPIOS[normalizar($ref)])
                    : numero($lee('destacado')) > 0,
                'fecha'     => fechaMobilia($lee('fecha')),
            );

            /* Sin titulo la ficha sigue siendo util: se compone uno con lo
               que si se sabe, que es lo que hace el propio portal. */
            if ($ficha['titulo'] === '') {
                $nombreEs = isset($NOMBRE_TIPO[$tipoClave]['es']) ? $NOMBRE_TIPO[$tipoClave]['es'] : $tipoCru;
                $ficha['titulo'] = componerTitulo($nombreEs, $poblacio, 'es');
                if ($ficha['titulo'] === '') { $ficha['titulo'] = $ficha['ref']; }
            }

            /* Mobilia ya guarda el titulo y el tipo en catalan y en ingles.
               Hasta ahora no se leian, y la pagina catalana ensenaba fichas
               en castellano teniendo la traduccion al lado. Van todas en el
               mismo sitio: una sola peticion, una sola copia guardada, y la
               web coge la que toca segun en que idioma se este. */
            $otros = array();
            foreach ($IDIOMAS as $codigo => $sufijo) {
                $suTipo   = texto(buscar($plano, array(normalizar('tipo' . $sufijo),
                                                       normalizar('familia' . $sufijo))));
                $suTitulo = texto(buscar($plano, array(normalizar('titulo' . $sufijo))));
                $suEnergia = texto(buscar($plano, array(normalizar('calificacionenergetica' . $sufijo))));
                $suDesc = texto(buscar($plano, array(normalizar('descripcionampliada' . $sufijo),
                                                     normalizar('descripcion' . $sufijo))));
                /* Si Mobilia no lo trae en este idioma, se compone con el
                   nombre nuestro del tipo, que esta en singular. */
                if ($suTitulo === '' && isset($NOMBRE_TIPO[$tipoClave][$codigo])) {
                    $suTitulo = componerTitulo($NOMBRE_TIPO[$tipoClave][$codigo], $poblacio, $codigo);
                }
                $entrada = array();
                if ($suTipo !== '')   { $entrada['tipo'] = $suTipo; }
                if ($suEnergia !== '' && $suEnergia !== $ficha['energia']) { $entrada['energia'] = $suEnergia; }
                if ($suDesc !== '' && $suDesc !== $ficha['descripcio']) { $entrada['descripcio'] = $suDesc; }
                if ($suTitulo !== '' && $suTitulo !== $ficha['titulo']) { $entrada['titulo'] = $suTitulo; }
                if (count($entrada)) { $otros[$codigo] = $entrada; }
            }
            if (count($otros)) { $ficha['i18n'] = $otros; }

            $fuera[] = $ficha;
            $informe['publicados']++;
            $dadas++;
        }

        if ($dadas === 0) {
            $informe['inmueblessinpublicar'][] = $refFicha !== '' ? $refFicha : ('#' . $indice);
        }

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

    /* Las referencias de la lista propia que no existen en la cartera:
       casi siempre una errata al escribirlas a mano. */
    $informe['destacados_sin_encontrar'] = array_keys(array_diff_key($PROPIOS, $vistas));

    /* Un feed no tiene por que ser uniforme: la venta puede traer 'precio'
       y el alquiler 'precio_alquiler'. Guardando solo el ultimo nombre, el
       diagnostico ensenaba uno y escondia el otro, que es justo el dato que
       se va a mirar cuando algo salga raro. Salen todos. */
    foreach ($informe['emparejados'] as $campo => $nombres) {
        $informe['emparejados'][$campo] = implode(', ', array_keys($nombres));
    }

    return $fuera;
}

$informe = array();
$PROPIOS = destacadosPropios($PRIVADO . '/destacados.txt');
$limpio = traducir($crudo, $cfg, $SINONIMOS, $SINONIMOS_ALQUILER, $IDIOMAS, $TIPOS, $NOMBRE_TIPO, $EXTRAS_MOBILIA, $PROPIOS, $informe);

/* ---------- 5. el modo diagnostico ---------- */
/**
 * Tapa los datos personales de la muestra del diagnostico. Del propietario
 * de un piso no hace falta saber nada para emparejar campos: basta con ver
 * COMO SE LLAMA el campo y de que tipo es. Se conserva el nombre y se
 * sustituye el contenido.
 */
/**
 * Recorta a lo ancho de CARACTERES, no de bytes. Un substr a pelo parte
 * una letra acentuada por la mitad y lo que queda ya no es UTF-8: a
 * partir de ahi json_encode se niega a codificar el conjunto entero.
 * En un feed en castellano eso no es un caso raro, es cuestion de tiempo.
 */
function recortar($t, $max) {
    if (function_exists('mb_substr')) {
        return mb_substr($t, 0, $max, 'UTF-8');
    }
    /* Sin mbstring: se retrocede mientras el ultimo byte sea la
       continuacion de un caracter (10xxxxxx), y se suelta tambien el byte
       inicial que se haya quedado sin su continuacion. */
    $corte = substr($t, 0, $max);
    while ($corte !== '' && (ord($corte[strlen($corte) - 1]) & 0xC0) === 0x80) {
        $corte = substr($corte, 0, -1);
    }
    if ($corte !== '' && (ord($corte[strlen($corte) - 1]) & 0xC0) === 0xC0) {
        $corte = substr($corte, 0, -1);
    }
    return $corte;
}

function tapar($registro, $hondo = 0) {
    if (!is_array($registro) || $hondo > 3) { return $registro; }
    $delicados = array('propietario', 'telefono', 'movil', 'mobil', 'email', 'correo',
                       'nif', 'dni', 'cif', 'nombrepropietario', 'contacto', 'observaciones',
                       'notas', 'notasinternas', 'direccion', 'calle', 'numero', 'piso',
                       'portal', 'catastro', 'referenciacatastral', 'iban', 'titular');
    /* La lista de nombres exactos no basta: Mobilia llama a las cosas
       'TelefonoAgente' y 'EmailAgente', no 'telefono' ni 'email', y asi el
       telefono y el correo de una empleada salieron enteros en un
       diagnostico de verdad. Un dato personal no deja de serlo porque el
       campo lleve un sufijo. */
    $largos = array('telefon', 'email', 'correo', 'whatsapp', 'propietario', 'agente');

    /* Estas son demasiado cortas para buscarlas sueltas dentro del nombre:
       'nif' vive dentro de 'TratamientoIgnifugo', que es un si/no de un
       local y no el documento de nadie. Taparlo no hacia dano, pero un
       diagnostico que esconde campos que no son personales despista a quien
       lo lee y puede ocultar justo el que hacia falta para emparejar. Asi
       que estas solo cuentan si son el nombre entero, el principio o el
       final: 'Nif', 'NifPropietario', 'NumeroNif'. */
    $cortos = array('nif', 'dni', 'cif', 'iban', 'movil', 'mobil');

    $fuera = array();
    foreach ($registro as $clave => $valor) {
        $k = normalizar($clave);
        $personal = in_array($k, $delicados, true);
        if (!$personal) {
            foreach ($largos as $d) {
                if (strpos($k, $d) !== false) { $personal = true; break; }
            }
        }
        if (!$personal) {
            foreach ($cortos as $d) {
                $l = strlen($d);
                if ($k === $d || strncmp($k, $d, $l) === 0 || substr($k, -$l) === $d) {
                    $personal = true; break;
                }
            }
        }
        if ($personal) {
            $fuera[$clave] = '(tapado: ' . gettype($valor) . ')';
        } elseif (is_array($valor)) {
            $fuera[$clave] = tapar($valor, $hondo + 1);
        } else {
            /* Un texto largo puede ser la descripcion, y ahi la gente escribe
               de todo. Se recorta: para ver el nombre del campo sobra. */
            $t = (string) $valor;
            $fuera[$clave] = strlen($t) > 80 ? recortar($t, 80) . '...' : $valor;
        }
    }
    return $fuera;
}

if ($DIAG) {
    $lista = localizarLista($crudo);
    $muestra = array();
    if (isset($lista[0])) { $muestra = tapar($lista[0]); }
    responder(array(
        'oauth'         => $notaOAuth,
        'formato'       => $formato,
        'http'          => $codigo,
        'destacados_propios'       => $informe['destacados_propios'],
        'inmuebles_sin_publicar'   => $informe['inmueblessinpublicar'],
        'destacados_sin_encontrar' => $informe['destacados_sin_encontrar'],
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

responder(aligerar($limpio), 'recien traido de Mobilia (' . $informe['publicados'] . ' de ' . $informe['registros'] . ')');

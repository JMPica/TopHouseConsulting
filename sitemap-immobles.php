<?php
/* =====================================================================
   El sitemap de las fichas de inmueble

   El sitemap normal lo escribe el generador y lleva las paginas fijas.
   Las fichas no caben ahi: no son una direccion, son una por inmueble, y
   cambian solas cada vez que Mobilia da de alta o de baja una casa.

   Este se hace al vuelo con la misma copia que usa todo lo demas, asi que
   siempre dice la verdad sin que nadie tenga que acordarse de nada.
   ===================================================================== */
header('Content-Type: application/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');

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

$DOMINIO = 'https://www.tophouserealestate.es';
$RUTAS = array('ca' => 'immoble', 'es' => 'es/inmueble', 'en' => 'en/property');
$RUTAS_BARCO = array('ca' => 'vaixell', 'es' => 'es/barco', 'en' => 'en/boat');
/* Los mismos codigos que llevan las propias paginas (ca, es, en y
   x-default). Antes aqui decia ca-ES, es-ES y en-GB: no era un error, pero
   el sitemap y las paginas se contradecian. */
$ETIQUETA = array('ca' => 'ca', 'es' => 'es', 'en' => 'en');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

$vistas = array();
foreach ($lista as $n) {
    if (!is_array($n) || empty($n['ref'])) { continue; }
    /* Un inmueble en venta Y en alquiler sale dos veces en la cartera,
       pero es una sola pagina: se manda una vez. */
    $ref = rawurlencode($n['ref']);
    if (isset($vistas[$ref])) { continue; }
    $vistas[$ref] = true;

    $fecha = !empty($n['fecha']) ? date('Y-m-d', (int) $n['fecha']) : '';
    /* Los barcos de Top House Marine tienen su propia direccion. */
    $rutas = (isset($n['seccion']) && $n['seccion'] === 'marine') ? $RUTAS_BARCO : $RUTAS;
    foreach ($rutas as $cod => $tramo) {
        echo '  <url>' . "\n";
        echo '    <loc>' . $DOMINIO . '/' . $tramo . '/' . $ref . '</loc>' . "\n";
        if ($fecha !== '') { echo '    <lastmod>' . $fecha . '</lastmod>' . "\n"; }
        foreach ($rutas as $otro => $tramoOtro) {
            printf('    <xhtml:link rel="alternate" hreflang="%s" href="%s/%s/%s"/>' . "\n",
                   $ETIQUETA[$otro], $DOMINIO, $tramoOtro, $ref);
        }
        printf('    <xhtml:link rel="alternate" hreflang="x-default" href="%s/%s/%s"/>' . "\n",
               $DOMINIO, $rutas['ca'], $ref);
        echo '    <priority>0.7</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}

/* Las listas por poblacion (/comprar/arenys-de-mar...). Solo las de los
   pueblos con 3 inmuebles o mas, que son las que llista.php sirve: el
   minimo y la forma de escribir el nombre tienen que coincidir con los de
   alli. */
$TRAMOS = array('venta'    => array('ca' => 'comprar', 'es' => 'es/comprar',  'en' => 'en/buy'),
                'alquiler' => array('ca' => 'llogar',  'es' => 'es/alquilar', 'en' => 'en/rent'));
$pueblos = array();
foreach ($lista as $n) {
    if (!is_array($n) || empty($n['ref']) || empty($n['poblacio']) || empty($n['operacion'])) { continue; }
    if (isset($n['seccion']) && $n['seccion'] === 'marine') { continue; }
    if (!isset($TRAMOS[$n['operacion']])) { continue; }
    $s = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $n['poblacio']), 'UTF-8') : strtolower(trim((string) $n['poblacio']));
    $s = strtr($s, array('à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'í' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
                         'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', '·' => '', '’' => '', "'" => ''));
    $s = trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
    if ($s === '') { continue; }
    $k = $n['operacion'] . '|' . $s;
    if (!isset($pueblos[$k])) { $pueblos[$k] = array('n' => 0, 'fecha' => 0); }
    $pueblos[$k]['n']++;
    if (!empty($n['fecha']) && (int) $n['fecha'] > $pueblos[$k]['fecha']) { $pueblos[$k]['fecha'] = (int) $n['fecha']; }
}
foreach ($pueblos as $k => $info) {
    if ($info['n'] < 3) { continue; }
    list($oper, $s) = explode('|', $k, 2);
    foreach ($TRAMOS[$oper] as $cod => $tramo) {
        echo '  <url>' . "\n";
        echo '    <loc>' . $DOMINIO . '/' . $tramo . '/' . $s . '</loc>' . "\n";
        if ($info['fecha']) { echo '    <lastmod>' . date('Y-m-d', $info['fecha']) . '</lastmod>' . "\n"; }
        foreach ($TRAMOS[$oper] as $otro => $tramoOtro) {
            printf('    <xhtml:link rel="alternate" hreflang="%s" href="%s/%s/%s"/>' . "\n", $otro, $DOMINIO, $tramoOtro, $s);
        }
        printf('    <xhtml:link rel="alternate" hreflang="x-default" href="%s/%s/%s"/>' . "\n", $DOMINIO, $TRAMOS[$oper]['ca'], $s);
        echo '    <priority>0.8</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}
echo '</urlset>' . "\n";

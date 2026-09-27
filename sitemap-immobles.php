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
$ETIQUETA = array('ca' => 'ca-ES', 'es' => 'es-ES', 'en' => 'en-GB');

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
    foreach ($RUTAS as $cod => $tramo) {
        echo '  <url>' . "\n";
        echo '    <loc>' . $DOMINIO . '/' . $tramo . '/' . $ref . '</loc>' . "\n";
        if ($fecha !== '') { echo '    <lastmod>' . $fecha . '</lastmod>' . "\n"; }
        foreach ($RUTAS as $otro => $tramoOtro) {
            printf('    <xhtml:link rel="alternate" hreflang="%s" href="%s/%s/%s"/>' . "\n",
                   $ETIQUETA[$otro], $DOMINIO, $tramoOtro, $ref);
        }
        echo '    <priority>0.7</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}
echo '</urlset>' . "\n";

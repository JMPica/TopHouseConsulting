<?php
/* =========================================================
   Pagina que no existe
   =========================================================

   Antes el .htaccess ensenaba siempre /index.html, la portada en catalan,
   aunque la direccion fuera /es/... o /en/.... Aqui se elige la portada
   del idioma de la direccion. Los enlaces de las portadas llevan la ruta
   entera (ver construir.py), asi que funcionan desde cualquier direccion.

   El codigo sigue siendo 404: para Google la direccion no existe, y no
   la confunde con la portada.
   ========================================================= */
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$ruta = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
if (preg_match('#^/es(/|$)#i', $ruta)) {
    $portada = __DIR__ . '/es/index.html';
} elseif (preg_match('#^/en(/|$)#i', $ruta)) {
    $portada = __DIR__ . '/en/index.html';
} else {
    $portada = __DIR__ . '/index.html';
}
readfile($portada);

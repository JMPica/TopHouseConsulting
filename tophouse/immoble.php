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

$L       = '{{IDIOMA}}';
$CARPETA = '{{CARPETA}}';

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
        $cfg = include $PRIVADO . '/mobilia-config.php';
        if (is_array($cfg) && isset($cfg['minutos'])) { $minutos = (int) $cfg['minutos']; }
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

$titulo = $n ? porIdioma($n, 'titulo', $L) : '';
$desc   = $n ? porIdioma($n, 'descripcio', $L) : '';
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($tituloPagina); ?></title>
<meta name="description" content="<?php echo h($resumen !== '' ? $resumen : 'Top House Real Estate'); ?>">
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
<meta property="og:description" content="<?php echo h($resumen); ?>">
<?php if (count($fotos)) { ?>
<meta property="og:image" content="<?php echo h($fotos[0]); ?>">
<meta name="twitter:card" content="summary_large_image">
<?php } ?>

<link rel="icon" type="image/png" href="/assets/favicon.png">
<link rel="preload" href="/assets/fonts/fraunces-var-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/manrope-var-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/fonts.css">
<link rel="stylesheet" href="/assets/site.css">
</head>
<body id="top">
<a class="skip" href="#main">Saltar al contenido</a>
<div class="env" aria-hidden="true"><div class="env__glow"></div><div class="env__dust"></div></div>
<nav class="nav" id="nav" aria-label="Principal">
  <a class="nav__brand" href="index.html" aria-label="Top House Real Estate, inicio">
    <img class="mark" src="/assets/logo.png" width="256" height="256" alt="" aria-hidden="true" decoding="async">
    <span class="nav__word">
      <span class="nav__name">Top House</span>
      <span class="nav__kicker">Real Estate</span>
    </span>
  </a>
  <div class="nav__links">
    <a href="index.html#vender">Vender</a>
    <a href="comprar.html">Comprar</a>
    <a href="alquilar.html">Alquilar</a>
    <a href="index.html#valorar">Valoración</a>
    <a href="index.html#servicios">Servicios</a>
    <a href="index.html#contacto">Contacto</a>
  </div>
  <div class="nav__actions">
    <a class="btn btn--ghost nav__tel" href="tel:+34605273150">605 27 31 50</a>
    <a class="btn btn--solid" href="index.html#contacto">Pedir mi valoración</a>
  </div>
</nav>

<main id="main">
<?php if ($n === null) { ?>

<section class="cart__head">
  <p class="kicker">Inmueble</p>
  <h1 class="d1">Este inmueble ya no está publicado</h1>
  <p class="lede">Puede que se haya vendido o que lo hayamos retirado. Buena parte de lo que vendemos se cierra antes de llegar a publicarse, así que llámenos y le decimos qué tenemos parecido.</p>
  <p class="fitxa__acciones">
    <a class="btn btn--solid btn--lg" href="<?php echo h($volver); ?>">Ver lo que tenemos</a>
    <a class="btn btn--ghost btn--lg" href="tel:+34605273150">605 27 31 50</a>
  </p>
</section>

<?php } else { ?>

<article class="fitxa" data-ref="<?php echo h($n['ref']); ?>"
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
        <?php foreach (preg_split('/\n\s*\n/', trim($desc)) as $parrafo) {
                  echo '<p>' . nl2br(h(trim($parrafo))) . '</p>';
              } ?>
      </div>
      <?php } ?>
    </div>

    <aside class="fitxa__lateral">
      <form class="form fitxa__form" id="form" novalidate>
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
            <span>He leído y acepto la política de privacidad. Al enviar, se abre WhatsApp: mis datos viajan por ese servicio, que es de Meta.</span>
          </label>
        </div>
        <p class="form__err" id="form-err" role="alert" hidden></p>
        <button class="btn btn--solid btn--lg form__go" type="submit">Pedir visita</button>
        <p class="form__note mono">Se abre WhatsApp con su mensaje ya escrito. Nada se guarda en esta web.</p>
        <p class="form__ok" id="form-ok" role="status" hidden>Recibido. Se abre WhatsApp con su mensaje ya escrito para que solo tenga que darle a enviar.</p>
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
    <a class="nav__brand" href="index.html" aria-label="Top House Real Estate, inicio">
      <img class="mark" src="/assets/logo.png" width="256" height="256" alt="" aria-hidden="true" decoding="async">
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
        <p><a href="legal.html#aviso-legal">Aviso legal</a><br>
           <a href="legal.html#privacidad">Privacidad</a><br>
           <a href="legal.html#cookies">Cookies</a></p>
      </div>
    </div>
  </div>
  <div class="foot__bar">
    <p>© <span id="year">2026</span> Top House Real Estate</p>
    <p class="foot__note">Precios y superficies orientativos. La información definitiva de cada inmueble se entrega por escrito antes de cualquier reserva.</p>
  </div>
</footer>
<script src="/assets/idioma.js" defer></script>
<script src="/assets/base.js" defer></script>
<script src="/assets/extras.js" defer></script>
<script src="/assets/fitxa.js" defer></script>
</body>
</html>

/* =========================================================
   Top House Real Estate
   Motor del héroe con scroll, entradas, mapa de tramos y formulario.
   HTML, CSS y JavaScript a secas. Sin librerías y sin compilación.
   ========================================================= */
(function () {
  'use strict';

  /* ---------- utilidades ---------- */

  var clamp = function (v, lo, hi) { return Math.min(hi, Math.max(lo, v)); };
  var smoothstep = function (p, e0, e1) {
    var t = clamp((p - e0) / (e1 - e0), 0, 1);
    return t * t * (3 - 2 * t);
  };
  function rng(seed) {
    var s = seed >>> 0;
    return function () { return (s = (s * 1664525 + 1013904223) >>> 0) / 4294967296; };
  }

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- referencias ---------- */

  var stage    = $('#stage');
  var hero     = $('#top');
  var video    = $('#hero-video');
  var poster   = $('#poster');
  var loader   = $('#loader');
  var ring     = loader ? $('circle', loader) : null;
  var bandEls  = $$('.band');
  var navEl    = $('#nav');
  var statichero = $('#statichero');

  var VIDEO_URL   = '/assets/hero-scrub.mp4?v=d050fa2b';
  var POSTER_URL  = '/assets/hero-poster.jpg?v=d3a69492';
  var ENDING_URL  = '/assets/hero-ending.jpg?v=2da43856';
  var VIDEO_BYTES = 8155401;   /* tamaño real, respaldo cuando falta Content-Length */

  /* La variable se consume desde assets/site.css, y ahi una ruta relativa se
     resolveria contra la hoja de estilos (assets/assets/...). Se absolutiza
     contra el documento para que apunte al fichero real. */
  var ENDING_ABS = ENDING_URL;
  try { ENDING_ABS = new URL(ENDING_URL, document.baseURI).href; } catch (e) {}
  document.documentElement.style.setProperty('--hero-still', "url('" + ENDING_ABS + "')");
  var yearEl = $('#year');
  if (yearEl) yearEl.textContent = String(new Date().getFullYear());

  requestAnimationFrame(function () { document.body.classList.add('lit'); });

  /* =========================================================
     1. Partir el texto en palabras, una sola vez, con azar sembrado
     ========================================================= */

  function splitInto(el, seed) {
    if (!el || el.dataset.done === '1') return;
    var text = el.textContent;
    var words = text.split(/(\s+)/);
    var r = rng(seed);
    var frag = document.createDocumentFragment();
    var count = 0;
    words.forEach(function (w) {
      if (!w.length) return;
      var span = document.createElement('span');
      span.className = 'w';
      span.textContent = w;
      if (/\S/.test(w)) {
        span.style.setProperty('--th', (r() * 0.42).toFixed(3));
        count++;
      } else {
        span.style.setProperty('--th', '0');
      }
      frag.appendChild(span);
    });
    el.textContent = '';
    el.appendChild(frag);
    el.dataset.done = '1';
    el.dataset.words = String(count);
  }

  bandEls.forEach(function (band, i) {
    var entrance = band.getAttribute('data-entrance');
    $$('[data-split]', band).forEach(function (el, j) {
      if (entrance === 'blur') {
        /* dos copias cruzadas: la blanda lleva un desenfoque estático */
        var raw = el.textContent;
        el.textContent = '';
        var soft = document.createElement('span');
        soft.className = 'soft';
        soft.setAttribute('aria-hidden', 'true');
        soft.textContent = raw;
        var sharp = document.createElement('span');
        sharp.className = 'sharp';
        sharp.textContent = raw;
        el.appendChild(soft);
        el.appendChild(sharp);
      } else {
        splitInto(el, (i + 1) * 977 + (j + 1) * 131);
      }
    });
  });

  /* =========================================================
     2. Bandas: rangos, opacidad y progreso de montaje
     ========================================================= */

  var bands = bandEls.map(function (el, i) {
    return {
      el: el,
      a: parseFloat(el.getAttribute('data-a')),
      b: parseFloat(el.getAttribute('data-b')),
      ramp: el.hasAttribute('data-ramp') ? parseFloat(el.getAttribute('data-ramp')) : 0,
      first: el.hasAttribute('data-first'),
      last: i === bandEls.length - 1,
      op: -1,
      k: -1
    };
  });

  var loadK = 0;
  var loadStart = 0;

  function updateBands(p) {
    for (var i = 0; i < bands.length; i++) {
      var B = bands[i];
      var f = Math.min(0.02, (B.b - B.a) / 3);
      var inRamp  = B.first ? 1 : smoothstep(p, B.a, B.a + f);
      var outRamp = B.last  ? 1 : (1 - smoothstep(p, B.b - f, B.b));
      var op = inRamp * outRamp;

      var ramp = B.ramp || Math.min(0.025, (B.b - B.a) * 0.35);
      var k = clamp((p - B.a) / ramp, 0, 1);
      if (B.first) k = Math.max(k, loadK);

      if (Math.abs(op - B.op) > 0.004) {
        B.op = op;
        B.el.style.opacity = op.toFixed(3);
      }
      if (Math.abs(k - B.k) > 0.008) {
        B.k = k;
        B.el.style.setProperty('--k', k.toFixed(3));
      }
    }
  }

  /* =========================================================
     3. Búsquedas con compuerta, a prueba de bloqueo
     ========================================================= */

  var seekBusy = false;
  var pendingTime = null;

  function requestSeek(t) {
    if (!video.duration || !isFinite(t)) return;
    if (seekBusy) { pendingTime = t; return; }
    seekBusy = true;
    try { video.currentTime = t; } catch (e) { seekBusy = false; }
  }

  video.addEventListener('seeked', function () {
    seekBusy = false;
    if (pendingTime !== null) {
      var t = pendingTime;
      pendingTime = null;
      requestSeek(t);
    }
  });

  video.addEventListener('error', function () {
    seekBusy = false;
    pendingTime = null;
    failVideo();
  });

  /* =========================================================
     4. Bucle de suavizado que descansa
     ========================================================= */

  var target = 0, shown = 0, rafId = null, lastTick = 0, heroOnScreen = true;

  function heroProgress() {
    if (!hero) return 0;
    var range = hero.offsetHeight - window.innerHeight;
    if (range <= 0) return 0;
    return clamp(-hero.getBoundingClientRect().top / range, 0, 1);
  }

  function tick(now) {
    var dt = Math.min(100, now - (lastTick || now));
    lastTick = now;
    var k = 0.16;
    shown += (target - shown) * (1 - Math.pow(1 - k, dt / 16.667));

    if (loadK < 1 && loadStart) {
      loadK = clamp((now - loadStart) / 1100, 0, 1);
    }

    var settled = Math.abs(target - shown) < 0.0005;
    if (settled) { shown = target; }

    if (modeSeq) seqPinta(shown);
    else if (video.duration) requestSeek(shown * video.duration);
    updateBands(shown);

    if (settled && loadK >= 1) {
      rafId = null;
      lastTick = 0;
    } else {
      rafId = requestAnimationFrame(tick);
    }
  }

  function kick() {
    if (rafId === null && heroOnScreen) {
      lastTick = 0;
      rafId = requestAnimationFrame(tick);
    }
  }

  function onScroll() {
    target = heroProgress();
    kick();
  }

  if (hero && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      heroOnScreen = entries[0].isIntersecting;
      if (heroOnScreen) kick();
    }, { rootMargin: '120px' }).observe(hero);
  }

  /* =========================================================
     5. Carga del vídeo en Blob, con anillo honesto
     ========================================================= */

  var heroStarted = false;
  var fetchStarted = false;

  function failVideo() {
    if (!stage) return;
    stage.classList.add('video-failed');
    if (loader) loader.style.display = 'none';
  }

  function startBlobFetch() {
    if (fetchStarted) return;
    fetchStarted = true;
    loadHeroBlob().catch(failVideo);
  }

  function loadHeroBlob() {
    if (!window.fetch || !window.ReadableStream) return Promise.reject(new Error('sin stream'));

    var ctrl = new AbortController();
    var watchdog = setTimeout(function () { ctrl.abort(); }, 20000);

    var opts = { signal: ctrl.signal };
    try { opts.priority = 'low'; } catch (e) {}

    return fetch(VIDEO_URL, opts).then(function (res) {
      if (!res.ok || !res.body) throw new Error('sin cuerpo');
      var total = Number(res.headers.get('Content-Length')) || VIDEO_BYTES;
      var reader = res.body.getReader();
      var chunks = [];
      var got = 0, lastRing = 0;

      function pump() {
        return reader.read().then(function (r) {
          if (r.done) return;
          clearTimeout(watchdog);
          watchdog = setTimeout(function () { ctrl.abort(); }, 20000);
          chunks.push(r.value);
          got += r.value.length;
          var frac = Math.min(1, got / total);
          var now = performance.now();
          if (ring && (now - lastRing > 100 || frac === 1)) {
            lastRing = now;
            ring.style.setProperty('--ld', String(Math.round(126 * (1 - frac))));
          }
          return pump();
        });
      }

      return pump().then(function () {
        clearTimeout(watchdog);
        if (ring) ring.style.setProperty('--ld', '0');
        video.src = URL.createObjectURL(new Blob(chunks, { type: 'video/mp4' }));
        video.load();
        video.addEventListener('canplay', function () {
          requestSeek(heroProgress() * video.duration);
          stage.classList.add('video-ready');
          onScroll();
        }, { once: true });
      });
    });
  }

  function initHeroOnce() {
    if (heroStarted) return;
    heroStarted = true;

    if (modeSeq) {
      if (stage) stage.classList.add('stage--seq');
      loadStart = performance.now();
      kick();
      carregaSeq().catch(function () {
        /* Si los fotogramas no llegan, se cae a la foto fija de siempre:
           mas vale un heroe quieto que un hueco negro. */
        disableScrub();
        failVideo();
      });
      window.addEventListener('resize', function () { mesuraLienzo(); seqPinta(shown); }, { passive: true });
      return;
    }

    if (poster) poster.style.backgroundImage = "url('" + POSTER_URL + "')";
    var img = new Image();
    img.onload = startBlobFetch;
    img.onerror = startBlobFetch;
    img.src = POSTER_URL;
    setTimeout(startBlobFetch, 4000);
    loadStart = performance.now();
    kick();
  }

  /* =========================================================
     5 bis. El mismo heroe en el movil, pintado con fotogramas
     =========================================================

     EL PROBLEMA. En el movil este heroe estaba apagado, y con razon:
     mover un video con el dedo (ir cambiando currentTime segun el
     scroll) va a tirones en iPhone, y el video pesa 4,3 MB de datos
     moviles. Asi que el telefono veia una foto fija y el ordenador una
     pelicula: dos webs distintas.

     LA SOLUCION. La misma que usa Apple en sus paginas de producto:
     no se mueve un video, se pintan FOTOGRAMAS sueltos en un lienzo.
     Son 97 imagenes recortadas en vertical, 1,2 MB en total, casi cuatro
     veces menos que el video. Con 49 se veian los saltos: a 500vh de
     recorrido tocaban a un fotograma cada 70px de scroll, y el dedo eso
     lo nota. Con 97 y un heroe algo mas corto salen a 28px, que ya no. Y sobre todo: dibujar una imagen en un lienzo
     es instantaneo y se comporta igual en todos los navegadores, que es
     precisamente lo que no se puede decir de buscar dentro de un video.

     Lo demas no cambia. El calculo del scroll, el suavizado y las
     bandas son exactamente el mismo codigo que en el ordenador. Lo
     unico que cambia es quien pinta.

     LA CACHE. Los ficheros se piden a mano, asi que el generador no
     puede sellarlos uno a uno como hace con el resto. Se le pide la
     huella de UN fotograma, que el generador si sella por ser una
     cadena literal, y se le pega la misma a los otros 48: si algun dia
     se regenera la secuencia, cambia el primero y cambian todos. */

  /* Donde no hay sitio para el plano entero ni conviene bajar 4,3 MB, se
     pinta con fotogramas. Se decide una sola vez: cambiar de motor a
     mitad de sesion no aporta nada y complica el codigo. */
  var modeSeq = window.matchMedia('(max-width: 820px)').matches ||
                window.matchMedia('(orientation: portrait) and (pointer: coarse)').matches;

  var SEQ_N = 97;
  var SEQ_BASE = '/assets/hero-mobil/';
  /* esta cadena la sella el generador; de ahi se saca la version */
  var SEQ_PRIMER = '/assets/hero-mobil/f-001.webp?v=95977fa2';
  var seqVersio = (function () {
    var i = SEQ_PRIMER.indexOf('?');
    return i === -1 ? '' : SEQ_PRIMER.slice(i);
  })();

  var lienzo = $('#hero-lienzo');
  var ctx = null;
  var seqImgs = [];
  var seqLlest = false;
  var seqPintat = -1;

  function seqRuta(n) {
    var t = String(n);
    while (t.length < 3) { t = '0' + t; }
    return SEQ_BASE + 'f-' + t + '.webp' + seqVersio;
  }

  function mesuraLienzo() {
    if (!lienzo) return;
    var r = lienzo.getBoundingClientRect();
    /* Se limita a 2 el factor de pantalla: en un movil de 3x el lienzo
       seria enorme y no se notaria, porque el fotograma de origen mide
       540 de ancho y no da para mas. */
    var d = Math.min(2, window.devicePixelRatio || 1);
    var w = Math.round(r.width * d), h = Math.round(r.height * d);
    if (w && h && (lienzo.width !== w || lienzo.height !== h)) {
      lienzo.width = w; lienzo.height = h;
      seqPintat = -1;
    }
  }

  function seqPinta(p) {
    if (!seqLlest || !ctx) return;
    var n = clamp(Math.round(p * (SEQ_N - 1)), 0, SEQ_N - 1);
    if (n === seqPintat) return;
    var img = seqImgs[n];
    if (!img || !img.complete || !img.naturalWidth) return;
    seqPintat = n;
    /* recorte "cover" a mano: asi se comporta igual en todos los
       navegadores, sin depender de object-fit sobre un lienzo */
    var W = lienzo.width, H = lienzo.height;
    var e = Math.max(W / img.naturalWidth, H / img.naturalHeight);
    var dw = img.naturalWidth * e, dh = img.naturalHeight * e;
    ctx.drawImage(img, (W - dw) / 2, (H - dh) / 2, dw, dh);
  }

  function carregaSeq() {
    if (!lienzo) return Promise.reject(new Error('sin lienzo'));
    ctx = lienzo.getContext('2d');
    if (!ctx) return Promise.reject(new Error('sin contexto'));
    mesuraLienzo();

    var fets = 0;
    return new Promise(function (resol, rebutja) {
      var fallats = 0;
      for (var i = 0; i < SEQ_N; i++) {
        (function (i) {
          var img = new Image();
          img.decoding = 'async';
          img.onload = function () {
            fets++;
            if (ring) ring.style.setProperty('--ld', String(Math.round(126 * (1 - fets / SEQ_N))));
            /* El primero se pinta en cuanto llega: mas vale ver el
               principio del plano que un hueco mientras cargan los 48
               que faltan. */
            if (i === 0) { seqLlest = true; seqPinta(heroProgress()); }
            /* Con la mitad dentro ya se puede ensenar: se ve movimiento y
               los que faltan van entrando sin que nadie lo note. Esperar a
               los 97 seria tener la pantalla en blanco de balde. */
            if (fets === Math.ceil(SEQ_N / 2)) mostrar();
            if (fets + fallats === SEQ_N) acabar();
          };
          img.onerror = function () {
            fallats++;
            if (fets + fallats === SEQ_N) acabar();
          };
          img.src = seqRuta(i + 1);
          seqImgs[i] = img;
        })(i);
      }
      function mostrar() {
        seqLlest = true;
        if (stage) stage.classList.add('video-ready');
        seqPinta(heroProgress());
        onScroll();
      }
      function acabar() {
        /* Con la mitad de los fotogramas ya se ve el movimiento; por
           debajo de eso seria un pase de diapositivas y es mejor la
           foto fija. */
        if (fets < SEQ_N / 2) { rebutja(new Error('faltan fotogramas')); return; }
        if (ring) ring.style.setProperty('--ld', '0');
        mostrar();
        resol();
      }
    });
  }

  /* =========================================================
     6. Las cinco compuertas del héroe estático, vivas
     ========================================================= */

  /* Solo quedan dos motivos para renunciar al heroe en movimiento: que
     el visitante haya pedido menos animacion, y el telefono tumbado, que
     no tiene alto suficiente para que quepan las bandas. Las tres
     compuertas que quedaban antes eran "esto es un movil", y ya no hacen
     falta: en el movil el heroe se pinta con fotogramas. */
  var GATES = [
    '(orientation: landscape) and (pointer: coarse) and (max-height: 560px)',
    '(prefers-reduced-motion: reduce)'
  ];
  var MQLS = GATES.map(function (q) { return window.matchMedia(q); });

  var scrubOn = false;

  function enableScrub() {
    if (scrubOn) return;
    scrubOn = true;
    if (hero) hero.hidden = false;
    if (statichero) statichero.hidden = true;
    initHeroOnce();
    window.addEventListener('scroll', onScroll, { passive: true });
    bands.forEach(function (b) { b.op = -1; b.k = -1; });
    updateBands(heroProgress());
    onScroll();
  }

  function disableScrub() {
    if (!scrubOn) {
      if (hero) hero.hidden = true;
      if (statichero) statichero.hidden = false;
      return;
    }
    scrubOn = false;
    window.removeEventListener('scroll', onScroll);
    if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; }
    if (hero) hero.hidden = true;
    if (statichero) statichero.hidden = false;
  }

  function applyHeroMode() {
    var off = MQLS.some(function (m) { return m.matches; });
    if (off) disableScrub(); else enableScrub();
  }

  MQLS.forEach(function (m) {
    if (m.addEventListener) m.addEventListener('change', applyHeroMode);
    else if (m.addListener) m.addListener(applyHeroMode);
  });

  applyHeroMode();
  window.addEventListener('resize', function () { if (scrubOn) onScroll(); }, { passive: true });

  /* =========================================================
     7. Navegación sólida al salir del héroe
     ========================================================= */

  var navSolid = false;
  function navCheck() {
    var want = window.scrollY > (window.innerHeight * 0.6);
    if (want !== navSolid) {
      navSolid = want;
      navEl.classList.toggle('solid', want);
    }
  }
  navCheck();
  window.addEventListener('scroll', navCheck, { passive: true });

  /* =========================================================
     8. Entradas de sección y trazos que se dibujan
     ========================================================= */

  var revealIO = null;
  if ('IntersectionObserver' in window) {
    revealIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target;
        el.classList.add('in');
        if (el.classList.contains('premise') || el.classList.contains('steps')) {
          el.classList.add('drawn');
        }
        setTimeout(function () { el.classList.add('done'); }, 1400);
        revealIO.unobserve(el);
      });
    }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });

    $$('.reveal').forEach(function (el) { revealIO.observe(el); });
  } else {
    $$('.reveal').forEach(function (el) { el.classList.add('in', 'drawn', 'done'); });
  }

  /* ---------- contadores ---------- */

  var counters = $$('.count');
  if (counters.length && 'IntersectionObserver' in window) {
    var cIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        runCount(e.target);
        cIO.unobserve(e.target);
      });
    }, { threshold: 0.6 });
    counters.forEach(function (c) { cIO.observe(c); });
  }

  function runCount(el) {
    var to = parseInt(el.getAttribute('data-to'), 10) || 0;
    var prefix = el.getAttribute('data-prefix') || '';
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      el.textContent = prefix + to;
      return;
    }
    var t0 = performance.now(), dur = 1300, last = '';
    (function step(now) {
      var p = clamp((now - t0) / dur, 0, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var s = prefix + Math.round(to * eased);
      if (s !== last) { last = s; el.textContent = s; }
      if (p < 1) requestAnimationFrame(step);
    })(t0);
  }

  /* =========================================================
     9. La calculadora de pre-valoracion

     ATENCION, TOP HOUSE: estos son los numeros que mueven el resultado.
     Estan aqui arriba a proposito para que se puedan cambiar sin tocar
     nada mas. Revisadlos, porque salen con vuestro nombre.

     BASE por tipo, en euros por metro construido.
       Sembrado con precios publicos de Arenys de Mar de julio de 2026:
       media del municipio 2.871 EUR/m2, pisos entre 2.846 y 3.122,
       casas entre 2.219 y 2.690. Fuentes: idealista y Fotocasa.
       Conviene repasarlo cada seis meses o se queda viejo.

     TRAMO: cuanto se aparta cada tramo de la media del pueblo.
       ESTOS NUMEROS SON UNA ESTIMACION MIA Y HAY QUE CAMBIARLOS.
       Nadie publica el precio por tramo de Arenys, y ese dato lo teneis
       vosotros, que llevais diez anos firmando operaciones calle a calle.
       Es justo lo que dice la web: la media del pueblo no sirve.
     ========================================================= */

  var PRECIO = {
    base:   { piso: 1.00, atico: 1.12, bajo: 0.91, casa: 0.85 },  /* factor sobre el EUR/m2 de la poblacion */
    estado: { reformar: 0.80, bien: 1.00, reformado: 1.14 },
    extra:  { ascensor: 0.03, exterior: 0.04, parking: 0.05, mar: 0.08 },
    horquilla: 0.07,          /* el resultado se da como +-7%, nunca como cifra unica */
    fecha: 'julio de 2026'
  };

  var NOMBRE_TIPO   = { piso:T('Piso'), atico:T('Ático'), bajo:T('Planta baja'), casa:T('Casa o torre') };
  var NOMBRE_ESTADO = { reformar:'para reformar', bien:'en buen estado', reformado:'reformado' };
  var NOMBRE_EXTRA  = { ascensor:'ascensor', exterior:'terraza o patio', parking:'parking', mar:'vistas al mar' };

  var POBLES = window.POBLACIONS || {};
  var ZGEN   = window.ZONES_GENERIQUES || { costa: [], interior: [] };

  function zonasDe(clau) {
    var p = POBLES[clau];
    if (!p) return [];
    if (p.zones) return p.zones;
    return p.costa ? ZGEN.costa : ZGEN.interior;
  }

  /* El desplegable de poblaciones va agrupado por comarca: son 65 y sin
     agrupar no hay quien encuentre la suya. */
  function montarPoblaciones(sel) {
    var porComarca = {};
    Object.keys(POBLES).forEach(function (k) {
      var c = POBLES[k].comarca;
      (porComarca[c] = porComarca[c] || []).push(k);
    });
    var orden = Object.keys(porComarca).sort(function (a, b) {
      if (a === 'El Maresme') return -1;   /* el mercado propio, primero */
      if (b === 'El Maresme') return 1;
      if (a === 'Otras') return 1;
      if (b === 'Otras') return -1;
      return a.localeCompare(b, 'es');
    });
    orden.forEach(function (c) {
      var g = document.createElement('optgroup');
      g.label = c;
      porComarca[c].sort(function (a, b) {
        return POBLES[a].nom.localeCompare(POBLES[b].nom, 'es');
      }).forEach(function (k) {
        var o = document.createElement('option');
        o.value = k; o.textContent = POBLES[k].nom;
        if (k === 'arenys-de-mar') o.selected = true;
        g.appendChild(o);
      });
      sel.appendChild(g);
    });
  }

  function montarZonas(selZona, clauPoble) {
    var z = zonasDe(clauPoble);
    selZona.innerHTML = '';
    z.forEach(function (par, i) {
      var o = document.createElement('option');
      o.value = par[0]; o.textContent = par[1];
      if (i === Math.min(1, z.length - 1)) o.selected = true;
      selZona.appendChild(o);
    });
  }

  var calcForm = $('#calc-form');
  var calcOut  = $('#calc-out');
  var ultimaValoracion = null;   /* lo que se arrastra hasta el formulario */

  function eur(n) {
    return Math.round(n / 1000) * 1000 === 0
      ? '0'
      : new Intl.NumberFormat('es-ES').format(Math.round(n / 1000) * 1000);
  }

  function calcular(d) {
    var tipo   = String(d.get('tipo') || 'piso');
    var poble  = String(d.get('poblacio') || 'arenys-de-mar');
    var zona   = String(d.get('zona') || '');
    var estado = String(d.get('estado') || 'bien');
    var m2     = Math.max(25, Math.min(600, parseInt(d.get('m2'), 10) || 90));
    var extras = d.getAll('ex').map(String);

    var P = POBLES[poble] || POBLES['arenys-de-mar'];
    var zonas = zonasDe(poble);
    var fZona = 1, nomZona = '';
    for (var z = 0; z < zonas.length; z++) {
      if (zonas[z][0] === zona) { fZona = zonas[z][2]; nomZona = zonas[z][1]; }
    }

    /* El ajuste de oferta baja el precio de portal hacia precio de cierre.
       Sin el, la calculadora valoraria con lo que se pide y no con lo que
       se firma, que es inflar expectativas. */
    var ajuste = typeof window.AJUSTE_OFERTA === 'number' ? window.AJUSTE_OFERTA : 1;

    var eur_m2 = P.base * ajuste
               * (PRECIO.base[tipo] || 1)
               * fZona
               * (PRECIO.estado[estado] || 1);

    /* Los extras suman sobre el precio del metro. El ascensor no cuenta en
       una casa, y las vistas al mar no se ofrecen tierra adentro. */
    var suma = 0;
    for (var i = 0; i < extras.length; i++) {
      if (extras[i] === 'ascensor' && tipo === 'casa') continue;
      if (extras[i] === 'mar' && !P.costa) continue;
      suma += PRECIO.extra[extras[i]] || 0;
    }
    eur_m2 *= (1 + suma);

    var total = eur_m2 * m2;
    return {
      bajo: total * (1 - PRECIO.horquilla),
      alto: total * (1 + PRECIO.horquilla),
      eur_m2: eur_m2, generica: !!P.generica, font: P.font || 'estimado',
      tipo: tipo, poble: P.nom, zona: nomZona, estado: estado, m2: m2, extras: extras,
      hab: String(d.get('hab') || '')
    };
  }

  if (calcForm) {
    var selPob  = $('#c-poblacio');
    var selZona = $('#c-zona');
    if (selPob && selZona) {
      montarPoblaciones(selPob);
      montarZonas(selZona, selPob.value);
      selPob.addEventListener('change', function () { montarZonas(selZona, selPob.value); });
    }

    calcForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var r = calcular(new FormData(calcForm));
      ultimaValoracion = r;

      $('#calc-range').textContent = eur(r.bajo) + ' a ' + eur(r.alto) + ' €';
      $('#calc-unit').textContent  =
        T('Unos ') + new Intl.NumberFormat('es-ES').format(Math.round(r.eur_m2)) +
        ' €/m² · ' + r.m2 + ' m² · ' + r.poble + (r.zona ? ', ' + r.zona : '') +
        T(' · precios de ') + PRECIO.fecha;

      /* Si han elegido el comodin, el numero es mucho mas grueso y hay que
         decirlo, no dejar que parezca igual de fino que el de una poblacion
         con dato propio. */
      var aviso = $('#calc-generica');
      if (aviso) aviso.hidden = !r.generica;

      /* Se dice en voz alta si el precio base de esa poblacion es un dato
         publicado o una estimacion nuestra. Cambia mucho lo que vale. */
      var fuente = $('#calc-fuente');
      if (fuente) {
        fuente.textContent = (r.font === 'publicado' || r.font === 'rango')
          ? T('Precio base de ') + r.poble + T(' tomado de datos publicados de mercado.')
          : T('El precio base de ') + r.poble + T(' es una estimación nuestra, no un dato publicado. Llámenos y se lo afinamos.');
      }

      calcOut.hidden = false;
      var fz = $('#f-zona');
      if (fz) fz.value = r.poble + (r.zona ? ', ' + r.zona : '');
      var fq = $('#f-que');  if (fq) fq.value = 'Vender';

      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        calcOut.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  }

  function resumenInmueble() {
    if (!ultimaValoracion) return null;
    var r = ultimaValoracion;
    var t = NOMBRE_TIPO[r.tipo] + ' de ' + r.m2 + ' m² en ' + r.poble +
            (r.zona ? ' (' + r.zona + ')' : '');
    if (r.hab) t += ', ' + r.hab + ' habitaciones';
    t += ', ' + NOMBRE_ESTADO[r.estado];
    if (r.extras.length) {
      var nom = [];
      for (var i = 0; i < r.extras.length; i++) nom.push(NOMBRE_EXTRA[r.extras[i]]);
      t += ', con ' + nom.join(', ');
    }
    return { linea: t, horquilla: eur(r.bajo) + ' a ' + eur(r.alto) + ' €' };
  }

  /* =========================================================
     10. El formulario abre WhatsApp con el mensaje escrito
     ========================================================= */

  var WA = '34605273150';
  var form   = $('#form');
  var formOk = $('#form-ok');
  var formErr = $('#form-err');

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = new FormData(form);
      var nombre = String(d.get('nombre') || '').trim();
      var tel    = String(d.get('telefono') || '').trim();

      if (!nombre || !tel) {
        formErr.textContent = T('Nos faltan su nombre y un teléfono para poder llamarle.');
        formErr.hidden = false;
        (nombre ? $('#f-tel') : $('#f-nombre')).focus();
        return;
      }
      /* El consentimiento no es un tramite: sin el no hay base legal para
         tratar sus datos, y ademas sus datos van a salir por WhatsApp, que
         es de un tercero. Tiene que haberlo leido antes, no despues. */
      var ok = $('#f-ok');
      if (ok && !ok.checked) {
        formErr.textContent = T('Necesitamos que acepte la política de privacidad antes de enviarnos sus datos.');
        formErr.hidden = false;
        ok.focus();
        return;
      }
      formErr.hidden = true;

      var lines = [
        T('Hola, soy ') + nombre + '.',
        T('Teléfono: ') + tel,
        T('Qué necesito: ') + (d.get('necesita') || 'Vender')
      ];
      var zona = String(d.get('zona') || '').trim();
      if (zona) lines.push(T('Tramo: ') + zona);

      /* Si viene de la calculadora, el mensaje lleva ya el inmueble entero,
         para que quien reciba el WhatsApp no tenga que preguntarlo todo. */
      var res = resumenInmueble();
      if (res) {
        lines.push(T('Inmueble: ') + res.linea);
        lines.push(T('Horquilla que me ha salido en la web: ') + res.horquilla);
      }

      var msg = String(d.get('mensaje') || '').trim();
      if (msg) lines.push('', msg);

      formOk.hidden = false;
      window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener');
    });
  }

  /* =========================================================
     11. Movimiento reducido en vivo, en los dos sentidos
     ========================================================= */

  function pinToFinalStates() {
    document.body.classList.add('rm');
    $$('.reveal').forEach(function (el) { el.classList.add('in', 'drawn', 'done'); });
    counters.forEach(function (c) {
      c.textContent = (c.getAttribute('data-prefix') || '') + c.getAttribute('data-to');
    });
    if (picked && !revealed) finishHold();
  }

  function unpinFinalStates() {
    document.body.classList.remove('rm');
  }

  var rmq = window.matchMedia('(prefers-reduced-motion: reduce)');
  function onRM(e) {
    if (e.matches) pinToFinalStates();
    else { unpinFinalStates(); applyHeroMode(); }
  }
  if (rmq.addEventListener) rmq.addEventListener('change', onRM);
  else if (rmq.addListener) rmq.addListener(onRM);
  if (rmq.matches) pinToFinalStates();

  /* =========================================================
     12. Una imagen que no llega no deja hueco roto
     ========================================================= */

  $$('.step__art img').forEach(function (img) {
    img.addEventListener('error', function () { img.style.display = 'none'; });
    if (img.complete && img.naturalWidth === 0) img.style.display = 'none';
  });

  $$('.gate__img').forEach(function (im) {
    im.addEventListener('error', function () { im.style.display = 'none'; });
  });

  /* =========================================================
     13. Nada se anima con la pestaña oculta
     ========================================================= */

  document.addEventListener('visibilitychange', function () {
    document.body.classList.toggle('paused', document.hidden);
  });

})();

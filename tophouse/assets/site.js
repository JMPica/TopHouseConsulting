/* =========================================================
   Top House Consulting
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

  var VIDEO_URL   = 'assets/hero-scrub.mp4';
  var POSTER_URL  = 'assets/hero-poster.jpg';
  var ENDING_URL  = 'assets/hero-ending.jpg';
  var VIDEO_BYTES = 5600000;   /* respaldo cuando falta Content-Length */

  document.documentElement.style.setProperty('--hero-still', "url('" + ENDING_URL + "')");
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

    if (video.duration) requestSeek(shown * video.duration);
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
     6. Las cinco compuertas del héroe estático, vivas
     ========================================================= */

  var GATES = [
    '(max-width: 720px)',
    '(orientation: portrait) and (max-width: 1024px)',
    '(orientation: portrait) and (pointer: coarse)',
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
     9. El mapa de tramos: el momento interactivo
     ========================================================= */

  var ZONES = {
    passeig:  { name: 'Primera línia i passeig', line: 'Aquí se paga la vista, no los metros. Y se paga rápido.' },
    centre:   { name: 'Centre i la Riera',       line: 'Casas de pueblo con planta baja. Lo que decide el precio es si tiene patio.' },
    eixample: { name: 'Eixample i estació',      line: 'El comprador que llega de Barcelona empieza mirando aquí. Los diez minutos a la estación valen dinero.' },
    alta:     { name: 'Zona alta',               line: 'Vistas y coche. Se vende más despacio y a mejor precio.' },
    afores:   { name: 'Afores i Sant Elm',       line: 'Parcelas grandes. Aquí lo que se compra es el terreno.' }
  };

  var zoneEls  = $$('.zone');
  var zoneName = $('#zone-name');
  var zoneLine = $('#zone-line');
  var zoneGo   = $('#zone-go');
  var holdBtn  = $('#hold-btn');
  var holdWrap = $('#hold');
  var holdLbl  = holdBtn ? $('.hold__label', holdBtn) : null;
  var zoneSel  = $('#f-zona');

  var picked = null;
  var hp = 0;          /* progreso del arco, 0 a 1 */
  var holding = false;
  var holdRaf = null;
  var holdLast = 0;
  var revealed = false;

  function pickZone(key) {
    picked = key;
    zoneEls.forEach(function (z) { z.classList.toggle('on', z.getAttribute('data-zone') === key); });
    zoneName.textContent = ZONES[key].name;
    holdBtn.disabled = false;
    if (revealed) {
      revealed = false;
      zoneLine.classList.remove('on');
      zoneGo.hidden = true;
      holdLbl.textContent = 'Mantén pulsado para ver tu tramo';
    }
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) finishHold();
  }

  zoneEls.forEach(function (z) {
    var key = z.getAttribute('data-zone');
    z.addEventListener('click', function () { pickZone(key); });
    z.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pickZone(key); }
    });
  });

  function setHold(v) {
    hp = clamp(v, 0, 1);
    holdWrap.style.setProperty('--hp', hp.toFixed(3));
  }

  function holdTick(now) {
    var dt = Math.min(60, now - (holdLast || now));
    holdLast = now;
    if (holding) setHold(hp + dt / 900);
    else setHold(hp - dt / 1400);

    if (hp >= 1) { holdRaf = null; holdLast = 0; finishHold(); return; }
    if (!holding && hp <= 0) { holdRaf = null; holdLast = 0; return; }
    holdRaf = requestAnimationFrame(holdTick);
  }

  function startHold(e) {
    if (!picked || revealed) return;
    if (e && e.cancelable) e.preventDefault();
    holding = true;
    if (holdRaf === null) { holdLast = 0; holdRaf = requestAnimationFrame(holdTick); }
  }
  function endHold() {
    holding = false;
    if (holdRaf === null && hp > 0) { holdLast = 0; holdRaf = requestAnimationFrame(holdTick); }
  }

  function finishHold() {
    if (!picked || revealed) return;
    revealed = true;
    holding = false;
    setHold(1);
    zoneLine.textContent = ZONES[picked].line;
    zoneLine.classList.add('on');
    zoneGo.hidden = false;
    holdLbl.textContent = 'Elige otro tramo del mapa';
    if (zoneSel) zoneSel.value = ZONES[picked].name;
  }

  if (holdBtn) {
    holdBtn.addEventListener('pointerdown', startHold);
    holdBtn.addEventListener('pointerup', endHold);
    holdBtn.addEventListener('pointercancel', endHold);
    holdBtn.addEventListener('pointerleave', endHold);
    holdBtn.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); startHold(); }
    });
    holdBtn.addEventListener('keyup', function (e) {
      if (e.key === 'Enter' || e.key === ' ') endHold();
    });
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
        formErr.textContent = 'Nos faltan tu nombre y un teléfono para poder llamarte.';
        formErr.hidden = false;
        (nombre ? $('#f-tel') : $('#f-nombre')).focus();
        return;
      }
      formErr.hidden = true;

      var lines = [
        'Hola, soy ' + nombre + '.',
        'Teléfono: ' + tel,
        'Qué necesito: ' + (d.get('necesita') || 'Vender')
      ];
      var zona = String(d.get('zona') || '').trim();
      if (zona) lines.push('Tramo: ' + zona);
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

  $$('.arch__img').forEach(function (im) {
    im.addEventListener('error', function () { im.style.display = 'none'; });
  });

  /* =========================================================
     13. Nada se anima con la pestaña oculta
     ========================================================= */

  document.addEventListener('visibilitychange', function () {
    document.body.classList.toggle('paused', document.hidden);
  });

})();

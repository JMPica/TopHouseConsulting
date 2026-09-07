/* =========================================================
   La cartera: filtra y pinta los inmuebles
   =========================================================

   DE DONDE SALEN LOS INMUEBLES

   Top House trabaja con Mobilia, que ya empuja cada ficha nueva a su web.
   Lo suyo es que esta web beba de esa misma fuente y no de una copia a mano,
   que se quedaria vieja al dia siguiente.

   Por eso hay dos caminos, y el codigo aguanta los dos:

   1. CON FEED (lo que hay que conseguir). Si existe window.CARTERA_FEED con
      la direccion del feed de Mobilia, se pide de ahi y siempre esta al dia.
      Como cada CRM entrega los campos con nombres distintos, la traduccion
      vive en una sola funcion, CARTERA_ADAPTADOR, abajo del todo de este
      fichero. Hay que verla contra un feed de verdad antes de darla por buena.

   2. SIN FEED (lo que hay hoy). Se usa lo que haya en inmuebles.js.

   Si el feed falla, se cae al fichero local sin romper la pagina: mas vale
   una cartera vieja que una pagina rota.
   ========================================================= */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };

  /* El CSS arranca con body{opacity:0} y solo se revela con la clase 'lit',
     que en la home la pone site.js. Estas paginas no cargan site.js, asi que
     sin esto se quedan en blanco del todo. */
  requestAnimationFrame(function () { document.body.classList.add('lit'); });

  /* La barra se vuelve solida al bajar, igual que en la home. */
  var navEl = $('#nav');
  if (navEl) {
    var solida = false;
    var mirarNav = function () {
      var quiere = window.scrollY > 40;
      if (quiere !== solida) { solida = quiere; navEl.classList.toggle('solid', quiere); }
    };
    mirarNav();
    window.addEventListener('scroll', mirarNav, { passive: true });
  }
  /* Se monta por ambito y no con un global: asi pueden convivir la cartera
     de venta y la de alquiler en un mismo documento, que es lo que necesita
     la vista previa de las tres paginas juntas. */
  var raices = document.querySelectorAll('[data-cartera]');
  if (!raices.length) return;

  var NOM_TIPO = { piso:'Piso', atico:'Ático', casa:'Casa', bajo:'Planta baja',
                   local:'Local', terreno:'Terreno' };

  /* Trae los inmuebles del feed si lo hay, y si no del fichero local. */
  function traerInmuebles() {
    var local = window.INMUEBLES || [];
    if (!window.CARTERA_FEED || !window.fetch) return Promise.resolve(local);

    return fetch(window.CARTERA_FEED, { headers: { 'Accept': 'application/json' } })
      .then(function (res) {
        if (!res.ok) throw new Error('el feed responde ' + res.status);
        return res.json();
      })
      .then(function (datos) {
        var f = window.CARTERA_ADAPTADOR;
        var lista = typeof f === 'function' ? f(datos) : datos;
        if (!Array.isArray(lista) || !lista.length) throw new Error('el feed no trae inmuebles');
        return lista;
      })
      .catch(function (e) {
        /* Nunca se rompe la pagina por esto: se avisa en consola para quien
           lo mantenga y se sigue con lo que haya en local. */
        if (window.console) console.warn('Cartera: no se ha podido leer el feed (' + e.message + '). Se usa inmuebles.js.');
        return local;
      });
  }

  traerInmuebles().then(function (INMUEBLES_OK) {
  Array.prototype.forEach.call(raices, function (raiz) {
  var OPER = raiz.getAttribute('data-cartera') || 'venta';
  var TODOS = INMUEBLES_OK.filter(function (i) { return i.operacion === OPER; });

  var grid   = $('.cart__grid', raiz);
  var vacio  = $('.cart__vacio', raiz);
  var cuenta = $('.cart__cuenta', raiz);
  var fTipo  = $('.f-tipo', raiz);
  var fPob   = $('.f-pob', raiz);
  var fMax   = $('.f-max', raiz);
  if (!grid) return;

  var eur = function (n) { return new Intl.NumberFormat('es-ES').format(n); };

  /* Los filtros se construyen con lo que hay de verdad en la cartera, no con
     una lista fija: asi nunca se ofrece un filtro que no devuelve nada. */
  function opcionesDe(clave, nombres) {
    var vistos = {};
    TODOS.forEach(function (i) { if (i[clave]) vistos[i[clave]] = true; });
    return Object.keys(vistos).sort(function (a, b) {
      return String(nombres ? nombres[a] || a : a).localeCompare(String(nombres ? nombres[b] || b : b), 'es');
    });
  }

  function montarFiltros() {
    opcionesDe('tipo', NOM_TIPO).forEach(function (t) {
      var o = document.createElement('option');
      o.value = t; o.textContent = NOM_TIPO[t] || t; fTipo.appendChild(o);
    });
    opcionesDe('poblacio').forEach(function (p) {
      var o = document.createElement('option');
      o.value = p; o.textContent = p; fPob.appendChild(o);
    });
    if (!TODOS.length) return;
    var precios = TODOS.map(function (i) { return i.precio; }).filter(Boolean).sort(function (a, b) { return a - b; });
    var tope = precios[precios.length - 1];
    var pasos = OPER === 'alquiler' ? [800, 1200, 1600, 2200, 3000] : [200000, 300000, 400000, 600000, 900000];
    pasos.forEach(function (v) {
      if (v > tope) return;
      var o = document.createElement('option');
      o.value = String(v);
      o.textContent = 'Hasta ' + eur(v) + ' €' + (OPER === 'alquiler' ? ' al mes' : '');
      fMax.appendChild(o);
    });
  }

  function ficha(i) {
    var li = document.createElement('li');
    li.className = 'inm' + (i.destacado ? ' inm--dest' : '');
    var precio = i.precio ? eur(i.precio) + ' €' + (OPER === 'alquiler' ? '<span class="inm__mes">/mes</span>' : '') : 'A consultar';
    var datos = [];
    if (i.m2)    datos.push(i.m2 + ' m²');
    if (i.hab)   datos.push(i.hab + (i.hab === 1 ? ' habitación' : ' habitaciones'));
    if (i.banys) datos.push(i.banys + (i.banys === 1 ? ' baño' : ' baños'));
    var etiquetas = (i.extras || []).map(function (e) {
      return '<li>' + e + '</li>';
    }).join('');

    li.innerHTML =
      '<div class="inm__foto' + (i.foto ? '' : ' inm__foto--sin') + '">' +
        (i.foto ? '<img src="' + i.foto + '" alt="" loading="lazy" decoding="async">'
                : '<span class="mono">Sin foto todavía</span>') +
        (i.destacado ? '<span class="inm__flag mono">Destacado</span>' : '') +
      '</div>' +
      '<div class="inm__cuerpo">' +
        '<p class="inm__sitio mono">' + (i.poblacio || '') + (i.zona ? ' · ' + i.zona : '') + '</p>' +
        '<h3 class="inm__t">' + (i.titulo || NOM_TIPO[i.tipo] || 'Inmueble') + '</h3>' +
        (datos.length ? '<p class="inm__datos">' + datos.join(' · ') + '</p>' : '') +
        (etiquetas ? '<ul class="inm__tags">' + etiquetas + '</ul>' : '') +
        '<p class="inm__precio">' + precio + '</p>' +
        '<a class="btn btn--ghost inm__cta" href="index.html#valoracion">Quiero verlo' +
          (i.ref ? '<span class="sr-only"> (referencia ' + i.ref + ')</span>' : '') + '</a>' +
      '</div>';
    return li;
  }

  function pintar() {
    var t = fTipo.value, p = fPob.value, m = parseInt(fMax.value, 10);
    var lista = TODOS.filter(function (i) {
      if (t && i.tipo !== t) return false;
      if (p && i.poblacio !== p) return false;
      if (m && i.precio && i.precio > m) return false;
      return true;
    });
    lista.sort(function (a, b) {
      if (!!b.destacado !== !!a.destacado) return b.destacado ? 1 : -1;
      return (a.precio || 0) - (b.precio || 0);
    });

    grid.innerHTML = '';
    lista.forEach(function (i) { grid.appendChild(ficha(i)); });

    var hayAlguno = lista.length > 0;
    grid.hidden = !hayAlguno;
    vacio.hidden = hayAlguno;
    cuenta.textContent = TODOS.length
      ? lista.length + (lista.length === 1 ? ' inmueble' : ' inmuebles')
      : '';
    /* Con la cartera entera vacia no tiene sentido enseñar filtros que no
       filtran nada, asi que se esconden y manda el aviso honesto. */
    var cajaFiltros = $('.cart__filtros', raiz);
    if (cajaFiltros) cajaFiltros.hidden = !TODOS.length;
  }

  montarFiltros();
  [fTipo, fPob, fMax].forEach(function (s) { s.addEventListener('change', pintar); });
  pintar();
  });
  });
})();

/* =========================================================
   EL TRADUCTOR DEL FEED DE MOBILIA

   ATENCION: esto NO esta terminado, y a proposito. Cada CRM nombra sus
   campos a su manera, y escribir esta traduccion adivinando el formato es
   la forma segura de que salgan precios y metros equivocados.

   PARA TERMINARLO hace falta ver UNA respuesta de verdad del feed. Con eso
   se rellenan las cuatro lineas de abajo y queda hecho.

   Lo que esta web necesita de cada inmueble:
     ref, operacion ('venta' u 'alquiler'), tipo, titulo, poblacio, zona,
     precio (numero), m2, hab, banys, extras (lista), foto (direccion)

   Ejemplo de como quedaria si el feed devolviese {propiedades:[...]} con
   campos en ingles. Cambiad los nombres por los que traiga Mobilia:

     window.CARTERA_ADAPTADOR = function (datos) {
       return (datos.propiedades || []).map(function (p) {
         return {
           ref:       p.reference,
           operacion: p.operation === 'rent' ? 'alquiler' : 'venta',
           tipo:      p.type,
           titulo:    p.title,
           poblacio:  p.town,
           zona:      p.area,
           precio:    Number(p.price) || 0,
           m2:        Number(p.built_area) || 0,
           hab:       Number(p.bedrooms) || 0,
           banys:     Number(p.bathrooms) || 0,
           extras:    p.features || [],
           foto:      (p.images && p.images[0]) || ''
         };
       });
     };
   ========================================================= */

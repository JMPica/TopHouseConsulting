/* =========================================================
   La cartera: filtra y pinta los inmuebles de inmuebles.js
   Sirve igual a comprar.html y a alquilar.html; lo unico que
   cambia es window.CARTERA_OPERACION.
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
  var OPER = window.CARTERA_OPERACION || 'venta';
  var TODOS = (window.INMUEBLES || []).filter(function (i) { return i.operacion === OPER; });

  var NOM_TIPO = { piso:'Piso', atico:'Ático', casa:'Casa', bajo:'Planta baja',
                   local:'Local', terreno:'Terreno' };

  var grid   = $('#cart-grid');
  var vacio  = $('#cart-vacio');
  var cuenta = $('#cart-cuenta');
  var fTipo  = $('#f-tipo');
  var fPob   = $('#f-pob');
  var fMax   = $('#f-max');
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
    var cajaFiltros = document.querySelector('.cart__filtros');
    if (cajaFiltros) cajaFiltros.hidden = !TODOS.length;
  }

  montarFiltros();
  [fTipo, fPob, fMax].forEach(function (s) { s.addEventListener('change', pintar); });
  pintar();
})();

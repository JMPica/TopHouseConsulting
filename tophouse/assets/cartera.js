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

  /* Revelar la pagina y la barra de arriba los lleva base.js, que cargan
     todas las paginas que no son la portada. Aqui solo va la cartera. */

  /* Se monta por ambito y no con un global: asi pueden convivir la cartera
     de venta y la de alquiler en un mismo documento, que es lo que necesita
     la vista previa de las tres paginas juntas. */
  var raices = document.querySelectorAll('[data-cartera]');
  if (!raices.length) return;

  var NOM_TIPO = { piso:T('Piso'), atico:T('Ático'), casa:T('Casa'), bajo:T('Planta baja'),
                   local:T('Local'), terreno:T('Terreno') };

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
      o.textContent = T('Hasta ') + eur(v) + ' €' + (OPER === 'alquiler' ? T(' al mes') : '');
      fMax.appendChild(o);
    });
  }

  /* -------------------------------------------------------------------
     Construir la ficha con nodos, NUNCA pegando cadenas de HTML.

     Esto no es purismo. Los titulos, poblaciones y etiquetas de cada
     inmueble los escriben personas a mano en Mobilia. Pegando texto en
     HTML, un titulo con un simbolo < parte la pagina, y uno escrito con
     mala idea, o un feed que alguien intercepte, puede meter codigo que
     se ejecuta en vuestro dominio: cambiar los telefonos de contacto,
     colocar un formulario falso de reserva con senal por adelantado, o
     mandar a los visitantes a otra web. En una inmobiliaria eso es una
     estafa montada sobre vuestra marca.

     Con textContent el navegador trata todo como texto y ese ataque no
     existe. Si alguien anade campos aqui en el futuro: mismo camino, y
     no volver a innerHTML.
     ------------------------------------------------------------------- */

  function nodo(etiqueta, clase, texto) {
    var n = document.createElement(etiqueta);
    if (clase) n.className = clase;
    if (texto !== undefined && texto !== null && texto !== '') n.textContent = texto;
    return n;
  }

  /* Una foto solo puede ser http, https o una direccion del propio sitio.
     Sin esto, un campo con javascript: se convierte en codigo al pinchar,
     y uno con data: permite incrustar cualquier cosa. */
  function fotoSegura(valor) {
    if (!valor) return '';
    try {
      var u = new URL(String(valor), document.baseURI);
      return (u.protocol === 'http:' || u.protocol === 'https:') ? u.href : '';
    } catch (e) {
      return '';
    }
  }

  /* Los numeros del feed llegan como vengan: texto, vacios, o algo raro. */
  function numero(valor) {
    var n = Number(valor);
    return isFinite(n) && n > 0 ? n : 0;
  }

  function ficha(i) {
    var li = nodo('li', 'inm' + (i.destacado ? ' inm--dest' : ''));

    /* --- la foto --- */
    var foto = fotoSegura(i.foto);
    var caja = nodo('div', 'inm__foto' + (foto ? '' : ' inm__foto--sin'));
    if (foto) {
      var img = document.createElement('img');
      img.src = foto;
      img.alt = '';
      img.loading = 'lazy';
      img.decoding = 'async';
      /* Una foto caida en Mobilia dejaria el icono de imagen rota en la
         ficha. Mejor caer al mismo hueco de "sin foto" que ya existe. */
      img.addEventListener('error', function () {
        caja.className = 'inm__foto inm__foto--sin';
        caja.textContent = '';
        caja.appendChild(nodo('span', 'mono', T('Sin foto todavía')));
        if (i.destacado) caja.appendChild(nodo('span', 'inm__flag mono', T('Destacado')));
      });
      caja.appendChild(img);
    } else {
      caja.appendChild(nodo('span', 'mono', T('Sin foto todavía')));
    }
    if (i.destacado) caja.appendChild(nodo('span', 'inm__flag mono', T('Destacado')));
    li.appendChild(caja);

    /* --- el cuerpo --- */
    var cuerpo = nodo('div', 'inm__cuerpo');

    var sitio = String(i.poblacio || '') + (i.zona ? ' · ' + i.zona : '');
    cuerpo.appendChild(nodo('p', 'inm__sitio mono', sitio));

    cuerpo.appendChild(nodo('h3', 'inm__t', i.titulo || NOM_TIPO[i.tipo] || T('Inmueble')));

    var datos = [];
    var m2 = numero(i.m2), hab = numero(i.hab), banys = numero(i.banys);
    if (m2)    datos.push(m2 + ' m²');
    if (hab)   datos.push(hab + (hab === 1 ? T(' habitación') : T(' habitaciones')));
    if (banys) datos.push(banys + (banys === 1 ? T(' baño') : T(' baños')));
    if (datos.length) cuerpo.appendChild(nodo('p', 'inm__datos', datos.join(' · ')));

    var extras = (i.extras || []).filter(Boolean);
    if (extras.length) {
      var ul = nodo('ul', 'inm__tags');
      extras.forEach(function (e) { ul.appendChild(nodo('li', '', String(e))); });
      cuerpo.appendChild(ul);
    }

    var pPrecio = nodo('p', 'inm__precio');
    var precio = numero(i.precio);
    if (precio) {
      pPrecio.appendChild(document.createTextNode(eur(precio) + ' €'));
      if (OPER === 'alquiler') pPrecio.appendChild(nodo('span', 'inm__mes', T('/mes')));
    } else {
      pPrecio.textContent = T('A consultar');
    }
    cuerpo.appendChild(pPrecio);

    var cta = nodo('a', 'btn btn--ghost inm__cta', T('Quiero verlo'));
    cta.href = 'index.html#contacto';
    if (i.ref) cta.appendChild(nodo('span', 'sr-only', T(' (referencia ') + i.ref + ')'));
    cuerpo.appendChild(cta);

    li.appendChild(cuerpo);
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
      ? lista.length + (lista.length === 1 ? T(' inmueble') : T(' inmuebles'))
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

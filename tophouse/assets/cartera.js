/* =========================================================
   La cartera: filtra y pinta los inmuebles
   =========================================================

   DE DONDE SALEN LOS INMUEBLES

   Top House trabaja con Mobilia, que ya empuja cada ficha nueva a su web.
   Lo suyo es que esta web beba de esa misma fuente y no de una copia a mano,
   que se quedaria vieja al dia siguiente.

   Por eso hay dos caminos, y el codigo aguanta los dos:

   1. CON FEED. window.CARTERA_FEED apunta a /api/cartera.php, el puente
      que habla con Mobilia desde el servidor y devuelve los inmuebles ya
      traducidos. Mientras ese puente tenga su configuracion, la cartera
      esta siempre al dia sin tocar nada a mano.

   2. SIN FEED. Si el puente no esta configurado o Mobilia no responde, se
      usa lo que haya en inmuebles.js.

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
                   local:T('Local'), terreno:T('Terreno'),
                   garaje:T('Plaza de aparcamiento'), trastero:T('Trastero') };

  /* El nombre visible de cada caracteristica. El puente manda CLAVES
     estables ('primera-linia') y no texto, para que salgan iguales en los
     tres idiomas y no dependan de como las escriba Mobilia. El orden de
     la ficha lo pone el puente, que las manda ya priorizadas. */
  var NOM_EXTRA = {
    'primera-linia':T('Primera línea de playa'), 'segona-linia':T('Segunda línea de playa'),
    'vistes':T('Vistas'), 'piscina':T('Piscina privada'),
    'piscina-comunitaria':T('Piscina comunitaria'), 'ascensor':T('Ascensor'),
    'terrassa':T('Terraza'), 'jardi':T('Jardín'), 'pati':T('Patio'),
    'parquing':T('Parking'), 'traster':T('Trastero'),
    'calefaccio':T('Calefacción'), 'aire':T('Aire acondicionado'),
    'llar-de-foc':T('Chimenea'), 'moblat':T('Amueblado'),
    'cuina-equipada':T('Cocina equipada'), 'armaris':T('Armarios'),
    'exterior':T('Exterior'), 'zones-comunes':T('Zonas comunes'),
    'zones-verdes':T('Zonas verdes'), 'barbacoa':T('Barbacoa'),
    'solarium':T('Solárium'), 'safareig':T('Lavadero'), 'celler':T('Bodega'),
    'golfes':T('Buhardilla'), 'gimnas':T('Gimnasio'), 'padel':T('Pista de pádel'),
    'tenis':T('Pista de tenis'), 'conserge':T('Conserje'),
    'vigilancia':T('Vigilancia 24 h'), 'alarma':T('Alarma'),
    'porta-blindada':T('Puerta blindada'), 'adaptat':T('Adaptado'),
    'mascotes':T('Admite mascotas')
  };

  /* En que idioma se esta leyendo la pagina. El generador deja el codigo en
     <html lang>, asi que no hace falta preguntarselo a nadie. */
  var IDIOMA = (document.documentElement.getAttribute('lang') || 'es').slice(0, 2);

  /* El texto de un inmueble en el idioma de la pagina.

     El puente manda el castellano en el campo de siempre y, cuando Mobilia
     lo tiene, el catalan y el ingles en i.i18n. Antes esto no se leia y las
     fichas salian en castellano en las tres versiones de la web, teniendo
     la traduccion al lado. Si falta, se cae al castellano: mejor una ficha
     en el idioma que no toca que una ficha sin titulo.

     El TIPO es aparte: si es uno de los que la web sabe nombrar, manda su
     traduccion (NOM_TIPO), que esta escrita por nosotros. El campo de
     Mobilia solo se usa para los tipos que no conocemos. */
  function texto(i, campo) {
    if (IDIOMA !== 'es' && i.i18n && i.i18n[IDIOMA] && i.i18n[IDIOMA][campo]) {
      return i.i18n[IDIOMA][campo];
    }
    return i[campo] || '';
  }

  function nombreTipo(i) {
    return NOM_TIPO[i.tipo] || texto(i, 'tipo') || i.tipo || '';
  }

  /* El titulo en el idioma de la pagina. Mobilia rellena el castellano casi
     siempre y el catalan y el ingles casi nunca, asi que cuando no lo hay
     se compone aqui y no en el puente: aqui se sabe decir 'Pis', mientras
     que Mobilia guarda los tipos en plural y saldria 'Pisos a Arenys'. */
  function tituloDe(i) {
    var propio = (IDIOMA !== 'es' && i.i18n && i.i18n[IDIOMA])
      ? i.i18n[IDIOMA].titulo
      : i.titulo;
    if (propio) return propio;
    var tipo = nombreTipo(i), donde = i.poblacio || '';
    if (tipo && donde) return tipo + T(' en ') + donde;
    return tipo || donde || i.titulo || '';
  }

  /* Trae los inmuebles del feed si lo hay, y si no del fichero local. */
  function traerInmuebles() {
    var local = window.INMUEBLES || [];
    if (!window.CARTERA_FEED || !window.fetch) return Promise.resolve(local);

    /* Tope de espera. La cartera no se pinta hasta que contesta el feed, asi
       que un servidor lento dejaria la rejilla en blanco un rato largo. A los
       4 segundos se corta y se pinta con lo local, que siempre esta a mano. */
    var corte = null, aborto = null;
    var opciones = { headers: { 'Accept': 'application/json' } };
    if (window.AbortController) {
      aborto = new AbortController();
      opciones.signal = aborto.signal;
      corte = setTimeout(function () { aborto.abort(); }, 4000);
    }

    return fetch(window.CARTERA_FEED, opciones)
      .then(function (res) {
        if (corte) { clearTimeout(corte); corte = null; }
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
        if (corte) { clearTimeout(corte); corte = null; }
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
  var sinres = $('.cart__sinres', raiz);
  var fRef   = $('.f-ref', raiz);
  var fTipo  = $('.f-tipo', raiz);
  var fPob   = $('.f-pob', raiz);
  var fHab   = $('.f-hab', raiz);
  var fMin   = $('.f-min', raiz);
  var fMax   = $('.f-max', raiz);
  var limpiar = raiz.querySelectorAll('.f-limpiar');
  if (!grid) return;

  /* Los seis mandos, en una lista: casi todo lo que se hace con ellos se
     hace con los seis a la vez. */
  var MANDOS = [fRef, fTipo, fPob, fHab, fMin, fMax].filter(Boolean);

  /* UN DISTINTIVO QUE LO LLEVA TODO EL MUNDO NO DISTINGUE A NADIE.

     La cartera de verdad venia entera marcada como destacada, asi que
     las treinta y tres fichas salian con su etiqueta y con el borde
     resaltado. Una etiqueta que lo lleva todo el mundo deja de querer
     decir nada: es ruido repetido en cada tarjeta.

     Asi que el distintivo se ensena solo cuando de verdad senala a unos
     pocos. Si lo lleva la mitad o mas, se apaga entero, etiqueta y borde.
     Y se corrige solo: el dia que en Mobilia se marquen cuatro pisos en
     vez de treinta y tres, vuelve a aparecer sin tocar nada de aqui.

     El ORDEN no depende de esto: si todos estan destacados ese criterio
     no desempata y manda la fecha, que es justo lo que se quiere. */
  var DESTACA = (function () {
    if (!TODOS.length) return false;
    var cuantos = TODOS.filter(function (i) { return i.destacado; }).length;
    return cuantos > 0 && cuantos < TODOS.length / 2;
  })();
  function destaca(i) { return !!i.destacado && DESTACA; }

  /* El separador de miles cambia con el idioma, y aqui no es un detalle:
     '380.000' leido por un ingles son trescientos ochenta euros. */
  var LOCAL = { ca: 'ca-ES', es: 'es-ES', en: 'en-GB' }[IDIOMA] || 'es-ES';
  var eur = function (n) { return new Intl.NumberFormat(LOCAL).format(n); };

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
    /* La etiqueta del desplegable sale del propio inmueble cuando el tipo
       no es de los que la web sabe nombrar: asi un 'Trastero' que Mobilia
       llame de otra manera sigue saliendo en catalan o en ingles. El VALOR
       es siempre la clave, que es igual en los tres idiomas; si no, filtrar
       en catalan no encontraria nada. */
    var ETIQUETA = {};
    TODOS.forEach(function (i) { if (i.tipo && !ETIQUETA[i.tipo]) ETIQUETA[i.tipo] = nombreTipo(i); });
    opcionesDe('tipo', ETIQUETA).forEach(function (t) {
      var o = document.createElement('option');
      o.value = t; o.textContent = ETIQUETA[t] || t; fTipo.appendChild(o);
    });
    opcionesDe('poblacio').forEach(function (p) {
      var o = document.createElement('option');
      o.value = p; o.textContent = p; fPob.appendChild(o);
    });
    if (!TODOS.length) return;

    /* Habitaciones: se ofrece "2 o mas" y no "exactamente 2", que es como
       busca la gente de verdad. Solo salen los numeros que existen en la
       cartera, asi que ninguna opcion devuelve cero. */
    if (fHab) {
      var haysHab = {};
      TODOS.forEach(function (i) { if (i.hab > 0) haysHab[i.hab] = true; });
      Object.keys(haysHab).map(Number).sort(function (a, b) { return a - b; })
        .forEach(function (n) {
          /* El maximo de la cartera como "o mas" solo se cumpliria a si
             mismo, asi que no aporta nada como minimo: se deja igual
             porque delimita, y quien pide 5+ quiere ver los de 5. */
          var o = document.createElement('option');
          o.value = String(n);
          o.textContent = n + T(' o más');
          fHab.appendChild(o);
        });
    }

    /* Precio: los escalones se sacan del rango real de la cartera. Uno por
       encima del inmueble mas caro no lo veria nadie, y uno por debajo del
       mas barato dejaria fuera la cartera entera. */
    var precios = TODOS.map(function (i) { return i.precio; }).filter(Boolean).sort(function (a, b) { return a - b; });
    if (!precios.length) return;
    var suelo = precios[0];
    var tope = precios[precios.length - 1];
    var pasos = OPER === 'alquiler' ? [600, 800, 1000, 1200, 1600, 2200, 3000]
                                    : [150000, 200000, 300000, 400000, 600000, 900000, 1500000];
    var conMes = function (v) { return eur(v) + ' €' + (OPER === 'alquiler' ? T(' al mes') : ''); };

    pasos.forEach(function (v) {
      if (fMin && v > suelo && v < tope) {
        var a = document.createElement('option');
        a.value = String(v);
        a.textContent = T('Desde ') + conMes(v);
        fMin.appendChild(a);
      }
      if (fMax && v >= suelo && v < tope) {
        var b = document.createElement('option');
        b.value = String(v);
        b.textContent = T('Hasta ') + conMes(v);
        fMax.appendChild(b);
      }
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
    var li = nodo('li', 'inm' + (destaca(i) ? ' inm--dest' : ''));

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
        if (destaca(i)) caja.appendChild(nodo('span', 'inm__flag mono', T('Destacado')));
      });
      caja.appendChild(img);
    } else {
      caja.appendChild(nodo('span', 'mono', T('Sin foto todavía')));
    }
    if (destaca(i)) caja.appendChild(nodo('span', 'inm__flag mono', T('Destacado')));
    li.appendChild(caja);

    /* --- el cuerpo --- */
    var cuerpo = nodo('div', 'inm__cuerpo');

    var sitio = String(i.poblacio || '') + (i.zona ? ' · ' + i.zona : '');
    cuerpo.appendChild(nodo('p', 'inm__sitio mono', sitio));

    cuerpo.appendChild(nodo('h3', 'inm__t', tituloDe(i) || T('Inmueble')));

    var datos = [];
    var m2 = numero(i.m2), hab = numero(i.hab), banys = numero(i.banys);
    if (m2)    datos.push(m2 + ' m²');
    if (hab)   datos.push(hab + (hab === 1 ? T(' habitación') : T(' habitaciones')));
    if (banys) datos.push(banys + (banys === 1 ? T(' baño') : T(' baños')));
    if (datos.length) cuerpo.appendChild(nodo('p', 'inm__datos', datos.join(' · ')));

    /* En la tarjeta caben pocas y ordenadas: el puente las manda ya
       priorizadas, asi que las cinco primeras son las que mas venden. Con
       ocho, la lista ocupaba cuatro lineas y hundia el precio, que es lo
       que la gente busca con la mirada. Las demas viajan igualmente en los
       datos, para la ficha del inmueble. */
    var extras = (i.extras || []).filter(Boolean).slice(0, 5);
    if (extras.length) {
      var ul = nodo('ul', 'inm__tags');
      extras.forEach(function (e) {
        /* Si no es una clave conocida, se ensena tal cual: hay CRM que
           mandan la lista de extras en texto y esos no se pierden. */
        ul.appendChild(nodo('li', '', NOM_EXTRA[e] || String(e)));
      });
      cuerpo.appendChild(ul);
    }

    /* La calificacion energetica es obligatoria en la publicidad de un
       inmueble. Va despues de las caracteristicas y en tono menor: es un
       dato legal, no un argumento de venta. */
    var energia = texto(i, 'energia');
    if (energia) {
      cuerpo.appendChild(nodo('p', 'inm__energia mono', T('Energía') + ': ' + energia));
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

  /* La referencia se compara sin acentos, sin mayusculas y sin guiones ni
     espacios, y basta con que sea un trozo: quien la apunta a mano casi
     siempre se queda con el numero, y "101" tiene que encontrar "THR-101".
     No se intenta adivinar mas alla de eso. Una busqueda lista de mas que
     empareja referencias parecidas ensena el piso equivocado, y aqui eso
     acaba en una visita a la direccion que no era. */
  function llana(v) {
    var t = String(v == null ? '' : v).toLowerCase();
    if (t.normalize) t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    return t.replace(/[^a-z0-9]/g, '');
  }

  function valor(mando) { return mando ? mando.value : ''; }

  function hayFiltro() {
    return MANDOS.some(function (m) { return m.value !== ''; });
  }

  function pintar() {
    var r = llana(valor(fRef));
    var t = valor(fTipo), p = valor(fPob);
    var h = parseInt(valor(fHab), 10);
    var min = parseInt(valor(fMin), 10);
    var max = parseInt(valor(fMax), 10);

    var lista = TODOS.filter(function (i) {
      /* La referencia manda sobre el resto: quien la escribe sabe lo que
         busca, y seria absurdo esconderle su inmueble porque arrastraba
         puesto un filtro de poblacion de antes. */
      if (r) return llana(i.ref).indexOf(r) !== -1;
      if (t && i.tipo !== t) return false;
      if (p && i.poblacio !== p) return false;
      if (h && !(i.hab >= h)) return false;
      if (min && i.precio && i.precio < min) return false;
      if (max && i.precio && i.precio > max) return false;
      return true;
    });
    /* EL ORDEN. Primero lo que la agencia ha marcado como destacado en
       Mobilia, que es el unico criterio de "popularidad" que existe: el
       feed no trae visitas ni contactos, asi que quien decide que se
       ensena primero es Top House y no una formula inventada aqui.

       Despues, lo mas reciente. Antes venia el precio de menor a mayor, y
       eso abria la pagina de Comprar con tres plazas de aparcamiento, que
       es lo mas barato de la cartera. Quien entra buscando casa se
       encontraba cuatro fotos de garaje. */
    lista.sort(function (a, b) {
      if (!!b.destacado !== !!a.destacado) return b.destacado ? 1 : -1;
      var fa = numero(a.fecha), fb = numero(b.fecha);
      if (fa !== fb) return fb - fa;
      return (b.precio || 0) - (a.precio || 0);
    });

    grid.innerHTML = '';
    lista.forEach(function (i) { grid.appendChild(ficha(i)); });

    var hayAlguno = lista.length > 0;
    var filtrando = hayFiltro();
    grid.hidden = !hayAlguno;

    /* Tres estados, y no dos. Antes, quedarse sin resultados por culpa de
       un filtro sacaba el aviso de "aqui no hay nada publicado", que era
       mentira: haberlo lo habia, pero no de eso. Decirle a alguien que no
       tienes nada cuando si tienes es la forma mas tonta de perder una
       llamada. */
    vacio.hidden  = !(!hayAlguno && !filtrando);
    if (sinres) sinres.hidden = !(!hayAlguno && filtrando);

    cuenta.textContent = TODOS.length
      ? lista.length + (lista.length === 1 ? T(' inmueble') : T(' inmuebles'))
      : '';

    Array.prototype.forEach.call(limpiar, function (b) { b.hidden = !filtrando; });

    /* Con la cartera entera vacia no tiene sentido enseñar un buscador que
       no busca nada, asi que se esconde y manda el aviso honesto.

       El buscador nace escondido en el html y lo destapa esta linea. Al
       reves parpadeaba: salia con la pagina, y cuando el javascript veia
       que no habia nada lo volvia a tapar. Un trozo de pagina que aparece
       y desaparece solo parece averiado aunque no lo este. */
    var cajaFiltros = $('.cart__filtros', raiz);
    if (cajaFiltros) cajaFiltros.hidden = !TODOS.length;
  }

  montarFiltros();
  MANDOS.forEach(function (m) {
    /* En la referencia se filtra mientras se escribe; en los desplegables,
       al elegir. 'input' vale para los dos, pero 'change' no cubre teclear. */
    m.addEventListener('input', pintar);
    m.addEventListener('change', pintar);
  });
  Array.prototype.forEach.call(limpiar, function (b) {
    b.addEventListener('click', function () {
      MANDOS.forEach(function (m) { m.value = ''; });
      pintar();
      if (fRef) fRef.focus();
    });
  });
  pintar();
  });
  });
})();

/* =========================================================
   EL TRADUCTOR DEL FEED

   Ya no hace falta aqui. Lo hace /api/cartera.php en el servidor, que es
   donde tiene que estar por tres razones: el navegador no puede leer el
   feed de Mobilia (lo impide CORS), las credenciales no deben viajar al
   visitante, y asi mil visitas no son mil llamadas a Mobilia.

   Esta linea sigue existiendo por si algun dia hace falta retocar los
   datos ya traducidos antes de pintarlos. Si no se define, no se toca nada.
   ========================================================= */

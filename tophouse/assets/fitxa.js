/* =========================================================
   La ficha de un inmueble
   =========================================================

   La pagina sale ya hecha del servidor (immoble.php): el titulo, el
   precio, las fotos y la descripcion estan en el html antes de que
   llegue ningun javascript. Eso es lo que hace que al compartirla por
   WhatsApp salga la foto y el precio.

   Aqui solo van los tres anadidos que no hacen falta para eso:
     1. cambiar la foto grande al tocar una miniatura,
     2. poner nombre a las caracteristicas, que viajan como clave,
     3. el formulario de visita, que abre WhatsApp con la referencia
        del inmueble ya escrita.
   ========================================================= */
(function () {
  'use strict';

  var art = document.querySelector('.fitxa');
  if (!art) return;

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var WA = '34605273150';

  /* ---------- 1. la galeria ---------- */
  var grande = $('#fitxa-grande');
  var tiras = art.querySelectorAll('.fitxa__tira');
  Array.prototype.forEach.call(tiras, function (b) {
    b.addEventListener('click', function () {
      var f = b.getAttribute('data-foto');
      if (!f || !grande) return;
      grande.src = f;
      Array.prototype.forEach.call(tiras, function (o) { o.classList.remove('is-on'); });
      b.classList.add('is-on');
    });
  });

  /* ---------- 2. las caracteristicas ---------- */
  var caja = $('#fitxa-tags');
  var claves = (art.getAttribute('data-extras') || '').split(',').filter(Boolean);
  if (caja && claves.length) {
    var nombres = window.NOM_EXTRA || {};
    claves.forEach(function (c) {
      var li = document.createElement('li');
      li.textContent = nombres[c] || c;
      caja.appendChild(li);
    });
  }

  /* ---------- 3. la peticion de visita ---------- */
  var form = $('#form');
  if (!form) return;
  var err = $('#form-err'), ok = $('#form-ok');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = new FormData(form);
    var nombre = String(d.get('nombre') || '').trim();
    var tel = String(d.get('telefono') || '').trim();

    if (!nombre || !tel) {
      err.textContent = T('Nos faltan su nombre y un teléfono para poder llamarle.');
      err.hidden = false;
      (nombre ? $('#f-tel') : $('#f-nombre')).focus();
      return;
    }
    /* Sin consentimiento no hay base legal para tratar sus datos, y ademas
       van a salir por WhatsApp, que es de un tercero. Antes, no despues. */
    var casilla = $('#f-ok');
    if (casilla && !casilla.checked) {
      err.textContent = T('Necesitamos que acepte la política de privacidad antes de enviarnos sus datos.');
      err.hidden = false;
      casilla.focus();
      return;
    }
    err.hidden = true;

    /* La referencia va SIEMPRE y va la primera. Quien reciba el mensaje
       tiene que saber de que piso se habla sin preguntar: es la diferencia
       entre una visita cerrada y tres mensajes de ida y vuelta. */
    var ref = art.getAttribute('data-ref') || '';
    var titulo = (document.querySelector('.fitxa__cap h1') || {}).textContent || '';
    var lineas = [
      T('Hola, soy ') + nombre + '.',
      T('Quiero ver este inmueble: ') + titulo.trim() + (ref ? ' (' + T('referencia ') + ref + ')' : ''),
      T('Teléfono: ') + tel,
      location.href
    ];
    var msg = String(d.get('mensaje') || '').trim();
    if (msg) lineas.push('', msg);

    ok.hidden = false;
    window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(lineas.join('\n')), '_blank', 'noopener');
  });
})();

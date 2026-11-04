/* =========================================================
   Enviar una solicitud y confirmar que ha llegado
   =========================================================

   Los tres formularios de la web (la portada, la ficha de cada inmueble y
   Top House Marine) mandan aqui lo que ha escrito el visitante.

   COMO ERA. El formulario abria WhatsApp con el mensaje escrito y, de
   paso, mandaba una copia por correo sin esperar respuesta. Al visitante
   se le iba la pagina a WhatsApp nada mas pulsar, y nadie le confirmaba
   que su solicitud habia llegado.

   COMO ES. Se manda la solicitud al servidor (api/solicitud.php, que la
   reenvia a la oficina y a Mobilia) y SE ESPERA su respuesta:
     - si dice que ha llegado, se va a la pagina de gracias (gracies.html,
       gracias.html, thanks.html, segun el idioma), que lo confirma y
       ofrece, con un boton, escribir tambien por WhatsApp con el mensaje
       ya redactado;
     - si no ha llegado (sin conexion, servidor caido, quince segundos sin
       respuesta), se le dice en el mismo formulario, con el telefono y el
       boton de WhatsApp a mano. Nunca se le dice "recibido" sin saberlo.

   El mensaje de WhatsApp viaja a la pagina de gracias en sessionStorage:
   no sale de su navegador ni queda en la direccion de la pagina.
   ========================================================= */
window.enviarSolicitud = (function () {
  'use strict';

  var WA = 'https://wa.me/34605273150';
  var t = function (s) { return window.T ? window.T(s) : s; };

  function botonWhatsApp(texto) {
    var a = document.createElement('a');
    a.className = 'btn btn--ghost form__err-wa';
    a.href = WA + (texto ? '?text=' + encodeURIComponent(texto) : '');
    a.target = '_blank';
    a.rel = 'noopener';
    a.textContent = t('Escribir por WhatsApp');
    return a;
  }

  return function (o) {
    var form = o.form, err = o.error;
    if (!form || form.getAttribute('data-enviando') === '1') return;
    form.setAttribute('data-enviando', '1');

    var boton = form.querySelector('button[type="submit"]');
    var textoBoton = boton ? boton.textContent : '';
    if (boton) { boton.disabled = true; boton.textContent = t('Enviando…'); }
    if (err) { err.hidden = true; err.textContent = ''; }

    var datos = o.datos || {};
    /* De la pagina, no del formulario: quien reciba el correo quiere poder
       abrir el inmueble de un clic, y el idioma le dice en que lengua
       conviene contestar. */
    datos.pagina = location.href;
    datos.idioma = (document.documentElement.getAttribute('lang') || '').slice(0, 2);

    function fallo() {
      form.removeAttribute('data-enviando');
      if (boton) { boton.disabled = false; boton.textContent = textoBoton; }
      if (!err) return;
      err.textContent = t('No hemos podido enviar su solicitud. Llámenos al 605 27 31 50 o escríbanos por WhatsApp, y la atendemos igual.');
      err.appendChild(document.createElement('br'));
      err.appendChild(botonWhatsApp(o.whatsapp));
      err.hidden = false;
    }

    if (!window.fetch) { fallo(); return; }

    var ctrl = window.AbortController ? new AbortController() : null;
    var reloj = setTimeout(function () { if (ctrl) ctrl.abort(); }, 15000);

    fetch('/api/solicitud.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datos),
      signal: ctrl ? ctrl.signal : undefined
    }).then(function (res) {
      return res.json()['catch'](function () { return null; }).then(function (j) {
        return res.ok && j && j.ok === true;
      });
    })['catch'](function () { return false; })
      .then(function (llegado) {
        clearTimeout(reloj);
        if (!llegado) { fallo(); return; }
        try {
          sessionStorage.setItem('th-solicitud', JSON.stringify({
            whatsapp: o.whatsapp || '',
            origen: datos.origen || ''
          }));
        } catch (e) { /* sin sessionStorage, la pagina de gracias pone el boton sin mensaje */ }
        location.href = form.getAttribute('data-gracias') || '/gracies.html';
      });
  };
})();

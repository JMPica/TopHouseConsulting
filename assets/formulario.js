/* =========================================================
   El formulario de las paginas de servicio
   =========================================================

   Vender, valoracion, gestion de alquileres y contacto llevan el mismo
   formulario que la portada, pero sin el motor de la portada (site.js),
   que arrastra el video. Aqui va solo lo del formulario, con los mismos
   mensajes y el mismo envio: assets/aviso.js manda la solicitud, espera
   a que el servidor confirme y lleva a la pagina de gracias.

   Cada pagina dice en el propio <form> de donde viene la solicitud
   (data-origen) para que en el correo y en las cifras se sepa si es un
   propietario que vende, uno que alquila o alguien que pregunta.
   ========================================================= */
(function () {
  'use strict';

  var form = document.querySelector('#form[data-origen]');
  if (!form) return;
  var err = document.querySelector('#form-err');
  var T = window.T || function (s) { return s; };

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = new FormData(form);
    var nombre = String(d.get('nombre') || '').trim();
    var tel = String(d.get('telefono') || '').trim();

    if (!nombre || !tel) {
      err.textContent = T('Nos faltan su nombre y un teléfono para poder llamarle.');
      err.hidden = false;
      document.querySelector(nombre ? '#f-tel' : '#f-nombre').focus();
      return;
    }
    var ok = document.querySelector('#f-ok');
    if (ok && !ok.checked) {
      err.textContent = T('Necesitamos que acepte la política de privacidad antes de enviarnos sus datos.');
      err.hidden = false;
      ok.focus();
      return;
    }
    err.hidden = true;

    var necesita = String(d.get('necesita') || '').trim();
    var zona = String(d.get('zona') || '').trim();
    var msg = String(d.get('mensaje') || '').trim();
    var lineas = [T('Hola, soy ') + nombre + '.', T('Teléfono: ') + tel];
    if (necesita) lineas.push(T('Qué necesito: ') + necesita);
    if (zona) lineas.push(T('Tramo: ') + zona);
    if (msg) lineas.push('', msg);

    window.enviarSolicitud({
      form: form,
      error: err,
      whatsapp: lineas.join('\n'),
      datos: {
        origen: form.getAttribute('data-origen'),
        nombre: nombre,
        telefono: tel,
        zona: zona,
        necesita: necesita,
        mensaje: msg,
        consentimiento: true,
        empresa: String(d.get('empresa') || '')
      }
    });
  });
})();

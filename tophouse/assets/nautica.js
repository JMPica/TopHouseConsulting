/* =========================================================
   Top House Marine: el formulario de la pagina nautica
   =========================================================

   El mismo camino que el de la portada y el de las fichas: una copia
   por correo a la oficina (assets/aviso.js) y WhatsApp con el mensaje ya
   escrito. Lo unico propio son los datos del barco, que viajan juntos
   en 'extra' para que el correo los ensene en una sola linea.
   ========================================================= */
(function () {
  'use strict';

  var form = document.querySelector('#form');
  if (!form) return;
  var $ = function (s) { return document.querySelector(s); };
  var err = $('#form-err');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var d = new FormData(form);
    var val = function (k) { return String(d.get(k) || '').trim(); };
    var nombre = val('nombre'), tel = val('telefono');

    if (!nombre || !tel) {
      err.textContent = T('Nos faltan su nombre y un teléfono para poder llamarle.');
      err.hidden = false;
      (nombre ? $('#f-tel') : $('#f-nombre')).focus();
      return;
    }
    var casilla = $('#f-ok');
    if (casilla && !casilla.checked) {
      err.textContent = T('Necesitamos que acepte la política de privacidad antes de enviarnos sus datos.');
      err.hidden = false;
      casilla.focus();
      return;
    }
    err.hidden = true;

    var tipo = val('tipo'), eslora = val('eslora'), puerto = val('puerto');
    var necesita = val('necesita') || 'Vender';
    var msg = val('mensaje');

    /* En el correo, una sola linea con todo el barco. */
    var barco = [tipo, eslora ? 'eslora ' + eslora : ''].filter(Boolean).join(', ');

    var lineas = [
      T('Hola, soy ') + nombre + '.',
      T('Teléfono: ') + tel,
      T('Qué necesito: ') + T(necesita),
      T('Embarcación: ') + T(tipo) + (eslora ? ', ' + eslora : '')
    ];
    if (puerto) lineas.push(T('Puerto: ') + puerto);
    if (msg) lineas.push('', msg);

    window.enviarSolicitud({
      form: form,
      error: err,
      whatsapp: lineas.join('\n'),
      datos: {
        origen: 'nautica',
        nombre: nombre,
        telefono: tel,
        zona: puerto,
        necesita: necesita,
        extra: barco,
        mensaje: msg,
        consentimiento: true,
        empresa: val('empresa')
      }
    });
  });
})();

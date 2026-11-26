/* =========================================================
   La pagina de gracias
   =========================================================
   Recoge lo que dejo el formulario al confirmarse el envio (assets/aviso.js):
   el mensaje para WhatsApp y de que formulario venia. Con el mensaje, el
   boton abre WhatsApp con la solicitud ya escrita; sin el, abre el chat
   vacio. Si venia de Top House Marine, la pagina se pone en azul.

   Se borra al leerlo: si el visitante vuelve a entrar mas tarde, no
   arrastra la solicitud de otro dia.
   ========================================================= */
(function () {
  'use strict';
  var datos = null;
  try {
    datos = JSON.parse(sessionStorage.getItem('th-solicitud') || 'null');
    sessionStorage.removeItem('th-solicitud');
  } catch (e) { datos = null; }

  var wa = document.querySelector('#gr-wa');
  if (wa && datos && datos.whatsapp) {
    wa.href = 'https://wa.me/34605273150?text=' + encodeURIComponent(datos.whatsapp);
  }
  if (datos && datos.origen === 'nautica') {
    document.body.classList.add('mar');
    var k = document.querySelector('.nav__kicker');
    if (k) k.textContent = 'Marine';
  }
})();

/* =========================================================
   Que la solicitud llegue aunque WhatsApp no se envie
   =========================================================

   Los dos formularios de la web (el de la portada y el de la ficha de
   cada inmueble) abren WhatsApp con el mensaje ya escrito. Eso esta bien
   para el visitante, pero deja un hueco grande: si no le da a enviar, si
   lo abre en un ordenador sin sesion, o si se lo piensa, la solicitud no
   le llega a nadie y no hay forma de saber que existio.

   Esto manda una copia al servidor (api/solicitud.php), que la reenvia
   por correo a la oficina en el momento en que el visitante le da al
   boton. Los dos caminos a la vez.

   TRES DECISIONES QUE IMPORTAN

   1. No se espera respuesta. Si el servidor tarda o falla, al visitante
      no le pasa nada: WhatsApp se le abre igual y ve lo mismo de antes.
      Esto solo anade, nunca quita.

   2. Se llama ANTES de abrir WhatsApp, pero en la misma vuelta del
      codigo. fetch() no bloquea, asi que la ventana de WhatsApp se sigue
      abriendo dentro del gesto del visitante y el navegador no la toma
      por una ventana emergente que hay que tapar.

   3. keepalive. En el movil, abrir WhatsApp deja esta pagina en segundo
      plano y el navegador puede cortar lo que tenga a medias. Con esto
      la peticion sale entera aunque la pagina se quede atras.
   ========================================================= */
window.avisar = (function () {
  'use strict';

  return function (datos) {
    if (!datos) return;
    try {
      /* De la pagina, no del formulario: quien reciba el correo quiere
         poder abrir el inmueble de un clic, y el idioma le dice en que
         lengua conviene contestarle. */
      datos.pagina = location.href;
      datos.idioma = (document.documentElement.getAttribute('lang') || '').slice(0, 2);

      if (!window.fetch) return;
      fetch('/api/solicitud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos),
        keepalive: true
      })['catch'](function () { /* el visitante ya tiene su WhatsApp */ });
    } catch (e) { /* lo mismo */ }
  };
})();

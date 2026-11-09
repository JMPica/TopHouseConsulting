/* =========================================================
   Contar lo que importa, sin seguir a nadie
   =========================================================

   Manda a /api/evento.php un aviso de una linea cuando pasa algo que
   interesa al negocio: alguien entra desde fuera, pulsa el telefono, el
   WhatsApp o el correo, usa la calculadora, o envia un formulario.

   Sin cookies, sin guardar nada en el navegador, sin identificadores.
   De donde viene la visita se reduce a una categoria (chatgpt, google,
   portal...): la direccion exacta no sale de aqui.

   Los formularios avisan desde assets/aviso.js con window.contar(), que
   es la unica cosa que este fichero deja a la vista.
   ========================================================= */
(function () {
  'use strict';

  function enviar(e, extra) {
    var datos = { e: e, p: location.pathname, f: fuente(), o: (extra && extra.o) || '' };
    try {
      var cuerpo = JSON.stringify(datos);
      if (navigator.sendBeacon) {
        navigator.sendBeacon('/api/evento.php', new Blob([cuerpo], { type: 'application/json' }));
      } else if (window.fetch) {
        fetch('/api/evento.php', { method: 'POST', body: cuerpo, keepalive: true,
          headers: { 'Content-Type': 'application/json' } })['catch'](function () {});
      }
    } catch (x) { /* contar nunca puede romper la pagina */ }
  }
  window.contar = enviar;

  /* De donde viene la visita, en una palabra. Los asistentes de IA que
     enlazan fuentes (ChatGPT anade ?utm_source=chatgpt.com) se distinguen
     de los buscadores de toda la vida. */
  var memo = null;
  function fuente() {
    if (memo) return memo;
    var utm = '';
    try { utm = (new URLSearchParams(location.search).get('utm_source') || '').toLowerCase(); } catch (x) {}
    var r = '';
    try { r = document.referrer ? new URL(document.referrer).hostname.toLowerCase() : ''; } catch (x) {}
    var t = utm + ' ' + r;
    var reglas = [
      ['chatgpt', /chatgpt|openai/], ['perplexity', /perplexity/], ['copilot', /copilot/],
      ['gemini', /gemini|bard\.google/], ['claude', /claude\.ai|anthropic/],
      ['otra-ia', /you\.com|phind|mistral|deepseek|grok|meta\.ai/],
      ['qr', /\bqr\b|cartel/],
      ['google', /(^|\.)google\./], ['bing', /(^|\.)bing\.com/], ['duckduckgo', /duckduckgo/],
      ['otro-buscador', /yahoo|ecosia|qwant|brave|yandex|startpage/],
      ['portal', /idealista|fotocasa|habitaclia|pisos\.com|milanuncios|yaencontre|indomio|mobilia/],
      ['social', /facebook|instagram|t\.co$|twitter|x\.com|linkedin|whatsapp|tiktok|pinterest/],
      ['correo', /mail\.|outlook|gmail|newsletter|email/]
    ];
    for (var i = 0; i < reglas.length; i++) {
      if (reglas[i][1].test(t)) return (memo = reglas[i][0]);
    }
    if (r && r === location.hostname) return (memo = 'interna');
    return (memo = r ? 'otra' : 'directa');
  }

  /* La entrada: solo cuando se llega de fuera. Pasar de una pagina a
     otra dentro de la web no es una entrada nueva. */
  if (fuente() !== 'interna') enviar('llegada');

  /* Los clics que son un contacto. Un solo oyente para toda la pagina. */
  document.addEventListener('click', function (ev) {
    var a = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
    if (!a) return;
    var h = a.getAttribute('href') || '';
    if (h.indexOf('tel:') === 0) enviar('tel');
    else if (h.indexOf('wa.me') !== -1 || h.indexOf('whatsapp') !== -1) enviar('whatsapp');
    else if (h.indexOf('mailto:') === 0) enviar('correo');
  }, true);

  /* La calculadora de la portada: es una senal de que alguien piensa en
     vender, aunque luego no escriba. */
  var calc = document.querySelector('#calc-form');
  if (calc) calc.addEventListener('submit', function () { enviar('calculadora'); });
})();

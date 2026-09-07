/* =========================================================
   Los textos que viven dentro del javascript
   =========================================================

   El html lo traduce el generador (construir.py) antes de publicar, pero
   hay frases que el javascript escribe sobre la marcha: el resultado de
   la calculadora, los avisos del formulario, las fichas de la cartera.
   Esas van aqui.

   LAS CLAVES SON EL TEXTO EN CASTELLANO. Eso tiene dos ventajas: el
   codigo se sigue leyendo sin ir a buscar que significa cada clave, y el
   castellano no necesita tabla, porque la clave ya es la frase.

   PARA ANADIR UNA FRASE NUEVA: envuelvela en T('...') en el codigo y
   anade su traduccion aqui en ca y en en. Si te la dejas, en catalan y en
   ingles saldra en castellano; no se rompe nada, pero se ve.
   ========================================================= */
window.T = (function () {
  'use strict';

  var DIC = {
    ca: {
      /* tipos de inmueble */
      'Piso':'Pis', 'Ático':'Àtic', 'Planta baja':'Planta baixa',
      'Casa':'Casa', 'Casa o torre':'Casa o torre',
      'Local':'Local', 'Terreno':'Terreny',

      /* la calculadora */
      'Unos ':'Uns ',
      ' · precios de ':' · preus de ',
      /* En catala "de" es contrau davant de vocal: es "d'Arenys", no "de
         Arenys". Como el nombre de la poblacion lo pone el codigo, la
         contraccion no se puede resolver concatenando. Se esquiva
         poniendo la poblacion delante y dejando el resto detras. */
      'Precio base de ':'',
      ' tomado de datos publicados de mercado.':': preu base pres de dades publicades de mercat.',
      'El precio base de ':'',
      ' es una estimación nuestra, no un dato publicado. Llámenos y se lo afinamos.':
        ': el preu base és una estimació nostra, no una dada publicada. Truqui’ns i l’hi afinem.',

      /* el formulario */
      'Nos faltan su nombre y un teléfono para poder llamarle.':
        'Ens falta el seu nom i un telèfon per poder trucar-li.',
      'Necesitamos que acepte la política de privacidad antes de enviarnos sus datos.':
        'Necessitem que accepti la política de privacitat abans d’enviar-nos les seves dades.',
      'Hola, soy ':'Hola, sóc ',
      'Teléfono: ':'Telèfon: ',
      'Qué necesito: ':'Què necessito: ',
      'Tramo: ':'Tram: ',
      'Inmueble: ':'Immoble: ',
      'Horquilla que me ha salido en la web: ':'Forquilla que m’ha sortit al web: ',

      /* la cartera */
      'Hasta ':'Fins a ',
      ' al mes':' al mes',
      'Sin foto todavía':'Encara sense foto',
      'Destacado':'Destacat',
      'Inmueble':'Immoble',
      ' habitación':' habitació', ' habitaciones':' habitacions',
      ' baño':' bany',        ' baños':' banys',
      'A consultar':'A consultar',
      'Quiero verlo':'El vull veure',
      ' (referencia ':' (referència ',
      ' inmueble':' immoble', ' inmuebles':' immobles'
    },

    en: {
      'Piso':'Flat', 'Ático':'Penthouse', 'Planta baja':'Ground floor',
      'Casa':'House', 'Casa o torre':'House or villa',
      'Local':'Commercial unit', 'Terreno':'Land',

      'Unos ':'About ',
      ' · precios de ':' · prices from ',
      'Precio base de ':'Base price for ',
      ' tomado de datos publicados de mercado.':' taken from published market data.',
      'El precio base de ':'The base price for ',
      ' es una estimación nuestra, no un dato publicado. Llámenos y se lo afinamos.':
        ' is our own estimate, not published data. Call us and we will refine it.',

      'Nos faltan su nombre y un teléfono para poder llamarle.':
        'We need your name and a phone number to call you.',
      'Necesitamos que acepte la política de privacidad antes de enviarnos sus datos.':
        'Please accept the privacy policy before sending us your details.',
      'Hola, soy ':'Hello, I am ',
      'Teléfono: ':'Phone: ',
      'Qué necesito: ':'What I need: ',
      'Tramo: ':'Area: ',
      'Inmueble: ':'Property: ',
      'Horquilla que me ha salido en la web: ':'Range the website gave me: ',

      'Hasta ':'Up to ',
      ' al mes':' per month',
      'Sin foto todavía':'No photo yet',
      'Destacado':'Featured',
      'Inmueble':'Property',
      ' habitación':' bedroom', ' habitaciones':' bedrooms',
      ' baño':' bathroom',  ' baños':' bathrooms',
      'A consultar':'On request',
      'Quiero verlo':'I want to see it',
      ' (referencia ':' (reference ',
      ' inmueble':' property', ' inmuebles':' properties'
    }
  };

  var idioma = (document.documentElement.getAttribute('lang') || 'es').slice(0, 2);
  var tabla  = DIC[idioma] || {};

  /* Si falta una traduccion se devuelve el castellano en vez de una clave
     cruda o un hueco: se ve raro, pero la pagina sigue teniendo sentido. */
  return function (texto) {
    return Object.prototype.hasOwnProperty.call(tabla, texto) ? tabla[texto] : texto;
  };
})();

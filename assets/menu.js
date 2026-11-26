/* =========================================================
   El menu de la barra en movil y tableta
   =========================================================

   Por debajo de 1080px los enlaces de la barra (Vender, Comprar,
   Alquilar...) se esconden porque no caben, y hasta ahora no habia nada
   en su lugar: en un movil no habia forma de ir a la cartera sin bajar
   la portada entera hasta dar con el boton. Lo vio Top House en su
   telefono.

   Esto pone un boton de menu que despliega ESOS MISMOS enlaces. No se
   duplican en el html: se reusa el mismo bloque, asi que no hay dos
   listas que mantener ni textos nuevos que traducir.

   SIN JAVASCRIPT NO CAMBIA NADA. El boton lo crea este fichero y la
   clase 'menu-js' la pone este fichero; todo lo del css cuelga de esa
   clase. Si esto no llega a ejecutarse, la barra queda exactamente como
   estaba, con el selector de idioma y el telefono a la vista.

   LO QUE SE MUEVE DENTRO DEL MENU, Y POR QUE

   En la barra de un movil no cabe un boton mas: a 390px quedan 25
   pixeles libres y a 360, 14; el boton necesita unos 50 para poder
   tocarse con el dedo. Algo tenia que entrar en el menu, y se eligio
   por lo que vale para una inmobiliaria:

     - El TELEFONO se queda siempre fuera. Es el boton que mas convierte.
     - El SELECTOR DE IDIOMA entra en el menu en movil (<=480px). Se usa
       una vez: quien llega desde Google ya llega en su idioma.
     - El boton de VALORACION entra en el menu en tableta (<=860px),
       donde con el boton nuevo ya no cabe en la barra. En movil ya
       estaba retirado.

   No se mueven: se COPIAN dentro del menu, y el css ensena en cada
   ancho una sola de las dos. Asi el lector de pantalla nunca lee dos.
   ========================================================= */
(function () {
  'use strict';

  var nav = document.getElementById('nav');
  if (!nav) return;
  var lista = nav.querySelector('.nav__links');
  var acciones = nav.querySelector('.nav__actions');
  if (!lista || !acciones) return;

  var T = window.T || function (s) { return s; };
  if (!lista.id) lista.id = 'menu-principal';

  /* ---------- las copias que viven dentro del menu ---------- */
  var idioma = acciones.querySelector('.lang');
  if (idioma) {
    var copiaIdioma = idioma.cloneNode(true);
    copiaIdioma.classList.add('menu__lang');
    lista.appendChild(copiaIdioma);
  }
  var cta = acciones.querySelector('.btn--solid');
  if (cta) {
    var copiaCta = cta.cloneNode(true);
    copiaCta.classList.add('menu__cta');
    copiaCta.removeAttribute('tabindex');
    lista.appendChild(copiaCta);
  }

  /* ---------- el boton ---------- */
  var boton = document.createElement('button');
  boton.type = 'button';
  boton.className = 'nav__menu';
  boton.setAttribute('aria-controls', lista.id);
  boton.setAttribute('aria-expanded', 'false');
  boton.setAttribute('aria-label', T('Abrir el menú'));
  boton.innerHTML = '<span class="nav__menu-i" aria-hidden="true"><i></i><i></i><i></i></span>';
  acciones.appendChild(boton);

  document.documentElement.classList.add('menu-js');

  /* El menu solo existe mientras la barra no ensena los enlaces. Tiene
     que coincidir con el 1080 del css: si no, el boton podria abrir una
     lista que ya se ve, o no abrir una que no se ve. */
  var estrecho = window.matchMedia ? window.matchMedia('(max-width:1080px)') : null;

  function abierto() { return nav.classList.contains('is-open'); }

  function abrir() {
    nav.classList.add('is-open');
    boton.setAttribute('aria-expanded', 'true');
    boton.setAttribute('aria-label', T('Cerrar el menú'));
    /* El foco al primer enlace: quien navega con teclado o con lector de
       pantalla tiene que caer dentro de lo que acaba de abrir. */
    var primero = lista.querySelector('a');
    if (primero) { try { primero.focus({ preventScroll: true }); } catch (e) { primero.focus(); } }
  }

  function cerrar(devolverFoco) {
    if (!abierto()) return;
    nav.classList.remove('is-open');
    boton.setAttribute('aria-expanded', 'false');
    boton.setAttribute('aria-label', T('Abrir el menú'));
    if (devolverFoco) boton.focus();
  }

  boton.addEventListener('click', function () {
    if (abierto()) cerrar(false); else abrir();
  });

  /* Al tocar un enlace se cierra. En la portada casi todos son anclas de
     la misma pagina (#vender, #contacto): sin esto, la pagina bajaba a
     la seccion y el menu se quedaba abierto tapandola. */
  lista.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a') : null;
    if (a && abierto()) cerrar(false);
  });

  /* Escape cierra y devuelve el foco al boton, que es donde estaba. */
  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Escape' || e.key === 'Esc') && abierto()) cerrar(true);
  });

  /* Tocar fuera de la barra lo cierra. */
  document.addEventListener('pointerdown', function (e) {
    if (abierto() && !nav.contains(e.target)) cerrar(false);
  });

  /* Si la ventana se ensancha hasta ensenar los enlaces en la barra, el
     menu no puede quedarse abierto por debajo. */
  if (estrecho) {
    var alCambiar = function (m) { if (!m.matches) cerrar(false); };
    if (estrecho.addEventListener) estrecho.addEventListener('change', alCambiar);
    else if (estrecho.addListener) estrecho.addListener(alCambiar);
  }
})();

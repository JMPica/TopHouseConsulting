/* =========================================================
   Lo comun a todas las paginas que no son la portada
   =========================================================

   La portada tiene su propio motor (site.js) porque lleva el video que
   avanza con el scroll. Las demas paginas solo necesitan tres cosas, y
   son estas. Vive aparte porque con tres idiomas serian nueve paginas
   repitiendo lo mismo, y algo repetido nueve veces se corrige mal en
   ocho de ellas.
   ========================================================= */
(function () {
  'use strict';

  /* El CSS arranca con body{opacity:0} y la pagina solo se revela con la
     clase 'lit'. Sin esto se queda en blanco del todo: no es un detalle
     estetico, es la diferencia entre verse y no verse. */
  requestAnimationFrame(function () { document.body.classList.add('lit'); });

  /* La barra de arriba se vuelve solida al bajar, igual que en la portada. */
  var navEl = document.querySelector('#nav');
  if (navEl) {
    var solida = false;
    var mirarNav = function () {
      var quiere = window.scrollY > 40;
      if (quiere !== solida) { solida = quiere; navEl.classList.toggle('solid', quiere); }
    };
    mirarNav();
    window.addEventListener('scroll', mirarNav, { passive: true });
  }

  /* El año del pie, para que no envejezca solo. */
  var y = document.querySelector('#year');
  if (y) y.textContent = String(new Date().getFullYear());
})();

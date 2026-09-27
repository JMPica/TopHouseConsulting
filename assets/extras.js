/* =========================================================
   El nombre visible de cada caracteristica
   =========================================================

   El puente manda CLAVES estables ('primera-linia') y no texto, para que
   salgan iguales en los tres idiomas y no dependan de como las escriba
   Mobilia. Aqui se les pone nombre.

   Vive en su propio fichero porque lo usan dos paginas: las tarjetas de
   la cartera y la ficha de cada inmueble. Tenerlo dos veces significaria
   que un dia se anade una caracteristica en un sitio y no en el otro.

   Se carga DESPUES de idioma.js, que es de donde sale T().
   ========================================================= */
window.NOM_EXTRA = {
  'primera-linia':T('Primera línea de playa'), 'segona-linia':T('Segunda línea de playa'),
  'vistes':T('Vistas'), 'piscina':T('Piscina privada'),
  'piscina-comunitaria':T('Piscina comunitaria'), 'ascensor':T('Ascensor'),
  'terrassa':T('Terraza'), 'jardi':T('Jardín'), 'pati':T('Patio'),
  'parquing':T('Parking'), 'traster':T('Trastero'),
  'calefaccio':T('Calefacción'), 'aire':T('Aire acondicionado'),
  'llar-de-foc':T('Chimenea'), 'moblat':T('Amueblado'),
  'cuina-equipada':T('Cocina equipada'), 'armaris':T('Armarios'),
  'exterior':T('Exterior'), 'zones-comunes':T('Zonas comunes'),
  'zones-verdes':T('Zonas verdes'), 'barbacoa':T('Barbacoa'),
  'solarium':T('Solárium'), 'safareig':T('Lavadero'), 'celler':T('Bodega'),
  'golfes':T('Buhardilla'), 'gimnas':T('Gimnasio'), 'padel':T('Pista de pádel'),
  'tenis':T('Pista de tenis'), 'conserge':T('Conserje'),
  'vigilancia':T('Vigilancia 24 h'), 'alarma':T('Alarma'),
  'porta-blindada':T('Puerta blindada'), 'adaptat':T('Adaptado'),
  'mascotes':T('Admite mascotas')
};

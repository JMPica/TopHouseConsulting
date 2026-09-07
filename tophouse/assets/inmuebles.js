/* =========================================================
   La cartera de inmuebles
   =========================================================

   ATENCION, TOP HOUSE: este fichero es el PLAN B.

   Mobilia ya empuja las fichas a vuestra web actual, asi que lo suyo es que
   esta web lea ese mismo feed y no una copia a mano, que se quedaria vieja.
   Como conseguirlo esta en CARTERA-Y-MOBILIA.txt, en la carpeta de la web.

   Este fichero se usa mientras no haya feed, y como red de seguridad si el
   feed falla algun dia.

   PARA ANADIR UN INMUEBLE, copiad un bloque y cambiad los datos. Los campos:

     ref ......... vuestra referencia de Mobilia, la que sale en la ficha
     operacion ... 'venta' o 'alquiler'
     tipo ........ 'piso', 'atico', 'casa', 'bajo', 'local' o 'terreno'
     titulo ...... una linea, lo primero que lee el visitante
     poblacio .... la poblacion, escrita igual que en el desplegable
     zona ........ barrio o tramo, opcional
     precio ...... numero, sin puntos ni simbolo. En alquiler, al mes
     m2 .......... metros construidos
     hab ......... habitaciones
     banys ....... banos
     extras ...... lista libre, sale como etiquetas en la ficha
     foto ........ ruta de la imagen, por ejemplo 'assets/inmuebles/ref-101.jpg'
                   Si se deja vacia sale un marcador y la ficha sigue valiendo
     destacado ... true si quereis que salga primero

   MIENTRAS ESTE ARRAY ESTE VACIO, la web muestra un aviso honesto de que la
   cartera se esta cargando y empuja al telefono. Es a proposito: es preferible
   eso a inventar pisos que no existen.
   ========================================================= */

window.INMUEBLES = [

  /* Ejemplo, borradlo cuando pongais los de verdad:

  {
    ref: 'THC-101',
    operacion: 'venta',
    tipo: 'piso',
    titulo: 'Piso reformado a dos calles del paseo',
    poblacio: 'Arenys de Mar',
    zona: 'Centre i la Riera',
    precio: 315000,
    m2: 92,
    hab: 3,
    banys: 2,
    extras: ['Ascensor', 'Terraza', 'Exterior'],
    foto: '',
    destacado: true
  },

  */

];

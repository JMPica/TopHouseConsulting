/* =========================================================
   Poblaciones y zonas de Catalunya para la calculadora
   =========================================================

   ATENCION, TOP HOUSE: esta tabla decide el precio que ve el visitante.
   Repasadla, porque sale con vuestro nombre.

   LO PRIMERO Y MAS IMPORTANTE
   Todas las cifras publicadas de las que parte esta tabla son PRECIOS DE
   OFERTA: lo que se pide en los portales, no lo que se firma en notaria. Lo
   pedido esta siempre por encima de lo cerrado, asi que valorar con precios
   de portal infla las expectativas del vendedor. Es justo lo que vuestra web
   dice que se paga en meses.

   Por eso existe AJUSTE_OFERTA, que baja toda la tabla de golpe para
   acercarla a precio de cierre. Va a 0,94 (un 6% menos) como punto de
   partida prudente, pero EL NUMERO BUENO LO TENEIS VOSOTROS: mirad vuestras
   ultimas diez ventas, dividid precio de firma entre precio de salida, y
   poned esa media aqui. Es el cambio que mas afina toda la calculadora, y
   solo lo podeis hacer vosotros.

   EL CAMPO 'font' DICE DE DONDE SALE CADA NUMERO
     'publicado' .. cifra publicada para ese municipio (la mas fiable)
     'rango' ...... publicado como horquilla; se toma un punto medio
     'conflicto' .. las fuentes se contradicen; se coge la mas prudente
     'estimado' ... interpolacion mia sobre el gradiente conocido.
                    ESTAS SON LAS QUE HAY QUE CAMBIAR PRIMERO donde vendais.

   Fuentes de las cifras publicadas, entre julio y septiembre de 2026:
   idealista, Fotocasa, RealAdvisor y el ranking del Maresme de Capgros.

   Ante la duda siempre se tira a la baja. Quedarse corto en la horquilla
   cuesta una llamada; pasarse cuesta seis meses de casa parada.

   'costa' marca los municipios con mar, que son los unicos donde tiene
   sentido ofrecer la zona de primera linea.
   ========================================================= */

/* Convierte precio de oferta en precio de cierre. Cambiadlo por vuestro
   ratio real de firma sobre salida. */
window.AJUSTE_OFERTA = 0.94;

window.POBLACIONS = {

  /* ---- El Maresme, el mercado propio ---- */
  'arenys-de-mar':      { nom:'Arenys de Mar',        comarca:'El Maresme', base:2871, costa:true, font:'publicado',
      zones:[ ['passeig','Primera línia i passeig',1.25], ['centre','Centre i la Riera',1.05],
              ['eixample','Eixample i estació',1.00], ['alta','Zona alta',0.92],
              ['afores','Afores i Sant Elm',0.85] ] },
  'arenys-de-munt':     { nom:'Arenys de Munt',       comarca:'El Maresme', base:2200, costa:false, font:'estimado' },
  'caldes-destrac':     { nom:"Caldes d'Estrac",      comarca:'El Maresme', base:3300, costa:true, font:'estimado' },
  'sant-vicenc-montalt':{ nom:'Sant Vicenç de Montalt',comarca:'El Maresme', base:3300, costa:true, font:'estimado' },
  'sant-andreu-llavaneres':{nom:'Sant Andreu de Llavaneres',comarca:'El Maresme',base:3200,costa:true, font:'rango' },
  'mataro':             { nom:'Mataró',               comarca:'El Maresme', base:2471, costa:true, font:'publicado' },
  'cabrera-de-mar':     { nom:'Cabrera de Mar',       comarca:'El Maresme', base:3300, costa:true, font:'estimado' },
  'vilassar-de-mar':    { nom:'Vilassar de Mar',      comarca:'El Maresme', base:3975, costa:true, font:'publicado' },
  'vilassar-de-dalt':   { nom:'Vilassar de Dalt',     comarca:'El Maresme', base:2750, costa:false, font:'estimado' },
  'premia-de-mar':      { nom:'Premià de Mar',        comarca:'El Maresme', base:2800, costa:true, font:'rango' },
  'premia-de-dalt':     { nom:'Premià de Dalt',       comarca:'El Maresme', base:3777, costa:false, font:'publicado' },
  'el-masnou':          { nom:'El Masnou',            comarca:'El Maresme', base:4200, costa:true, font:'conflicto' },  /* una fuente da 6.516, claramente de oferta; se deja muy por debajo */
  'montgat':            { nom:'Montgat',              comarca:'El Maresme', base:3700, costa:true, font:'estimado' },  /* los 5.457 publicados son de OBRA NUEVA, no sirven para segunda mano */
  'teia':               { nom:'Teià',                 comarca:'El Maresme', base:3200, costa:false, font:'rango' },
  'alella':             { nom:'Alella',               comarca:'El Maresme', base:3993, costa:false, font:'publicado' },  /* el mas caro del Maresme */
  'argentona':          { nom:'Argentona',            comarca:'El Maresme', base:1882, costa:false, font:'publicado' },
  'cabrils':            { nom:'Cabrils',              comarca:'El Maresme', base:2700, costa:false, font:'rango' },
  'canet-de-mar':       { nom:'Canet de Mar',         comarca:'El Maresme', base:2400, costa:true, font:'estimado' },
  'sant-pol-de-mar':    { nom:'Sant Pol de Mar',      comarca:'El Maresme', base:3533, costa:true, font:'publicado' },
  'calella':            { nom:'Calella',              comarca:'El Maresme', base:2950, costa:true, font:'conflicto' },  /* 2.950 y 3.117 segun fuente; se coge la baja */
  'pineda-de-mar':      { nom:'Pineda de Mar',        comarca:'El Maresme', base:2216, costa:true, font:'conflicto' },  /* 2.216 y 4.372 segun fuente; se coge la baja */
  'malgrat-de-mar':     { nom:'Malgrat de Mar',       comarca:'El Maresme', base:2100, costa:true, font:'estimado' },
  'santa-susanna':      { nom:'Santa Susanna',        comarca:'El Maresme', base:2250, costa:true, font:'estimado' },
  'tordera':            { nom:'Tordera',              comarca:'El Maresme', base:1742, costa:false, font:'publicado' },  /* el mas barato del Maresme */
  'palafolls':          { nom:'Palafolls',            comarca:'El Maresme', base:2050, costa:false, font:'estimado' },

  /* Los cinco que faltaban para tener la comarca entera. Top House opera
     en todo el Maresme, asi que la calculadora no puede dejar fuera cinco
     de sus treinta municipios: quien busque el suyo y no lo encuentre da
     por hecho que ahi no trabajais.

     Los cinco son estimaciones, no datos publicados, y salen de comparar
     con sus vecinos inmediatos. Estan marcados como 'estimado', asi que
     la web lo dice en voz alta al dar el resultado. Tiana es el caro del
     grupo (pegado a Alella y a Montgat); los cuatro de interior son
     pueblos pequenos de la Vallalta y el Corredor, mas baratos que la
     costa que tienen debajo. Revisadlos con vuestros datos. */
  'tiana':              { nom:'Tiana',                comarca:'El Maresme', base:3800, costa:false, font:'estimado' },
  'dosrius':            { nom:'Dosrius',              comarca:'El Maresme', base:1900, costa:false, font:'estimado' },
  'orrius':             { nom:'Òrrius',               comarca:'El Maresme', base:2000, costa:false, font:'estimado' },
  'sant-cebria':        { nom:'Sant Cebrià de Vallalta', comarca:'El Maresme', base:1900, costa:false, font:'estimado' },
  'sant-iscle':         { nom:'Sant Iscle de Vallalta',  comarca:'El Maresme', base:1850, costa:false, font:'estimado' },

  /* ---- Barcelonès ---- */
  'barcelona':          { nom:'Barcelona',            comarca:'Barcelonès', base:5400, costa:true, font:'publicado',
      zones:[ ['sarria','Sarrià i Sant Gervasi',1.28], ['eixample','Eixample',1.13],
              ['ciutat-vella','Ciutat Vella i Barceloneta',1.02], ['gracia','Gràcia',1.06],
              ['sants','Sants i Les Corts',0.98], ['sant-marti','Sant Martí i Poblenou',0.95],
              ['horta','Horta i Nou Barris',0.72] ] },
  'badalona':           { nom:'Badalona',             comarca:'Barcelonès', base:3100, costa:true, font:'estimado' },
  'santa-coloma':       { nom:'Santa Coloma de Gramenet',comarca:'Barcelonès', base:2750, costa:false, font:'estimado' },
  'hospitalet':         { nom:"L'Hospitalet de Llobregat",comarca:'Barcelonès', base:3400, costa:false, font:'estimado' },
  'sant-adria':         { nom:'Sant Adrià de Besòs',  comarca:'Barcelonès', base:3200, costa:true, font:'estimado' },

  /* ---- Vallès Oriental i Occidental ---- */
  'granollers':         { nom:'Granollers',           comarca:'Vallès Oriental', base:2450, costa:false, font:'estimado' },
  'la-garriga':         { nom:'La Garriga',           comarca:'Vallès Oriental', base:2600, costa:false, font:'estimado' },
  'cardedeu':           { nom:'Cardedeu',             comarca:'Vallès Oriental', base:2500, costa:false, font:'estimado' },
  'sabadell':           { nom:'Sabadell',             comarca:'Vallès Occidental', base:2400, costa:false, font:'estimado' },
  'terrassa':           { nom:'Terrassa',             comarca:'Vallès Occidental', base:2250, costa:false, font:'estimado' },
  'sant-cugat':         { nom:'Sant Cugat del Vallès',comarca:'Vallès Occidental', base:4300, costa:false, font:'estimado' },
  'cerdanyola':         { nom:'Cerdanyola del Vallès',comarca:'Vallès Occidental', base:2900, costa:false, font:'estimado' },

  /* ---- Baix Llobregat i Garraf ---- */
  'castelldefels':      { nom:'Castelldefels',        comarca:'Baix Llobregat', base:3900, costa:true, font:'estimado' },
  'gava':               { nom:'Gavà',                 comarca:'Baix Llobregat', base:3300, costa:true, font:'estimado' },
  'sant-boi':           { nom:'Sant Boi de Llobregat',comarca:'Baix Llobregat', base:2700, costa:false, font:'estimado' },
  'cornella':           { nom:'Cornellà de Llobregat',comarca:'Baix Llobregat', base:3000, costa:false, font:'estimado' },
  'sitges':             { nom:'Sitges',               comarca:'Garraf', base:4600, costa:true, font:'estimado' },
  'vilanova':           { nom:'Vilanova i la Geltrú', comarca:'Garraf', base:2700, costa:true, font:'estimado' },

  /* ---- Girona i Costa Brava ---- */
  'girona':             { nom:'Girona',               comarca:'Gironès', base:2600, costa:false, font:'estimado' },
  'blanes':             { nom:'Blanes',               comarca:'La Selva', base:2150, costa:true, font:'estimado' },
  'lloret':             { nom:'Lloret de Mar',        comarca:'La Selva', base:2300, costa:true, font:'estimado' },
  'tossa':              { nom:'Tossa de Mar',         comarca:'La Selva', base:2900, costa:true, font:'estimado' },
  'sant-feliu-guixols': { nom:'Sant Feliu de Guíxols',comarca:'Baix Empordà', base:2950, costa:true, font:'estimado' },
  'palamos':            { nom:'Palamós',              comarca:'Baix Empordà', base:2850, costa:true, font:'estimado' },
  'begur':              { nom:'Begur',                comarca:'Baix Empordà', base:4200, costa:true, font:'estimado' },
  'cadaques':           { nom:'Cadaqués',             comarca:'Alt Empordà', base:4500, costa:true, font:'estimado' },
  'roses':              { nom:'Roses',                comarca:'Alt Empordà', base:2500, costa:true, font:'estimado' },
  'figueres':           { nom:'Figueres',             comarca:'Alt Empordà', base:1750, costa:false, font:'estimado' },

  /* ---- Tarragona i Costa Daurada ---- */
  'tarragona':          { nom:'Tarragona',            comarca:'Tarragonès', base:2100, costa:true, font:'estimado' },
  'salou':              { nom:'Salou',                comarca:'Tarragonès', base:2400, costa:true, font:'estimado' },
  'cambrils':           { nom:'Cambrils',             comarca:'Baix Camp', base:2350, costa:true, font:'estimado' },
  'reus':               { nom:'Reus',                 comarca:'Baix Camp', base:1650, costa:false, font:'estimado' },
  'el-vendrell':        { nom:'El Vendrell',          comarca:'Baix Penedès', base:1800, costa:true, font:'estimado' },

  /* ---- Lleida i interior ---- */
  'lleida':             { nom:'Lleida',               comarca:'Segrià', base:1450, costa:false, font:'estimado' },
  'manresa':            { nom:'Manresa',              comarca:'Bages', base:1500, costa:false, font:'estimado' },
  'vic':                { nom:'Vic',                  comarca:'Osona', base:1900, costa:false, font:'estimado' },
  'igualada':           { nom:'Igualada',             comarca:'Anoia', base:1600, costa:false, font:'estimado' },
  'la-seu':             { nom:"La Seu d'Urgell",      comarca:'Alt Urgell', base:1550, costa:false, font:'estimado' },
  'puigcerda':          { nom:'Puigcerdà',            comarca:'Cerdanya', base:3200, costa:false, font:'estimado' },

  /* ---- Comodin: cualquier otra poblacion ---- */
  'altra':              { nom:'Otra población de Catalunya', comarca:'Otras', base:2400, costa:true, generica:true, font:'estimado' }
};

/* Zonas genéricas, para los municipios que no tienen las suyas propias.
   Las de mar solo se ofrecen donde hay costa. */
/* La primera linea va a 1,22 y no mas arriba a proposito. Toda la pagina
   defiende que un precio alto se paga en meses, asi que la calculadora no
   puede ser la que infle expectativas: ante la duda, tira a la baja. */
/* DE AQUI SALE EL "CERCA DE UN 40%" DE LA PORTADA.
   En un pueblo de costa, del mejor tramo al peor: 1.22 / 0.86 = 1.42, o
   sea un 42% mas caro arriba que abajo. En Arenys, con base 2871 y el
   ajuste de oferta, son unos 3293 EUR/m2 en primera linea contra 2321 en
   las afueras: en 90 metros, unos 87.000 EUR de diferencia.
   Si estos coeficientes se revisan, hay que revisar tambien esa cifra. */
window.ZONES_GENERIQUES = {
  costa: [ ['primera-linia','Primera línea de mar',1.22], ['centre','Centro',1.02],
           ['eixample','Ensanche y estación',1.00], ['alta','Zona alta',0.93],
           ['afores','Afueras y urbanizaciones',0.86] ],
  interior:[ ['centre','Centro',1.06], ['eixample','Ensanche y estación',1.00],
             ['alta','Zona alta',0.94], ['afores','Afueras y urbanizaciones',0.87] ]
};

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
      zones:[ ['passeig','Primera línea y paseo',1.25], ['centre','Centro y la Riera',1.05],
              ['eixample','Ensanche y estación',1.00], ['alta','Zona alta',0.92],
              ['afores','Afueras y Sant Elm',0.85] ] },
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

  /* ---- Costa Brava (26/11/2026) ----

     De Blanes a Portbou, todos los municipios con mar y los de al lado
     que se buscan con ellos. Antes habia diez, todos estimados por mi.

     DE DONDE SALEN. Informes mensuales de precios de idealista (precio
     de OFERTA, como el resto de la tabla: el AJUSTE_OFERTA de arriba lo
     baja a precio de cierre). Casi todos son de septiembre de 2026, de la
     tabla de la provincia de Girona; los que no, llevan su mes en
     'fecha' y la calculadora lo ensena.

     LAS ZONAS salen del MISMO informe y del MISMO mes que el precio del
     municipio: precio de la zona / precio del municipio. Ejemplo, Roses
     en agosto: Salatar 4.475 / 3.481 = 1,29. Donde idealista no da zonas,
     van las genericas de costa.

     LO QUE HAY QUE MIRAR CON LUPA, de mas a menos:
       - Palafrugell: el precio (2.539) y sus zonas salen de la pagina de
         valoracion de idealista de junio, no del informe mensual. Calella
         y Llafranc doblan al pueblo: son otro mercado, y por eso tienen
         su zona.
       - Castello d'Empuries y Empuriabrava: idealista las cuenta por
         separado. El pueblo (la vila) esta a 2.004 y Empuriabrava a
         3.590; otras fuentes dan para todo el municipio 3.381-3.737
         (Engel & Volkers, RealAdvisor). Por eso van como dos poblaciones.
       - Tossa de Mar: pagina de valoracion de idealista, sin mes.
       - Portbou y Colera: no hay informe; media de los anuncios de la
         zona (1.918), que es oferta pura. Marcados como estimados.
       - Girona: no he encontrado dato de 2026. Sigue estimado.
       - Los factores de zona por encima de 1,5 (Calella, Llafranc, Cala
         Sant Francesc) son reales, pero de muy pocos anuncios y casi
         todos de casas grandes. Se dejan, porque bajarlos seria
         inventar; pero ahi, mas que en ningun sitio, hay que ir a verla. */
  'blanes':             { nom:'Blanes',               comarca:'Costa Brava · La Selva', base:2512, costa:true, font:'publicado', fecha:'septiembre de 2026',
      zones:[ ['sant-francesc','Cala Sant Francesc y Santa Cristina',1.68], ['pins','Els Pins y paseo marítimo',1.25],
              ['centre','Centro',0.98], ['pavos','Els Pavos',0.90], ['plantera','La Plantera',0.78] ] },
  'lloret':             { nom:'Lloret de Mar',        comarca:'Costa Brava · La Selva', base:2597, costa:true, font:'publicado', fecha:'junio de 2026',
      zones:[ ['canyelles','Canyelles y Montgoda',1.29], ['primera-linia','Primera línea de mar',1.22],
              ['centre','Centro',1.00], ['rieral','El Molí y El Rieral',0.85], ['afores','Afueras y urbanizaciones',0.86] ] },
  'tossa':              { nom:'Tossa de Mar',         comarca:'Costa Brava · La Selva', base:3109, costa:true, font:'publicado',
      zones:[ ['llorell','Santa Maria de Llorell',1.12], ['primera-linia','Primera línea de mar',1.22],
              ['centre','Tossa, pueblo',0.97], ['afores','Afueras y urbanizaciones',0.86] ] },
  'sant-feliu-guixols': { nom:'Sant Feliu de Guíxols',comarca:'Costa Brava · Baix Empordà', base:3315, costa:true, font:'publicado', fecha:'septiembre de 2026' },
  'platja-daro':        { nom:"Castell-Platja d'Aro", comarca:'Costa Brava · Baix Empordà', base:4026, costa:true, font:'publicado', fecha:'septiembre de 2026',
      zones:[ ['platja-llarga','Platja Llarga y primera línea',1.19], ['platja-daro','Platja d\'Aro, centro',1.00],
              ['pinos','Los Pinos y Politur',0.87], ['castell','Castell d\'Aro y urbanizaciones',0.86] ] },
  'santa-cristina-aro': { nom:"Santa Cristina d'Aro", comarca:'Costa Brava · Baix Empordà', base:2581, costa:false, font:'publicado', fecha:'septiembre de 2026' },
  'calonge':            { nom:'Calonge i Sant Antoni',comarca:'Costa Brava · Baix Empordà', base:3367, costa:true, font:'publicado', fecha:'septiembre de 2026' },
  'palamos':            { nom:'Palamós',              comarca:'Costa Brava · Baix Empordà', base:3018, costa:true, font:'publicado', fecha:'enero de 2026',
      zones:[ ['platja-gran','Platja Gran',1.14], ['centre','Centro',1.02],
              ['eixample','Ensanche',1.00], ['afores','Afueras y urbanizaciones',0.86] ] },
  'palafrugell':        { nom:'Palafrugell',          comarca:'Costa Brava · Baix Empordà', base:2539, costa:true, font:'publicado', fecha:'junio de 2026',
      zones:[ ['llafranc','Llafranc',2.21], ['calella','Calella de Palafrugell',2.00],
              ['tamariu','Tamariu',1.56], ['vila','Palafrugell, pueblo',0.75] ] },
  'begur':              { nom:'Begur',                comarca:'Costa Brava · Baix Empordà', base:4239, costa:true, font:'publicado', fecha:'septiembre de 2026' },
  'pals':               { nom:'Pals',                 comarca:'Costa Brava · Baix Empordà', base:3458, costa:true, font:'publicado', fecha:'septiembre de 2026',
      zones:[ ['platja-pals','Platja de Pals',1.13], ['centre','Pals, pueblo',1.00], ['afores','Afueras y urbanizaciones',0.86] ] },
  'torroella':          { nom:'Torroella de Montgrí', comarca:'Costa Brava · Baix Empordà', base:2620, costa:true, font:'publicado', fecha:'septiembre de 2026',
      zones:[ ['estartit','L\'Estartit',1.13], ['centre','Torroella, pueblo',1.00], ['afores','Afueras y urbanizaciones',0.86] ] },
  'lescala':            { nom:"L'Escala",             comarca:'Costa Brava · Alt Empordà', base:3219, costa:true, font:'publicado', fecha:'septiembre de 2026' },
  'sant-pere-pescador': { nom:'Sant Pere Pescador',   comarca:'Costa Brava · Alt Empordà', base:2092, costa:true, font:'publicado', fecha:'julio de 2026' },  /* serie muy movida: 2.386 en abril */
  'castello-empuries':  { nom:"Castelló d'Empúries",  comarca:'Costa Brava · Alt Empordà', base:2004, costa:false, font:'publicado', fecha:'octubre de 2026' },  /* la vila; Empuriabrava va aparte */
  'empuriabrava':       { nom:'Empuriabrava',         comarca:'Costa Brava · Alt Empordà', base:3590, costa:true, font:'publicado', fecha:'septiembre de 2026',
      zones:[ ['canals','Canales y puertos (Port Banyuls, Port Alegre, Port Empordà)',1.01],
              ['interior','Puigmal y Mas Nou',0.68] ] },
  'roses':              { nom:'Roses',                comarca:'Costa Brava · Alt Empordà', base:3494, costa:true, font:'publicado', fecha:'septiembre de 2026',
      zones:[ ['salatar','Salatar',1.29], ['canyelles','Puerto deportivo, Puig Rom y Canyelles',1.21],
              ['santa-margarida','Santa Margarida',0.98], ['nord','Norte',0.83], ['centre','Centro',0.79] ] },
  'cadaques':           { nom:'Cadaqués',             comarca:'Costa Brava · Alt Empordà', base:6769, costa:true, font:'publicado', fecha:'septiembre de 2026' },  /* el mas caro de la costa */
  'port-de-la-selva':   { nom:'El Port de la Selva',  comarca:'Costa Brava · Alt Empordà', base:3640, costa:true, font:'publicado', fecha:'junio de 2026' },
  'llanca':             { nom:'Llançà',               comarca:'Costa Brava · Alt Empordà', base:2935, costa:true, font:'publicado', fecha:'septiembre de 2026' },
  'colera':             { nom:'Colera',               comarca:'Costa Brava · Alt Empordà', base:1800, costa:true, font:'estimado' },
  'portbou':            { nom:'Portbou',              comarca:'Costa Brava · Alt Empordà', base:1800, costa:true, font:'estimado' },
  'figueres':           { nom:'Figueres',             comarca:'Costa Brava · Alt Empordà', base:1655, costa:false, font:'publicado', fecha:'septiembre de 2026' },
  'girona':             { nom:'Girona',               comarca:'Gironès', base:2600, costa:false, font:'estimado' },

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

/* =========================================================
   Poblaciones y zonas de Catalunya para la calculadora
   =========================================================

   ATENCION, TOP HOUSE: esta tabla decide el precio que ve el visitante.
   Repasadla, porque sale con vuestro nombre.

   De donde sale cada numero:

   ANCLAS PUBLICADAS (julio-septiembre 2026)
     Arenys de Mar ....... 2.871 EUR/m2   idealista
     Vilassar de Mar ..... 3.975 EUR/m2   lider de segunda mano del Maresme
     Barcelona ciudad .... 5.400 EUR/m2   media, con distritos de 3.200 a 6.900
     Provincia Barcelona . 3.338 EUR/m2
     Comarca del Maresme . 2.700 a 3.000 EUR/m2

   EL RESTO SON INTERPOLACIONES sobre esas anclas y el gradiente conocido
   (cuanto mas cerca de Barcelona, mas caro; primera linea de costa, mas caro).
   NO son datos publicados. Donde vendais de verdad, cambiadlos por los
   vuestros, que valen mas que cualquier media.

   'costa' marca los municipios con mar, que son los unicos donde tiene
   sentido ofrecer la zona de primera linea.
   ========================================================= */

window.POBLACIONS = {

  /* ---- El Maresme, el mercado propio ---- */
  'arenys-de-mar':      { nom:'Arenys de Mar',        comarca:'El Maresme', base:2871, costa:true, dato:'publicado',
      zones:[ ['passeig','Primera línia i passeig',1.25], ['centre','Centre i la Riera',1.05],
              ['eixample','Eixample i estació',1.00], ['alta','Zona alta',0.92],
              ['afores','Afores i Sant Elm',0.85] ] },
  'arenys-de-munt':     { nom:'Arenys de Munt',       comarca:'El Maresme', base:2380, costa:false },
  'caldes-destrac':     { nom:"Caldes d'Estrac",      comarca:'El Maresme', base:3450, costa:true },
  'sant-vicenc-montalt':{ nom:'Sant Vicenç de Montalt',comarca:'El Maresme', base:3520, costa:true },
  'sant-andreu-llavaneres':{nom:'Sant Andreu de Llavaneres',comarca:'El Maresme',base:3380,costa:true },
  'mataro':             { nom:'Mataró',               comarca:'El Maresme', base:2650, costa:true },
  'cabrera-de-mar':     { nom:'Cabrera de Mar',       comarca:'El Maresme', base:3600, costa:true },
  'vilassar-de-mar':    { nom:'Vilassar de Mar',      comarca:'El Maresme', base:3975, costa:true, dato:'publicado' },
  'vilassar-de-dalt':   { nom:'Vilassar de Dalt',     comarca:'El Maresme', base:2950, costa:false },
  'premia-de-mar':      { nom:'Premià de Mar',        comarca:'El Maresme', base:3450, costa:true },
  'premia-de-dalt':     { nom:'Premià de Dalt',       comarca:'El Maresme', base:3100, costa:false },
  'el-masnou':          { nom:'El Masnou',            comarca:'El Maresme', base:3850, costa:true },
  'montgat':            { nom:'Montgat',              comarca:'El Maresme', base:3700, costa:true },
  'teia':               { nom:'Teià',                 comarca:'El Maresme', base:3300, costa:false },
  'alella':             { nom:'Alella',               comarca:'El Maresme', base:3400, costa:false },
  'argentona':          { nom:'Argentona',            comarca:'El Maresme', base:2700, costa:false },
  'cabrils':            { nom:'Cabrils',              comarca:'El Maresme', base:3250, costa:false },
  'canet-de-mar':       { nom:'Canet de Mar',         comarca:'El Maresme', base:2550, costa:true },
  'sant-pol-de-mar':    { nom:'Sant Pol de Mar',      comarca:'El Maresme', base:3050, costa:true },
  'calella':            { nom:'Calella',              comarca:'El Maresme', base:2200, costa:true },
  'pineda-de-mar':      { nom:'Pineda de Mar',        comarca:'El Maresme', base:2150, costa:true },
  'malgrat-de-mar':     { nom:'Malgrat de Mar',       comarca:'El Maresme', base:2100, costa:true },
  'santa-susanna':      { nom:'Santa Susanna',        comarca:'El Maresme', base:2250, costa:true },
  'tordera':            { nom:'Tordera',              comarca:'El Maresme', base:1950, costa:false },
  'palafolls':          { nom:'Palafolls',            comarca:'El Maresme', base:2050, costa:false },

  /* ---- Barcelonès ---- */
  'barcelona':          { nom:'Barcelona',            comarca:'Barcelonès', base:5400, costa:true, dato:'publicado',
      zones:[ ['sarria','Sarrià i Sant Gervasi',1.28], ['eixample','Eixample',1.13],
              ['ciutat-vella','Ciutat Vella i Barceloneta',1.02], ['gracia','Gràcia',1.06],
              ['sants','Sants i Les Corts',0.98], ['sant-marti','Sant Martí i Poblenou',0.95],
              ['horta','Horta i Nou Barris',0.72] ] },
  'badalona':           { nom:'Badalona',             comarca:'Barcelonès', base:3100, costa:true },
  'santa-coloma':       { nom:'Santa Coloma de Gramenet',comarca:'Barcelonès', base:2750, costa:false },
  'hospitalet':         { nom:"L'Hospitalet de Llobregat",comarca:'Barcelonès', base:3400, costa:false },
  'sant-adria':         { nom:'Sant Adrià de Besòs',  comarca:'Barcelonès', base:3200, costa:true },

  /* ---- Vallès Oriental i Occidental ---- */
  'granollers':         { nom:'Granollers',           comarca:'Vallès Oriental', base:2450, costa:false },
  'la-garriga':         { nom:'La Garriga',           comarca:'Vallès Oriental', base:2600, costa:false },
  'cardedeu':           { nom:'Cardedeu',             comarca:'Vallès Oriental', base:2500, costa:false },
  'sabadell':           { nom:'Sabadell',             comarca:'Vallès Occidental', base:2400, costa:false },
  'terrassa':           { nom:'Terrassa',             comarca:'Vallès Occidental', base:2250, costa:false },
  'sant-cugat':         { nom:'Sant Cugat del Vallès',comarca:'Vallès Occidental', base:4300, costa:false },
  'cerdanyola':         { nom:'Cerdanyola del Vallès',comarca:'Vallès Occidental', base:2900, costa:false },

  /* ---- Baix Llobregat i Garraf ---- */
  'castelldefels':      { nom:'Castelldefels',        comarca:'Baix Llobregat', base:3900, costa:true },
  'gava':               { nom:'Gavà',                 comarca:'Baix Llobregat', base:3300, costa:true },
  'sant-boi':           { nom:'Sant Boi de Llobregat',comarca:'Baix Llobregat', base:2700, costa:false },
  'cornella':           { nom:'Cornellà de Llobregat',comarca:'Baix Llobregat', base:3000, costa:false },
  'sitges':             { nom:'Sitges',               comarca:'Garraf', base:4600, costa:true },
  'vilanova':           { nom:'Vilanova i la Geltrú', comarca:'Garraf', base:2700, costa:true },

  /* ---- Girona i Costa Brava ---- */
  'girona':             { nom:'Girona',               comarca:'Gironès', base:2600, costa:false },
  'blanes':             { nom:'Blanes',               comarca:'La Selva', base:2150, costa:true },
  'lloret':             { nom:'Lloret de Mar',        comarca:'La Selva', base:2300, costa:true },
  'tossa':              { nom:'Tossa de Mar',         comarca:'La Selva', base:2900, costa:true },
  'sant-feliu-guixols': { nom:'Sant Feliu de Guíxols',comarca:'Baix Empordà', base:2950, costa:true },
  'palamos':            { nom:'Palamós',              comarca:'Baix Empordà', base:2850, costa:true },
  'begur':              { nom:'Begur',                comarca:'Baix Empordà', base:4200, costa:true },
  'cadaques':           { nom:'Cadaqués',             comarca:'Alt Empordà', base:4500, costa:true },
  'roses':              { nom:'Roses',                comarca:'Alt Empordà', base:2500, costa:true },
  'figueres':           { nom:'Figueres',             comarca:'Alt Empordà', base:1750, costa:false },

  /* ---- Tarragona i Costa Daurada ---- */
  'tarragona':          { nom:'Tarragona',            comarca:'Tarragonès', base:2100, costa:true },
  'salou':              { nom:'Salou',                comarca:'Tarragonès', base:2400, costa:true },
  'cambrils':           { nom:'Cambrils',             comarca:'Baix Camp', base:2350, costa:true },
  'reus':               { nom:'Reus',                 comarca:'Baix Camp', base:1650, costa:false },
  'el-vendrell':        { nom:'El Vendrell',          comarca:'Baix Penedès', base:1800, costa:true },

  /* ---- Lleida i interior ---- */
  'lleida':             { nom:'Lleida',               comarca:'Segrià', base:1450, costa:false },
  'manresa':            { nom:'Manresa',              comarca:'Bages', base:1500, costa:false },
  'vic':                { nom:'Vic',                  comarca:'Osona', base:1900, costa:false },
  'igualada':           { nom:'Igualada',             comarca:'Anoia', base:1600, costa:false },
  'la-seu':             { nom:"La Seu d'Urgell",      comarca:'Alt Urgell', base:1550, costa:false },
  'puigcerda':          { nom:'Puigcerdà',            comarca:'Cerdanya', base:3200, costa:false },

  /* ---- Comodin: cualquier otra poblacion ---- */
  'altra':              { nom:'Otra población de Catalunya', comarca:'Otras', base:2400, costa:true, generica:true }
};

/* Zonas genéricas, para los municipios que no tienen las suyas propias.
   Las de mar solo se ofrecen donde hay costa. */
/* La primera linea va a 1,22 y no mas arriba a proposito. Toda la pagina
   defiende que un precio alto se paga en meses, asi que la calculadora no
   puede ser la que infle expectativas: ante la duda, tira a la baja. */
window.ZONES_GENERIQUES = {
  costa: [ ['primera-linia','Primera línea de mar',1.22], ['centre','Centro',1.02],
           ['eixample','Ensanche y estación',1.00], ['alta','Zona alta',0.93],
           ['afores','Afueras y urbanizaciones',0.86] ],
  interior:[ ['centre','Centro',1.06], ['eixample','Ensanche y estación',1.00],
             ['alta','Zona alta',0.94], ['afores','Afueras y urbanizaciones',0.87] ]
};

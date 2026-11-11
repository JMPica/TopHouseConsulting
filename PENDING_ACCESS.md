# Accessos i confirmacions que falten

Només el que ha impedit acabar alguna cosa. Per ordre d'impacte.

## Accessos

1. **Google Search Console** (propietat de domini `tophouserealestate.es`).
   Sense això no hi ha consultes, impressions ni posicions reals, ni INP de
   camp. Com fer-ho: a https://search.google.com/search-console, «Afegeix una
   propietat» → «Domini» → copieu el registre TXT i poseu-lo a la zona DNS de
   `tophouserealestate.es` a Hostinger. Després envieu els dos sitemaps:
   `sitemap.xml` i `sitemap-immobles.php` (aquest ja inclou les pàgines per
   població, com `/comprar/arenys-de-mar`). Si em doneu accés de lectura,
   refaig `KEYWORD_STRATEGY.md` amb dades reals.
2. **Bing Webmaster Tools** (es pot importar des de Search Console). Cal per:
   - confirmar si Hostinger bloqueja el **bingbot** de veritat (en la meva prova, el bingbot rep un 403);
   - fer servir la inspecció d'URL.

   Si el bloqueig és real, s'ha de demanar a Hostinger que el desactivi per a aquest domini.
3. **Google Business Profile** de l'empresa. Vegeu `LOCAL_SEO_ACTIONS.md`.
4. **`'clave'` a `mobilia-config.php`** (al servidor, fora de `public_html`):
   una paraula de 16 caràcters o més. Sense ella, el quadre de comandament
   (`/api/evento.php?informe=...`) respon «No disponible». No me la passeu
   per xat: poseu-la directament al fitxer.

## Confirmacions de contingut (no publicat fins que es confirmi)

5. **Gestió del dia a dia dels lloguers**: s'encarrega Top House de cobrar la
   renda, de les incidències, de les renovacions i del dipòsit de la fiança?
   Quant costa? Ara la pàgina només diu el que ja era a la web (busquem
   l'inquilí, en comprovem la solvència, fem el contracte i els papers, i
   «lloguer garantit»), i per a la resta diu que s'explica per escrit. Amb la
   llista confirmada, la poso a la pàgina.
6. **«Lloguer garantit»**: què cobreix exactament (assegurança d'impagament?
   de quina companyia? quants mesos?). Ara la pàgina diu «li ho expliquem per
   escrit».
7. **«Més de deu anys en aquest mercat»**: la societat es va constituir el
   2021. Si es refereix a l'experiència de l'equip, està bé, però convé que
   ho confirmeu.
8. **Horari d'oficina**: per a la pàgina de contacte, les dades estructurades
   i Google Business Profile.
9. **Perfils oficials** (Instagram, Facebook, LinkedIn, Idealista): els
   enllaços, per afegir-los com a `sameAs`.

## Decisions de la propietat

10. **GPTBot** (el robot d'entrenament d'OpenAI) rep un 429 de Hostinger.
    No afecta la cerca de ChatGPT, que fa servir OAI-SearchBot i que sí que
    entra. Si voleu que els models d'IA aprenguin de la web, s'ha de permetre
    a Hostinger. Si no ho voleu, no cal fer res.
11. **GA4**: no l'he instal·lat. Vegeu `ANALYTICS_MEASUREMENT.md`, secció 3.

# Implementació SEO + GEO + CRO: què s'ha fet realment

Data: 9/11/2026. **Publicat** a https://www.tophouserealestate.es i verificat a
la web real (vegeu `SEO_GEO_QA.md`). Branca:
`claude/top-house-consulting-web-ndzku9`. Commits: `4809ed2` i el següent
(`medida.js` amb `async`). Per tornar enrere: `git revert` i `./publicar.sh`.

Criteri de tot plegat: només s'ha publicat informació que ja era a la web o a
l'avís legal. No hi ha tarifes, garanties, ressenyes, premis ni perfils
inventats. La identitat visual no s'ha tocat: les pàgines noves fan servir
les mateixes peces de disseny que la portada.

---

## 1. Pàgines comercials noves (tres idiomes cadascuna)

| Pàgina | ca / es / en | Prioritat | Contingut (tot verificat a la web existent) |
|---|---|---|---|
| Vendre | `/vendre.html` · `/es/vender.html` · `/en/sell.html` | 1 | Venda des de la valoració fins a la notaria. Compradors abans de l'anunci. Els 4 passos. Què es cobra i quan (percentatge sobre el preu final; si no es ven, no es paga). Exclusiva o no. Certificats, finançament i reformes. 5 FAQ. Formulari. |
| Gestió de lloguers | `/gestio-lloguers.html` · `/es/gestion-alquileres.html` · `/en/rental-management.html` | 2 | Per a propietaris: busquem l'inquilí, en comprovem la solvència abans de signar, fem el contracte i els papers, certificats, «lloguer garantit» (amb el detall per escrit). **La gestió del dia a dia no es descriu fins que es confirmi** (vegeu `PENDING_ACCESS.md`). Enllaç per a qui busca lloguer. Formulari de propietari. |
| Valoració | `/valoracio.html` · `/es/valoracion.html` · `/en/valuation.html` | 3 | Com es fa (visita, comparació amb vendes del tram, sense cost ni exclusiva obligatòria). Diferència amb la calculadora en línia i enllaç a la calculadora. 2 FAQ. Formulari. |
| Contacte | `/contacte.html` · `/es/contacto.html` · `/en/contact.html` | — | Oficina amb «Com arribar-hi», telèfon, WhatsApp, correu, raó social, NIF, AICAT. Formulari general. |

Fitxers: `tophouse/vender.html`, `valoracion.html`, `gestion-alquileres.html`,
`contacto.html`, `assets/formulario.js` (el formulari d'aquestes pàgines,
amb la mateixa validació i el mateix enviament que la portada), estils al final
de `assets/site.css`, unes 70 frases noves a `idiomas/ca.json` i `en.json`.

**No s'han creat pàgines per municipi**: no hi ha contingut propi de cada poble
i serien pàgines gairebé idèntiques.

## 2. Arquitectura i enllaçat intern

- **Menú** (11 plantilles): «Vendre», «Valoració» i «Contacte» porten a les pàgines noves (abans eren seccions de la portada). Fora de la portada, el botó «Demanar la meva valoració» va a `valoracio.html#form`.
- **Peu**: nova columna «Serveis» (Vendre, Valoració, Gestió de lloguers, Comprar, Marine) i el correu, a totes les pàgines.
- **Portada**:
  - «Vull saber què val la meva casa» afegeix «Com venem»;
  - «Vull llogar» afegeix «Sóc propietari»;
  - els serveis enllacen a la seva pàgina.
- **Llogar** (llistat): el botó principal per a propietaris porta a Gestió de lloguers.
- **Comprar**: «Escriure'ns» va a Contacte i «Demanar valoració» a Valoració.

## 3. SEO tècnic

| Canvi | Fitxer | Per què |
|---|---|---|
| Títol de la portada: «Immobiliària a Arenys de Mar i el Maresme · Top House» | `index.html`, `idiomas/*` | La consulta principal del negoci no hi era |
| Descripcions de 160 caràcters o menys a totes les pàgines (n'hi havia 6 de 161 a 188) | diverses | Google les tallava |
| `lastmod` del sitemap amb la data real de l'últim commit de cada pàgina i idioma (abans, una data fixa) i `x-default` | `construir.py` | Un `lastmod` fals fa que Google deixi de fer-hi cas |
| Memòria cau d'1 any per als `.js` (Hostinger els serveix com `application/x-javascript`) | `.htaccess` | Abans, 7 dies |
| Logo de 96 px (10 KB) al menú i al peu, en lloc del de 256 px (55 KB) | totes | Es mostra a 38 px |
| Contrast suficient al peu i a les notes del formulari | `site.css` | WCAG AA (4,5:1) |
| Miniatures de la galeria de la fitxa amb nom («Foto 1»...) i `aria-pressed`. La foto gran té `alt` amb el títol de l'immoble | `immoble.php`, `fitxa.js` | Abans eren botons sense nom i una foto sense descripció |
| Hreflang: es mantenen `ca`/`es`/`en` + `x-default` | — | Són vàlids i més amplis que `ca-ES`/`es-ES`; canviar-los no aporta res |
| Robots.txt: sense canvis (`Allow: /`) | — | OAI-SearchBot, Googlebot, ChatGPT-User, PerplexityBot i ClaudeBot entren. Els bloquejos de bingbot (403) i GPTBot (429) són del servidor de Hostinger: vegeu `PENDING_ACCESS.md` |

## 4. Dades estructurades (GEO i resultats enriquits)

Les genera `construir.py` (`datos_estructurados()`) a partir del mateix HTML
ja traduït, de manera que el que diuen les dades i el que es veu a la pàgina
no poden divergir.

| Node | On | Contingut |
|---|---|---|
| `RealEstateAgent` (`@id` `/#agencia`) | totes les pàgines | Nom, raó social, NIF, logo, adreça, telèfon, correu, `areaServed` (Maresme), idiomes, `identifier` AICAT 11152, `contactPoint` |
| `WebSite` (`/#web`) | totes | Editor = l'agència |
| `WebPage` / `ContactPage` | totes | Títol, descripció, idioma, `about` l'agència |
| `BreadcrumbList` | totes menys la portada, i les fitxes (Inici › Comprar/Llogar/Marine › immoble) | |
| `Service` | Vendre, Valoració, Gestió de lloguers | Proveïdor = l'agència, zona Maresme, canal (URL + telèfon) |
| `FAQPage` | Vendre (5), Valoració (2) | Extretes del text visible de cada idioma. **Cada pregunta, marcada en una sola pàgina** |
| `RealEstateListing` (fitxes) | ja existia | Ara `offeredBy` porta l'`@id` de l'agència: Google pot relacionar cada immoble amb l'empresa |

**No hi ha** `sameAs` (no hi ha perfils verificats), `aggregateRating` (no hi
ha ressenyes verificables), `openingHours` (l'horari no consta) ni coordenades
(no verificades).

## 5. Conversió (CRO)

- **Formulari de la portada**: l'opció «Llogar» sortia **dues vegades** i el propietari que volia llogar no es podia identificar. Ara hi ha «Llogar el meu habitatge» (propietari) i «Busco habitatge de lloguer» (llogater).
- **Correu a l'oficina**: l'assumpte diu el que s'ha demanat (abans, sempre «Valoración»).
- **A Mobilia**: el lloguer entra com a *Alquiler* (abans, tot com a *Venta*).
- Cada pàgina de servei té el seu formulari, amb l'opció del servei ja escollida, i el telèfon a la vista.

## 6. Mesura sense galetes

`assets/medida.js`, `api/evento.php` i el quadre `api/evento.php?informe=CLAU`.
Vegeu `ANALYTICS_MEASUREMENT.md`. Política de privacitat i de galetes
actualitzada en els tres idiomes perquè digui exactament què es compta.

## 7. Proves

`pruebas/revisar_web.py` s'executa cada vegada que es publica i atura la
publicació si troba enllaços trencats, JSON-LD dolent, hreflang no recíproc o
URL del sitemap inexistents. Vegeu `SEO_GEO_QA.md`.

## 8. Recomanació no aplicada, amb dades

**El motor del vídeo de la portada** (`site.js`, la seqüència de 97 fotogrames
al mòbil) és la causa principal del TBT de la portada i de Marine (tasques de
100 a 250 ms entre els segons 2 i 3). Es pot millorar, per exemple
descodificant els fotogrames de mica en mica en lloc de demanar-los tots alhora,
però és l'element signatura de la web i tocar-lo té risc de trencar-lo. Cal
decidir-ho amb dades de camp (INP a Search Console): si l'INP real és bo, no
cal tocar-lo.

---

## Segona passada (11/11/2026) — publicat i verificat a la web real

Commit `SEO/GEO: listas hechas en el servidor...`. Què s'ha trobat i què s'ha fet:

| Problema trobat (verificat) | Canvi | Fitxers |
|---|---|---|
| **Un robot sense JavaScript veia 0 immobles a Comprar i a Llogar.** La llista només la pintava el navegador. OAI-SearchBot, PerplexityBot i ClaudeBot no executen JavaScript. | Les llistes surten ja fetes del servidor: targetes, recompte, cercador i `ItemList`. El navegador reconeix la llista (`data-ssr`) i no la torna a pintar: CLS 0. Si alguna cosa falla, surt la pàgina de sempre i la pinta el navegador. | `tophouse/llista.php` (nou), `.htaccess`, `assets/cartera.js` |
| **Cap pàgina per a «pisos en venda a Arenys de Mar»**, amb 20 immobles en venda allà (i 6 a Canet de Mar). | Pàgines per població: `/comprar/arenys-de-mar`, `/es/comprar/arenys-de-mar`, `/en/buy/arenys-de-mar` (i Canet). Títol, H1, descripció, canònica, hreflang, molles de pa i `ItemList` propis. **Només per a pobles amb 3 immobles o més**: la resta redirigeix (302) a la llista general filtrada, i no hi ha pàgines buides. El títol només anomena els tipus que hi ha de veritat. Enllaçades des de les llistes, des de cada fitxa («Més a Arenys de Mar») i des del sitemap. | `llista.php`, `immoble.php`, `sitemap-immobles.php`, `site.css` |
| `/index.html` i `/` responien igual, i els enllaços interns anaven a `/index.html`. | 301 de `/index.html` (i `/es/`, `/en/`) a `/`. Tots els enllaços interns van a `/`. | `.htaccess`, `construir.py` |
| H1 de les llistes poc descriptius («El que tenim en venda»). | «Pisos i cases en venda al Maresme» / «…de lloguer al Maresme», també als títols. | `comprar.html`, `alquilar.html`, `idiomas/*` |
| El sitemap de fitxes i les fitxes feien servir `ca-ES/es-ES/en-GB`, i la resta de la web `ca/es/en`. Faltava `x-default` al sitemap de fitxes. | Els mateixos codis a tot arreu, i `x-default`. | `immoble.php`, `sitemap-immobles.php` |
| Cap text que digui, en una frase citable, qui és Top House i què fa. | Bloc «Qui som» a Contacte (només fets ja publicats) amb enllaços als serveis. | `contacto.html` |
| — | `/llms.txt`: resum de l'empresa i de les pàgines per a assistents d'IA, generat a partir dels títols i les descripcions reals. **És una convenció nova: cap cercador no ha confirmat que la faci servir.** Costa poc i no fa mal. | `construir.py` |
| La primera foto de la fitxa esperava la connexió amb el servidor de fotos. | `preconnect` al servidor de Mobilia i `fetchpriority="high"` a la foto principal. | `immoble.php`, `comprar.html`, `alquilar.html` |
| L'error del 9/11 (un PHP trencat publicat). | `publicar.sh` comprova la sintaxi de tots els PHP abans de publicar. | `publicar.sh` |

No s'ha fet: minificar CSS i JS. L'estalvi real amb gzip és d'uns 2 KB i no compensa el risc.

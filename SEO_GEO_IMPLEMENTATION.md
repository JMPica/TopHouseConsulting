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

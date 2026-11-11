# QA: proves executades i resultats

Data: 9/11/2026. Totes les proves s'han fet dues vegades: en local, sobre
Apache 2.4 + PHP 8.3 amb el mateix `.htaccess` que producció (i un correu
fals perquè cap prova no arribés a la bústia real), i després de publicar,
sobre la web real.

## 1. Proves automàtiques afegides al repositori

`pruebas/revisar_web.py` (sense dependències). `publicar.sh` l'executa
després de generar la web i **no publica si troba un error**. Comprova:
- enllaços interns trencats o relatius;
- JSON-LD il·legible;
- pàgines indexables sense `RealEstateAgent`;
- preguntes marcades com a FAQ que no surten a la pàgina;
- hreflang que no és recíproc o que apunta a una pàgina inexistent;
- URL del sitemap que no existeixen;
- pàgines sense title, description o canonical.

També avisa de títols de més de 65 caràcters, descripcions de més de 160 i duplicats.

**Resultat final: 30 pàgines revisades, 0 errors, 0 avisos.**

## 2. Funcionals (Playwright, Apache local)

| Prova | Resultat |
|---|---|
| Formulari de Vendre (ca) → pàgina de gràcies | ✅ |
| Formulari de Gestió de lloguers (es) → gràcies | ✅ |
| Formulari de Valoració (en) → thanks | ✅ |
| Formulari de Contacte (ca), opció «busco lloguer» | ✅ |
| Formulari de la portada, opció nova de propietari | ✅ |
| Formulari d'una fitxa (1401) | ✅ |
| Formulari de Marine (es), pàgina en blau | ✅ |
| Error forçat del servidor (502): es queda al formulari amb telèfon i WhatsApp | ✅ i es compta com a `fallo` |
| Límit antispam (429) després de massa enviaments des de la mateixa IP | ✅ (comportament esperat; l'avís mostra «ref. 429») |
| Assumpte del correu a l'oficina | ✅ «Vendre · …», «Alquilar mi vivienda · …», «Busco habitatge de lloguer · …» |
| Operació a Mobilia | ✅ Venda / **Alquiler** per a propietari i llogater (abans, sempre Venta) |
| Comptador: entrades, ChatGPT (`utm_source`), telèfon, WhatsApp, sol·licituds per formulari | ✅ el quadre mostra les xifres de la prova |
| Quadre sense clau o amb una clau incorrecta | ✅ 404 «No disponible» |
| Menú mòbil amb els enllaços nous, selector d'idioma | ✅ |
| Comprar: 37 immobles, enllaços absoluts | ✅ |
| Filtre per poble des d'una adreça antiga (`?poblacio=mataro/pisos`) | ✅ Mataró |
| Moviment reduït: portada estàtica visible | ✅ |
| Errors de JavaScript a totes les proves | ✅ cap |

## 3. Redireccions (Apache local i després en directe)

| Adreça | Resultat |
|---|---|
| `tophouseconsulting.com/es/sant_andreu_de_llavaneres/pisos/ref-1401` (QR) | ✅ 301 → `/es/inmueble/1401` en un sol salt |
| `/es/ref-1423`, `/ref-1423` (Mobilia) | ✅ 301 → fitxa |
| `/es/arenys_de_mar/casas/` (llistat antic) | ✅ 301 → `/es/comprar.html?poblacio=…` |
| `/es/no-existe` | ✅ 404 real amb la portada en castellà |
| Les 38 fitxes publicades amb format de QR, des del domini antic | ✅ 38/38 (prova del matí, mateixa regla) |

## 4. En directe, després de publicar

| Prova | Resultat |
|---|---|
| 15 URL noves (5 pàgines × 3 idiomes) + portades, sitemap, robots | ✅ 200 |
| Dades estructurades a la web real | ✅ Portada: RealEstateAgent + WebSite + WebPage. Vendre i Valoració: + BreadcrumbList + Service + FAQPage. Gestió: + Service. Contacte: ContactPage. Fitxa: RealEstateListing + BreadcrumbList. |
| `/api/evento.php` sense Origin / amb esdeveniment inventat / vàlid | ✅ 403 / 400 / 204 |
| Recompte des del navegador a 7 pàgines | ✅ 7 × 204 |
| Memòria cau dels `.js` | ✅ 1 any (abans, 7 dies) |
| Errors de JavaScript a la web real | ✅ cap |

**No s'ha enviat cap formulari a la web real**, perquè cap sol·licitud de prova
arribés a la bústia ni a Mobilia. El circuit complet de sol·licituds ja es va
provar en directe el 4/11/2026 i ara s'ha provat sencer en local.

## 5. Lighthouse 12, mòbil, web real

| Pàgina | Abans (Perf/Acc/BP/SEO · LCP · TBT) | Després |
|---|---|---|
| Portada ca | 84 / 96 / 100 / 100 · 2,5 s · 460 ms | 92–97 / **100** / 100 / 100 · 2,3–2,4 s · 120–240 ms |
| Portada es | 86 / 96 / 100 / 100 · 2,3 s · 440 ms | 87–90 / **100** / 100 / 100 · 2,3–2,9 s · 220–360 ms |
| Comprar es | 99 / 96 / 96 / 100 · 1,9 s | 99 / **100** / 96 / 100 · 2,0 s |
| Fitxa 1401 es | 97 / 90 / 96 / 100 · 2,1 s | 97 / **100** / 96 / 100 · 2,4 s |
| Marine es | 96 / 96 / 100 / 100 · 2,5 s · 70 ms | 86–91 / **100** / 100 / 100 · 2,4–3,4 s · 190–340 ms |
| Vendre es *(nova)* | — | 98 / 100 / 100 / 100 · 2,2 s |
| Gestió de lloguers ca *(nova)* | — | 99 / 100 / 100 / 100 · 2,1 s |
| Valoració es *(nova)* | — | 99 / 100 / 100 / 100 · 1,9 s |
| Contacte ca *(nova)* | — | 100 / 100 / 100 / 100 · 1,8 s |
| CLS | 0 a totes | 0 a totes |

Com cal llegir-ho:
- **Accessibilitat: 100 a totes les pàgines** (abans 90–96). S'ha corregit el contrast del peu i de les notes del formulari, i les miniatures de la galeria tenen nom.
- Les **xifres de rendiment varien entre execucions** amb el mateix codi. A Marine, les tasques llargues són de `site.js` amb la **mateixa empremta (`f9f675d2`) que abans**: codi idèntic que una vegada triga 857 ms i una altra 1.152 ms. És soroll de la màquina de proves, no un canvi. `medida.js` no apareix entre les tasques llargues i es carrega amb `async`.
- El «96» de Best Practices a Comprar i a la fitxa són les fotos de Mobilia bloquejades **per la xarxa del meu entorn**. A la web real es carreguen.
- **INP real**: no es pot mesurar al laboratori. Cal Search Console (Core Web Vitals) amb trànsit real.

## 6. Incidències trobades durant la QA

| Incidència | Estat |
|---|---|
| El quadre de comandament usava estils dins de l'HTML, i la CSP de la web els bloqueja | Corregit: `assets/informe.css` |
| `medida.js` amb `defer` entrava a la cua d'scripts que revelen la pàgina | Corregit: `async` |
| Un fitxer de proves local (`medida-2026-11.json`) va aparèixer a l'arrel del repo | Esborrat i afegit a `.gitignore` |
| La graella de serveis amb 5 elements deixava una cel·la buida | Corregit: 4 elements per pàgina |
| A la fitxa, la miga de pa duplicava la carpeta de l'idioma | Corregit abans de publicar |

---

## Segona passada (11/11/2026)

Provat en local (Apache 2.4 + PHP 8.3 amb la cartera real) i després a la web real.

| Prova | Resultat |
|---|---|
| Robot sense JavaScript a `/es/comprar/arenys-de-mar` (OAI-SearchBot, PerplexityBot, Googlebot, ClaudeBot), web real | ✅ 200, 20 immobles i 20 enllaços a fitxes a l'HTML (abans: 0) |
| Comprar / Llogar / pàgines de poble, mòbil i escriptori, web real | ✅ CLS 0; la llista **no** es torna a pintar; recompte i filtre correctes |
| Treure el filtre a la pàgina d'un poble | ✅ torna a mostrar els 37 |
| Adreça antiga amb `?poblacio=` | ✅ es repinta filtrada (CLS 0–0,002) |
| Poble amb menys de 3 immobles o inexistent | ✅ 302 a la llista general filtrada |
| `/llista.php` demanat directament | ✅ 301 a `/comprar.html` |
| `/index.html`, `/es/index.html`, `/en/index.html` | ✅ 301 a `/`, `/es/`, `/en/` |
| Selector d'idioma a la pàgina d'un poble | ✅ porta al mateix poble en l'altre idioma |
| Fitxa: enllaç «Més a Arenys de Mar» en ca/es/en i molles de pa amb el poble | ✅ (les proves van trobar un enllaç duplicat `/es//es/...` **abans de publicar**; corregit) |
| Sitemap de fitxes | ✅ XML vàlid, 120 URL (114 fitxes + 6 de poble), hreflang `ca/es/en/x-default` |
| **Casos dolents de `llista.php`**: sense còpia de la cartera, còpia corrupta, sense cap lloguer, sense el fitxer de textos | ✅ sempre surt la pàgina sencera (la de sempre); mai un 500 ni una pàgina en blanc |
| Títol maliciós (`<script>`), foto `javascript:`, preu no numèric, entrades escombraria | ✅ escapat o ignorat; JSON-LD llegible |
| Els 38 QR dels cartells (domini antic) | ✅ 38/38 |
| Regressió: formularis de fitxa i Marine, menú mòbil, moviment reduït | ✅, cap error de JavaScript |
| `revisar_web.py` | ✅ 30 pàgines, 0 errors, 0 avisos |
| Lighthouse mòbil, web real | Comprar 99/100/96/100 (LCP 2,1 s, CLS 0); pàgina d'Arenys 97/100/96/100; fitxa 96/100/96/100; Contacte 99/100/100/100; Llogar 98/100/96/100. El 96 de Best Practices són les fotos de Mobilia, bloquejades al meu entorn de proves. |

**bingbot**: amb el seu *user-agent* des de la meva IP, de vegades 200 i de vegades 403 a **totes** les adreces alhora (també `robots.txt`). És el comportament d'un tallafoc que frena qui diu ser Bing sense ser-ho. Segueix pendent de confirmar a Bing Webmaster Tools.

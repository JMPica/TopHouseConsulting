# Auditoria SEO + GEO + SEO local · línia base

Data: 9/11/2026. Feta abans de canviar res, sobre el repositori
(`claude/top-house-consulting-web-ndzku9`) i sobre la web publicada
(https://www.tophouserealestate.es).

Llegenda: **[confirmat]** = verificat amb una prova reproduïble;
**[hipòtesi]** = indici que cal confirmar amb una eina a la qual no tinc accés.

---

## 1. Tecnologia i desplegament [confirmat]

| Element | Estat |
|---|---|
| Framework | Cap. HTML estàtic generat per `construir.py` (Python) a partir de `tophouse/` (castellà) + `idiomas/ca.json` i `en.json`. |
| Idiomes | Català a l'arrel (`/`), castellà a `/es/`, anglès a `/en/`. |
| Dinàmic | PHP 8.3: `api/cartera.php` (pont amb el XML de Mobilia, còpia de 15 min), `api/solicitud.php` (formularis → correu SMTP + CRM Mobilia), `immoble.php` (fitxa per referència), `sitemap-immobles.php`, `404.php`. |
| Hosting | Hostinger (LiteSpeed + CDN «hcdn»). |
| Desplegament | `./publicar.sh` regenera `web/` i força la branca `deploy-web`; Hostinger la publica sola. Reversible: cada publicació és un commit. |
| Dependències | Cap de tercers al navegador. Tipografies servides des del domini. CSP estricta (`script-src 'self'`). |
| Proves | No hi havia cap suite de proves al repositori. Les proves es fan amb Playwright i Lighthouse fora del repo. |

## 2. Indexació i rastreig

| Element | Estat |
|---|---|
| `robots.txt` | `Allow: /` per a tothom, i declara dos sitemaps. **[confirmat]** |
| `sitemap.xml` | 15 URL (5 pàgines × 3 idiomes) amb hreflang. **`lastmod` fix a 2026-09-07** a totes, encara que les pàgines han canviat després: és una dada falsa per a Google. Sense `x-default`. **[confirmat]** |
| `sitemap-immobles.php` | Una URL per fitxa i idioma, generada en directe. **[confirmat]** |
| Canonicals | Correctes i absolutes a totes les pàgines. Les fitxes posen la seva canònica per referència. **[confirmat]** |
| Hreflang | `ca`, `es`, `en` + `x-default` (català), recíprocs, a totes les pàgines i fitxes. Els codis només d'idioma són vàlids i cobreixen més que `ca-ES`/`es-ES`. **[confirmat]** |
| Redireccions | `tophouseconsulting.com` (amb www i sense) → 301 a la mateixa ruta del domini nou. Les adreces antigues `ref-NNNN` (també les dels QR amb poble i tipus) → 301 a la fitxa. Els llistats antics `/es/<poble>/<tipus>/` → Comprar filtrat. **[confirmat, publicat avui]** |
| 404 | Codi 404 real, amb la portada en l'idioma de l'adreça. **[confirmat]** |
| Capçaleres | HTTPS, CSP, X-Frame-Options, nosniff, Referrer-Policy i Permissions-Policy. L'HTML va sense memòria cau. **[confirmat]** |
| Memòria cau dels JS | Hostinger serveix els `.js` com a `application/x-javascript`, un tipus que el `.htaccess` no preveu: tenen 7 dies en lloc d'1 any, tot i que porten l'empremta del fitxer a l'URL. **[confirmat]** |

## 3. Rastrejadors i IA

Provat demanant `/es/` amb el *user-agent* de cada robot, des d'una IP que
no és la seva:

| Robot | Resposta | Lectura |
|---|---|---|
| OAI-SearchBot (cerca de ChatGPT) | 200 | Pot llegir la web. **[confirmat]** |
| ChatGPT-User | 200 | **[confirmat]** |
| Googlebot | 200 | **[confirmat]** |
| PerplexityBot, ClaudeBot | 200 | **[confirmat]** |
| **bingbot** | **403** (pàgina d'error de LiteSpeed; `robots.txt` sí que dona 200) | **[hipòtesi]** Hostinger bloqueja els bots que diuen ser Bing però no venen d'una IP de Bing. Si també bloquegés el Bing de veritat, la web no sortiria a Bing, Copilot ni DuckDuckGo, i ChatGPT en perdria una font. **S'ha de comprovar a Bing Webmaster Tools (inspecció d'URL).** |
| GPTBot (entrenament d'OpenAI) | 429 | **[hipòtesi]** Bloqueig d'IA del CDN de Hostinger. GPTBot no és el que fa servir la cerca de ChatGPT, així que no afecta la visibilitat en la cerca. Que estigui permès o no ho ha de decidir la propietat. |

## 4. Contingut i metadades

- **Títols**: n'hi ha un de propi a cada pàgina i idioma, de 40 a 61 caràcters. El de la portada no esmenta Arenys de Mar, que és la consulta principal del negoci. **[confirmat]**
- **Descripcions**: n'hi ha una de pròpia a cada pàgina, però sis passen de 160 caràcters (de 171 a 188) i Google les talla. **[confirmat]**
- **H1**: n'hi ha un per pàgina. A la portada el text surt duplicat, perquè hi ha una còpia per a lectors de pantalla i una de visual, totes dues dins l'H1. No és greu. **[confirmat]**
- **Dades estructurades**: **cap a la portada ni a les pàgines de llistat**. Només les fitxes tenen `RealEstateListing`. No hi ha `RealEstateAgent`, `Organization`, `WebSite`, `BreadcrumbList` ni `Service`, i per tant els cercadors i les IA no tenen enlloc, de manera llegible per a màquines, qui és l'empresa, on és i què fa. **[confirmat]**
- **Arquitectura comercial**: tot el contingut de captació (vendre, valoració, serveis, preguntes, contacte) viu en seccions de la portada (`#vender`, `#valorar`, `#servicios`, `#contacto`). **No hi ha cap URL pròpia** per a «vendre pis Arenys de Mar», «valoració immobiliària», «gestió de lloguers» ni «contacte». Una sola URL competeix per totes les intencions. **[confirmat]**
- **Gestió de lloguers**: la web només en diu «alquiler garantizado», «buscamos el inquilino, comprobamos que puede pagar y preparamos el contrato». **No hi ha cap font que confirmi cobraments, incidències, renovacions ni tarifes.** **[confirmat]**
- **Formulari de la portada**: el desplegable «Qué necesita» té l'opció **«Alquilar» repetida dues vegades** i cap per al propietari que vol llogar. Els leads de prioritat 2 no es poden distingir. **[confirmat]**
- **Alt de les imatges**: no hi ha cap `<img>` sense `alt`. Les decoratives porten `alt=""`, que és correcte. **[confirmat]**
- **Afirmacions per validar**: la web diu «Más de diez años en este mercado», però la societat es va constituir el 2/8/2021 (Empresite). Pot referir-se a l'experiència de l'equip; no ho canvio, però la propietat ho ha de confirmar. **[hipòtesi]**

## 5. Rendiment i accessibilitat (Lighthouse 12, mòbil, web en directe)

| Pàgina | Perf | Acc | BP | SEO | LCP | CLS | TBT |
|---|---|---|---|---|---|---|---|
| Portada ca | 84 | 96 | 100 | 100 | 2,5 s | 0 | 460 ms |
| Portada es | 86 | 96 | 100 | 100 | 2,3 s | 0 | 440 ms |
| Comprar es | 99 | 96 | 96* | 100 | 1,9 s | 0 | 80 ms |
| Fitxa 1401 es | 97 | 90 | 96* | 100 | 2,1 s | 0 | 140 ms |
| Marine es | 96 | 96 | 100 | 100 | 2,5 s | 0 | 70 ms |

\* Els «errors de consola» de Comprar i de les fitxes són les fotos de Mobilia,
bloquejades **per la xarxa del meu entorn de proves**, no per la web. Per
aquest mateix motiu, els números de Comprar i de les fitxes no inclouen el pes
de les fotos i són optimistes. Les fotos es demanen en mida `-original`
**[hipòtesi: poden ser pesades a les fitxes]**.

Errors concrets:
- Contrast insuficient: la nota del peu (`.foot__note`) i el «(opcional)» dels formularis.
- Les miniatures de la galeria de la fitxa són botons sense nom accessible.
- El logo de 256 px es mostra petit: s'hi poden estalviar 13 KB a cada pàgina.
- TBT de 440-460 ms a la portada, pel motor del vídeo que avança amb el scroll.
- INP real: no es pot mesurar sense dades de camp (CrUX/Search Console).

## 6. Mesura [confirmat]

- No hi ha GA4, Search Console, Bing Webmaster Tools ni cap etiqueta de verificació.
- La política de privacitat promet explícitament «Sin analítica... ningún contador de visitas».
- Ara mateix l'única dada de conversió són els correus que arriben a la bústia i a Mobilia.

## 7. Presència externa i SEO local

- **Registre mercantil** (Empresite): Top House Real Estate S.L., NIF B06925135, Riera del Bisbe Pol 56, constituïda el 2/8/2021. Coincideix amb l'avís legal. **[confirmat]**
- **AICAT 11152** (registre d'agents immobiliaris de Catalunya): figura a l'avís legal. **[confirmat a la web; no verificat al registre]**
- **Perfils socials, Google Business Profile, Idealista, Bing Places i Apple**: el cercador no en retorna cap associat a la marca. No ho puc veure sense accés als comptes. **[hipòtesi: presència externa molt feble]**
- **Competència visible** a «inmobiliaria Arenys de Mar»: Firhabit (Riera del Bisbe Pol 68, «29 anys», 5★ a Google segons RealAdvisor), Finques La Riera, Urbenia, a més de portals i xarxes com Engel & Völkers. Els directoris (Trustlocal, RealAdvisor) ocupen la primera pàgina amb llistes de «Top 10».
- **Domini antic**: no he pogut veure quines URL té indexades Google (no tinc accés a Search Console i el cercador no en retorna cap). Les redireccions cobreixen tots els formats coneguts.

## 8. Prioritats que en surten

1. Pàgines pròpies per a vendre, valorar, llogar com a propietari (gestió) i contactar, amb formulari i només amb fets confirmats.
2. Dades estructurades de l'empresa (`RealEstateAgent`) i de cada pàgina.
3. Arreglar el formulari perquè el propietari que vol llogar es pugui identificar.
4. Mesura de conversions sense galetes i actualització honesta de la privacitat.
5. Detalls tècnics: sitemap, memòria cau, contrast, logo i noms dels botons.
6. Fora del codi: Search Console, Bing Webmaster Tools, Google Business Profile, ressenyes i perfils.

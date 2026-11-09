# Mesura de conversions i referències

Data: 9/11/2026.

## 1. Què hi ha ara (publicat)

Un comptador propi, **sense galetes, sense IP i sense identificadors**, al
mateix servidor de la web. No necessita avís de galetes, i la política de
privacitat explica exactament què compta (secció «Política de galetes», en
els tres idiomes).

| Fitxer | Què fa |
|---|---|
| `tophouse/assets/medida.js` | Al navegador: avisa quan passa alguna de les coses de la taula de sota. Es carrega amb `async`, així que no retarda la pàgina. |
| `tophouse/api/evento.php` | Al servidor: accepta només esdeveniments de la llista, només des del mateix domini, i en guarda **totals per dia** fora de `public_html` (`medida-AAAA-MM.json`). Té un límit de 3.000 combinacions per dia perquè ningú no pugui omplir el disc. |
| `tophouse/assets/aviso.js` | Compta la sol·licitud **només quan el servidor confirma que ha arribat**, i compta a part les que fallen. |

### Esdeveniments

| Esdeveniment | Quan | Per a què |
|---|---|---|
| `llegada` | Algú entra a la web des de fora (no compten les pàgines que visita després) | Trànsit i origen |
| `tel` | Prem un telèfon | Lead per telèfon |
| `whatsapp` | Prem un enllaç de WhatsApp | Lead per WhatsApp |
| `correo` | Prem el correu | Lead per correu |
| `calculadora` | Fa servir la calculadora de preu de la portada | Intenció de vendre |
| `solicitud` | Envia un formulari i el servidor confirma que ha arribat | **Conversió principal** |
| `fallo` | Envia un formulari i NO arriba | Detectar avaries |

Cada esdeveniment porta la **pàgina** i, a les sol·licituds, **quin
formulari** l'ha enviada:

| Formulari (`o`) | Prioritat de negoci |
|---|---|
| `vender` | 1 · propietari que ven |
| `gestion-alquiler` | 2 · propietari que lloga |
| `valoracion`, `portada` | 3 · valoració (a la portada, el desplegable diu què vol) |
| `contacto` | general |
| `ficha` | 4 · comprador o llogater interessat en un immoble |
| `nautica` | Top House Marine |

### Origen de la visita (només la categoria, mai l'adreça)
`chatgpt`, `perplexity`, `copilot`, `gemini`, `claude`, `otra-ia`, `google`,
`bing`, `duckduckgo`, `otro-buscador`, `portal` (Idealista, Fotocasa...),
`social`, `correo`, `qr`, `directa`, `otra`.

ChatGPT afegeix `?utm_source=chatgpt.com` als enllaços que cita, i això es
reconeix encara que el navegador no enviï d'on ve.

### El correu de cada sol·licitud
L'assumpte ara diu què ha demanat la persona (per exemple «Alquilar mi
vivienda · Nom», «Vendre · Nom»), no sempre «Valoración». A Mobilia, les
sol·licituds de lloguer (propietari o llogater) entren com a **Alquiler**:
abans totes entraven com a Venta.

## 2. El quadre de comandament

`https://www.tophouserealestate.es/api/evento.php?informe=CLAU`

- La `CLAU` és el valor `'clave'` del fitxer `mobilia-config.php` del servidor (el mateix que fa servir el diagnòstic de la cartera). Ha de tenir 16 caràcters o més. **Si no hi és, el quadre respon «No disponible»** (vegeu `PENDING_ACCESS.md`).
- Per defecte mostra 90 dies. Per canviar-ho: `&dias=30`.
- Mostra:
  - entrades;
  - sol·licituds;
  - contactes (sol·licituds + trucades + WhatsApp + correu) i el percentatge de contactes per entrada;
  - entrades des d'assistents d'IA;
  - formularis que no han arribat;
  - i, en detall: origen, formulari, pàgina i dia.

**Nota**: el 9/11/2026 hi ha unes quantes entrades de prova fetes durant la
verificació (pàgina `/prova-qa` i set entrades «directa»). Es poden ignorar.

## 3. Què falta i per què

| Eina | Estat | Què cal |
|---|---|---|
| Google Search Console | **No configurat** | Accés de la propietat. És l'única font de consultes, impressions i posicions reals a Google, i també de l'INP i l'LCP de camp. |
| Bing Webmaster Tools | **No configurat** | Es pot importar des de Search Console. Cal per confirmar si Hostinger bloqueja el bingbot de veritat (vegeu `SEO_GEO_AUDIT.md`, secció 3). |
| GA4 | **No instal·lat, a propòsit** | Afegir GA4 voldria dir galetes, un bàner de consentiment i canviar la política de privacitat. Per mesurar contactes i orígens n'hi ha prou amb el comptador propi. Si la propietat el vol igualment, cal l'ID de mesura i decidir l'eina de consentiment. |

## 4. Com mesurarem a 30, 60 i 90 dies

| Quan | Mirar | D'on |
|---|---|---|
| **30 dies** | Les pàgines noves (vendre, valoració, gestió, contacte) estan indexades en tres idiomes. Hi ha sol·licituds per formulari? Quantes `fallo`? | Search Console (Indexació) + quadre |
| **60 dies** | Impressions i clics de les consultes de `KEYWORD_STRATEGY.md`, per URL. Percentatge de contactes per entrada. Entrades des d'IA. | Search Console (Rendiment) + quadre |
| **90 dies** | Leads de propietaris (`vender` + `gestion-alquiler`) en comparació amb el trimestre anterior (correus a la bústia / Mobilia). Visites comercials i encàrrecs que n'han sortit: **es compten a Mobilia, no a la web**. | Mobilia + quadre |

L'èxit es mesura en **encàrrecs de venda i de lloguer**, no en visites. El
quadre diu d'on surten els contactes, i Mobilia diu quants s'han convertit
en encàrrec.

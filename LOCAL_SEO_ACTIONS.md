# SEO local, fitxes i reputació

Data: 9/11/2026. No tinc accés a cap compte extern (Google Business Profile,
Bing Places, Apple, Idealista, xarxes), així que **no he modificat ni creat
cap fitxa**. Aquí hi ha què s'ha comprovat, què s'ha fet a la web i què ha de
fer una persona, per ordre d'impacte.

## 1. Dades de l'empresa (NAP) — font única

Aquestes són les dades que la web publica ara a tot arreu: al peu, a la pàgina
de contacte, a l'avís legal i a les dades estructurades `RealEstateAgent`.
**Totes les fitxes externes han de dir exactament el mateix**, lletra per
lletra:

| Camp | Valor |
|---|---|
| Nom comercial | Top House Real Estate |
| Raó social | Top House Real Estate, S.L. |
| NIF | B06925135 |
| Adreça | Riera del Bisbe Pol, 56, 08350 Arenys de Mar (Barcelona) |
| Telèfon | 605 27 31 50 (+34 605 27 31 50) |
| WhatsApp | 605 27 31 50 |
| Correu | info@tophouserealestate.es |
| Web | https://www.tophouserealestate.es/ |
| Registre | AICAT 11152 |
| Idiomes | català, castellà, anglès |
| Zona | Maresme (els trenta municipis, de Montgat a Tordera) |
| Categoria principal recomanada (GBP) | «Agència immobiliària» |

**Falta i no l'he posat**: l'horari d'oficina. No consta enlloc i no l'invento.
Quan el tingueu, s'ha d'afegir a GBP i jo l'afegeixo a la web i a les dades
estructurades (`openingHoursSpecification`).

## 2. Comprovacions fetes

| Comprovació | Resultat |
|---|---|
| Registre mercantil (Empresite) | **Coincideix**: Top House Real Estate S.L., B06925135, Riera Bisbe Pol 56, constituïda el 2/8/2021. |
| Coherència a la web | **Corregit**: el correu no sortia al peu; ara surt a totes les pàgines. Les dades són idèntiques al peu, a contacte, a l'avís legal i al JSON-LD. |
| Google Business Profile | **No verificable** des d'aquí. El cercador no retorna cap fitxa associada a la marca. |
| Perfils socials, Idealista, Fotocasa | El cercador no retorna cap perfil de marca. Els immobles sí que surten als portals (via Mobilia), però sense que es vegi clarament l'agència. |
| Top House Consulting vs Real Estate | La web antiga redirigeix (301) a la nova amb la mateixa ruta, incloses les fitxes `ref-NNNN` i els QR. **Risc pendent**: si existeix una fitxa de Google o directoris amb el nom «Top House Consulting» o amb el web antic, s'ha d'actualitzar (vegeu 3.2). |

## 3. Accions per a una persona, per ordre

### 3.1 Google Business Profile (el més important per a «immobiliària Arenys de Mar»)
1. Entreu a https://business.google.com amb el compte de l'empresa i comproveu si ja hi ha una fitxa (busqueu també «Top House Consulting»).
2. Si n'hi ha **dues**, no n'esborreu cap pel vostre compte: demaneu a Google que les fusioni (opció «Marcar com a duplicada»). Una fitxa duplicada divideix les ressenyes.
3. Deixeu-hi les dades de la taula de la secció 1, la categoria «Agència immobiliària» i, com a categories secundàries, només les que feu de debò (per exemple, «Administrador de propietats» **només** si la gestió del dia a dia dels lloguers es confirma).
4. Web de la fitxa: `https://www.tophouserealestate.es/` (no la del web antic).
5. Afegiu fotos reals de l'oficina i de l'equip. Les de la web són d'ambient, i la web mateixa ho diu.
6. **Ressenyes**: demaneu-les a clients reals després de cada operació, amb l'enllaç directe que dona GBP. Responeu-les totes, també les negatives, sense amagar-ne cap. No n'hi ha cap d'inventada ni de comprada, i no n'hi ha d'haver.

### 3.2 Coexistència Top House Consulting / Real Estate
- Busqueu a Google «Top House Consulting Arenys» i a Google Maps. Si apareix una fitxa amb aquest nom i aquesta adreça, ha de passar a dir-se Top House Real Estate (si és el mateix negoci) i apuntar al web nou.
- Directoris on pot quedar el nom o el web antic: Pàgines Grogues, Empresite, eInforma, Trustlocal i RealAdvisor. Actualitzeu-hi el nom i el web.
- No cal fer res més amb el domini: `tophouseconsulting.com` ja redirigeix a la ruta equivalent, i el correu es manté a OVH.

### 3.3 Bing Places i Apple Business Connect
- **Bing Places** (https://www.bingplaces.com) permet importar la fitxa de Google Business Profile. Bing alimenta Copilot i és una de les fonts de la cerca de ChatGPT.
- **Apple Business Connect** (https://businessconnect.apple.com): és la fitxa d'Apple Maps i de Siri.

### 3.4 Portals
- A Idealista, Fotocasa i Habitaclia, comproveu que el perfil d'agència tingui el web nou i les mateixes dades. Molts compradors hi busquen l'agència pel nom.

### 3.5 Perfils socials
- Si teniu Instagram, Facebook o LinkedIn de l'empresa, passeu-me els enllaços. Els afegeixo a les dades estructurades (`sameAs`), que és com Google i les IA confirmen que aquests perfils i la web són la mateixa empresa. Sense enllaços verificats, no n'hi he posat cap.

## 4. Què s'ha fet a la web per al SEO local
- `RealEstateAgent` amb adreça, telèfon, correu, NIF, AICAT, zona (Maresme) i idiomes, a totes les pàgines.
- Pàgina de contacte pròpia (`/contacte.html`, `/es/contacto.html`, `/en/contact.html`) amb enllaç a «Com arribar-hi».
- Títols amb «Arenys de Mar» a la portada i a les pàgines de servei.
- Correu de contacte al peu de totes les pàgines.

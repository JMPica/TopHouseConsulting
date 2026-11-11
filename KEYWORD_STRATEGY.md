# Estratègia de consultes i mapatge a URL

Data: 9/11/2026.

**Sobre els volums.** No tinc accés a Google Keyword Planner, Search Console
ni cap eina amb volums reals, així que **no hi poso cap xifra de cerques**.
La prioritat surt de tres coses verificables: la intenció (qui cerca això
contracta?), el valor comercial per a Top House (prioritats 1 a 5 de
l'encàrrec) i el que es veu a la primera pàgina de resultats
(directoris, portals i agències com Firhabit o Finques La Riera). Quan
Search Console tingui 4-8 setmanes de dades, aquest document s'ha de
refer amb les impressions reals (vegeu `ANALYTICS_MEASUREMENT.md`).

Llegenda de prioritat: **A** = capta propietaris (venda o lloguer), **B** =
valoració, **C** = compradors o llogaters, **D** = marca.

---

## 1. Mapa: una URL per intenció

Cada intenció té una sola URL principal, per evitar que dues pàgines
pròpies competeixin per la mateixa consulta.

| Intenció | URL principal (ca / es / en) | Prioritat |
|---|---|---|
| Vendre un habitatge | `/vendre.html` · `/es/vender.html` · `/en/sell.html` | A |
| Llogar el meu habitatge (propietari) | `/gestio-lloguers.html` · `/es/gestion-alquileres.html` · `/en/rental-management.html` | A |
| Saber què val el meu pis | `/valoracio.html` · `/es/valoracion.html` · `/en/valuation.html` | B |
| Immobiliària a Arenys / Maresme (marca i genèrica) | `/` · `/es/` · `/en/` | A/D |
| Contacte, oficina, telèfon | `/contacte.html` · `/es/contacto.html` · `/en/contact.html` | D |
| Comprar | `/comprar.html` · `/es/comprar.html` · `/en/buy.html` | C |
| Llogar per viure-hi | `/llogar.html` · `/es/alquilar.html` · `/en/rent.html` | C |
| Comprar en un poble concret (només pobles amb 3+ immobles; avui Arenys de Mar i Canet de Mar) | `/comprar/arenys-de-mar` · `/es/comprar/arenys-de-mar` · `/en/buy/arenys-de-mar` | C |
| Un immoble concret | `/immoble/REF` · `/es/inmueble/REF` · `/en/property/REF` | C |
| Vaixells i amarraments | `/nautica.html` · `/es/nautica.html` · `/en/boats.html` | (línia a part) |

## 2. Consultes per URL

### Vendre (A) → `vendre.html` / `vender.html`
| Consulta | Llengua | Intenció | Com la cobreix la pàgina |
|---|---|---|---|
| vendre pis Arenys de Mar / vender piso Arenys de Mar | ca/es | Contractar | H1 i títol amb «Arenys de Mar i el Maresme» |
| vendre casa Maresme / vender casa Maresme | ca/es | Contractar | H1, text, servei (`Service`, `areaServed`: Maresme) |
| immobiliària per vendre el meu pis / inmobiliaria para vender mi piso | ca/es | Contractar | Procés en 4 passos, què costa, exclusiva o no |
| quant cobra una immobiliària per vendre / cuánto cobra una inmobiliaria | ca/es | Comparar | Secció «El que cobrem, i quan» i FAQ marcada |
| vendre sense exclusiva | ca/es | Comparar | FAQ «M'obliguen a una exclusiva?» |
| quant es triga a vendre una casa al Maresme | ca/es | Informar-se | FAQ marcada |

### Gestió de lloguers (A) → `gestio-lloguers.html` / `gestion-alquileres.html`
| Consulta | Llengua | Intenció | Com la cobreix |
|---|---|---|---|
| gestió de lloguers Maresme / gestión de alquileres Maresme | ca/es | Contractar | Títol i H1 |
| inmobiliaria gestión alquileres Maresme | es | Contractar | Títol, H1, `Service` |
| buscar inquilins Maresme / buscar inquilino para mi piso | ca/es | Contractar | «Li busquem l'inquilí» |
| comprovar solvència inquilí | ca/es | Contractar | «Solvència comprovada abans de signar» |
| administració de lloguers Arenys de Mar | ca | Contractar | Cobertura parcial: **no es publica** què inclou la gestió del dia a dia fins que la propietat ho confirmi (vegeu `PENDING_ACCESS.md`) |
| gestió d'incidències i cobraments de lloguer | ca/es | Contractar | **No coberta** a propòsit: no consta que el servei ho inclogui |

### Valoració (B) → `valoracio.html` / `valoracion.html`
| Consulta | Llengua | Intenció | Com la cobreix |
|---|---|---|---|
| valoració immobiliària gratuïta / valoración inmobiliaria gratis | ca/es | Contractar | Títol, H1, FAQ «És gratis de veritat?» |
| quant val el meu pis a Arenys de Mar / cuánto vale mi piso | ca/es | Informar-se → contractar | Explicació del mètode i enllaç a la calculadora |
| taxació pis Arenys | ca | Ambigua (taxació oficial ≠ valoració) | No es reclama: Top House no fa taxacions homologades |

### Portada (A/D) → `/` i `/es/`
| Consulta | Llengua | Com la cobreix |
|---|---|---|
| immobiliària Arenys de Mar / inmobiliaria Arenys de Mar | ca/es | Títol nou: «Immobiliària a Arenys de Mar i el Maresme» |
| agència immobiliària Maresme | ca/es | Títol, descripció, `RealEstateAgent` amb `areaServed` |
| millor immobiliària Arenys de Mar | ca/es | Cap pàgina no pot «ser la millor» sense ressenyes: depèn de Google Business Profile (vegeu `LOCAL_SEO_ACTIONS.md`) |
| immobiliària de confiança Maresme | ca/es | Dades verificables a la web: NIF, AICAT, adreça. Les ressenyes, a GBP |

### Compra (C) → `comprar.html`
| Consulta | Com la cobreix |
|---|---|
| pisos en venda Arenys de Mar / pisos en venta Arenys de Mar | **Pàgina pròpia** `/comprar/arenys-de-mar` (des de l'11/11/2026), amb els immobles a l'HTML i `ItemList` |
| pisos en venda Canet de Mar | Pàgina pròpia `/comprar/canet-de-mar` |
| cases en venda al Maresme / comprar casa Maresme | Llistat i fitxes. Els portals (Idealista, Fotocasa) dominen aquestes consultes: la via realista són les fitxes individuals |

## 3. Consultes conversacionals (ChatGPT i altres assistents)

Són preguntes llargues. L'assistent respon citant pàgines que contesten
exactament la pregunta i que tenen dades clares de qui és l'empresa.

| Pregunta típica | On troba la resposta |
|---|---|
| «Quina immobiliària em recomanes a Arenys de Mar per vendre el meu pis?» | Portada + `vendre.html` + `RealEstateAgent` (NIF, AICAT, adreça) |
| «Quant cobra una immobiliària al Maresme per vendre?» | `vendre.html`: percentatge sobre el preu final, acordat abans de signar; si no es ven, no es paga |
| «Necessito algú que em busqui inquilí i faci el contracte al Maresme» | `gestio-lloguers.html` |
| «Com sé quant val el meu pis a Arenys sense compromís?» | `valoracio.html` |
| «Hi ha immobiliàries a Arenys que treballin sense exclusiva?» | FAQ de `vendre.html` |
| «Telèfon / adreça de Top House Real Estate» | `contacte.html`, `ContactPage` |

**No s'ha fet** (i no s'ha de fer): pàgines per a cada poble amb el mateix
text, text amagat per a les IA ni repetició de paraules clau. Les pàgines de
poble que hi ha són llistes reals d'immobles i només existeixen on n'hi ha 3
o més. Per anar més enllà (text propi de cada poble: vendes tancades, preus,
barris) cal contingut que només pot donar Top House.

## 4. Pròxim pas amb dades reals

1. A les 4-8 setmanes, exportar de Search Console les consultes amb impressions per a cada URL d'aquest mapa.
2. Les consultes amb impressions i una posició entre 8 i 20 són les que més valen: afinar el títol i el text de la pàgina que ja les rep.
3. Consultes que apareixen a la URL equivocada (per exemple, «vendre» a la portada): reforçar l'enllaç intern cap a la pàgina correcta.

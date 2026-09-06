# Paquete de diseño — Top House Consulting

Tier 1. Un solo plano de 6 segundos, recorrido por scroll. Este documento es la entrada de la fase de construcción. Todo el texto de aquí se copia literal a la web.

---

## 1. La premisa de marca

**El umbral.** En Arenys toda casa mira al mar a través de algo: una puerta, un balcón, una calle que baja. La web enseña y vende una sola idea: *una casa en Arenys no es un anuncio, es una posición en la ladera, y el precio lo marca la calle, no los metros*. Top House vende Arenys porque camina Arenys, calle a calle.

Nota de cambio de mundo: el plano aprobado no es un soportal de piedra sino un interior de lujo con puerta de cristal al mar. El elemento firma pasa de arco de piedra a umbral recto, para que la página y el vídeo sigan siendo el mismo sitio.

Cada sección sirve a esa idea. La valoración es por tramo. El comprador entra por la calle, no por el filtro del portal. Las preguntas frecuentes contestan la objeción del precio con la calle. Si una sección no enseña eso, no va en la página.

---

## 2. La paleta, en tokens CSS

Muestreada del mundo del plano: sombra de cal, piedra caliente, agua profunda y el primer sol, que es el oro del logo.

```css
:root{
  --canvas:#0B1417;        /* grafito de mar, tintado en verde azulado, nunca negro puro */
  --canvas-deep:#070E11;
  --panel:#101D21;
  --panel-2:#16272C;
  --accent:#D4A94B;        /* el oro del logo */
  --accent-hover:#E8C572;
  --accent-muted:rgba(212,169,75,.18);
  --text-secondary:#9EB2B4;  /* bruma de mar, 8.1:1 sobre el lienzo */
  --text-primary:#F1EBDF;    /* blanco de cal templado */
  --line:rgba(158,178,180,.16);
}
```

Nota de dirección dicha en voz alta: oro sobre casi negro está en la lista de miradas prohibidas por defecto de esta skill. Aquí se gana la excepción porque el oro sobre negro **es** el logo real de la marca, no un reflejo automático. Se gana desviando el lienzo hacia el verde azulado del mar en vez del negro, sacando el acento de la propia luz del plano, e inventando un elemento firma propio (el arco dibujado). Nada de serif de alto contraste tipo Didone, que es lo que remata esa mirada de plantilla.

---

## 3. El trío tipográfico

- **Display: Fraunces** (serif variable, blanda y cálida, con carácter real). Pesos 300 y 500.
- **Cuerpo: Manrope** 400, 500, 700.
- **Mono: IBM Plex Mono** 400, 500, para etiquetas pequeñas y datos.

Ni Inter ni Roboto como display.

---

## 4. El mapa de bandas del héroe

Vídeo aprobado: Seedance 1.5 Pro, 8 segundos, 1080p, sin audio, 24 créditos.
Altura del héroe: 760vh, es decir 660vh de recorrido. Rampas de 0.02 de progreso, o sea 13vh.

El plano tiene una puerta de cristal centrada con las paredes en sombra a izquierda y derecha. Por eso cada banda es **una frase partida en dos**, media a cada lado del arco, que se lee cruzando la abertura. Es el recurso de marca del héroe, elegido a propósito.

| Banda | Rango (punto de partida) | Momento del plano | Texto (literal) | Entrada |
|---|---|---|---|---|
| 1 | 0.00 a 0.19 | Dentro de la casa en sombra, la puerta pequeña en el centro | Izq: "El Maresme" · Der: "de memoria." · Sub: "Arenys de Mar, casa por casa, desde hace más de diez años." | Mitades que se separan, eco del umbral abriéndose |
| 2 | 0.24 a 0.44 | Avance hacia la puerta, que crece | Izq: "Un precio mal puesto" · Der: "se paga en meses." · Sub: "La casa que lleva medio año publicada ya no la mira nadie igual." | Aproximación desde la profundidad, eco del empuje adelante |
| 3 | 0.49 a 0.69 | Cruce del plano del cristal, la luz revienta y el objetivo respira | Izq: "Tu calle" · Der: "marca el precio." · Sub: "Valoramos con lo que se ha firmado en tu tramo, no con la media del pueblo." | De desenfoque a nitidez, eco del ojo acostumbrándose a la luz |
| 4 | 0.76 a 1.00 | Reposo en la terraza, mar y primer sol, cielo abierto | Centrado: "Primero sabemos lo que vale." / "Después le ponemos precio." · Sub: "Valoración gratuita en tu casa. Sin compromiso y sin ataduras." · Botones: "Pedir mi valoración" y "Ver qué hay en venta" | Palabra a palabra hacia un posado en tres tiempos |

La banda 1 no tiene entrada de opacidad y arranca ya montada con una rampa de carga. La banda 4 no tiene salida.

Legibilidad: variante de dos velos, uno por columna, con el carril central del arco intacto. La banda 4 lleva un solo velo elíptico superior centrado.

---

## 5. El bloque del héroe estático

Para móviles y para quien pide menos movimiento. Sobre el fotograma final.

- Titular: "El Maresme, de memoria."
- Subtítulo: "Vendemos en Arenys de Mar casa por casa desde hace más de diez años. Primero sabemos lo que vale tu casa. Después le ponemos precio."
- Botones: "Pedir mi valoración" y "Ver qué hay en venta"

---

## 6. El guion de todo lo que va debajo

Ninguna sección vecina comparte esqueleto. Todo empuja a una sola llamada: la valoración. El comprador tiene su puerta propia y bien visible.

### 6.1 La bifurcación
Dos tarjetas asimétricas, la del propietario a dos tercios y la del comprador a uno.

- Kicker: "Dos maneras de entrar"
- Vendo: "Quiero saber qué vale mi casa" · "Media hora en tu casa y te vas con un precio real, lo vendas con nosotros o no." · Botón: "Pedir mi valoración"
- Compro: "Quiero ver lo que hay en Arenys" · "Pisos, casas, locales y terreno en Arenys y en la costa del Maresme." · Botón: "Ver la cartera"

### 6.2 El umbral (el elemento firma)
Sección de la premisa. Un umbral dibujado a mano en SVG que se traza solo al bajar, y dentro la imagen de la calle. Tres tiempos de texto escalonados a la derecha.

- Kicker: "Calle a calle"
- Titular: "Arenys baja de la montaña al mar en quince minutos a pie."
- Beat 2: "Y en esos quince minutos el precio del metro cambia tres veces."
- Beat 3: "Por eso valoramos por tramo, no por pueblo."

### 6.3 Cómo vendemos tu casa
Cuatro pasos, cada uno con su propia imagen generada, todos tratados igual. Un hilo vertical dibujado los une.

- Kicker: "El encargo"
- Titular: "Cuatro pasos y ni uno de relleno."
1. "Vamos a verla" · "Media hora en tu casa. Sin formularios de veinte campos ni tasadores automáticos que no la han visto."
2. "Le ponemos precio con datos de tu tramo" · "Lo que se ha firmado cerca, no lo que se pide en los portales."
3. "La enseñamos como se merece" · "Fotografía, plano y visitas filtradas. Solo entra quien puede comprarla."
4. "Cerramos" · "Arras, notaría y papeles. Tú firmas, del resto nos ocupamos nosotros."

### 6.4 El mapa de Arenys (el momento interactivo)
Mapa de Arenys dibujado a mano en SVG con cinco tramos. El visitante elige el suyo, mantiene pulsado "Ver mi tramo", un arco de oro se va llenando y se revela la línea de ese tramo. Al soltar antes de tiempo el arco baja despacio, nunca de golpe. Al completarlo, el tramo queda cargado en el formulario. Con movimiento reducido, estado final directo.

- Kicker: "Tu tramo"
- Titular: "Elige dónde está tu casa."
- Lede: "En Arenys el precio cambia de una calle a la siguiente. Mira lo que pasa en el tuyo."

Líneas por tramo (**el usuario tiene que revisarlas: es su mercado, no el mío**):
- Primera línia i passeig: "Aquí se paga la vista, no los metros. Y se paga rápido."
- Centre i la Riera: "Casas de pueblo con planta baja. Lo que decide el precio es si tiene patio."
- Eixample i estació: "El comprador que llega de Barcelona empieza mirando aquí. Los diez minutos a la estación valen dinero."
- Zona alta: "Vistas y coche. Se vende más despacio y a mejor precio."
- Afores i Sant Elm: "Parcelas grandes. Aquí lo que se compra es el terreno."

Botón al completar: "Pedir la valoración de mi tramo"

### 6.5 La prueba
Tres datos, en cifras grandes, sobre el lienzo. Solo lo confirmado.
- "+10" · "años vendiendo en Arenys de Mar"
- "39" · "inmuebles en cartera ahora mismo" (**a confirmar por el usuario**)
- "3" · "idiomas: català, castellano y English" (**a confirmar por el usuario**)

Sin testimonios inventados. La sección de reseñas se añade cuando el usuario mande reseñas reales.

### 6.6 Lo que preguntan siempre
- "¿Cuánto cobráis?" · "Un porcentaje sobre el precio final de venta, acordado contigo antes de firmar nada. No hay cuota de entrada ni gastos de publicación." (**a confirmar por el usuario**)
- "¿Me atáis a una exclusiva?" · "Trabajamos de las dos maneras. Te explicamos qué gana tu casa con cada una y decides tú."
- "¿La valoración es gratis de verdad?" · "Sí. Vamos a tu casa, la vemos y te damos un precio con lo que se ha vendido en tu tramo. Ni te cobramos ni te comprometes a nada."
- "Ya la tengo publicada y no llama nadie. ¿Qué cambia?" · "Casi siempre es el precio o son las fotos. En la valoración te decimos cuál de las dos cosas es, aunque luego no nos des el encargo."
- "¿Cuánto tarda en venderse una casa en Arenys?" · "Depende del tramo y del precio de salida. Una casa bien puesta se mueve en semanas. Una mal puesta puede estar un año y acabar vendiéndose por menos."
- "¿Atendéis en inglés?" · "Sí. Y en català y en castellano."

### 6.7 El cierre
Kicker: "Valoración gratuita"
Titular: "Empecemos por saber qué vale."
Lede: "Rellena esto y te llamamos hoy para acordar el día. Media hora en tu casa y te vas con un precio real."

Campos: Nombre · Teléfono · Tu tramo de Arenys (se rellena solo desde el mapa) · Qué necesitas (Vender, Comprar, Solo saber el precio) · Mensaje (opcional)
Botón: "Pedir mi valoración"
Estado de éxito: "Recibido. Se abre WhatsApp con tu mensaje ya escrito. Si prefieres, escríbenos por correo y te contestamos igual."

Manejo del formulario en una web estática: el envío construye el mensaje completo y abre WhatsApp al 605 27 31 50. No hay servidor, no hay cuenta que crear, y el mensaje llega al teléfono al instante. Al lado, un enlace de correo para quien lo prefiera. Se le dice al visitante exactamente dónde acaba su mensaje.

### 6.8 El pie
Top House Consulting · Riera del Bisbe Pol, 56 · 08350 Arenys de Mar (Barcelona) · 605 27 31 50 · WhatsApp · català, castellano, English. Marca real, así que no hay nota de marca ficticia. Las imágenes son de ambiente y ninguna se presenta como un inmueble en cartera.

---

## 7. La capa vectorial

- **El logo redibujado en SVG a mano**: círculo de oro con la silueta de edificios en línea. Va en la cabecera, en el pie y en el favicon.
- **El umbral firma**: el trazo de una puerta que se dibuja solo al bajar y enmarca la imagen de la sección de la premisa. En la tarjeta de compradores se repite en tres umbrales encajados que se alejan, como un pasillo de puertas.
- **El mapa de Arenys**: dibujado a mano, cinco tramos, con la costa y la riera. Estados de hover, de foco y de pulsado.
- **El hilo de los cuatro pasos**: línea vertical que se traza sola al entrar la sección.
- **Divisores**: una regla fina de oro que se abre desde el centro al entrar la sección.
- **El entorno fijo**: una sola capa de fondo detrás de todo, degradado de grafito de mar con una luz que deriva muy despacio, ciclo de 90 segundos, más un polvo de mar a nivel susurro.

Todo respeta el movimiento reducido: estados finales visibles, motores parados.

---

## 8. La lista de ingeniería

Blob con anillo de carga, lerp normalizado por dt, búsquedas con compuerta, escrituras al DOM solo al cambiar, ritmo de bandas validado con la prueba del golpe de rueda, sistema de legibilidad de cuatro capas, las cinco compuertas del héroe estático vivas con escuchadores de cambio, página completa sin vídeo, y el suelo de calidad entero.

---

## 9. La compuerta del texto

Todo el texto de arriba se copia literal. La página construida tiene que pasar la revisión de la fase 9 (cero rayas largas, cero palabras de folleto, más el barrido de tics de escritura automática) antes de que nadie la vea. Los recursos de marca decididos aquí a propósito, como la frase partida por el arco o los pares de frases cortas, son oficio y se quedan.

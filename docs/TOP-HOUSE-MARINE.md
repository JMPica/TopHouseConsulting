# Top House Marine: el heroe con video

Paquete de diseno del heroe de `nautica.html`. Todo lo que hay aqui se decidio
antes de gastar un credito. El copy de las bandas va literal a la web.

## Estado (31/10/2026)

- Paleta azul: HECHA y en la rama (body.mar en site.css).
- Video: PENDIENTE. La cuenta de Higgsfield tiene 0 creditos (plan gratuito).
- Mientras tanto la pagina abre con el titular y la foto real del puerto, que
  es tambien lo que vera el movil si el video no carga.

## El concepto, y por que

Tres candidatos, decididos con la Sala de Genios (Hormozi + Sutherland):

1. **Subir a bordo** (pantalan, cubierta, camarote, proa). Descartado: muchas
   superficies y geometria de cerca (barandillas, escaleras, manos), que es lo
   que el video generado rompe. Y un vendedor no necesita que le ensenen su
   propio barco por dentro.
2. **Salir a navegar, desde la proa.** ELEGIDO.
3. **Dron cenital sobre un velero que sale del puerto.** Bonito y facil de
   generar, pero frio: se mira el barco desde fuera, no se siente.

Por que el 2:

- *Sutherland*: la proa saliendo por la bocana es el sabado que el propietario
  quiere recuperar, y a la vez el barco yendose a su nuevo dueno. Y la bocana es
  el umbral de Marine, igual que la puerta al mar lo es de la portada: la misma
  marca contada en el agua.
- *Hormozi*: el dolor del vendedor es el barco parado que paga cada mes y los
  sabados perdidos ensenandolo. Las tres bandas van de ese dolor a la salida,
  y la ultima se posa sobre la oferta.
- *Leyes del video*: un solo empuje hacia delante (bajar = avanzar), materia
  agradecida (agua, bruma, luz, siluetas lejanas), sin personas, y un final en
  calma: mar abierto, horizonte bajo el centro, sol bajo. El agua de la mitad
  de abajo es zona tranquila para el texto final.

## Las bandas (castellano; catalan e ingles en idiomas/)

| Banda | Momento del plano | Texto |
|---|---|---|
| 1 | Amarrado, entre las dos filas de barcos | "Un barco amarrado / tambien cuesta." Sub: "Amarre, seguro y mantenimiento. Cada mes, lo use o no." |
| 2 | Cruzando la bocana, junto al faro | "Nosotros lo ensenamos. / Usted, ni un sabado." Sub: "Solo sube a bordo quien puede comprarlo." |
| 3 | Mar abierto, en reposo | "Venda su barco / sin perder un sabado." Sub: "Valoracion gratuita en el amarre. Si no se vende, no paga." Botones: "Valorar mi barco" y "Llamar al 605 27 31 50" |

Las dos primeras, partidas a izquierda y derecha sobre las filas de barcos; el
carril central (canal y bocana) siempre libre. La tercera, centrada sobre el agua.

## Coste (consultado el 31/10, sin gastar nada)

| Paso | Modelo | Creditos |
|---|---|---|
| Fotograma inicial 16:9 | gpt_image_2_5 | 0,25 |
| Video 8 s, sin sonido | **Kling 3.0 Pro (recomendado)** | 14 |
| Video 8 s, sin sonido | Kling 3.0 Std | 12 |
| Video 8 s, 720p | Seedance 2.5 | 56 |
| Video 8 s, 1080p | Seedance 2.5 | 128 |

Con unos 30 creditos cabe el plano con Kling 3.0 Pro y un segundo intento.

## Prompts

### Fotograma inicial

Eye-level view from the foredeck of a white sailing yacht moored in a small
Mediterranean marina on the Catalan coast at first light, the bow pointing
straight ahead down a calm channel of water between two rows of moored
sailboats. The harbour mouth, with its stone breakwater and a small white
lighthouse, sits far ahead in the exact center of the frame, open sea beyond.
Composed as the first moment of a slow forward glide out to sea. Soft dawn
light from ahead, the low sun just below the horizon warming the sky pale gold
over deep blue water. Palette: deep sea blue, navy shadows, mist white, a touch
of brass on the deck fittings. Atmosphere: thin sea mist, gentle ripples, a few
gulls far away. The moored boats on the left and right form calm, softly
shadowed walls along both sides of the frame; the channel and the harbour mouth
stay clear in the middle. Cinematic, photorealistic, 16:9. No text, no logos,
no lettering, no boat names, no people anywhere.

### Video (image-to-video, 8 s, sin sonido)

One continuous shot, no cuts. The camera on the foredeck of a sailing yacht
glides slowly straight forward down the calm marina channel, past the moored
boats on both sides, out through the harbour mouth beside the stone breakwater
and its small white lighthouse, and onto the open sea. The yacht stays alive
throughout: a gentle rise and fall on the water, the forestay and lines swaying
slightly. The scene stays alive: ripples, thin drifting mist, gulls far away,
the dawn light growing warmer. As the bow clears the harbour mouth a light spray
of sea water catches the lens for a moment with a brief soft blur. The shot ends
at rest on open sea: the bow steady at the bottom center, calm deep blue water
filling the lower half, the horizon a little below the middle, the low golden
sun just above the horizon at the center, a clear pale sky above. No text, no
logos, no lettering, no people anywhere.

## Lo que queda cuando haya video

1. Fotograma, revisarlo (marcas, nombres de barco, composicion) y ensenarlo.
2. Video, revisar inicio, medio y final, y PUERTA: lo aprueba Top House antes
   de montar nada.
3. Procesarlo como el de la portada (recodificado para scroll, poster, final,
   y la secuencia de fotogramas para el movil).
4. site.js lee hoy las rutas del video escritas a pelo. Pasarlas a atributos
   del heroe (data-video, data-poster, data-ending, data-seq) para que la
   portada y Marine compartan motor, y que el formulario de site.js no se
   enganche al de nautica.js.
5. Bandas con el copy de arriba, prueba de lectura sobre el peor fotograma de
   cada banda, y prueba de scroll a golpes.

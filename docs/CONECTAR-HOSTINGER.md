# Conectar Hostinger para que los despliegues se hagan solos

Objetivo: que cualquier cambio en la web se publique sin que tengas que
tocar el panel de Hostinger.

Ya está hecho el fichero `.mcp.json` de este repositorio, que hace que el
conector de Hostinger se cargue solo en cada sesión nueva. Faltan dos
ajustes que solo puedes hacer tú, porque están en tu cuenta.

---

## 1. Crear un token de API en Hostinger

En hPanel, en la sección de tu perfil / cuenta, busca **API**. Genera un
token nuevo y cópialo: **solo se enseña una vez**.

Un aviso importante y honesto: ese token da acceso a **toda la cuenta**,
no solo a esta web. Con él se pueden gestionar dominios, correo, VPS y
facturación. Si Hostinger te deja limitar su alcance al crearlo, límitalo.
Y si algún día quieres cortar el acceso, se revoca desde ese mismo sitio y
deja de funcionar al instante.

## 2. Configurar el entorno en claude.ai/code

Pulsa el icono de nube que hay encima del cuadro de mensaje, pasa el ratón
por encima del entorno y dale al engranaje. Cambia dos cosas:

**Network access → Custom.** En "Allowed domains", una por línea:

```
*.hostinger.com
```

Deja marcada la casilla de incluir la lista por defecto, o se romperá lo
que ya funciona (npm, GitHub).

El comodín cubre los tres hosts que hacen falta: `auth.hostinger.com` para
identificarse, `developers.hostinger.com` para las llamadas, y el servidor
de subida de ficheros, que va por su cuenta y cuyo nombre todavía no
conocemos. Si al desplegar aparece un host bloqueado que no encaje en
`*.hostinger.com`, se añade y listo.

**Environment variables.** Añade esta línea, con tu token:

```
HOSTINGER_API_TOKEN=el-token-que-acabas-de-copiar
```

Tiene que ser una variable de entorno, no una "API credential". Las API
credentials las añade el proxy de Anthropic al salir la petición, así que
el conector no llegaría a verlas y se pondría a buscar un navegador para
hacer login, y aquí no hay navegador. Con la variable de entorno se salta
el login entero.

Contrapartida: el valor de una variable de entorno es visible para
cualquiera que use ese entorno. Por eso lo de limitar y poder revocar el
token del punto 1.

## 3. Abrir una sesión nueva

Los cambios de red se aplican al arrancar el contenedor, así que la sesión
actual no los ve. Abre una sesión nueva sobre este repositorio y di que
está listo.

---

## Qué pasará entonces, sin que tengas que hacer nada

1. Listar las webs de la cuenta y localizar tophouserealestate.es.
2. Crearla en el hosting si aún no existe.
3. Generar la URL de subida.
4. Subir el zip de la web por el protocolo TUS.
5. Descomprimirlo en public_html.
6. Comprobar la web ya publicada y medir cuánto tarda en cargar.

A partir de ahí, cada cambio es: se toca el código, se despliega, se
verifica. Sin panel y sin ficheros a mano.

## Lo que sigue necesitando decisión vuestra

Esto no lo desbloquea el conector:

- La **URL del feed de Mobilia** y una respuesta de ejemplo, para terminar
  el adaptador de la cartera. Hasta entonces Comprar y Alquilar salen
  vacías, con el aviso de llamar por teléfono.
- Revisar los **49 coeficientes estimados** de la calculadora y el ajuste
  de oferta. Están documentados en `tophouse/assets/poblacions.js`.
- El **nombre legal** de la empresa para el pie de la web.

# Publicar tophouserealestate.es y dejarlo automatico

La web se despliega desde GitHub. Configuras esto UNA vez y a partir de
ahi cada cambio se publica solo, sin que tengas que tocar el panel.

No hace falta token de API, ni conector, ni permisos de red.

---

## Lo que ya esta hecho

La rama **`deploy-web`** del repositorio `JMPica/TopHouseConsulting`
contiene la web con los ficheros en la raiz, que es como Hostinger los
espera. Se regenera con `./publicar.sh` y empujarla es, literalmente,
publicar.

Esa rama NO se toca a mano. El trabajo va siempre en la rama normal,
dentro de `tophouse/`.

---

## Lo que tienes que hacer tu, una sola vez

Aviso: no puedo abrir hostinger.com desde donde trabajo, asi que los
nombres exactos de los botones pueden variar un poco segun la version del
panel. La secuencia es la que cuenta.

### 1. Apuntar Hostinger al repositorio

En hPanel, entra en la web y busca el apartado **GIT** (suele estar en
Avanzado). Ahi:

- **Repositorio**: `git@github.com:JMPica/TopHouseConsulting.git`
- **Rama**: `deploy-web`
- **Directorio**: dejalo vacio, para que despliegue en `public_html`

Es un repositorio privado, asi que Hostinger te ensenara una **clave SSH
publica** y te dira que la autorices.

### 2. Autorizar esa clave en GitHub

En `github.com/JMPica/TopHouseConsulting` → **Settings** → **Deploy keys**
→ **Add deploy key**. Le pones un nombre (`Hostinger`), pegas la clave que
te dio Hostinger, y **NO marques** "Allow write access": solo necesita
leer.

Vuelve a hPanel y crea el despliegue. El primer despliegue puede tardar un
par de minutos: son 11 MB, casi todos del video.

### 3. Que se publique solo en cada cambio

En el mismo apartado GIT de hPanel hay una **URL de despliegue automatico**
(auto-deployment webhook). Copiala.

En GitHub → **Settings** → **Webhooks** → **Add webhook**:

- **Payload URL**: la que copiaste
- **Content type**: `application/json`
- **Which events**: solo el evento push

A partir de ahi, cada vez que yo empuje `deploy-web`, la web se actualiza
sola.

### 4. El certificado y el dominio

- En el apartado **SSL** del panel, activa el certificado gratuito. Hasta
  que este, el navegador avisara de que la web no es segura.
- El `.htaccess` manda todo a **https con www**. Asegurate de que
  `www.tophouserealestate.es` resuelve. Si el dominio esta registrado en
  Hostinger, esto se configura solo.

### 5. Comprobar

Abre `https://www.tophouserealestate.es` en el ordenador y **en el movil**:

- que al bajar con la rueda el video de arriba avanza con el scroll
- que se ven las seis imagenes de mas abajo
- que la calculadora da un precio y abre WhatsApp
- que Comprar y Alquilar se abren

Comprar y Alquilar saldran vacias, con el aviso de llamar por telefono,
hasta que Mobilia envie los inmuebles. Es lo previsto, no un fallo.

---

## Como se publica un cambio a partir de ahora

Yo toco lo que haga falta en `tophouse/`, hago commit, y ejecuto
`./publicar.sh`. La web se actualiza sola en un minuto. Tu no tienes que
entrar en el panel.

---

## Lo que sigue necesitando decision vuestra

Nada de esto lo desbloquea la publicacion:

- La **URL del feed de Mobilia** y una respuesta de ejemplo, para terminar
  el adaptador de la cartera.
- Revisar los **49 coeficientes estimados** de la calculadora y el ajuste
  de oferta, documentados en `tophouse/assets/poblacions.js`.
- El **nombre legal** de la empresa para el pie de la web.
- Las **redirecciones 301** desde tophouseconsulting.com, para no perder el
  posicionamiento. El `.htaccess` ya recoge las direcciones viejas
  conocidas; falta apuntar el dominio antiguo aqui.

---

## Apendice: la otra via, por si algun dia la quieres

Existe un conector de Hostinger que permitiria desplegar por su API en vez
de por GitHub. Esta ya configurado en `.mcp.json` y se carga solo, pero no
funciona desde aqui: la red de este entorno bloquea `auth.hostinger.com` y
`developers.hostinger.com` con un 403.

Para habilitarlo harian falta dos cosas en los ajustes del entorno, en
claude.ai/code: **Network access → Custom** con `*.hostinger.com`, y una
variable de entorno `HOSTINGER_API_TOKEN` con un token de la cuenta.

Tiene que ser variable de entorno y no "API credential": las API
credentials las anade el proxy cuando la peticion ya ha salido, asi que el
conector no las ve y se pone a buscar un navegador para hacer login.

No hace falta para nada de lo de arriba. La via de GitHub es mas simple y
no expone un token que da acceso a toda la cuenta de Hostinger.

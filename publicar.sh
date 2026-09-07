#!/usr/bin/env bash
# =====================================================================
# Publicar la web
# =====================================================================
#
# Regenera la rama 'deploy-web' a partir de tophouse/ y la empuja.
# Hostinger esta escuchando esa rama, asi que empujarla ES publicar.
#
# Se usa asi, desde la raiz del repositorio:
#
#     ./publicar.sh
#
# Antes de tocar esto conviene saber por que existe: Hostinger despliega
# el contenido de una rama en public_html tal cual, sin subcarpetas. Como
# la web vive en tophouse/, hace falta una rama donde esos ficheros esten
# en la raiz. Eso es lo unico que hace este script.
#
# La rama se REESCRIBE entera en cada publicacion. Es deliberado: es una
# rama generada, no un sitio donde trabajar. Nunca hagas commits a mano
# en deploy-web, se perderian en la siguiente publicacion. Todo el
# trabajo va en la rama normal, dentro de tophouse/.
# =====================================================================
set -euo pipefail

ORIGEN=tophouse
RAMA=deploy-web

cd "$(dirname "$0")"

# Trabajar con cambios sin guardar publicaria una version que no existe
# en ninguna parte, imposible de reproducir despues.
if [ -n "$(git status --porcelain -- "$ORIGEN")" ]; then
  echo "ERROR: hay cambios sin guardar en $ORIGEN/."
  echo "Haz commit antes de publicar, o no habra forma de saber que se publico."
  git status --short -- "$ORIGEN"
  exit 1
fi

# Ningun fichero a medias sale al dominio. Los huecos que faltan por
# rellenar se marcan en el html con data-falta, y esta comprobacion existe
# porque ya publique una vez, sin querer, un aviso legal con nueve huecos
# en rojo a la vista de cualquiera: el script corta tophouse/ ENTERA, asi
# que basta con dejarse un fichero dentro para publicarlo.
# Solo en los .html: en el .css vive el selector que pinta esos huecos
# de rojo, y ese si tiene que publicarse.
PENDIENTES=$(grep -rl --include="*.html" "data-falta" "$ORIGEN" 2>/dev/null || true)
if [ -n "$PENDIENTES" ]; then
  echo "ERROR: hay ficheros con huecos sin rellenar dentro de $ORIGEN/."
  echo "Publicarlos los pondria en el dominio tal cual. Ficheros:"
  echo "$PENDIENTES" | sed 's/^/    /'
  echo
  echo "Rellena los huecos marcados con data-falta, o saca el fichero de $ORIGEN/."
  exit 1
fi

echo "==> Cortando $ORIGEN/ a la raiz"
git branch -D "$RAMA" 2>/dev/null || true
git subtree split --prefix="$ORIGEN" -b "$RAMA" >/dev/null

echo "==> Comprobando que el corte tiene sentido"
FICHEROS=$(git ls-tree --name-only "$RAMA")
for necesario in index.html assets .htaccess; do
  echo "$FICHEROS" | grep -qx "$necesario" || { echo "ERROR: falta $necesario en la raiz de $RAMA"; exit 1; }
done
echo "$FICHEROS" | sed 's/^/    /'

echo "==> Empujando (esto publica)"
for intento in 1 2 3 4; do
  if git push --force origin "$RAMA:$RAMA"; then break; fi
  espera=$((2 ** intento)); echo "    fallo de red, reintento en ${espera}s"; sleep "$espera"
done

echo
echo "Publicado. Si Hostinger tiene el despliegue automatico activado, la web"
echo "se actualiza sola en un minuto. Si no, hay que darle a Deploy en hPanel."

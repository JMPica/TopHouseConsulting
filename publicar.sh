#!/usr/bin/env bash
# =====================================================================
# Publicar la web
# =====================================================================
#
#     ./publicar.sh
#
# Genera los tres idiomas y publica. Hostinger escucha la rama
# 'deploy-web', asi que empujarla ES publicar.
#
# COMO ESTA MONTADO
#   tophouse/   la fuente, en castellano. Aqui se trabaja.
#   idiomas/    las tablas de traduccion a catalan e ingles.
#   web/        lo que se publica. LO GENERA EL SCRIPT: no se toca a
#               mano, porque en la siguiente publicacion se sobrescribe.
#
# El script SIEMPRE regenera antes de publicar. Asi no puede ocurrir que
# se publique un web/ viejo mientras la fuente ya decia otra cosa.
#
# La rama deploy-web se reescribe entera en cada publicacion. Es una rama
# generada, no un sitio donde trabajar: nunca hagas commits en ella.
# =====================================================================
set -euo pipefail

ORIGEN=web
RAMA=deploy-web

cd "$(dirname "$0")"

# Publicar con cambios sin guardar pondria en el dominio una version que
# no existe en ningun commit y que no habria forma de reproducir despues.
if [ -n "$(git status --porcelain -- tophouse idiomas construir.py)" ]; then
  echo "ERROR: hay cambios sin guardar en la fuente."
  echo "Haz commit antes de publicar, o no habra forma de saber que se publico."
  git status --short -- tophouse idiomas construir.py
  exit 1
fi

echo "==> Generando los tres idiomas"
python3 construir.py

# Ningun fichero a medias sale al dominio. Los huecos pendientes se marcan
# con data-falta. Esta comprobacion existe porque ya publique una vez, sin
# querer, un aviso legal con nueve huecos en rojo a la vista de cualquiera.
PENDIENTES=$(grep -rl --include="*.html" "data-falta" "$ORIGEN" 2>/dev/null || true)
if [ -n "$PENDIENTES" ]; then
  echo "ERROR: hay ficheros con huecos sin rellenar:"
  echo "$PENDIENTES" | sed 's/^/    /'
  exit 1
fi

echo "==> Revisando enlaces, datos estructurados, hreflang y sitemap"
# Si algo de esto falla, la web publicada tendria un enlace roto o datos
# que Google no puede leer. Ver pruebas/revisar_web.py.
python3 pruebas/revisar_web.py "$ORIGEN" || { echo "ERROR: la revision ha encontrado problemas. No se publica."; exit 1; }

# Un php con un error de sintaxis tumba la pagina entera en el servidor.
# Se comprueban todos antes de publicar (si hay php en esta maquina).
if command -v php >/dev/null 2>&1; then
  echo "==> Comprobando la sintaxis de los php"
  for f in $(find "$ORIGEN" -name "*.php"); do
    php -l "$f" >/dev/null 2>&1 || { echo "ERROR: $f tiene un error de sintaxis:"; php -l "$f"; exit 1; }
  done
fi

echo "==> Comprobando que estan las tres portadas y los recursos"
for necesario in index.html es/index.html en/index.html llista.php api/textos-llista.json llms.txt vendre.html es/gestion-alquileres.html api/evento.php assets/medida.js assets/site.css assets/hero-scrub.mp4 assets/hero-mobil/f-001.webp api/cartera.php .htaccess immoble.php es/inmueble.php en/property.php assets/fitxa.js assets/extras.js sitemap-immobles.php; do
  [ -e "$ORIGEN/$necesario" ] || { echo "ERROR: falta $necesario en $ORIGEN/"; exit 1; }
done

echo "==> Empujando (esto publica)"
# web/ esta en .gitignore, asi que no puede ir en un commit normal. En vez
# de cambiar de rama y arriesgarse a dejar el arbol de trabajo a medias,
# se construye el commit directamente a partir del contenido de web/ y se
# empuja. La rama de trabajo no se toca en ningun momento.
git add -f "$ORIGEN" >/dev/null
ARBOL=$(git rev-parse "$(git write-tree)":"$ORIGEN")
git reset -q                                   # dejar el indice como estaba
COMMIT=$(git commit-tree "$ARBOL" -m "Publicacion $(date -u '+%Y-%m-%d %H:%M') UTC")

for intento in 1 2 3 4; do
  if git push --force origin "$COMMIT:refs/heads/$RAMA"; then break; fi
  espera=$((2 ** intento)); echo "    fallo de red, reintento en ${espera}s"; sleep "$espera"
done

echo
echo "Publicado. La web se actualiza sola en un minuto."

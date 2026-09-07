#!/usr/bin/env python3
"""
Genera la web en los tres idiomas a partir de la fuente en castellano.

    python3 construir.py

QUE HACE
    Lee tophouse/ (la fuente, en castellano) y escribe web/ con:

        web/            -> catalan, que es el idioma principal
        web/es/         -> castellano
        web/en/         -> ingles
        web/assets/     -> los recursos, compartidos por los tres

COMO TRADUCE
    Con las tablas de idiomas/ca.json y idiomas/en.json, que van de la
    frase en castellano a su traduccion. No hay claves inventadas: la
    clave ES la frase, asi que la fuente se sigue leyendo y el castellano
    no necesita tabla.

POR QUE UN GENERADOR Y NO DOCE FICHEROS A MANO
    Porque a la primera correccion de copy, doce ficheros se
    desincronizan. Aqui se corrige la fuente, se regenera, y los tres
    idiomas van a la vez.

LA COMPROBACION QUE IMPORTA
    Al terminar, revisa la salida catalana e inglesa buscando frases de la
    tabla que se hayan quedado sin traducir. Si encuentra alguna, NO
    escribe nada y dice cuales son. Una web medio traducida da peor
    impresion que una que no lo esta.
"""
import json, os, re, shutil, sys, html

RAIZ    = os.path.dirname(os.path.abspath(__file__))
FUENTE  = os.path.join(RAIZ, 'tophouse')
SALIDA  = os.path.join(RAIZ, 'web')
TABLAS  = os.path.join(RAIZ, 'idiomas')

PAGINAS = ['index.html', 'comprar.html', 'alquilar.html']

# Como se llama cada pagina en cada idioma. La clave es el nombre en la
# fuente. Los enlaces entre paginas se reescriben con esto.
NOMBRES = {
    'ca': {'index.html':'index.html', 'comprar.html':'comprar.html', 'alquilar.html':'llogar.html'},
    'es': {'index.html':'index.html', 'comprar.html':'comprar.html', 'alquilar.html':'alquilar.html'},
    'en': {'index.html':'index.html', 'comprar.html':'buy.html',     'alquilar.html':'rent.html'},
}
CARPETA = {'ca':'', 'es':'es', 'en':'en'}
CODIGO  = {'ca':'ca', 'es':'es', 'en':'en'}
LOCALE  = {'ca':'ca_ES', 'es':'es_ES', 'en':'en_GB'}
DOMINIO = 'https://www.tophouserealestate.es'


def cargar_tabla(idioma):
    if idioma == 'es':
        return {}
    with open(os.path.join(TABLAS, idioma + '.json'), encoding='utf-8') as f:
        return json.load(f)


def traducir(texto, tabla):
    """Traduce en UNA sola pasada, de izquierda a derecha.

    Hacerlo frase a frase corrompe el texto ya traducido. Ejemplo real:
    si primero se traduce la frase larga y el catalan resultante contiene
    "Pisos", la sustitucion posterior de "Piso" por "Pis" la deja en
    "Piss". Con una pasada unica, cada trozo se traduce una vez y lo ya
    traducido no se vuelve a mirar.

    Las claves van de la mas larga a la mas corta porque la alternancia de
    expresiones regulares se queda con la primera que encaja, y queremos
    que sea siempre la frase mas larga.
    """
    if not tabla:
        return texto
    equivale = {}
    for es, otro in tabla.items():
        equivale[es] = otro
        esc = html.escape(es, quote=True)
        if esc != es:
            equivale[esc] = html.escape(otro, quote=True)
    claves = sorted(equivale, key=len, reverse=True)
    patron = re.compile('|'.join(re.escape(k) for k in claves))
    return patron.sub(lambda m: equivale[m.group(0)], texto)


def visibles(html_texto):
    """Las frases que un visitante llega a leer: texto entre etiquetas y los
    atributos que se ven o se oyen."""
    x = re.sub(r'<(script|style|svg)\b.*?</\1>', '', html_texto, flags=re.S)
    x = re.sub(r'<!--.*?-->', '', x, flags=re.S)
    fuera = set()
    for m in re.finditer(r'>([^<>]+)<', x):
        s = html.unescape(m.group(1)).strip()
        if s:
            fuera.add(s)
    for atributo in ('alt', 'title', 'placeholder', 'aria-label', 'content'):
        for m in re.finditer(atributo + r'="([^"]*)"', x):
            s = html.unescape(m.group(1)).strip()
            if s and not s.startswith(('http', '/assets', 'width=')):
                fuera.add(s)
    return fuera


def castellano_suelto(original, traducido, idioma, pagina):
    """Busca frases de la FUENTE que sigan tal cual en la salida.

    La comprobacion anterior preguntaba al reves: miraba si quedaban claves
    de la tabla sin aplicar. Eso no sirve para el caso que de verdad pasa,
    que es anadir copy nuevo y olvidarse de traducirlo: si la frase no esta
    en la tabla, no hay clave que buscar, y se publica en castellano sin
    que nadie se entere. Ya paso: cuatro bandas nuevas del video salieron
    en castellano en las paginas catalana e inglesa, y el generador dijo
    que todo estaba bien.

    Ahora se mira la fuente: cada frase visible tiene que haberse
    traducido, o estar declarada en no-traducir.json.
    """
    with open(os.path.join(TABLAS, 'no-traducir.json'), encoding='utf-8') as f:
        exentas = set(json.load(f))
    fallos = []
    for frase in visibles(original):
        if len(frase) < 8 or frase in exentas:
            continue
        if not re.search(r'[a-záéíóúñü]', frase, re.I):
            continue
        if frase in traducido:
            fallos.append('%s/%s: sin traducir -> "%s"' % (idioma, pagina, frase[:66]))
    return fallos


def enlaces(texto, idioma):
    for origen, destino in NOMBRES[idioma].items():
        if origen != destino:
            texto = texto.replace('"' + origen, '"' + destino)
            texto = texto.replace('"/' + origen, '"/' + destino)
    return texto


def cabecera_idiomas(pagina, idioma):
    """Las etiquetas hreflang: le dicen a Google que estas tres paginas son
    la misma en tres idiomas, y cual servir a cada visitante. Sin esto,
    Google puede tomarlas por contenido duplicado."""
    fuera = []
    for otro in ('ca', 'es', 'en'):
        carpeta = CARPETA[otro]
        ruta = ('/' + carpeta + '/' if carpeta else '/') + NOMBRES[otro][pagina]
        ruta = ruta.replace('/index.html', '/')
        fuera.append('<link rel="alternate" hreflang="%s" href="%s%s">' % (CODIGO[otro], DOMINIO, ruta))
    # x-default: a quien no encaje en ninguno, el catalan, que es el principal
    ruta_ca = '/' + NOMBRES['ca'][pagina]
    ruta_ca = ruta_ca.replace('/index.html', '/')
    fuera.append('<link rel="alternate" hreflang="x-default" href="%s%s">' % (DOMINIO, ruta_ca))
    return '\n'.join(fuera)


def selector(pagina, idioma):
    """El selector de idioma de la barra de arriba."""
    partes = []
    for otro in ('ca', 'es', 'en'):
        carpeta = CARPETA[otro]
        ruta = ('/' + carpeta + '/' if carpeta else '/') + NOMBRES[otro][pagina]
        ruta = ruta.replace('/index.html', '/')
        etiqueta = {'ca':'CA', 'es':'ES', 'en':'EN'}[otro]
        nombre   = {'ca':'Català', 'es':'Castellano', 'en':'English'}[otro]
        if otro == idioma:
            partes.append('<span class="lang__on" aria-current="true"><abbr title="%s">%s</abbr></span>' % (nombre, etiqueta))
        else:
            partes.append('<a href="%s" hreflang="%s" lang="%s"><abbr title="%s">%s</abbr></a>'
                          % (ruta, CODIGO[otro], CODIGO[otro], nombre, etiqueta))
    return '<div class="lang" role="group" aria-label="Idioma">' + ''.join(partes) + '</div>'


def canonica(pagina, idioma):
    carpeta = CARPETA[idioma]
    ruta = ('/' + carpeta + '/' if carpeta else '/') + NOMBRES[idioma][pagina]
    return DOMINIO + ruta.replace('/index.html', '/')


def construir():
    problemas = []
    generado = {}

    for idioma in ('ca', 'es', 'en'):
        tabla = cargar_tabla(idioma)
        for pagina in PAGINAS:
            with open(os.path.join(FUENTE, pagina), encoding='utf-8') as f:
                t = f.read()

            original = t
            t = traducir(t, tabla)
            t = enlaces(t, idioma)
            t = t.replace('<html lang="es">', '<html lang="%s">' % CODIGO[idioma])
            t = re.sub(r'<meta property="og:locale" content="[^"]*">',
                       '<meta property="og:locale" content="%s">' % LOCALE[idioma], t)
            t = re.sub(r'<link rel="canonical" href="[^"]*">',
                       '<link rel="canonical" href="%s">\n%s' % (canonica(pagina, idioma),
                                                                 cabecera_idiomas(pagina, idioma)), t)
            t = re.sub(r'<meta property="og:url" content="[^"]*">',
                       '<meta property="og:url" content="%s">' % canonica(pagina, idioma), t)
            # el selector va justo antes del telefono de la barra
            # dentro de nav__actions, no suelto en la barra: como cuarto hijo
            # de un flex con space-between quedaba flotando en medio, sin
            # nada que lo anclara y encima del cielo del video, ilegible.
            t = t.replace('<div class="nav__actions">',
                          '<div class="nav__actions">\n    ' + selector(pagina, idioma), 1)

            if idioma != 'es':
                problemas += castellano_suelto(original, t, idioma, pagina)

            destino = os.path.join(SALIDA, CARPETA[idioma], NOMBRES[idioma][pagina])
            generado[destino] = t

    if problemas:
        print('NO SE HA GENERADO NADA. Frases sin traducir:\n')
        for p in problemas[:25]:
            print('   ' + p)
        if len(problemas) > 25:
            print('   ... y %d mas' % (len(problemas) - 25))
        return 1

    if os.path.isdir(SALIDA):
        shutil.rmtree(SALIDA)
    for destino, contenido in generado.items():
        os.makedirs(os.path.dirname(destino), exist_ok=True)
        with open(destino, 'w', encoding='utf-8') as f:
            f.write(contenido)

    # los recursos son los mismos para los tres idiomas
    shutil.copytree(os.path.join(FUENTE, 'assets'), os.path.join(SALIDA, 'assets'))
    for suelto in ('.htaccess', 'robots.txt'):
        origen = os.path.join(FUENTE, suelto)
        if os.path.exists(origen):
            shutil.copy2(origen, os.path.join(SALIDA, suelto))

    escribir_sitemap()
    print('Generado web/ con %d paginas en tres idiomas.' % len(generado))
    return 0


def escribir_sitemap():
    """Un sitemap con las tres versiones de cada pagina enlazadas entre si."""
    filas = ['<?xml version="1.0" encoding="UTF-8"?>',
             '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
             '        xmlns:xhtml="http://www.w3.org/1999/xhtml">']
    for pagina in PAGINAS:
        for idioma in ('ca', 'es', 'en'):
            filas.append('  <url>')
            filas.append('    <loc>%s</loc>' % canonica(pagina, idioma))
            for otro in ('ca', 'es', 'en'):
                filas.append('    <xhtml:link rel="alternate" hreflang="%s" href="%s"/>'
                             % (CODIGO[otro], canonica(pagina, otro)))
            filas.append('    <lastmod>2026-09-07</lastmod>')
            filas.append('    <priority>%s</priority>' % ('1.0' if pagina == 'index.html' else '0.8'))
            filas.append('  </url>')
    filas.append('</urlset>')
    with open(os.path.join(SALIDA, 'sitemap.xml'), 'w', encoding='utf-8') as f:
        f.write('\n'.join(filas) + '\n')


if __name__ == '__main__':
    sys.exit(construir())

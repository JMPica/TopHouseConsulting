#!/usr/bin/env python3
"""
Revision automatica de la web generada (web/), antes de publicar.

    python3 pruebas/revisar_web.py          # revisa web/
    python3 pruebas/revisar_web.py otra/    # revisa otra carpeta

La llama publicar.sh despues de construir. Si encuentra un ERROR, no se
publica. Los AVISOS se ensenan pero no paran nada.

ERROR (rompe algo de verdad):
  - un enlace interno a una pagina que no existe, o relativo
  - un bloque JSON-LD que no se puede leer
  - una pagina indexable sin los datos de la empresa (RealEstateAgent)
  - una pregunta marcada como FAQ que no esta escrita en la pagina
  - un hreflang que apunta a una pagina que no existe o que no lo devuelve
  - una URL del sitemap que no existe
  - una pagina sin title, sin description o sin canonical
AVISO (empeora, pero no rompe):
  - titulo de mas de 65 caracteres o descripcion de mas de 160
  - titulo o descripcion repetidos entre paginas
"""
import glob, html, json, os, re, sys

RAIZ = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'web')
DOMINIO = 'https://www.tophouserealestate.es'
FICHAS = re.compile(r'^/(immoble|es/inmueble|en/property|vaixell|es/barco|en/boat)/')


def ruta_fichero(url_o_ruta):
    r = url_o_ruta.replace(DOMINIO, '').split('#')[0].split('?')[0]
    r = r.lstrip('/')
    if r == '' or r.endswith('/'):
        r += 'index.html'
    return os.path.join(RAIZ, r)


def main():
    errores, avisos = [], []
    paginas = sorted(glob.glob(os.path.join(RAIZ, '*.html')) + glob.glob(os.path.join(RAIZ, 'es', '*.html'))
                     + glob.glob(os.path.join(RAIZ, 'en', '*.html')))
    if not paginas:
        print('ERROR: no hay paginas en', RAIZ)
        return 1
    titulos, descs = {}, {}
    for f in paginas:
        nombre = os.path.relpath(f, RAIZ)
        t = open(f, encoding='utf-8').read()
        ti = re.search(r'<title>(.*?)</title>', t, re.S)
        de = re.search(r'<meta name="description" content="([^"]*)"', t)
        ca = re.search(r'<link rel="canonical" href="([^"]+)"', t)
        if not (ti and de and ca):
            errores.append(f'{nombre}: falta title, description o canonical')
            continue
        indexable = 'noindex' not in t[:4000]
        if indexable:
            titulos.setdefault(ti.group(1), []).append(nombre)
            descs.setdefault(de.group(1), []).append(nombre)
            if len(html.unescape(ti.group(1))) > 65:
                avisos.append(f'{nombre}: titulo de {len(html.unescape(ti.group(1)))} caracteres')
            if len(html.unescape(de.group(1))) > 160:
                avisos.append(f'{nombre}: descripcion de {len(html.unescape(de.group(1)))} caracteres')

        for h in re.findall(r'href="([^"]+)"', t):
            if h.startswith(('http:', 'https:', 'mailto:', 'tel:', '#', '//')):
                continue
            if not h.startswith('/'):
                errores.append(f'{nombre}: enlace relativo {h}')
            elif FICHAS.match(h) or h.startswith('/api/'):
                continue
            elif not os.path.exists(ruta_fichero(h)):
                errores.append(f'{nombre}: enlace roto {h}')

        tipos = []
        for bloque in re.findall(r'<script type="application/ld\+json">(.*?)</script>', t, re.S):
            try:
                j = json.loads(bloque)
            except ValueError as e:
                errores.append(f'{nombre}: JSON-LD ilegible ({e})')
                continue
            for n in j.get('@graph', [j]):
                tipos.append(n.get('@type'))
                if n.get('@type') == 'FAQPage':
                    visible = html.unescape(re.sub(r'<[^>]+>', ' ', t))
                    visible = re.sub(r'\s+', ' ', visible)
                    for q in n.get('mainEntity', []):
                        if q['name'] not in visible:
                            errores.append(f'{nombre}: pregunta marcada que no se ve: {q["name"]}')
        if indexable and 'RealEstateAgent' not in tipos:
            errores.append(f'{nombre}: sin datos de la empresa (RealEstateAgent)')

        for lang, url in re.findall(r'<link rel="alternate" hreflang="([^"]+)" href="([^"]+)"', t):
            destino = ruta_fichero(url)
            if not os.path.exists(destino):
                errores.append(f'{nombre}: hreflang {lang} a una pagina que no existe ({url})')
            elif lang != 'x-default' and ca.group(1) not in open(destino, encoding='utf-8').read():
                errores.append(f'{nombre}: {url} no le devuelve el hreflang')

    for k, v in titulos.items():
        if len(v) > 1:
            avisos.append(f'titulo repetido: {", ".join(v)}')
    for k, v in descs.items():
        if len(v) > 1:
            avisos.append(f'descripcion repetida: {", ".join(v)}')

    sm = os.path.join(RAIZ, 'sitemap.xml')
    if os.path.exists(sm):
        for loc in re.findall(r'<loc>(.*?)</loc>', open(sm, encoding='utf-8').read()):
            if not os.path.exists(ruta_fichero(loc)):
                errores.append(f'sitemap: {loc} no existe')
    else:
        errores.append('falta sitemap.xml')

    for a in avisos:
        print('AVISO  ', a)
    for e in errores:
        print('ERROR  ', e)
    print(f'{len(paginas)} paginas revisadas: {len(errores)} errores, {len(avisos)} avisos.')
    return 1 if errores else 0


if __name__ == '__main__':
    sys.exit(main())

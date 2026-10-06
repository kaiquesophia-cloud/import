#!/usr/bin/env python3
"""Gera as páginas de SEO da Veloce a partir de paginas_dados.py.

Cada página vira um HTML em paginas/<slug>.html no mesmo molde da /oficina-bmw/ (visual do
kit Elementor do site, dados estruturados, FAQ). A /oficina-bmw/ foi feita à mão e não é gerada aqui.

  python3 seo/veloce-auto-specialista/gerar_paginas.py            # gera todas
  python3 seo/veloce-auto-specialista/gerar_paginas.py remap      # gera só uma
"""
import html
import json
import os
import re
import sys
import urllib.parse

sys.path.insert(0, os.path.dirname(__file__))
from paginas_dados import PAGINAS  # noqa: E402

SITE = "https://veloceautospecialista.com.br"
WHATSAPP = "5511995552969"
TELEFONE = "+55-11-99555-2969"
ENDERECO = "Av. Casa Verde, 3010 – Casa Verde, São Paulo – SP, 02520-300"
HORARIO = "Seg–Sex 8h às 18h · Sáb 8h às 12h"
MARCAS = ["BMW", "MINI", "Audi", "Mercedes-Benz", "Porsche", "Land Rover", "Jaguar", "Volvo", "Lamborghini"]

CSS = """<style>
/* Paleta e fontes do kit Elementor do site: laranja #D16527, escuros #121212/#161616/#242424, cinzas #EDEDED/#C6C6C6 */
.vlc{--acc:#D16527;--bg1:#121212;--bg2:#161616;--card:#242424;--line:#333;--txt:#C6C6C6;--head:#FFFFFF;
  font-family:"Mulish",sans-serif;color:var(--txt);line-height:1.65;font-size:17px;background:var(--bg1)}
.vlc *{box-sizing:border-box}
.vlc section{padding:64px 20px;background:var(--bg1)}
.vlc section.alt-bg{background:var(--bg2)}
.vlc .wrap{max-width:1140px;margin:0 auto}
.vlc h1,.vlc h2,.vlc h3{font-family:"Chakra Petch",sans-serif!important;color:var(--head)!important;margin:0 0 14px!important}
.vlc h1{font-size:clamp(30px,4.4vw,48px)!important;line-height:1.12!important}
.vlc h2{font-size:clamp(24px,3vw,34px)!important;line-height:1.2!important}
.vlc h3{font-size:19px!important;line-height:1.3!important;text-transform:none!important}
.vlc h2:after{content:"";display:block;width:56px;height:3px;background:var(--acc);margin-top:12px}
.vlc p,.vlc li,.vlc summary,.vlc td,.vlc th{color:var(--txt)!important}
.vlc p{margin:0 0 14px}
.vlc strong{color:var(--head)!important}
.vlc a{color:var(--acc)!important}
.vlc .hero{padding:88px 20px 80px;background:linear-gradient(180deg,#161616,#121212)}
.vlc .hero p{font-size:19px;max-width:740px}
.vlc .addr{font-size:15px;margin-top:18px}
.vlc .btn{display:inline-block;background:var(--acc);color:#fff!important;font-family:"Chakra Petch",sans-serif;font-weight:700;
  text-transform:uppercase;letter-spacing:.03em;padding:15px 26px;border-radius:4px;text-decoration:none;margin:10px 10px 0 0}
.vlc .btn.alt{background:transparent;border:1px solid var(--acc)}
.vlc .grid{display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
.vlc .card{background:var(--card);border:1px solid var(--line);border-top:3px solid var(--acc);border-radius:6px;padding:24px}
.vlc .card p{font-size:16px;margin:0}
.vlc .chips{display:flex;flex-wrap:wrap;gap:10px;padding:0;margin:0 0 16px;list-style:none}
.vlc .chips li{background:var(--card);border:1px solid var(--line);border-radius:999px;padding:7px 14px;font-size:15px;margin:0}
.vlc .chips li a{text-decoration:none}
.vlc .steps{counter-reset:s;list-style:none;padding:0;margin:0}
.vlc .steps li{counter-increment:s;position:relative;padding:4px 0 20px 54px;margin:0}
.vlc .steps li:before{content:counter(s);position:absolute;left:0;top:0;width:36px;height:36px;border-radius:50%;
  background:var(--acc);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-family:"Chakra Petch",sans-serif}
.vlc .check{list-style:none;padding:0;margin:0;columns:2 280px;column-gap:32px}
.vlc .check li{position:relative;padding:0 0 10px 26px;break-inside:avoid;margin:0}
.vlc .check li:before{content:"✓";position:absolute;left:0;color:var(--acc);font-weight:700}
.vlc table{width:100%;border-collapse:collapse;margin:8px 0 16px;font-size:16px}
.vlc th,.vlc td{border:1px solid var(--line);padding:12px 14px;text-align:left;vertical-align:top}
.vlc th{background:var(--card);color:var(--head)!important;font-family:"Chakra Petch",sans-serif}
.vlc .tbl{overflow-x:auto}
.vlc details{background:var(--card);border:1px solid var(--line);border-radius:6px;padding:16px 20px;margin-bottom:12px}
.vlc summary{font-weight:700;cursor:pointer;color:var(--head)!important}
.vlc details p{margin:12px 0 0}
.vlc .cta{background:var(--bg2)}
.vlc .map{width:100%;height:320px;border:0;border-radius:6px;margin-top:22px}
@media (max-width:600px){.vlc section{padding:48px 16px}.vlc .btn{display:block;text-align:center;margin-right:0}}
</style>"""


def wa(msg):
    return f"https://wa.me/{WHATSAPP}?text={urllib.parse.quote(msg)}"


def texto(s):
    """Texto dos dados: aceita <a>, <strong> e <em>; o resto já vem escrito como HTML seguro."""
    return s


def bloco(sec, i):
    cls = ' class="alt-bg"' if i % 2 else ""
    out = [f"<section{cls}>", '  <div class="wrap">', f"    <h2>{sec['h2']}</h2>"]
    for p in sec.get("p", []):
        out.append(f"    <p>{texto(p)}</p>")
    if "cards" in sec:
        out.append('    <div class="grid">')
        for t, d in sec["cards"]:
            out.append(f'      <div class="card"><h3>{t}</h3>\n        <p>{texto(d)}</p></div>')
        out.append("    </div>")
    if "chips" in sec:
        out.append('    <ul class="chips">' + "".join(f"<li>{texto(c)}</li>" for c in sec["chips"]) + "</ul>")
    if "check" in sec:
        out.append('    <ul class="check">' + "".join(f"<li>{texto(c)}</li>" for c in sec["check"]) + "</ul>")
    if "steps" in sec:
        out.append('    <ol class="steps">')
        for t, d in sec["steps"]:
            out.append(f"      <li><strong>{t}</strong> {texto(d)}</li>")
        out.append("    </ol>")
    if "table" in sec:
        cab, *linhas = sec["table"]
        out.append('    <div class="tbl"><table><thead><tr>' + "".join(f"<th>{c}</th>" for c in cab) + "</tr></thead><tbody>")
        for l in linhas:
            out.append("      <tr>" + "".join(f"<td>{texto(c)}</td>" for c in l) + "</tr>")
        out.append("    </tbody></table></div>")
    for p in sec.get("p_depois", []):
        out.append(f"    <p>{texto(p)}</p>")
    out += ["  </div>", "</section>", ""]
    return "\n".join(out)


def sem_tags(s):
    return html.unescape(re.sub(r"<[^>]+>", "", s))


def schema(pg):
    url = f"{SITE}/{pg['slug']}/"
    return {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "AutoRepair",
                "@id": f"{SITE}/#oficina",
                "name": "Veloce Auto Specialista",
                "url": f"{SITE}/",
                "telephone": TELEFONE,
                "openingHours": ["Mo-Fr 08:00-18:00", "Sa 08:00-12:00"],
                "address": {"@type": "PostalAddress", "streetAddress": "Av. Casa Verde, 3010",
                            "addressLocality": "São Paulo", "addressRegion": "SP",
                            "postalCode": "02520-300", "addressCountry": "BR"},
                "areaServed": ["São Paulo", "Zona Norte de São Paulo"],
                "brand": [{"@type": "Brand", "name": m} for m in MARCAS],
            },
            {"@type": "Service", "name": pg["servico"], "serviceType": pg["servico_tipo"],
             "provider": {"@id": f"{SITE}/#oficina"}, "areaServed": "São Paulo", "url": url},
            {"@type": "BreadcrumbList", "itemListElement": [
                {"@type": "ListItem", "position": 1, "name": "Início", "item": f"{SITE}/"},
                {"@type": "ListItem", "position": 2, "name": pg["breadcrumb"], "item": url}]},
            {"@type": "FAQPage", "mainEntity": [
                {"@type": "Question", "name": q,
                 "acceptedAnswer": {"@type": "Answer", "text": sem_tags(a)}} for q, a in pg["faq"]]},
        ],
    }


def gerar(pg):
    zap = wa(pg["whatsapp_msg"])
    partes = [
        "<!--",
        f"  PÁGINA: /{pg['slug']}/  —  Veloce Auto Specialista (gerada por gerar_paginas.py; edite paginas_dados.py)",
        f"  Title SEO: {pg['title']}",
        f"  Meta description: {pg['description']}",
        "-->",
        CSS,
        "",
        '<div class="vlc">',
        "",
        '<section class="hero">',
        '  <div class="wrap">',
        f"    <h1>{pg['h1']}</h1>",
        f"    <p>{texto(pg['lead'])}</p>",
        f'    <a class="btn" href="{html.escape(zap)}" rel="nofollow">{pg["botao"]}</a>',
        '    <a class="btn alt" href="#como-chegar">Como chegar</a>',
        "    <p class=\"addr\">📍 Av. Casa Verde, 3010 – Casa Verde, Zona Norte de São Paulo</p>",
        "  </div>",
        "</section>",
        "",
    ]
    for i, sec in enumerate(pg["secoes"]):
        partes.append(bloco(sec, i))
    faq_cls = ' class="alt-bg"' if len(pg["secoes"]) % 2 else ""
    partes += [f"<section{faq_cls}>", '  <div class="wrap">', f"    <h2>{pg['faq_h2']}</h2>"]
    for q, a in pg["faq"]:
        partes.append(f"    <details><summary>{q}</summary>\n      <p>{texto(a)}</p></details>")
    partes += ["  </div>", "</section>", "",
               '<section class="cta" id="como-chegar">', '  <div class="wrap">',
               f"    <h2>{pg['cta_h2']}</h2>", f"    <p>{pg['cta_p']}</p>",
               f'    <a class="btn" href="{html.escape(zap)}" rel="nofollow">Chamar no WhatsApp</a>',
               '    <a class="btn alt" href="tel:+5511995552969">Ligar (11) 99555-2969</a>',
               f'    <p class="addr">📍 {ENDERECO}<br>🕒 {HORARIO}</p>',
               '    <iframe class="map" loading="lazy" title="Mapa da Veloce Auto Specialista"',
               '      src="https://www.google.com/maps?q=Av.+Casa+Verde,+3010+-+Casa+Verde,+S%C3%A3o+Paulo+-+SP,+02520-300&output=embed"></iframe>',
               "  </div>", "</section>", "", "</div>", "",
               "<!-- DADOS ESTRUTURADOS (o Google lê; o visitante não vê) -->",
               '<script type="application/ld+json">',
               json.dumps(schema(pg), ensure_ascii=False, indent=2),
               "</script>", ""]
    return "\n".join(partes)


def checar(pg, out):
    erros = []
    if len(pg["title"]) > 60:
        erros.append(f"title com {len(pg['title'])} caracteres")
    if len(pg["description"]) > 160:
        erros.append(f"description com {len(pg['description'])} caracteres")
    if out.count("<h1") != 1:
        erros.append("H1 duplicado")
    corpo = out[out.index('<div class="vlc">'):out.index("<!-- DADOS")]
    palavras = len(sem_tags(corpo).split())
    if palavras < 700:
        erros.append(f"só {palavras} palavras")
    return palavras, erros


def main():
    alvo = set(sys.argv[1:])
    pasta = os.path.join(os.path.dirname(__file__), "paginas")
    for pg in PAGINAS:
        if alvo and pg["slug"] not in alvo:
            continue
        out = gerar(pg)
        palavras, erros = checar(pg, out)
        with open(os.path.join(pasta, f"{pg['slug']}.html"), "w", encoding="utf-8") as f:
            f.write(out)
        print(f"{pg['slug']:34} {palavras:5} palavras  title {len(pg['title']):2}  desc {len(pg['description']):3}"
              + (f"  ⚠️ {'; '.join(erros)}" if erros else ""))


if __name__ == "__main__":
    main()

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
.vlc .fotos{display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin:8px 0 16px}
.vlc .fotos figure{margin:0}
.vlc .fotos img{width:100%;height:auto;aspect-ratio:3/4;object-fit:cover;border-radius:6px;border:1px solid var(--line);display:block}
.vlc .fotos figcaption{font-size:14px;color:var(--txt);margin-top:8px}
.vlc .map{width:100%;height:320px;border:0;border-radius:6px;margin-top:22px}
@media (max-width:600px){.vlc section{padding:48px 16px}.vlc .btn{display:block;text-align:center;margin-right:0}}
/* ---------- efeitos e interatividade ---------- */
.vlc .hero{position:relative;overflow:hidden;isolation:isolate;min-height:560px;display:flex;align-items:center}
.vlc .hero .wrap{position:relative;z-index:2;width:100%}
.vlc .hero-bg{position:absolute;inset:0;z-index:0;width:100%;height:100%;object-fit:cover;object-position:center;
  transform:scale(1.08);animation:vlcZoom 18s ease-out forwards}
.vlc .hero:before{content:"";position:absolute;inset:0;z-index:1;
  background:linear-gradient(90deg,rgba(18,18,18,.96) 0%,rgba(18,18,18,.82) 45%,rgba(18,18,18,.35) 100%),
             linear-gradient(0deg,#121212 0%,rgba(18,18,18,0) 35%)}
.vlc .hero h1,.vlc .hero p,.vlc .hero .btn{animation:vlcUp .8s cubic-bezier(.2,.7,.2,1) both}
.vlc .hero p{animation-delay:.12s}.vlc .hero .btn{animation-delay:.24s}.vlc .hero .addr{animation-delay:.32s}
.vlc .kicker{display:inline-block;font-family:"Chakra Petch",sans-serif;font-size:13px;letter-spacing:.18em;text-transform:uppercase;
  color:var(--acc)!important;border:1px solid rgba(209,101,39,.45);padding:6px 12px;border-radius:999px;margin-bottom:18px;
  animation:vlcUp .8s cubic-bezier(.2,.7,.2,1) both}
@keyframes vlcZoom{to{transform:scale(1)}}
@keyframes vlcUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:none}}
@keyframes vlcPulse{0%{box-shadow:0 0 0 0 rgba(209,101,39,.55)}70%{box-shadow:0 0 0 14px rgba(209,101,39,0)}100%{box-shadow:0 0 0 0 rgba(209,101,39,0)}}
.vlc .btn{position:relative;overflow:hidden;transition:transform .25s,box-shadow .25s,background .25s}
.vlc .btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(209,101,39,.35)}
.vlc .btn:after{content:"";position:absolute;top:0;left:-120%;width:60%;height:100%;
  background:linear-gradient(120deg,transparent,rgba(255,255,255,.28),transparent);transition:left .6s}
.vlc .btn:hover:after{left:130%}
.vlc .hero .btn:not(.alt){animation:vlcUp .8s .24s cubic-bezier(.2,.7,.2,1) both,vlcPulse 2.4s 1.6s infinite}
.vlc .btn.alt:hover{background:rgba(209,101,39,.12)}
.vlc .card{transition:transform .35s cubic-bezier(.2,.7,.2,1),box-shadow .35s,border-color .35s;position:relative}
.vlc .card:hover{transform:translateY(-6px);box-shadow:0 18px 40px rgba(0,0,0,.45),0 0 0 1px rgba(209,101,39,.35);border-color:rgba(209,101,39,.5)}
.vlc .card:before{content:"";position:absolute;left:0;top:-3px;height:3px;width:0;background:#fff;transition:width .45s}
.vlc .card:hover:before{width:100%;background:linear-gradient(90deg,var(--acc),#f0a46c)}
.vlc .chips li,.vlc .models li{transition:background .25s,border-color .25s,transform .25s}
.vlc .chips li:hover,.vlc .models li:hover{background:var(--acc);border-color:var(--acc);transform:translateY(-2px)}
.vlc .chips li:hover a,.vlc .chips li:hover{color:#fff!important}
.vlc .fotos figure{overflow:hidden;border-radius:6px}
.vlc .fotos img{transition:transform .7s cubic-bezier(.2,.7,.2,1)}
.vlc .fotos figure:hover img{transform:scale(1.05)}
.vlc tbody tr{transition:background .2s}.vlc tbody tr:hover{background:rgba(209,101,39,.08)}
.vlc details{transition:border-color .3s,background .3s}
.vlc details[open]{border-color:rgba(209,101,39,.55);background:#272727}
.vlc summary{list-style:none;position:relative;padding-right:34px}
.vlc summary::-webkit-details-marker{display:none}
.vlc summary:after{content:"+";position:absolute;right:0;top:50%;transform:translateY(-50%);width:24px;height:24px;border-radius:50%;
  border:1px solid var(--acc);color:var(--acc);display:flex;align-items:center;justify-content:center;font-size:18px;line-height:1;transition:transform .3s,background .3s,color .3s}
.vlc details[open] summary:after{transform:translateY(-50%) rotate(45deg);background:var(--acc);color:#fff}
.vlc details p{animation:vlcUp .35s ease both}
.vlc .steps li:before{transition:transform .5s cubic-bezier(.2,.7,.2,1)}
.vlc h2:after{transition:width .7s cubic-bezier(.2,.7,.2,1)}
/* aparecer ao rolar: só quando o script está ativo (sem script, tudo visível) */
.vlc.js .rv{opacity:0;transform:translateY(28px);transition:opacity .7s cubic-bezier(.2,.7,.2,1),transform .7s cubic-bezier(.2,.7,.2,1)}
.vlc.js .rv.on{opacity:1;transform:none}
.vlc.js h2.rv:after{width:0}.vlc.js h2.rv.on:after{width:56px}
.vlc.js .steps li.rv:before{transform:scale(0)}.vlc.js .steps li.rv.on:before{transform:scale(1)}
@media (max-width:600px){.vlc .hero{min-height:0}.vlc .hero:before{background:linear-gradient(0deg,rgba(18,18,18,.97) 30%,rgba(18,18,18,.75) 100%)}}
@media (prefers-reduced-motion:reduce){.vlc *,.vlc *:before,.vlc *:after{animation:none!important;transition:none!important}
  .vlc.js .rv{opacity:1;transform:none}.vlc .hero-bg{transform:none}}
</style>"""


JS = """<script>
(function(){var r=document.querySelectorAll('.vlc');if(!r.length||!('IntersectionObserver' in window))return;
r.forEach(function(root){root.classList.add('js');
var els=root.querySelectorAll('section:not(.hero) h2, section:not(.hero) .wrap > p, .card, .chips, .check, .steps li, .tbl, details, .fotos figure, .map');
var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('on');io.unobserve(e.target);}});},{rootMargin:'0px 0px -8% 0px'});
els.forEach(function(el){var p=el.parentElement,i=Array.prototype.indexOf.call(p.children,el);
el.classList.add('rv');el.style.transitionDelay=Math.min(i,6)*70+'ms';io.observe(el);});});})();
</script>"""

# Foto real no topo de cada página: (arquivo, alt, largura, altura)
UP = "https://veloceautospecialista.com.br/wp-content/uploads/2026/10/"
FOTO_PADRAO = ("oficina-veloce-carros-premium-zona-norte-sp.webp",
               "Oficina Veloce com Porsche Cayenne e carros premium nos elevadores, na Zona Norte de São Paulo", 1448, 1086)
FOTOS_HERO = {
    "oficina-mini": ("mini-john-cooper-works-oficina-veloce.webp", "MINI John Cooper Works preto na oficina Veloce", 1448, 1086),
    "oficina-land-rover": ("range-rover-evoque-oficina-veloce.webp", "Range Rover Evoque preto com o capô aberto na oficina Veloce", 1024, 1024),
    "oficina-jaguar": ("range-rover-evoque-oficina-veloce.webp", "Range Rover Evoque preto com o capô aberto na oficina Veloce", 1024, 1024),
    "oficina-porsche": ("porsche-911-dinamometro-veloce.webp", "Porsche 911 Carrera Cabriolet no dinamômetro da Veloce", 1200, 1600),
    "remap": ("porsche-911-dinamometro-veloce.webp", "Porsche 911 Carrera Cabriolet no dinamômetro da Veloce", 1200, 1600),
    "dinamometro": ("golf-gti-dinamometro-veloce.webp", "Volkswagen Golf GTI no dinamômetro da Veloce", 1200, 1600),
    "manutencao-preventiva": ("mecanico-veloce-montagem-motor.webp", "Mecânico da Veloce montando um motor na bancada", 739, 1600),
    "inspecao-pre-compra": ("mini-cooper-oficina-veloce-casa-verde.webp", "MINI Cooper na oficina Veloce Auto Specialista, na Casa Verde", 1200, 1600),
}


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
    if "fotos" in sec:
        out.append('    <div class="fotos">')
        for url, alt, w_, h_ in sec["fotos"]:
            out.append(f'      <figure><img src="{url}" alt="{html.escape(alt)}" width="{w_}" height="{h_}" '
                       f'loading="lazy" decoding="async"><figcaption>{html.escape(alt)}</figcaption></figure>')
        out.append("    </div>")
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
                "@id": f"{SITE}/#organization",
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
             "provider": {"@id": f"{SITE}/#organization"}, "areaServed": "São Paulo", "url": url},
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
    foto = FOTOS_HERO.get(pg["slug"], FOTO_PADRAO)
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
        f'  <img class="hero-bg" src="{UP}{foto[0]}" alt="{html.escape(foto[1])}" width="{foto[2]}" height="{foto[3]}" '
        'fetchpriority="high" decoding="async">',
        '  <div class="wrap">',
        f'    <span class="kicker">{pg.get("kicker", "Especialistas em carros premium · Zona Norte SP")}</span>',
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
               JS,
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
    palavras = len(sem_tags(re.sub(r"<script.*?</script>", "", corpo, flags=re.S)).split())
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

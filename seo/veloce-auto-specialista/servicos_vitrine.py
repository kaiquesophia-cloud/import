#!/usr/bin/env python3
"""Seção "Todos os serviços" da /servicos/ (página 205, Elementor) — 17/11/2026.

A página é do Elementor e está no menu: o visual dela fica igual. Esta seção entra como um container novo com um
widget HTML logo depois do bloco principal, e liga a /servicos/ a todas as páginas de serviço, às revisões por
marca e aos artigos do blog. O GA4 mostrou a /servicos/ com visitas e quase nenhum contato.

  python3 seo/veloce-auto-specialista/servicos_vitrine.py html      -> imprime o HTML (prévia)
  python3 seo/veloce-auto-specialista/servicos_vitrine.py publicar  -> grava no Elementor (precisa do "pode" do dono)
"""
import json, os, sys, urllib.parse

WHATSAPP = "5511995552969"
MSG = "Olá, vim pela página de serviços e quero um orçamento. Carro (modelo/ano) e serviço: "

SERVICOS = [
    ("Remap de motor", "Reprogramação feita na própria oficina, com backup do software original e ganho medido no dinamômetro.", "/remap/"),
    ("Remap de câmbio", "Reprogramação da central do câmbio para trocas mais rápidas e para acompanhar um motor com mais torque.", "/remap/cambio/"),
    ("Dinamômetro", "Medição de potência e torque no nosso dinamômetro, com ou sem remap. Fazemos a medição avulsa.", "/dinamometro/"),
    ("Troca de óleo do câmbio automático", "Óleo da especificação da montadora, peças genuínas e leitura da central do câmbio antes e depois.", "/troca-de-oleo-cambio-automatico/"),
    ("Revisão e manutenção preventiva", "Plano da montadora, óleo com a especificação correta, peças genuínas e relatório técnico.", "/manutencao-preventiva/"),
    ("Inspeção pré-compra", "Motor, câmbio, eletrônica e histórico de falhas avaliados antes de você fechar negócio.", "/inspecao-pre-compra/"),
]
REVISOES = [("Revisão BMW", "/oficina-bmw/revisao/"), ("Revisão Land Rover", "/oficina-land-rover/revisao/"),
            ("Revisão Volvo", "/oficina-volvo/revisao/"), ("Revisão Audi", "/oficina-audi/revisao/"),
            ("Revisão Mercedes-Benz", "/oficina-mercedes-benz/revisao/")]
ARTIGOS = [("O que é remap?", "/blog/o-que-e-remap/"), ("Stage 1 e stage 2", "/blog/remap-stage-1-e-stage-2/"),
           ("Quando trocar o óleo do câmbio", "/blog/quando-trocar-oleo-cambio-automatico/"),
           ("Câmbio dando tranco", "/blog/cambio-automatico-dando-tranco/"), ("Todos os artigos", "/blog/")]

CSS = """<style>
.vsv{--acc:#D16527;--bg:#121212;--card:#1C1C1C;--line:#2E2E2E;--txt:#C6C6C6;--head:#fff;background:var(--bg);
  color:var(--txt);font-family:"Mulish",sans-serif;font-size:17px;line-height:1.6;padding:72px 20px}
.vsv *{box-sizing:border-box}
.vsv .w{max-width:1140px;margin:0 auto}
.vsv .eb{font-family:"Chakra Petch",sans-serif;font-size:13px;letter-spacing:.18em;text-transform:uppercase;color:var(--acc);margin:0 0 10px}
.vsv h2{font-family:"Chakra Petch",sans-serif!important;color:var(--head)!important;font-size:clamp(26px,3.2vw,36px)!important;line-height:1.15!important;margin:0 0 12px!important}
.vsv h2:after{content:"";display:block;width:56px;height:3px;background:var(--acc);margin-top:12px}
.vsv h3{font-family:"Chakra Petch",sans-serif!important;color:var(--head)!important;font-size:19px!important;line-height:1.3!important;margin:0 0 8px!important;text-transform:none!important}
.vsv p{color:var(--txt)!important;margin:0 0 14px}
.vsv .lead{max-width:720px;margin-bottom:28px}
.vsv .g{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
.vsv .c{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--line);border-radius:6px;padding:24px;
  text-decoration:none!important;transition:border-color .25s,transform .25s}
.vsv .c:hover{border-color:var(--acc);transform:translateY(-3px)}
.vsv .c p{flex:1;font-size:15.5px}
.vsv .c span{font-family:"Chakra Petch",sans-serif;font-weight:700;font-size:14px;letter-spacing:.06em;text-transform:uppercase;color:var(--acc)}
.vsv .sub{margin-top:44px}
.vsv .chips{display:flex;flex-wrap:wrap;gap:10px;list-style:none;padding:0;margin:12px 0 0}
.vsv .chips a{display:inline-block;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:8px 16px;
  color:var(--txt)!important;font-size:15px;text-decoration:none!important;transition:background .25s,border-color .25s}
.vsv .chips a:hover{background:var(--acc);border-color:var(--acc);color:#fff!important}
.vsv .two{display:grid;grid-template-columns:1fr 1fr;gap:36px}
.vsv .cta{margin-top:48px;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--acc);border-radius:6px;
  padding:26px 28px;display:flex;gap:16px 28px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.vsv .cta div{flex:1 1 360px;min-width:0}
.vsv .cta strong{display:block;font-family:"Chakra Petch",sans-serif;color:var(--head);font-size:21px;margin-bottom:4px}
.vsv .cta p{margin:0}
.vsv .btn{display:inline-block;background:var(--acc);color:#fff!important;font-family:"Chakra Petch",sans-serif;font-weight:700;
  letter-spacing:.06em;text-transform:uppercase;padding:14px 26px;border-radius:4px;text-decoration:none!important;white-space:nowrap;
  transition:transform .25s,box-shadow .25s}
.vsv .btn:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(209,101,39,.35)}
@media (max-width:760px){.vsv{padding:52px 16px}.vsv .two{grid-template-columns:1fr;gap:28px}.vsv .btn{display:block;text-align:center;width:100%;white-space:normal}}
@media (prefers-reduced-motion:reduce){.vsv .c,.vsv .btn,.vsv .chips a{transition:none}}
</style>"""


def html():
    zap = f"https://wa.me/{WHATSAPP}?text={urllib.parse.quote(MSG)}"
    cards = "".join(f'<a class="c" href="{u}"><h3>{t}</h3><p>{d}</p><span>Ver o serviço →</span></a>' for t, d, u in SERVICOS)
    rev = "".join(f'<li><a href="{u}">{t}</a></li>' for t, u in REVISOES)
    art = "".join(f'<li><a href="{u}">{t}</a></li>' for t, u in ARTIGOS)
    return (CSS + '<section class="vsv" id="todos-os-servicos"><div class="w">'
            '<p class="eb">Todos os serviços</p><h2>Escolha o serviço e veja como funciona</h2>'
            '<p class="lead">Cada serviço tem uma página com o passo a passo, o que está incluído e as perguntas mais comuns. '
            'Todos são feitos na nossa oficina da Av. Casa Verde, na Zona Norte de São Paulo, com peças genuínas.</p>'
            f'<div class="g">{cards}</div>'
            '<div class="two sub">'
            f'<div><h3>Revisão por marca</h3><p>O plano de revisão de cada marca, com os itens e os modelos.</p><ul class="chips">{rev}</ul></div>'
            f'<div><h3>Dúvidas comuns</h3><p>Guias curtos sobre remap e câmbio automático, escritos pela nossa equipe.</p><ul class="chips">{art}</ul></div>'
            '</div>'
            '<div class="cta"><div><strong>Não sabe qual serviço o seu carro precisa?</strong>'
            '<p>Mande o modelo, o ano e o que está acontecendo. Um especialista responde pelo WhatsApp.</p></div>'
            f'<a class="btn" href="{zap}" rel="nofollow">Pedir orçamento no WhatsApp</a></div>'
            '</div></section>')


NOVOS_SCHEMA = [("Troca de óleo do câmbio automático", "/troca-de-oleo-cambio-automatico/"),
                ("Revisão Volvo", "/oficina-volvo/revisao/"), ("Revisão Audi", "/oficina-audi/revisao/"),
                ("Revisão Mercedes-Benz", "/oficina-mercedes-benz/revisao/")]


def publicar():
    import base64, re, urllib.request
    base = os.environ.get("WP_URL", "https://veloceautospecialista.com.br").rstrip("/")
    tok = base64.b64encode(f"{os.environ['WP_USER']}:{os.environ['WP_APP_PASSWORD']}".encode()).decode()

    def req(metodo, caminho, corpo=None):
        r = urllib.request.Request(f"{base}/wp-json/{caminho}", method=metodo,
                                   data=json.dumps(corpo).encode() if corpo is not None else None,
                                   headers={"Authorization": f"Basic {tok}", "Content-Type": "application/json",
                                            "User-Agent": "veloce-seo/1.0"})
        with urllib.request.urlopen(r, timeout=60) as x:
            return x.read().decode()
    pg = json.loads(req("GET", "wp/v2/pages/205?context=edit&_fields=meta"))
    els = json.loads(pg["meta"]["_elementor_data"])
    root = els[0]["elements"]
    if any(c.get("id") == "vsv0001" for c in root):
        sys.exit("A seção já existe")
    novo = {"id": "vsv0001", "elType": "container", "isInner": False,
            "settings": {"content_width": "full", "padding": {"unit": "px", "top": "0", "right": "0", "bottom": "0", "left": "0", "isLinked": True}},
            "elements": [{"id": "vsv0002", "elType": "widget", "widgetType": "html", "isInner": False,
                          "settings": {"html": html()}, "elements": []}]}
    root.insert(1, novo)
    # dados estruturados: inclui os serviços novos no ItemList da CollectionPage
    for c in root:
        if c.get("widgetType") == "html" and "CollectionPage" in c["settings"].get("html", ""):
            h = c["settings"]["html"]
            dados = json.loads(re.search(r'<script type="application/ld\+json">(.*)</script>', h, re.S).group(1))
            lista = dados["@graph"][0]["mainEntity"]["itemListElement"]
            urls = {i["item"]["url"] for i in lista}
            for nome, u in NOVOS_SCHEMA:
                url = "https://veloceautospecialista.com.br" + u
                if url not in urls:
                    lista.append({"@type": "ListItem", "position": len(lista) + 1,
                                  "item": {"@type": "Service", "name": nome, "url": url,
                                           "provider": {"@id": "https://veloceautospecialista.com.br/#organization"}}})
            c["settings"]["html"] = '<script type="application/ld+json">' + json.dumps(dados, ensure_ascii=False) + "</script>"
    print(req("POST", "wp/v2/pages/205", {"meta": {"_elementor_data": json.dumps(els, ensure_ascii=False)}})[:80])
    print("cache:", req("DELETE", "elementor/v1/cache")[:80])


if __name__ == "__main__":
    {"html": lambda: print(html()), "publicar": publicar}[sys.argv[1]]()

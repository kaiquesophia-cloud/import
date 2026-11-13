#!/usr/bin/env python3
"""Gera o relatório de performance SEO da Veloce (HTML -> PDF)."""
import base64, html, os, sys

AQUI = os.path.dirname(os.path.abspath(__file__))
SAIDA_HTML = os.path.join(AQUI, "relatorio-cliente.html")

def b64(nome):
    with open(os.path.join(AQUI, "img", nome), "rb") as f:
        return "data:image/webp;base64," + base64.b64encode(f.read()).decode()

# Ubersuggest, rank tracking do projeto (Brasil, celular). Leituras de 04/11 e 11/11/2026.
# (busca, buscas/mês no Brasil, posição 04/11, posição 11/11, página)
POS = [
    ("remap stage 1", 170, None, 6, "/blog/remap-stage-1-e-stage-2/"),
    ("remap estraga o motor", 110, 17, 10, "/blog/remap-estraga-o-motor/"),
    ("remap automotivo", 1000, 18, 12, "/blog/o-que-e-remap/"),
    ("remap carro", 1300, 9, 13, "/blog/o-que-e-remap/"),
    ("revisão land rover", 210, None, 18, "/oficina-land-rover/revisao/"),
    ("revisão bmw 320i", 50, None, 23, "/oficina-bmw/revisao/"),
    ("vistoria cautelar ou pré compra", 0, 24, 27, "/blog/vistoria-cautelar-ou-…/"),
    ("oficina volvo sp", 40, None, 32, "/oficina-volvo/revisao/"),
    ("oficina mercedes benz", 210, 33, 35, "/oficina-mercedes-benz/"),
    ("oficina especializada em volvo", 260, None, 36, "/oficina-volvo/"),
    ("oficina land rover", 320, 40, 36, "/oficina-land-rover/"),
    ("oficina bmw", 590, 41, 41, "/oficina-bmw/"),
    ("remap perde garantia", 90, 46, 41, "/blog/remap-estraga-o-motor/"),
    ("oficina especializada bmw", 210, 65, 48, "/oficina-bmw/"),
    ("oficina porsche são paulo", 30, None, 52, "/ (home)"),
]
SAIRAM = [
    ("auto veloce", 210, 2, "/ (home)"),
    ("oficina lamborghini", 40, 5, "/oficina-lamborghini/"),
    ("oficina jaguar", 140, 21, "/oficina-jaguar/"),
    ("dinamômetro automotivo", 590, 24, "/dinamometro/"),
    ("oficina zona norte", 140, 25, "/ (home)"),
    ("mecânica bmw", 110, 36, "/oficina-bmw/"),
    ("oficina mercedes", 170, 38, "/oficina-mercedes-benz/"),
    ("oficina especializada land rover", 170, 42, "/oficina-land-rover/"),
]
ACOMPANHADAS = 41

# Taxa de clique média por posição (referência de mercado, aproximada).
def ctr(p):
    if p is None: return 0
    t = {1: .28, 2: .15, 3: .11, 4: .08, 5: .07, 6: .05, 7: .04, 8: .03, 9: .025, 10: .02}
    return t.get(p, .01 if p <= 20 else 0)

vis_novo = sum(v * ctr(n) for _, v, _, n, _ in POS)
vis_antigo = sum(v * ctr(o) for _, v, o, _, _ in POS) + sum(v * ctr(o) for _, v, o, _ in SAIRAM)
top100_antigo = sum(1 for r in POS if r[2]) + len(SAIRAM)
top100_novo = len(POS)
top10_novo = sum(1 for r in POS if r[3] <= 10)
paginas = 29

def fmt(n):
    return f"{n:,.0f}".replace(",", ".")

def e(s):
    return html.escape(str(s))

# ---------------------------------------------------------------- gráficos
def grafico_evolucao():
    dados = [("Antes do projeto", "set–out/2026", 2), ("1ª leitura", "04/11/2026", top100_antigo), ("2ª leitura", "11/11/2026", top100_novo)]
    W, H, base, topo = 400, 215, 170, 26
    maxv = 20
    bw, gap = 84, 34
    x0 = (W - (3 * bw + 2 * gap)) / 2
    out = [f'<svg viewBox="0 0 {W} {H}" width="100%" role="img" aria-label="Buscas em que o site aparece entre os 100 primeiros: 2 antes do projeto, {top100_antigo} em 04/11 e {top100_novo} em 11/11">']
    for v in (0, 5, 10, 15, 20):
        y = base - (base - topo) * v / maxv
        out.append(f'<line x1="40" x2="{W-10}" y1="{y:.1f}" y2="{y:.1f}" class="grid"/><text x="32" y="{y+4:.1f}" class="tick" text-anchor="end">{v}</text>')
    for i, (rot, sub, v) in enumerate(dados):
        x = x0 + i * (bw + gap)
        h = (base - topo) * v / maxv
        cor = "var(--acc)" if i else "var(--muted2)"
        out.append(f'<rect x="{x:.1f}" y="{base-h:.1f}" width="{bw}" height="{h:.1f}" rx="3" fill="{cor}"/>')
        out.append(f'<text x="{x+bw/2:.1f}" y="{base-h-8:.1f}" text-anchor="middle" class="val">{v}</text>')
        out.append(f'<text x="{x+bw/2:.1f}" y="{base+18}" text-anchor="middle" class="lab">{rot}</text>')
        out.append(f'<text x="{x+bw/2:.1f}" y="{base+33}" text-anchor="middle" class="tick">{sub}</text>')
    out.append("</svg>")
    return "".join(out)

def grafico_posicoes():
    linhas = sorted(POS, key=lambda r: r[3])
    W, rowh, left, right, top = 680, 26, 220, 20, 34
    H = top + rowh * len(linhas) + 16
    x = lambda p: left + (W - left - right) * (p - 1) / 69  # escala 1..70
    out = [f'<svg viewBox="0 0 {W} {H}" width="100%" role="img" aria-label="Posição no Google de cada busca em 04/11 e 11/11">']
    for p in (1, 10, 20, 30, 40, 50, 60, 70):
        out.append(f'<line x1="{x(p):.1f}" x2="{x(p):.1f}" y1="{top-6}" y2="{H-10}" class="grid"/><text x="{x(p):.1f}" y="{top-12}" text-anchor="middle" class="tick">{p}º</text>')
    out.append(f'<rect x="{x(1):.1f}" y="{top-6}" width="{x(10)-x(1):.1f}" height="{H-top-4}" class="pg1"/>')
    out.append(f'<text x="{(x(1)+x(10))/2:.1f}" y="{H-1}" text-anchor="middle" class="tick">1ª página</text>')
    for i, (k, v, o, n, _) in enumerate(linhas):
        y = top + rowh * i + rowh / 2
        out.append(f'<text x="{left-12}" y="{y+4:.1f}" text-anchor="end" class="kw">{e(k)}</text>')
        if o:
            cor = "var(--ok)" if n < o else ("var(--bad)" if n > o else "var(--muted2)")
            out.append(f'<line x1="{x(o):.1f}" x2="{x(n):.1f}" y1="{y:.1f}" y2="{y:.1f}" stroke="{cor}" stroke-width="3" stroke-linecap="round"/>')
            out.append(f'<circle cx="{x(o):.1f}" cy="{y:.1f}" r="4.5" fill="var(--paper)" stroke="var(--muted2)" stroke-width="2"/>')
        out.append(f'<circle cx="{x(n):.1f}" cy="{y:.1f}" r="5.5" fill="var(--acc)"/>')
        out.append(f'<text x="{x(max(n, o or n))+10:.1f}" y="{y+4:.1f}" class="val-s">{n}º</text>')
    out.append("</svg>")
    return "".join(out)

# ---------------------------------------------------------------- tabelas
def var_txt(o, n):
    if o is None: return '<span class="pill novo">entrou</span>'
    d = o - n
    if d > 0: return f'<span class="pill up">▲ {d}</span>'
    if d < 0: return f'<span class="pill down">▼ {-d}</span>'
    return '<span class="pill eq">=</span>'

def tabela_pos():
    rows = []
    for k, v, o, n, url in sorted(POS, key=lambda r: r[3]):
        rows.append(f"<tr><td class='kwc'>{e(k)}</td><td class='num'>{fmt(v) if v else '–'}</td>"
                    f"<td class='num'>{str(o)+'º' if o else '–'}</td><td class='num strong'>{n}º</td>"
                    f"<td>{var_txt(o, n)}</td><td class='url'>{e(url)}</td></tr>")
    return "".join(rows)

def tabela_sairam():
    return "".join(f"<tr><td class='kwc'>{e(k)}</td><td class='num'>{fmt(v)}</td><td class='num'>{o}º</td>"
                   f"<td><span class='pill down'>fora dos 100</span></td><td class='url'>{e(u)}</td></tr>" for k, v, o, u in SAIRAM)

ENTREGAS = [
    ("05/10", "Plano de SEO", "Pesquisa de palavras-chave no Ubersuggest, mapa do site por marca e serviço, cronograma."),
    ("06/10", "Base técnica", "Rank Math instalado, limpeza do WooCommerce e páginas padrão, /contato/, imagens em WebP, textos alternativos, imagem de compartilhamento e telefone clicável."),
    ("06/10", "13 páginas de marca e serviço", "BMW, Land Rover, Volvo, Mercedes-Benz, Audi, Porsche, Jaguar, MINI, Lamborghini, remap, inspeção pré-compra, manutenção preventiva e Zona Norte. Página /dinamometro/ no mesmo dia."),
    ("07/10", "Blog e páginas de aprofundamento", "Blog com 6 artigos (remap, stage 1 e 2, garantia, custo, cautelar), revisão BMW, revisão Land Rover e remap de câmbio."),
    ("07/10", "Velocidade", "Tempo para mostrar o conteúdo principal da home caiu de 11,6 s para 5,5 s."),
    ("05/11", "Novas páginas", "Revisão Volvo, Audi e Mercedes-Benz, artigo “O que é dinamômetro” e troca de óleo do câmbio automático. Texto repetido da home corrigido."),
    ("06/11", "Google e fora do site", "Google Meu Negócio completo (serviços, descrição, fotos), links quebrados da página Sobre corrigidos, mapa do site atualizado e pedido de indexação."),
]

def timeline():
    return "".join(f"<li><span class='dt'>{d}</span><div><b>{e(t)}</b><p>{e(x)}</p></div></li>" for d, t, x in ENTREGAS)

# ---------------------------------------------------------------- página
CSS = r"""
@page { size: A4; margin: 0 }
:root{
  /* Identidade da Veloce: laranja do site, grafite e papel levemente quente */
  --acc:#D16527; --acc-d:#A94E1B; --acc-soft:#FBEDE4;
  --ink:#161616; --ink2:#242424; --paper:#FFFFFF; --soft:#F6F3F0;
  --muted:#5E5953; --muted2:#A8A19A; --line:#E7E1DB;
  --ok:#2E8B57; --ok-soft:#E5F3EA; --bad:#C0392B; --bad-soft:#F8E5E2;
  --display:"Chakra Petch", "Arial Narrow", Arial, sans-serif;
  --body:"Mulish", "Segoe UI", Arial, sans-serif;
}
*{box-sizing:border-box}
html,body{margin:0;background:#888}
body{font-family:var(--body);color:var(--ink);font-size:10.2pt;line-height:1.5;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.page{width:210mm;height:297mm;position:relative;overflow:hidden;background:var(--paper);page-break-after:always;margin:0 auto}
.page:last-child{page-break-after:auto}
.in{padding:18mm 17mm 22mm}
h1,h2,h3{font-family:var(--display);margin:0;line-height:1.1;text-wrap:balance}
h2{font-size:21pt;font-weight:600;margin-bottom:3mm}
h3{font-size:12pt;font-weight:600;margin:0 0 2mm}
p{margin:0 0 3mm}
.eyebrow{font-family:var(--display);font-size:8.5pt;letter-spacing:.18em;text-transform:uppercase;color:var(--acc);font-weight:600;margin-bottom:2.5mm}
.lead{font-size:11.5pt;color:var(--ink2);max-width:150mm}
.muted{color:var(--muted)}
.rule{height:3px;width:16mm;background:var(--acc);margin:0 0 6mm}
/* topo/rodapé das páginas internas */
.head{display:flex;justify-content:space-between;align-items:center;padding:9mm 17mm 0;font-family:var(--display);font-size:8pt;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
.head .brand{color:var(--ink);font-weight:700}
.head .brand i{color:var(--acc);font-style:normal}
.foot{position:absolute;left:17mm;right:17mm;bottom:9mm;display:flex;justify-content:space-between;font-size:7.5pt;color:var(--muted2);border-top:1px solid var(--line);padding-top:2.5mm}
/* capa */
.cover{background:var(--ink);color:#fff}
.cover .photo{position:absolute;inset:0 0 auto 0;height:170mm;background-size:cover;background-position:center}
.cover .photo:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(18,18,18,.25) 0%,rgba(18,18,18,.55) 55%,#121212 100%)}
.cover .brandbar{position:absolute;top:14mm;left:17mm;right:17mm;display:flex;justify-content:space-between;align-items:center;z-index:2}
.wordmark{font-family:var(--display);font-weight:700;font-size:15pt;letter-spacing:.06em;color:#fff}
.wordmark span{color:var(--acc)}
.wordmark small{display:block;font-size:6.8pt;letter-spacing:.42em;font-weight:500;color:#cfc9c3;margin-top:1mm}
.cover .tag{font-family:var(--display);font-size:8pt;letter-spacing:.2em;text-transform:uppercase;border:1px solid rgba(255,255,255,.45);padding:1.5mm 3mm;border-radius:2px}
.cover .title{position:absolute;left:17mm;right:17mm;top:150mm;z-index:2}
.cover h1{font-size:40pt;font-weight:700;letter-spacing:-.01em}
.cover h1 em{font-style:normal;color:var(--acc)}
.cover .sub{font-size:12.5pt;color:#d9d3cd;margin-top:5mm;max-width:150mm}
.cover .meta{position:absolute;left:17mm;right:17mm;bottom:16mm;display:grid;grid-template-columns:repeat(3,1fr);gap:6mm;border-top:1px solid rgba(255,255,255,.18);padding-top:6mm;z-index:2}
.cover .meta b{display:block;font-family:var(--display);font-size:7.5pt;letter-spacing:.18em;text-transform:uppercase;color:var(--acc);margin-bottom:1mm;font-weight:600}
.cover .meta span{font-size:10pt;color:#eee}
.stripe{position:absolute;right:0;top:118mm;width:58mm;height:3mm;background:var(--acc);z-index:2}
/* kpis */
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:4mm;margin:7mm 0 7mm}
.kpi{background:var(--soft);border-radius:3px;padding:4.5mm 4mm 4mm;border-top:3px solid var(--acc)}
.kpi .n{font-family:var(--display);font-size:25pt;font-weight:700;line-height:1;font-variant-numeric:tabular-nums}
.kpi .l{font-size:8.6pt;color:var(--muted);margin-top:2mm;line-height:1.35}
.kpi .d{font-size:8pt;margin-top:2mm;font-weight:700}
.kpi.dark{background:var(--ink);color:#fff;border-top-color:var(--acc)}
.kpi.dark .l{color:#cfc9c3}
.up-t{color:var(--ok)} .down-t{color:var(--bad)}
.box{border:1px solid var(--line);border-radius:3px;padding:5mm 5.5mm}
.callout{background:var(--acc-soft);border-left:3px solid var(--acc);padding:4mm 5mm;border-radius:0 3px 3px 0;margin:5mm 0}
.callout p:last-child{margin:0}
.two{display:grid;grid-template-columns:1fr 1fr;gap:6mm}
/* charts */
svg text{font-family:var(--body)}
.grid{stroke:var(--line);stroke-width:1}
.tick{font-size:9px;fill:var(--muted)}
.lab{font-size:11px;fill:var(--ink);font-weight:700}
.val{font-family:var(--display);font-size:20px;font-weight:700;fill:var(--ink)}
.val-s{font-size:10px;font-weight:700;fill:var(--ink)}
.kw{font-size:10.5px;fill:var(--ink)}
.pg1{fill:var(--acc-soft)}
.legend{display:flex;gap:6mm;font-size:8.3pt;color:var(--muted);margin-top:2mm}
.legend i{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:1.5mm;vertical-align:-1px}
/* tabela */
table{width:100%;border-collapse:collapse;font-size:8.5pt}
th{font-family:var(--display);font-size:7.6pt;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);text-align:left;font-weight:600;border-bottom:2px solid var(--ink);padding:0 2mm 1.8mm}
td{padding:1.15mm 2mm;border-bottom:1px solid var(--line);vertical-align:middle}
td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
td.strong{font-weight:800}
td.kwc{font-weight:600}
td.url{color:var(--muted);font-size:7.5pt;white-space:nowrap}
.pill{display:inline-block;font-size:7.4pt;font-weight:800;padding:.6mm 2mm;border-radius:10px;white-space:nowrap}
.pill.up{background:var(--ok-soft);color:var(--ok)} .pill.down{background:var(--bad-soft);color:var(--bad)}
.pill.novo{background:var(--acc-soft);color:var(--acc-d)} .pill.eq{background:var(--soft);color:var(--muted)}
/* timeline */
.tl{list-style:none;margin:6mm 0 0;padding:0;position:relative}
.tl:before{content:"";position:absolute;left:13mm;top:2mm;bottom:2mm;width:2px;background:var(--line)}
.tl li{display:grid;grid-template-columns:26mm 1fr;gap:0;margin-bottom:5mm;position:relative}
.tl li:after{content:"";position:absolute;left:12mm;top:1.6mm;width:8px;height:8px;border-radius:50%;background:var(--acc);box-shadow:0 0 0 3px var(--paper)}
.tl .dt{font-family:var(--display);font-weight:700;font-size:10pt;color:var(--acc);padding-top:.2mm}
.tl b{font-family:var(--display);font-size:11pt;font-weight:600}
.tl p{margin:1mm 0 0;color:var(--muted);font-size:9.3pt}
.facts{display:grid;grid-template-columns:repeat(3,1fr);gap:4mm;margin-top:3mm}
.fact{background:var(--ink);color:#fff;border-radius:3px;padding:4mm}
.fact .n{font-family:var(--display);font-size:20pt;font-weight:700;color:var(--acc);line-height:1}
.fact .l{font-size:8.5pt;color:#d9d3cd;margin-top:1.5mm}
/* próximos passos */
.steps{counter-reset:s;list-style:none;padding:0;margin:5mm 0 0;display:grid;gap:4mm}
.steps li{counter-increment:s;display:grid;grid-template-columns:11mm 1fr;align-items:start;border:1px solid var(--line);border-radius:3px;padding:4mm 4.5mm}
.steps li:before{content:counter(s);font-family:var(--display);font-weight:700;font-size:16pt;color:var(--acc);line-height:1}
.steps b{font-family:var(--display);font-size:11pt;font-weight:600}
.steps p{margin:1mm 0 0;font-size:9.3pt;color:var(--muted)}
.owner{display:inline-block;font-size:7.3pt;font-weight:800;letter-spacing:.06em;text-transform:uppercase;background:var(--soft);color:var(--ink2);padding:.5mm 2mm;border-radius:2px;margin-left:2mm;vertical-align:1px}
.notes{font-size:8.3pt;color:var(--muted)}
.notes li{margin-bottom:1.5mm}
"""

def head(n):
    return (f'<div class="head"><span class="brand">Veloce <i>Auto</i> Specialista</span>'
            f'<span>Relatório de SEO · Novembro 2026</span></div>')

def foot(n):
    return (f'<div class="foot"><span>Dados: Ubersuggest (Brasil, celular), leituras de 04/11 e 11/11/2026 · Histórico do projeto</span>'
            f'<span>{n} / 6</span></div>')

def main():
    capa = b64("oficina-veloce-carros-premium-zona-norte-sp.webp")
    dif = round(vis_novo - vis_antigo)
    doc = f"""<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<title>Relatório SEO Veloce · Novembro 2026</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>{CSS}</style></head><body>

<section class="page cover">
  <div class="photo" style="background-image:url('{capa}')"></div>
  <div class="brandbar"><div class="wordmark">VELOCE <span>AUTO</span><small>SPECIALISTA</small></div><div class="tag">Relatório para o cliente</div></div>
  <div class="stripe"></div>
  <div class="title">
    <div class="eyebrow">Performance no Google</div>
    <h1>Relatório de SEO<br><em>Novembro 2026</em></h1>
    <p class="sub">Como o site da Veloce está aparecendo no Google, quanta visita isso traz e tudo o que foi feito pelo site da oficina desde o início do projeto.</p>
  </div>
  <div class="meta">
    <div><b>Período</b><span>05/10 a 13/11/2026</span></div>
    <div><b>Site</b><span>veloceautospecialista.com.br</span></div>
    <div><b>Preparado para</b><span>Veloce Auto Specialista</span></div>
  </div>
</section>

<section class="page">{head(2)}<div class="in">
  <div class="eyebrow">Resumo</div>
  <h2>O site saiu do zero e já aparece em {top100_novo} buscas</h2>
  <div class="rule"></div>
  <p class="lead">Antes do projeto, o Google só mostrava o site da Veloce para quem já digitava o nome da oficina. Hoje ele aparece em {top100_novo} das {ACOMPANHADAS} buscas que acompanhamos, e em {top10_novo} delas já está na primeira página. O projeto tem cerca de 5 semanas: é a fase em que o Google ainda está conhecendo as páginas novas, e as posições oscilam de uma semana para outra.</p>
  <div class="kpis">
    <div class="kpi dark"><div class="n">{paginas}</div><div class="l">páginas novas publicadas no site</div><div class="d" style="color:var(--acc)">desde 06/10</div></div>
    <div class="kpi"><div class="n">{top100_novo}</div><div class="l">buscas em que o site aparece entre os 100 primeiros</div><div class="d up-t">antes do projeto: 2</div></div>
    <div class="kpi"><div class="n">{top10_novo}</div><div class="l">buscas na 1ª página do Google</div><div class="d muted">remap stage 1 (6º) e remap estraga o motor (10º)</div></div>
    <div class="kpi"><div class="n">~{round(vis_novo)}</div><div class="l">visitas por mês estimadas vindas dessas buscas</div><div class="d down-t">na leitura de 04/11: ~{round(vis_antigo)}</div></div>
  </div>
  <div class="two">
    <div>
      <h3>Buscas em que o site aparece (top 100)</h3>
      <p class="muted" style="font-size:8.8pt">Contagem entre as {ACOMPANHADAS} buscas acompanhadas no Ubersuggest.</p>
      {grafico_evolucao()}
    </div>
    <div>
      <h3>Quanto tráfego do Google veio este mês?</h3>
      <p>O número exato de visitas aparece no Google Search Console, a ferramenta gratuita do Google para donos de site. Assim que a Veloce liberar o acesso, ele entra nos próximos relatórios. Por enquanto usamos uma <b>estimativa</b>: o volume de cada busca multiplicado pela taxa média de clique da posição em que o site aparece.</p>
      <p>Pela leitura de 11/11, as buscas acompanhadas trazem <b>cerca de {round(vis_novo)} visitas por mês</b>. Na leitura de 04/11 eram cerca de {round(vis_antigo)}. A queda vem quase toda de duas buscas: o nome “auto veloce”, que estava em 2º e não apareceu nesta leitura, e “remap carro”, que foi do 9º para o 13º.</p>
      <p class="muted" style="font-size:8.8pt">A base mensal do Ubersuggest, que se atualiza só uma vez por mês, ainda mostra o site com tráfego perto de zero. Ela vai até outubro e ainda não reflete as páginas novas.</p>
    </div>
  </div>
  <div class="callout"><p><b>Importante saber:</b> SEO é um trabalho de médio prazo. Com 2 leituras de posição, separadas por uma semana, ainda é cedo para tirar conclusões. Páginas novas sobem e descem nas primeiras semanas. O normal é o resultado aparecer entre 60 e 120 dias depois da publicação, ou seja, de dezembro de 2026 a fevereiro de 2027.</p></div>
</div>{foot(2)}</section>

<section class="page">{head(3)}<div class="in">
  <div class="eyebrow">Posições no Google</div>
  <h2>Onde o site aparece hoje</h2>
  <div class="rule"></div>
  <p class="lead" style="margin-bottom:4mm">Cada linha é uma busca. O círculo laranja é a posição em 11/11, o círculo vazio é a posição em 04/11. Verde: subiu. Vermelho: caiu. Quanto mais perto do 1º, melhor.</p>
  {grafico_posicoes()}
  <div class="legend"><span><i style="background:var(--acc)"></i>11/11/2026</span><span><i style="background:var(--paper);border:2px solid var(--muted2)"></i>04/11/2026</span><span><i style="background:var(--ok)"></i>subiu</span><span><i style="background:var(--bad)"></i>caiu</span><span><i style="background:var(--acc-soft)"></i>1ª página</span></div>
  <div class="two" style="margin-top:6mm">
    <div class="box"><h3 class="up-t">O que avançou</h3>
      <p style="font-size:9.3pt">O <b>blog de remap</b> é o que mais cresce: “remap stage 1” entrou direto em 6º, “remap estraga o motor” subiu para 10º, e “remap automotivo” (1.000 buscas por mês) foi de 18º para 12º. As <b>páginas de revisão</b> estrearam bem: “revisão land rover” em 18º e “revisão bmw 320i” em 23º. A página da Volvo passou a aparecer em 2 buscas.</p></div>
    <div class="box"><h3 class="down-t">O que pede atenção</h3>
      <p style="font-size:9.3pt"><b>8 buscas saíram dos 100 primeiros</b> nesta leitura, entre elas o nome “auto veloce” (era 2º), “oficina lamborghini” (era 5º) e “dinamômetro automotivo” (era 24º). Oscilação assim é comum em site novo. Estamos acompanhando de perto: se o nome da oficina continuar fora na leitura de 18/11, investigamos a causa.</p></div>
  </div>
</div>{foot(3)}</section>

<section class="page">{head(4)}<div class="in">
  <div class="eyebrow">Detalhe das posições</div>
  <h2>Todas as buscas acompanhadas que aparecem</h2>
  <div class="rule"></div>
  <p class="muted" style="font-size:8.8pt;margin-bottom:3mm">“Buscas/mês” é a média mensal no Brasil, segundo o Ubersuggest, arredondada em faixas. As outras {ACOMPANHADAS - top100_novo - len(SAIRAM)} buscas acompanhadas ainda não aparecem entre os 100 primeiros em nenhuma das duas leituras.</p>
  <table>
    <thead><tr><th>Busca</th><th class="num">Buscas/mês</th><th class="num">04/11</th><th class="num">11/11</th><th>Variação</th><th>Página</th></tr></thead>
    <tbody>{tabela_pos()}</tbody>
  </table>
  <h3 style="margin:5.5mm 0 2mm">Saíram dos 100 primeiros nesta leitura</h3>
  <table>
    <thead><tr><th>Busca</th><th class="num">Buscas/mês</th><th class="num">04/11</th><th>11/11</th><th>Página</th></tr></thead>
    <tbody>{tabela_sairam()}</tbody>
  </table>
</div>{foot(4)}</section>

<section class="page">{head(5)}<div class="in">
  <div class="eyebrow">O avanço até hoje</div>
  <h2>O que foi feito desde 05/10</h2>
  <div class="rule"></div>
  <div class="facts">
    <div class="fact"><div class="n">{paginas}</div><div class="l">páginas novas: 9 de marca, 5 de revisão, 7 de serviço, 7 artigos e o blog</div></div>
    <div class="fact"><div class="n">11,6 → 5,5 s</div><div class="l">tempo para a home mostrar o conteúdo principal</div></div>
    <div class="fact"><div class="n">21</div><div class="l">serviços cadastrados no Google Meu Negócio, cada um com link para o site</div></div>
  </div>
  <ul class="tl">{timeline()}</ul>
</div>{foot(5)}</section>

<section class="page">{head(6)}<div class="in">
  <div class="eyebrow">Próximos passos</div>
  <h2>O que vem agora</h2>
  <div class="rule"></div>
  <p class="lead">Dois passos ficam com a Veloce e dois com a nossa equipe. Os dois primeiros são os que mais aceleram o resultado, e levam poucos minutos.</p>
  <ol class="steps">
    <li><div><b>Liberar o acesso ao Google Search Console</b><span class="owner">Veloce</span><p>Com ele o relatório passa a mostrar as visitas reais vindas do Google, e não só uma estimativa. Ajudamos no passo a passo.</p></div></li>
    <li><div><b>Pedir avaliação a todo cliente no Google</b><span class="owner">Veloce</span><p>É o que mais pesa para a oficina aparecer no mapa em “oficina bmw” e nas outras buscas de marca. A mensagem pronta para o WhatsApp já foi enviada.</p></div></li>
    <li><div><b>3 artigos sobre câmbio automático</b><span class="owner">Equipe de SEO</span><p>Apoiam a página de troca de óleo, que mira cerca de 8 mil buscas por mês somando as variações.</p></div></li>
    <li><div><b>Nova leitura em 18/11 e relatório em dezembro</b><span class="owner">Equipe de SEO</span><p>Confirmar se o nome “auto veloce” volta ao topo e acompanhar a subida das páginas de marca.</p></div></li>
  </ol>
  <div class="eyebrow" style="margin-top:9mm">Como ler este relatório</div>
  <ul class="notes">
    <li><b>Posição</b> é o lugar do site na lista do Google para aquela busca, medido pelo Ubersuggest no Brasil, no celular, uma vez por semana. Até o 10º lugar é a primeira página.</li>
    <li><b>Buscas por mês</b> é a média de vezes que a busca é feita no Brasil. O Ubersuggest arredonda esse número em faixas, então ele serve para comparar buscas entre si, não como número exato.</li>
    <li><b>Visitas estimadas</b> = buscas por mês × taxa média de clique da posição (cerca de 5% no 6º lugar, 2% no 10º, 1% do 11º ao 20º e quase zero depois do 20º). Conta só as {ACOMPANHADAS} buscas acompanhadas. As visitas reais podem ser maiores (outras buscas, mapa do Google) ou menores.</li>
    <li>Buscas da região, como “oficina bmw” feita em São Paulo, também mostram o mapa do Google com 3 oficinas acima dos sites. Esse espaço vem do Google Meu Negócio e não entra nas posições acima.</li>
    <li>O projeto do Ubersuggest acompanha {ACOMPANHADAS} buscas. A próxima leitura é em 18/11/2026.</li>
  </ul>
</div>{foot(6)}</section>

</body></html>"""
    with open(SAIDA_HTML, "w", encoding="utf-8") as f:
        f.write(doc)
    print(f"ok: visitas estimadas {vis_antigo:.1f} -> {vis_novo:.1f}; top100 {top100_antigo} -> {top100_novo}")

main()

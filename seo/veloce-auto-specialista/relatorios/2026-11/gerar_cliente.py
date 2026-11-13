#!/usr/bin/env python3
"""Relatório de SEO de novembro para enviar à Veloce: visitas reais (Search Console) + posições (Ubersuggest).

  python3 gerar_cliente.py   -> relatorio-cliente.html (o PDF sai do Chromium, ver README do relatório)
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import gerar as base
from dados_search_console import TOTAIS, PAGINAS, CONSULTAS_CLIQUE, CONSULTAS_SEM_CLIQUE, DISPOSITIVOS, DIAS, PERIODO

AQUI = os.path.dirname(os.path.abspath(__file__))
SAIDA = os.path.join(AQUI, "relatorio-cliente.html")
TOTAL_PAGS = 7
e, fmt = base.e, base.fmt

def br(x, casas=1):
    return f"{x:.{casas}f}".replace(".", ",")

# ------------------------------------------------------------ números derivados
out = [d for d in DIAS if d[0].endswith("/10")]
nov = [d for d in DIAS if d[0].endswith("/11")]
imp_dia_out = sum(d[2] for d in out) / len(out)
imp_dia_nov = sum(d[2] for d in nov) / len(nov)
cli_dia_out = sum(d[1] for d in out) / len(out)
cli_dia_nov = sum(d[1] for d in nov) / len(nov)
var_imp = (imp_dia_nov / imp_dia_out - 1) * 100
var_cli = (cli_dia_nov / cli_dia_out - 1) * 100
MARCA = {"veloce auto specialista", "veloce", "auto veloce", "veloce auto"}
cli_marca = sum(c for q, c, *_ in CONSULTAS_CLIQUE if q in MARCA)
cli_remap = sum(c for q, c, *_ in CONSULTAS_CLIQUE if "remap" in q or "stage" in q)
cli_ident = sum(c for _, c, *_ in CONSULTAS_CLIQUE)
REMAP_PAGS = ("/blog/remap-stage-1-e-stage-2/", "/blog/o-que-e-remap/")
imp_remap = sum(p[2] for p in PAGINAS if p[0] in REMAP_PAGS)
cli_remap_pags = sum(p[1] for p in PAGINAS if p[0] in REMAP_PAGS)
dias = len(DIAS)

# ------------------------------------------------------------ gráfico diário
def grafico_dias():
    W, H, left, right = 680, 250, 40, 12
    t1, b1 = 16, 120          # impressões
    t2, b2 = 150, 214         # cliques
    n = len(DIAS)
    slot = (W - left - right) / n
    bw = slot * 0.62
    mi = 800; mc = 6
    o = [f'<svg viewBox="0 0 {W} {H}" width="100%" role="img" aria-label="Impressões e cliques por dia de {PERIODO[0]} a {PERIODO[1]}">']
    for v in (0, 400, 800):
        y = b1 - (b1 - t1) * v / mi
        o.append(f'<line x1="{left}" x2="{W-right}" y1="{y:.1f}" y2="{y:.1f}" class="grid"/><text x="{left-6}" y="{y+3.5:.1f}" class="tick" text-anchor="end">{v}</text>')
    for v in (0, 3, 6):
        y = b2 - (b2 - t2) * v / mc
        o.append(f'<line x1="{left}" x2="{W-right}" y1="{y:.1f}" y2="{y:.1f}" class="grid"/><text x="{left-6}" y="{y+3.5:.1f}" class="tick" text-anchor="end">{v}</text>')
    o.append(f'<text x="{left}" y="{t1-5}" class="lab" style="font-size:10px">Impressões por dia</text>')
    o.append(f'<text x="{left}" y="{t2-5}" class="lab" style="font-size:10px">Cliques (visitas) por dia</text>')
    for i, (dia, c, imp, *_r) in enumerate(DIAS):
        x = left + slot * i + (slot - bw) / 2
        h1 = (b1 - t1) * imp / mi
        h2 = (b2 - t2) * c / mc
        o.append(f'<rect x="{x:.1f}" y="{b1-h1:.1f}" width="{bw:.1f}" height="{h1:.1f}" rx="1.5" fill="var(--muted2)" opacity=".55"/>')
        if c:
            o.append(f'<rect x="{x:.1f}" y="{b2-h2:.1f}" width="{bw:.1f}" height="{h2:.1f}" rx="1.5" fill="var(--acc)"/>')
            o.append(f'<text x="{x+bw/2:.1f}" y="{b2-h2-3:.1f}" text-anchor="middle" class="tick" style="font-size:8px;fill:var(--ink)">{c}</text>')
        if i % 3 == 0 or i == n - 1:
            o.append(f'<text x="{x+bw/2:.1f}" y="{b2+14}" text-anchor="middle" class="tick">{dia}</text>')
    # separação outubro / novembro
    xs = left + slot * len(out)
    o.append(f'<line x1="{xs:.1f}" x2="{xs:.1f}" y1="{t1-2}" y2="{b2+4}" stroke="var(--ink)" stroke-dasharray="3 3" stroke-width="1"/>')
    o.append(f'<text x="{xs+5:.1f}" y="{t1+8}" class="tick" style="fill:var(--ink)">novembro</text>')
    o.append("</svg>")
    return "".join(o)

def barras_dispositivo():
    tot = sum(d[1] for d in DISPOSITIVOS)
    partes = []
    for nome, c, imp, ctr, pos in DISPOSITIVOS:
        if not c: continue
        partes.append(f"<div class='dv'><span>{nome}</span><div class='bar'><i style='width:{100*c/tot:.0f}%'></i></div><b>{c}</b></div>")
    return "".join(partes)

def tabela_paginas():
    nomes = {"/": "Página inicial", "/blog/remap-stage-1-e-stage-2/": "Artigo: stage 1 x stage 2",
             "/oficina-mercedes-benz/": "Oficina Mercedes-Benz", "/blog/o-que-e-remap/": "Artigo: o que é remap",
             "/oficina-land-rover/": "Oficina Land Rover", "/oficina-audi/": "Oficina Audi",
             "/blog/remap-estraga-o-motor/": "Artigo: remap estraga o motor?", "/blog/quanto-custa-um-remap/": "Artigo: quanto custa um remap",
             "/sobre/": "Sobre", "/remap/": "Remap", "/servicos/": "Serviços", "/oficina-porsche/": "Oficina Porsche",
             "/contato/": "Contato", "/oficina-audi/revisao/": "Revisão Audi"}
    linhas = [p for p in PAGINAS if p[1] > 0]
    return "".join(f"<tr><td class='kwc'>{e(nomes.get(u, u))}<div class='url'>{e(u)}</div></td><td class='num strong'>{c}</td>"
                   f"<td class='num'>{fmt(i)}</td><td class='num'>{br(p)}</td></tr>" for u, c, i, _ctr, p in linhas)

def tabela_consultas():
    return "".join(f"<tr><td class='kwc'>{e(q)}</td><td class='num strong'>{c}</td><td class='num'>{fmt(i)}</td><td class='num'>{br(p)}</td></tr>"
                   for q, c, i, _ctr, p in CONSULTAS_CLIQUE)

def tabela_sem_clique():
    return "".join(f"<tr><td class='kwc'>{e(q)}</td><td class='num'>{fmt(i)}</td><td class='num'>{br(p)}</td></tr>"
                   for q, i, p in CONSULTAS_SEM_CLIQUE[:10])

def tabela_marcas():
    nomes = {"/oficina-mercedes-benz/": "Mercedes-Benz", "/oficina-audi/": "Audi", "/oficina-jaguar/": "Jaguar",
             "/oficina-porsche/": "Porsche", "/oficina-land-rover/": "Land Rover", "/oficina-volvo/": "Volvo",
             "/oficina-bmw/": "BMW", "/oficina-lamborghini/": "Lamborghini"}
    linhas = sorted((p for p in PAGINAS if p[0] in nomes), key=lambda p: p[4])
    return "".join(f"<tr><td class='kwc'>{nomes[u]}</td><td class='num'>{c}</td><td class='num'>{fmt(i)}</td><td class='num'>{br(p)}</td></tr>"
                   for u, c, i, _ctr, p in linhas)

EXTRA_CSS = """
.dv{display:grid;grid-template-columns:24mm 1fr 8mm;align-items:center;gap:3mm;font-size:9pt;margin-bottom:2mm}
.dv .bar{height:7px;background:var(--soft);border-radius:4px;overflow:hidden}
.dv .bar i{display:block;height:100%;background:var(--acc)}
.dv b{text-align:right;font-variant-numeric:tabular-nums}
td .url{font-weight:400;color:var(--muted);font-size:7.3pt}
.cmp{display:grid;grid-template-columns:1fr 1fr;gap:4mm;margin-top:4mm}
.cmp div{background:var(--soft);border-radius:3px;padding:3.5mm 4mm}
.cmp b{font-family:var(--display);font-size:15pt;display:block;line-height:1.1}
.cmp span{font-size:8.5pt;color:var(--muted)}
.big{font-family:var(--display);font-size:30pt;font-weight:700;color:var(--acc);line-height:1}
"""

def head(n):
    return ('<div class="head"><span class="brand">Veloce <i>Auto</i> Specialista</span>'
            '<span>Relatório de SEO · Novembro 2026</span></div>')

def foot(n, fonte):
    return f'<div class="foot"><span>{fonte}</span><span>{n} / {TOTAL_PAGS}</span></div>'

GSC = f"Dados: Google Search Console, {PERIODO[0]} a {PERIODO[1]}"
UBER = "Dados: Ubersuggest (Brasil, celular), leituras de 04/11 e 11/11/2026"

def main():
    capa = base.b64("oficina-veloce-carros-premium-zona-norte-sp.webp")
    doc = f"""<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<title>Relatório SEO Veloce · Novembro 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>{base.CSS}{EXTRA_CSS}</style></head><body>

<section class="page cover">
  <div class="photo" style="background-image:url('{capa}')"></div>
  <div class="brandbar"><div class="wordmark">VELOCE <span>AUTO</span><small>SPECIALISTA</small></div><div class="tag">Relatório para o cliente</div></div>
  <div class="stripe"></div>
  <div class="title">
    <div class="eyebrow">Performance no Google</div>
    <h1>Relatório de SEO<br><em>Novembro 2026</em></h1>
    <p class="sub">Quantas pessoas chegaram ao site da Veloce pelo Google, o que elas procuraram e tudo o que foi feito no site da oficina desde o início do projeto.</p>
  </div>
  <div class="meta">
    <div><b>Período</b><span>05/10 a 13/11/2026</span></div>
    <div><b>Site</b><span>veloceautospecialista.com.br</span></div>
    <div><b>Preparado para</b><span>Veloce Auto Specialista</span></div>
  </div>
</section>

<section class="page">{head(2)}<div class="in">
  <div class="eyebrow">Resumo</div>
  <h2>{TOTAIS['cliques']} visitas vindas do Google nos primeiros {dias} dias</h2>
  <div class="rule"></div>
  <p class="lead">Desde que o Google começou a registrar o site, em {PERIODO[0]}, a Veloce apareceu {fmt(TOTAIS['impressoes'])} vezes nos resultados de busca e recebeu {TOTAIS['cliques']} visitas. Em novembro o ritmo está maior que em outubro, tanto em aparições quanto em visitas.</p>
  <div class="kpis">
    <div class="kpi dark"><div class="n">{TOTAIS['cliques']}</div><div class="l">visitas vindas do Google (cliques)</div><div class="d" style="color:var(--acc)">{PERIODO[0]} a {PERIODO[1]}</div></div>
    <div class="kpi"><div class="n">11,6 mil</div><div class="l">vezes em que o site apareceu numa busca (impressões)</div><div class="d up-t">+{var_imp:.0f}% por dia em novembro</div></div>
    <div class="kpi"><div class="n">{br(TOTAIS['posicao'])}</div><div class="l">posição média nos resultados</div><div class="d muted">até o 10º é a 1ª página</div></div>
    <div class="kpi"><div class="n">{base.paginas}</div><div class="l">páginas novas publicadas no site</div><div class="d muted">desde 06/10</div></div>
  </div>
  {grafico_dias()}
  <div class="cmp">
    <div><b>{imp_dia_out:.0f} → {imp_dia_nov:.0f}</b><span>impressões por dia, média de outubro → média de novembro (+{var_imp:.0f}%)</span></div>
    <div><b>{br(cli_dia_out)} → {br(cli_dia_nov)}</b><span>visitas por dia, média de outubro → média de novembro (+{var_cli:.0f}%)</span></div>
  </div>
  <div class="callout"><p><b>Importante saber:</b> são os primeiros {dias} dias de dados, e os números diários ainda são pequenos. Um dia com 6 visitas já muda a média. O normal é o resultado de SEO aparecer entre 60 e 120 dias depois da publicação das páginas, de dezembro de 2026 a fevereiro de 2027.</p></div>
</div>{foot(2, GSC)}</section>

<section class="page">{head(3)}<div class="in">
  <div class="eyebrow">De onde vêm as visitas</div>
  <h2>As páginas e as buscas que trouxeram gente</h2>
  <div class="rule"></div>
  <div class="two">
    <div style="min-width:0">
      <h3>Páginas que receberam visitas</h3>
      <table><thead><tr><th>Página</th><th class="num">Visitas</th><th class="num">Impr.</th><th class="num">Posição</th></tr></thead>
      <tbody>{tabela_paginas()}</tbody></table>
    </div>
    <div style="min-width:0">
      <h3>O que as pessoas pesquisaram</h3>
      <p class="muted" style="font-size:8.6pt">Das {TOTAIS['cliques']} visitas, o Google mostra a busca de {cli_ident}. As outras vêm de buscas raras, que o Google não revela por privacidade.</p>
      <table><thead><tr><th>Busca</th><th class="num">Visitas</th><th class="num">Impr.</th><th class="num">Posição</th></tr></thead>
      <tbody>{tabela_consultas()}</tbody></table>
      <div class="cmp" style="grid-template-columns:1fr 1fr">
        <div><b>{cli_marca}</b><span>visitas de quem buscou o nome da oficina</span></div>
        <div><b>{cli_remap}</b><span>visitas de quem buscou remap e stage, sem conhecer a Veloce</span></div>
      </div>
      <h3 style="margin-top:6mm">Celular ou computador</h3>
      {barras_dispositivo()}
    </div>
  </div>
</div>{foot(3, GSC)}</section>

<section class="page">{head(4)}<div class="in">
  <div class="eyebrow">Oportunidades</div>
  <h2>Muita gente vê os artigos de remap, pouca gente clica</h2>
  <div class="rule"></div>
  <div class="two">
    <div>
      <div class="big">{fmt(imp_remap)}</div>
      <p class="muted" style="margin-top:1.5mm">vezes em que os artigos “stage 1 x stage 2” e “o que é remap” apareceram no Google, {imp_remap*100//11627}% de todas as aparições do site. Eles trouxeram só {cli_remap_pags} visitas.</p>
      <p>Os dois já estão perto da primeira página (posição 8), mas o título e a descrição que aparecem no Google não estão convencendo a pessoa a clicar. <b>Ajustar esses textos é a mudança com maior retorno agora</b>: não precisa criar nada novo, e o efeito costuma aparecer em poucas semanas.</p>
      <h3 style="margin-top:6mm">Páginas de marca ainda longe do topo</h3>
      <p>Nas buscas do tipo “oficina bmw” e “oficina land rover”, o site ainda aparece entre a 15ª e a 60ª posição. É o esperado para páginas com cerca de 5 semanas. O que mais ajuda aqui são as <b>avaliações no Google</b>, que fazem a oficina aparecer no mapa dessas buscas.</p>
    </div>
    <div style="min-width:0">
      <h3>Buscas que mostram o site, mas ainda sem clique</h3>
      <table><thead><tr><th>Busca</th><th class="num">Impressões</th><th class="num">Posição</th></tr></thead>
      <tbody>{tabela_sem_clique()}</tbody></table>
      <h3 style="margin-top:7mm">Páginas de marca no Google</h3>
      <table><thead><tr><th>Página</th><th class="num">Visitas</th><th class="num">Impr.</th><th class="num">Posição média</th></tr></thead>
      <tbody>{tabela_marcas()}</tbody></table>
    </div>
  </div>
</div>{foot(4, GSC)}</section>

<section class="page">{head(5)}<div class="in">
  <div class="eyebrow">Posições acompanhadas</div>
  <h2>Onde o site aparece nas buscas mais importantes</h2>
  <div class="rule"></div>
  <p class="lead" style="margin-bottom:4mm">Acompanhamos 41 buscas toda semana. Cada linha é uma delas: o círculo laranja é a posição em 11/11, o círculo vazio é a posição em 04/11. Verde: subiu. Vermelho: caiu.</p>
  {base.grafico_posicoes()}
  <div class="legend"><span><i style="background:var(--acc)"></i>11/11/2026</span><span><i style="background:var(--paper);border:2px solid var(--muted2)"></i>04/11/2026</span><span><i style="background:var(--ok)"></i>subiu</span><span><i style="background:var(--bad)"></i>caiu</span><span><i style="background:var(--acc-soft)"></i>1ª página</span></div>
  <div class="two" style="margin-top:6mm">
    <div class="box"><h3 class="up-t">O que avançou</h3>
      <p style="font-size:9.3pt">O blog de remap é o que mais cresce: “remap stage 1” entrou em 6º, “remap estraga o motor” subiu para 10º e “remap automotivo” (1.000 buscas por mês) foi de 18º para 12º. As páginas de revisão estrearam bem: “revisão land rover” em 18º e “revisão bmw 320i” em 23º.</p></div>
    <div class="box"><h3 class="down-t">O que estamos acompanhando</h3>
      <p style="font-size:9.3pt">8 buscas saíram dos 100 primeiros nesta leitura semanal, entre elas “oficina lamborghini” e “dinamômetro automotivo”. É comum em site novo. No Search Console, a busca pelo nome da oficina segue em 1º lugar, com 9 visitas.</p></div>
  </div>
</div>{foot(5, UBER)}</section>

<section class="page">{head(6)}<div class="in">
  <div class="eyebrow">O avanço até hoje</div>
  <h2>O que foi feito desde 05/10</h2>
  <div class="rule"></div>
  <div class="facts">
    <div class="fact"><div class="n">{base.paginas}</div><div class="l">páginas novas: 9 de marca, 5 de revisão, 7 de serviço, 7 artigos e o blog</div></div>
    <div class="fact"><div class="n">11,6 → 5,5 s</div><div class="l">tempo para a página inicial mostrar o conteúdo principal</div></div>
    <div class="fact"><div class="n">21</div><div class="l">serviços cadastrados no Google Meu Negócio, cada um com link para o site</div></div>
  </div>
  <ul class="tl">{base.timeline()}</ul>
</div>{foot(6, "Histórico do projeto")}</section>

<section class="page">{head(7)}<div class="in">
  <div class="eyebrow">Próximos passos</div>
  <h2>O que vem agora</h2>
  <div class="rule"></div>
  <p class="lead">Um passo fica com a Veloce e três com a nossa equipe.</p>
  <ol class="steps">
    <li><div><b>Novos títulos para os artigos de remap e stage</b><span class="owner">Equipe de SEO</span><p>Reescrever o título e a descrição que aparecem no Google para os 2 artigos que mais aparecem, para transformar as {fmt(imp_remap)} aparições em visitas.</p></div></li>
    <li><div><b>Pedir avaliação a todo cliente no Google</b><span class="owner">Veloce</span><p>É o que mais pesa para a oficina aparecer no mapa em “oficina bmw”, “oficina land rover” e nas outras buscas de marca.</p></div></li>
    <li><div><b>3 artigos sobre câmbio automático</b><span class="owner">Equipe de SEO</span><p>Apoiam a página de troca de óleo do câmbio, que mira cerca de 8 mil buscas por mês somando as variações.</p></div></li>
    <li><div><b>Relatório de dezembro</b><span class="owner">Equipe de SEO</span><p>Com o primeiro mês inteiro de Search Console, para comparar novembro com dezembro.</p></div></li>
  </ol>
  <div class="eyebrow" style="margin-top:9mm">Como ler este relatório</div>
  <ul class="notes">
    <li><b>Impressão</b> é cada vez que o site aparece numa página de resultados do Google, mesmo que a pessoa não clique.</li>
    <li><b>Clique</b> é cada vez que alguém clica no resultado e entra no site. É o número de visitas vindas do Google.</li>
    <li><b>Posição média</b> é o lugar médio do site na lista de resultados. Até o 10º lugar é a primeira página.</li>
    <li>Os dados de visitas vêm do Google Search Console, a ferramenta oficial do Google. As posições semanais vêm do Ubersuggest, que mede as buscas no Brasil pelo celular.</li>
    <li>Buscas da região, como “oficina bmw” feita em São Paulo, também mostram o mapa do Google com 3 oficinas. Esse espaço vem do Google Meu Negócio.</li>
  </ul>
</div>{foot(7, "Veloce Auto Specialista · Relatório de SEO")}</section>

</body></html>"""
    with open(SAIDA, "w", encoding="utf-8") as f:
        f.write(doc)
    print(f"ok: imp/dia {imp_dia_out:.0f}->{imp_dia_nov:.0f} ({var_imp:+.0f}%), cli/dia {cli_dia_out:.2f}->{cli_dia_nov:.2f} ({var_cli:+.0f}%), marca {cli_marca}, remap {cli_remap}, imp remap {imp_remap}")

if __name__ == "__main__":
    main()

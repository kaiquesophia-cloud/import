# Plano de SEO — Veloce Auto Specialista

> Domínio: `veloceautospecialista.com.br` · Pesquisa feita em 05/10/2026 no Ubersuggest
> (São Paulo capital = `locId 1001773`; Brasil = `2076`). Os volumes são médias mensais
> **arredondadas em faixas** pelo Ubersuggest: servem para priorizar, não para prometer tráfego.

---

## 1. Onde o site está hoje

| Métrica (Ubersuggest, Brasil) | Valor |
|---|---|
| Palavras orgânicas | 2 (só "auto veloci", posição 23) |
| Tráfego orgânico estimado | ~0/mês |
| Autoridade de domínio | 1 |
| Backlinks / domínios de referência | 0 / 0 |

**O site é WordPress + Elementor + WooCommerce.** A última auditoria rastreou 19 URLs, e quase todas
atrapalham:

| URL | Problema | O que fazer |
|---|---|---|
| `/cart/`, `/checkout/`, `/shop/`, `/my-account/` | lixo do WooCommerce (a oficina não vende online) | desativar o WooCommerce ou deixar as páginas `noindex` |
| `/hello-world/`, `/sample-page/`, `/category/uncategorized/` | conteúdo padrão do WordPress | apagar |
| `/elementor-23/` | rascunho publicado | apagar ou redirecionar (301) para a home |
| `/elementor-383/` (título "Contato") | slug sem sentido | renomear para `/contato/` + 301 |
| `/author/kaique/` | página de autor indexada | `noindex` (Yoast/Rank Math → Arquivos de autor) |
| `/pvads/` | landing do Google Ads indexada | `noindex`, para não competir com as páginas de SEO |
| `/about`, `/contact`, `/services` | **404** com link apontando para elas | corrigir os links do menu/rodapé |
| `/servicos/`, `/sobre/` | páginas genéricas, sem palavra-chave | viram hubs (ver §3) |

O título da home ("Especialistas em carros premium") **não contém nenhum termo que alguém pesquisa**.
Ninguém busca "carro premium": as pessoas buscam **"oficina + marca"**.

---

## 2. As palavras-chave (Ubersuggest)

### 2.1 Marca: o centro do plano (intenção de contratar)

| Palavra-chave | Volume SP capital | Volume Brasil | SD* | Observação |
|---|---:|---:|---:|---|
| **oficina bmw** | **110** | 590 | 35 | o maior termo de marca; SERP com *local pack* no topo |
| mecânica bmw | — | 110 | 34 | mesma página da BMW |
| oficina especializada bmw / em bmw | 50 | 210 + 170 | 44 | mesma página |
| revisão bmw | 40 | 210 | 47 | seção ou página filha |
| **oficina land rover** | **70** | 320 | 37 | |
| oficina especializada land rover | 50 | 210 | 35 | mesma página |
| revisão land rover | — | 210 | 16 | SD baixo: boa página filha |
| **oficina especializada em volvo** | **70** | 260 | 32 | |
| oficina volvo / oficina volvo sp | 40 | 320 / 40 | 44 | mesma página |
| oficina mercedes / mercedes benz | 40 + 40 | 210 + 210 | 27–37 | |
| oficina especializada mercedes | 30 | — | 37 | mesma página |
| revisão mercedes | — | 140 | 26 | |
| oficina audi | 40 | 170 | 35 | |
| oficina especializada audi | 30 | — | 42 | mesma página |
| oficina porsche | 20 | 90 | 33 | **SERP só com Instagram/YouTube**: vitória fácil com uma página de verdade |
| oficina porsche são paulo / sp | — | 30 + 10 | 22 | mesma página |
| oficina importados | 40 | 170 | 25 | página "importados" (hub) |
| oficina carros importados | — | 50 | 15 | mesma |
| oficina mini cooper | 20 | — | 42 | opcional, se atenderem MINI |

\* SD = dificuldade de SEO do Ubersuggest (0–100). Abaixo de 40 dá para disputar com uma página bem feita
mais Google Meu Negócio.

### 2.2 Remap / performance: o **maior volume** do nicho

| Palavra-chave | Volume SP capital | Volume Brasil | SD | Intenção |
|---|---:|---:|---:|---|
| **remap automotivo** | **170** | 1.000 | 40 | contratar |
| **remap carro** | **110** | 1.300 | 35 | contratar |
| remap carros / remap de carro | — | 1.000 + 210 | 8–9 | contratar |
| remap sp | 20 | — | 41 | contratar |
| **o que é remap** (+ variações) | — | 880 + 590 + 390 + 210 | 9–24 | informativo |
| **quanto custa um remap** | — | 390 | 23 | comercial |
| remap stage 1 / stage 2 | — | 170 / 320 | 7–9 | informativo |
| quanto custa um remap stage 1 / stage 2 | — | 170 / 140 | 17 | comercial |
| remap de câmbio | — | 260 + 210 | 20–23 | contratar |
| remap estraga o motor | — | 110 | 30 | objeção |
| remap perde garantia | — | 90 | 13 | objeção |
| remap bmw 320i | — | 90 | 36 | contratar (casa com a página BMW) |

Esse é o mesmo padrão da GTorre: lá, quem mais traz tráfego é o conteúdo informativo
(`/escora-para-lajes/`), não a página de aluguel. Aqui, o equivalente é o **"o que é remap"/"quanto custa"**.

### 2.3 Inspeção pré-compra

| Palavra-chave | Volume SP | Volume Brasil | SD |
|---|---:|---:|---:|
| inspeção pré compra carro | — | 40 | 29 |
| inspeção pré compra valor | — | 40 | 23 |
| inspeção pré compra / sp | 10 | 20 + 10 | 24–36 |
| *vistoria cautelar* | *1.600* | — | 40 |

⚠️ "Vistoria cautelar" tem volume alto, mas é um **serviço diferente**: laudo de empresa credenciada
(ECV). **Só use como página de serviço se a Veloce fizer cautelar de fato.** Se não fizer, use num
artigo comparativo ("vistoria cautelar x inspeção pré-compra: qual fazer antes de comprar um importado")
que leva para a inspeção da Veloce.

### 2.4 O que **não** perseguir

- "diagnóstico automotivo" (390/mês): quase tudo é gente procurando **scanner para comprar** ou curso.
  Fica como seção dentro das páginas de marca, não como página-alvo.
- "remap moto" / "remap [cidade fora de SP]": volume alto, cliente errado.
- "oficina carros de luxo", "carro premium": volume 0.

---

## 3. Arquitetura do site (silos)

```
/                                   → Oficina de carros importados em São Paulo (hub geral)
│
├── /oficina-bmw/                   → oficina bmw · mecânica bmw · oficina especializada bmw
│     └── /oficina-bmw/revisao/     → revisão bmw · preço revisão bmw x1 (fase 2)
├── /oficina-land-rover/            → oficina land rover · especializada land rover
│     └── /oficina-land-rover/revisao/ → revisão land rover (+ preço) (fase 2)
├── /oficina-volvo/                 → oficina especializada em volvo · oficina volvo sp
├── /oficina-mercedes-benz/         → oficina mercedes · mercedes benz · especializada
├── /oficina-audi/                  → oficina audi · especializada audi
├── /oficina-porsche/               → oficina porsche (são paulo/sp)
├── /oficina-mini/                  → (opcional)
│
├── /servicos/                      → hub dos serviços (já existe, reescrever)
│     ├── /remap/                   → remap automotivo · remap carro · remap sp
│     │     ├── /remap/stage-1-e-stage-2/  → stage 1, stage 2, quanto custa stage 1/2
│     │     └── /remap/cambio/             → remap de câmbio
│     ├── /inspecao-pre-compra/     → inspeção pré compra (carro, valor, sp)
│     ├── /manutencao-preventiva/   → revisão de importados
│     └── /diagnostico/             → scanner/ECU (apoio, não página-alvo)
│
├── /blog/
│     ├── /blog/o-que-e-remap/                    → o que é remap (+ variações, ~2.000/mês)
│     ├── /blog/quanto-custa-um-remap/            → 390/mês + stage 1/2
│     ├── /blog/remap-estraga-o-motor/            → objeção, 110/mês
│     ├── /blog/remap-perde-garantia/             → objeção, 90/mês
│     ├── /blog/vistoria-cautelar-ou-pre-compra/  → aproveita 1.600/mês em SP
│     └── /blog/quanto-custa-revisao-[marca]/     → revisão bmw/land rover/mercedes (fase 3)
│
├── /sobre/   /contato/
```

**Regras de linkagem interna**
- Home → todas as páginas de marca e de serviço (bloco "Marcas que atendemos" com link em cada logo).
- Cada página de marca → `/remap/`, `/inspecao-pre-compra/` e `/manutencao-preventiva/` ("Serviços para sua BMW").
- Cada artigo do blog → **uma** página de serviço como CTA principal (o-que-é-remap → `/remap/`).
- Página de serviço → as páginas de marca ("Remap para BMW, Audi, Mercedes…").
- Âncora com a palavra-chave ("oficina BMW em São Paulo"), nunca "clique aqui".

### Uma página por intenção, não uma por variação

A GTorre tem 8 páginas quase iguais para "aluguel de escoras / escoramento de laje / … preço / … SP"
e duas cópias (`/escoramento-torre-copy/`, `/escoramento-torre-2/`). Nos dados do Ubersuggest, quem
traz tráfego são só três delas; as outras dividem a força entre si (canibalização). **Na Veloce, não
repita isso**: "oficina bmw", "mecânica bmw" e "oficina especializada em bmw" são a **mesma busca**
para o Google. É uma página só, com as variações no H2 e no texto.

---

## 4. Molde de cada página de marca (exemplo: BMW)

| Elemento | Conteúdo |
|---|---|
| **Title** (≤ 60) | `Oficina BMW em São Paulo \| Especialista – Veloce` |
| **Meta description** (≤ 155) | `Oficina especializada em BMW em São Paulo: diagnóstico com scanner de fábrica, revisão, remap e peças genuínas. Relatório técnico em toda entrega. Agende.` |
| **URL** | `/oficina-bmw/` |
| **H1** | `Oficina especializada em BMW em São Paulo` |
| Hero | 1 frase de promessa + botão WhatsApp + endereço/bairro + nota do Google |
| H2 `Mecânica BMW com padrão de concessionária` | diferencial: scanner (ISTA/equivalente), peças genuínas, relatório técnico |
| H2 `Modelos BMW que atendemos` | Série 1, 3 (320i), 5, X1, X3, X5, M… (palavras de cauda longa) |
| H2 `Serviços para sua BMW` | revisão → `/oficina-bmw/revisao/`, remap → `/remap/`, pré-compra, diagnóstico |
| H2 `Problemas comuns em BMW` | 4–6 problemas reais (vazamento de óleo, bomba d'água, câmbio…): conteúdo que só um especialista escreve |
| Prova | fotos **reais** da oficina com BMW no elevador, 3 avaliações do Google |
| H2 `Perguntas frequentes` | 5 perguntas + schema `FAQPage` ("perde garantia?", "quanto custa a revisão?", "usa peça original?") |
| CTA final | WhatsApp + mapa incorporado + horário |
| Tamanho | 900–1.400 palavras, **texto próprio de cada marca** (não troque só o nome da marca) |

Schema em todas: `AutoRepair` (LocalBusiness) com endereço, telefone, horário, `areaServed: São Paulo`,
`brand` atendidas; `FAQPage` nas FAQs; `BreadcrumbList`.

**Página `/remap/`**: H1 `Remap automotivo em São Paulo`. Seções: o que é, stage 1 x stage 2 (tabela de ganho
por modelo), segurança (log de dados, dinamômetro?), garantia, preço "a partir de" (se o dono aceitar
publicar), FAQ com "estraga o motor?" e "perde garantia?" (cada uma linkando ao artigo completo).

**Página `/inspecao-pre-compra/`**: H1 `Inspeção pré-compra de carros importados em SP`. Checklist do que é
verificado, modelo do relatório (PDF de exemplo), preço/faixa, prazo, "vamos até o vendedor?".

---

## 5. Fora do site (pesa tanto quanto as páginas)

1. **Google Meu Negócio**: o SERP de "oficina bmw" abre com **3 resultados de mapa** antes do primeiro
   site orgânico. Categoria principal "Oficina mecânica" + secundárias "Serviço de reparo de automóveis
   europeus"/"Oficina de BMW" (se disponível). Fotos semanais, todas as marcas nos serviços, link de cada
   serviço para a página correspondente do site, e pedir avaliação a todo cliente citando marca e serviço.
2. **Citações (NAP idêntico)**: melhoresoficinas.com.br (aparece no top 10 de "oficina bmw"), Apontador,
   Yelp, Bing Places, Apple Maps, guias de bairro.
3. **Backlinks**: hoje são 0. Fontes realistas: clubes e grupos de marca (BMW Clube, fóruns de Land Rover e
   Volvo), parceiros (lojas de pneu, estética automotiva, despachante), e entrevistas em blogs automotivos.
4. **Instagram/YouTube**: o SERP de "oficina porsche são paulo" é inteiro de Reels. Cada vídeo publicado
   ali deve ter na legenda o termo exato ("oficina Porsche em São Paulo") e o link da página.

---

## 6. Cronograma

| Fase | Semana | Entregas |
|---|---|---|
| **0. Limpeza** | 1 | apagar ou `noindex` nas 10 URLs do §1, corrigir os 404, título/descrição da home, schema `AutoRepair`, Search Console + sitemap, Google Meu Negócio revisado |
| **1. Dinheiro** | 2–4 | `/oficina-bmw/`, `/oficina-land-rover/`, `/oficina-volvo/`, `/remap/`, reescrita de `/servicos/` e da home |
| **2. Restante das marcas** | 5–7 | `/oficina-mercedes-benz/`, `/oficina-audi/`, `/oficina-porsche/`, `/inspecao-pre-compra/`, `/manutencao-preventiva/` |
| **3. Tráfego (blog)** | 8–12 | o-que-é-remap, quanto-custa-um-remap, remap-estraga-o-motor, remap-perde-garantia, cautelar-x-pré-compra (1 por semana) |
| **4. Profundidade** | 13+ | `/oficina-bmw/revisao/`, `/oficina-land-rover/revisao/`, `/remap/stage-1-e-stage-2/`, `/remap/cambio/`, páginas de modelo (ex.: remap BMW 320i) |

**Como medir:** as palavras-alvo foram registradas no projeto da Veloce no Ubersuggest (atualização
semanal), igual ao projeto da GTorre. Avaliar posição a cada 30 dias; SEO local costuma mostrar efeito
entre 60 e 120 dias. Não declarar sucesso antes de 90 dias de dados.

---

## 7. O que falta o dono responder

1. **Endereço e bairro da oficina.** Com ele dá para criar páginas de região, como a GTorre fez com
   "zona sul" (ex.: "oficina BMW zona sul", "oficina importados Moema").
2. **Quais marcas atendem de verdade?** MINI, Jaguar, Jeep/Ram premium entram ou não?
3. **Fazem vistoria cautelar (credenciada)?** Ou só inspeção pré-compra?
4. **Remap:** fazem em casa ou terceirizam? Tem dinamômetro? Aceitam publicar preço "a partir de"?
5. **Pode desativar o WooCommerce?** Não há loja.
6. Quem tem acesso ao **Google Meu Negócio** e ao **Search Console** do domínio?

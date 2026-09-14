# GAQL sem decorar

A API do Google Ads não tem "um endpoint para cada coisa". Ela tem **uma linguagem de consulta** —
o GAQL (*Google Ads Query Language*) — e você pergunta o que quiser com ela.

Isso assusta quem chega, e é a melhor notícia do kit: **quem escreve a consulta é o agente**. Você
pergunta em português.

---

## Como é uma consulta

Parece SQL, e propositalmente:

```sql
SELECT
  campaign.name,
  metrics.cost_micros,
  metrics.clicks,
  metrics.conversions
FROM campaign
WHERE segments.date DURING LAST_30_DAYS
ORDER BY metrics.cost_micros DESC
```

Três coisas explicam quase tudo:

| Peça | O que é |
|---|---|
| `FROM <recurso>` | **o nível da pergunta**: `campaign`, `ad_group`, `keyword_view`, `search_term_view`, `customer` |
| `metrics.*` | os números — custo, cliques, conversões, impressões |
| `segments.*` | **como fatiar**: por data, dispositivo, hora, rede |

Não existe `JOIN`. O recurso do `FROM` já determina o que dá para pedir junto — por isso a mesma
métrica aparece em vários níveis, e o nível que você escolhe é a pergunta que você está fazendo.

---

## As três armadilhas de leitura

**1. `cost_micros` não é reais.** Custo vem em *micros*: divida por 1.000.000. `35750000` são
R$ 35,75. Os scripts do kit já convertem — mas se você montar uma consulta crua, lembre.

**2. `segments.*` multiplica linhas.** Pedir `segments.date` numa consulta de campanha devolve uma
linha **por campanha por dia**, e o total de cada linha não é o total da campanha. É por isso que
"o número não bate": ninguém errou, você mudou a granularidade.

**3. Métrica sem data pega o período padrão.** Sempre diga o período: `DURING LAST_30_DAYS` ou
`BETWEEN '2026-08-01' AND '2026-08-31'`.

---

## Na prática, você não escreve isso

Peça em português e deixe o agente traduzir:

| Você diz | Ele monta |
|---|---|
| *"quanto cada campanha gastou no mês passado?"* | `FROM campaign` + `metrics.cost_micros` + `DURING LAST_MONTH` |
| *"quais palavras trouxeram clique e nenhuma conversão?"* | `FROM keyword_view` + filtro em `metrics.conversions = 0` |
| *"o que as pessoas digitaram para cair no meu anúncio?"* | `FROM search_term_view` |
| *"converte mais no celular ou no computador?"* | `FROM campaign` + `segments.device` |
| *"que horário eu deveria subir o lance?"* | `FROM campaign` + `segments.hour` |

**Consultas prontas por caso de uso:** [04-consultas-gaql.md](04-consultas-gaql.md).

⚠️ **Nome de campo não se chuta.** O Google tem uma referência de campos por versão da API, e campo
inexistente devolve erro na hora (o que é bom — não devolve dado errado calado). Se o agente
inventar um nome, mande ele conferir na referência antes de tentar de novo.

---

## Quando a consulta vira script

Se você repete a mesma pergunta toda semana, ela merece virar comando. O caminho é o mesmo que os
scripts do kit usam:

```python
from lib import run_query, resolve_customer_id

rows = run_query(resolve_customer_id(), """
    SELECT campaign.name, metrics.cost_micros
    FROM campaign
    WHERE segments.date DURING LAST_7_DAYS
""")
```

Peça ao agente: *"transforma essa consulta num script que eu rodo toda segunda"*. É trabalho de
minutos, e o kit já tem os helpers prontos (`run_query`, `print_json`, conversão de micros e
tratamento de erro) em `scripts/lib/__init__.py`.

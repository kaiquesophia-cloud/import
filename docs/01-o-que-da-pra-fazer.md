# O que dá para fazer pela API — e o que não dá

O que está listado aqui é **o que a plataforma permite**, não o que este kit já embrulhou em
comando. A diferença importa: quando você souber que a API expõe algo, dá para pedir ao agente que
monte a consulta, mesmo sem script pronto.

---

## Ler — e isto é praticamente tudo

A API de leitura do Google Ads é generosa: quase todo número que existe no painel existe em
consulta. O que o kit já traz pronto:

| Função | Comando | O que devolve |
|---|---|---|
| Contas que você alcança | `read.py accounts` | a lista, direto do login |
| Campanhas | `read.py campaigns` | status, tipo, orçamento, métricas |
| Grupos de anúncios | `read.py ad-groups` | por campanha |
| Palavras-chave | `read.py keywords` | com tipo de correspondência e índice de qualidade |
| Anúncios | `read.py ads` | os títulos e descrições de cada RSA |
| **Termos de pesquisa** | `read.py search-terms` | o que a pessoa realmente digitou |
| Negativas | `read.py negative-keywords` | campanha e grupo |
| Índice de qualidade decomposto | `read.py quality-scores` | anúncio, página, CTR esperado |
| Extensões | `read.py extensions` | sitelinks, frases de destaque, snippets |

E as métricas, por recorte:

| Recorte | Comando |
|---|---|
| A conta inteira | `insights.py account` |
| Por campanha | `insights.py campaign` |
| Por grupo | `insights.py ad-group` |
| Por palavra-chave | `insights.py keyword` |
| Dia a dia | `insights.py daily` |
| Por dispositivo | `insights.py device` |
| Por hora do dia | `insights.py hourly` |

> **O recorte que mais rende e ninguém abre:** `search-terms`. Campanha não gasta com a palavra que
> você escolheu — gasta com o que as pessoas **digitaram** e o Google decidiu que combinava. É lá
> que mora o desperdício, e é a matéria-prima da negativação
> (`docs/estrategia/negativacao.md`).

---

## Escrever — com uma trava embutida

| Função | Comando | Nasce como |
|---|---|---|
| Orçamento + campanha | `create.py campaign` | **PAUSED** |
| Grupo de anúncios | `create.py ad-group` | **PAUSED** |
| Palavra-chave | `create.py keyword` | ativa dentro de um grupo pausado |
| Anúncio responsivo (RSA) | `create.py rsa` | **PAUSED** |
| Sitelink e frase de destaque | `create.py sitelink` / `callout` | — |
| Palavra-chave negativa | `create.py negative` | — |
| Campanha inteira, uma palavra por grupo | `create_skag.py` | **PAUSED** |
| Mudar status, orçamento, lance | `update.py` | — |
| Remover palavra, anúncio, campanha | `delete.py` | — |

**Por que tudo nasce pausado.** Porque o erro aqui não é reversível do jeito que parece: você pausa
em dois minutos, mas o dinheiro que saiu não volta, e a campanha entra em aprendizado de novo. A
trava custa um clique seu e evita a categoria inteira de acidente.

---

## Planejar

`keyword_planner.py` conversa com o mesmo serviço que alimenta o Planejador de Palavras-chave do
painel:

| Comando | Devolve |
|---|---|
| `keyword_planner.py ideas --keywords "a\|b"` | ideias derivadas, com volume, CPC e concorrência |
| `keyword_planner.py historical-metrics --keywords "a\|b"` | o histórico de busca dos termos que você já tem |

🧱 **Este é o único bloco que o nível Explorer não alcança.** As ferramentas de planejamento exigem
Basic. Se der erro de permissão só aqui, é isso — não é o script.

---

## O que a API **não** faz

Vale saber antes de tentar:

| Não dá | Por quê / o que fazer |
|---|---|
| Ver campanha criada pelo botão "Promover" do YouTube | são campanhas simplificadas; a API as esconde da tabela `campaign`. Os números aparecem no nível da conta (`insights.py account`) |
| Criar a conta de anúncios | conta se cria no painel; a API opera a que já existe |
| Mexer no faturamento | é serviço restrito, fora do acesso Basic |
| Ver o texto de anúncio do concorrente | isso é a Biblioteca de Anúncios, outro produto |
| Descobrir "a palavra mágica" | volume e CPC ela dá; o que converte no seu negócio, não |

---

## O que muda quando é o agente que opera

A lista acima é a mesma para quem programa na mão. A diferença aparece no meio do caminho:

- **Você não escolhe o recorte antes de saber o que quer.** *"Por que caiu esta semana?"* faz o
  agente ler o dia a dia, olhar por dispositivo, comparar com o mês anterior e voltar com uma
  hipótese — três consultas que você não teria pedido em sequência.
- **O termo de pesquisa vira decisão.** Ler 400 termos é trabalho chato; classificar 400 termos por
  intenção é trabalho que a IA faz bem — e você revisa a lista curta.
- **O anúncio nasce com rubrica.** `docs/estrategia/anatomia-do-anuncio.md` é a régua que o agente
  lê antes de escrever, não um PDF que ninguém abre.

E o que **não** muda: quem decide continua sendo você. A trava do `PAUSED` existe exatamente para
manter essa fronteira.

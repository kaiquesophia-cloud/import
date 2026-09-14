# Construa Sua Primeira SKAG — SOP Completo

Um guia autossuficiente para estruturar e criar campanhas de anúncios focadas em **SKAG (Single Keyword Ad Group / Grupo de Anúncios de Palavra-chave Única)** do zero no Google Ads usando a API.

**O que você terá ao final:**
- 1 campanha (criada como PAUSADA, pronta para revisão).
- 1 grupo de anúncios com uma palavra-chave em correspondência de frase (a SKAG).
- 15 palavras-chave negativas no nível da campanha (evitando tráfego inútil).
- 3 anúncios responsivos de pesquisa (RSAs), cada um com 15 títulos + 4 descrições preenchidos e o título principal fixado no Slot 1.
- Orçamento e lances configurados de acordo com o perfil do seu negócio (Local vs. Infoprodutos).
- **Segurança contra cliques falsos**: Exclusão de países indesejados (para evitar cliques de VPNs ou robôs).

---

## Passo 0 — Perfil do Negócio e Perguntas do Setup (Interativo)

Antes de fazer qualquer chamada de API, o script deve rodar um passo de diagnóstico/perfilamento para entender a natureza do produto a ser promovido. Isso garante que a campanha seja adaptada automaticamente sem engessar as configurações.

### Perguntas de Perfilamento:

1. **Tipo de Negócio (Local vs. Curso/Infoproduto):**
   > *"Qual o tipo do seu negócio? Digite 'local' para serviços locais físicos ou 'curso' para venda de cursos/infoprodutos online."*
   * Se **Local**: O script ativará a geolocalização por raio de proximidade (ex: 50km da sua cidade) e usará ganchos de copy focados em urgência e atendimento local.
   * Se **Curso**: O script ativará a geolocalização em nível nacional (ex: Brasil inteiro) e usará ganchos de copy focados em transformação profissional, bônus e acesso vitalício.

2. **Área de Atuação / País:**
   > *"Em qual país você deseja focar? (Padrão: Brasil)"*
   * Usado para configurar o país principal de segmentação positiva e gerar a lista de exclusão automática para todos os outros países (para mitigar cliques inválidos de VPNs).

3. **Orçamento Diário:**
   > *"Qual o seu orçamento diário pretendido para teste? (Recomendado: R$ 20,00 a R$ 50,00)"*

4. **URL de Destino (Landing Page / Página de Vendas):**
   > *"Qual o link da página para onde o usuário será enviado ao clicar no anúncio?"*

5. **Palavra-chave Principal:**
   > *"Qual a palavra-chave exata que você deseja dominar com este grupo de anúncio? (Ex: 'curso de programacao web' ou 'desentupidora 24h')"*

---

## 9 Configurações Padrão de Campanhas (Local vs. Cursos)

O script define estas configurações no Google Ads para maximizar o retorno sobre investimento (ROI):

| Configuração | Padrão Local | Padrão Cursos / Infoprodutos | Por que fazemos isso |
|---|---|---|---|
| **Tipo de Campanha** | Apenas Rede de Pesquisa | Apenas Rede de Pesquisa | Parceiros de Pesquisa e Display consomem orçamento com cliques de baixa qualidade. |
| **Bidding (Lances)** | Max Conversões (Sem Limite de tCPA no início) | Max Conversões (Sem Limite de tCPA no início) | Permite que o algoritmo colete dados dos primeiros 30 leads antes de fixar um Custo por Aquisição (tCPA). |
| **Programação** | Horário comercial ou 24h com redução noturna | 24 horas por dia | Leads de cursos podem converter a qualquer momento do dia ou da noite. |
| **Locais** | Presença Física (Raio de 50km) | Presença Física (Nacional / Ex: Brasil) | Evita que pessoas de fora pesquisando sobre o local cliquem no anúncio. |
| **Exclusão de Locais** | Todos os outros países do mundo | Todos os outros países do mundo (exceto o país de segmentação) | Kills cliques de fazendas de bots localizadas na Ásia ou cliques acidentais via VPN. |
| **Recomendações Aplicadas Automaticamente** | Desativadas (TUDO OFF) | Desativadas (TUDO OFF) | Recomendações automáticas do Google visam aumentar o gasto do anunciante, não o ROAS. |

---

## Fluxo de Execução Técnica do Script

### Passo 1 — Escolha da Palavra-chave
Escolha um termo de alta intenção alinhado ao seu produto.
* Exemplo local: `desentupidora 24h porto alegre`
* Exemplo de curso: `curso de programacao web`

### Passo 2 — Geração e Upload de Negativas
Adicione palavras que demonstram falta de intenção de compra. O script adiciona automaticamente estas 15 negativas universais à campanha em correspondência ampla:

```
gratis
gratuito
emprego
vagas
salario
trabalho
estagio
como fazer sozinho
pdf download
torrent
crack
login
area do aluno
suporte hotmart
reclame aqui
```

### Passo 3 — Execução do Script de Criação
O script `scripts/create_skag.py` é iniciado, lê o perfil do usuário e faz as mutações na API do Google Ads:
1. Cria o Orçamento da Campanha.
2. Cria a Campanha (como PAUSADA).
3. Associa a Segmentação Geográfica (Positiva do país/raio e Negativa de outros países).
4. Adiciona a lista de negativas.
5. Cria o Grupo de Anúncios (Ad Group).
6. Cria a Palavra-chave (Keyword) em correspondência de frase.
7. Cria as 3 variações do Anúncio Responsivo de Pesquisa (RSAs) seguindo as diretrizes de títulos/descrições em português.

---

*Testado e atualizado de acordo com a API do Google Ads v24.*

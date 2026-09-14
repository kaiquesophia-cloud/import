# Rastreamento de Conversão + Público de Pixel Morno

**Configure o rastreamento de conversão das suas páginas de vendas e crie um público de remarketing com pixel morno (warm-pixel) sem precisar dar cliques no painel do Google Ads.**

---

## O que isso faz, de ponta a ponta:

1. **Diagnóstico rápido:** Coleta informações sobre o seu domínio, valor estimado da venda e nome da ação de conversão.
2. **Criação da Ação de Conversão:** Cria a ação de conversão (ex: "Compra - Curso de Programação" ou "Lead - Inscrição Webinar") via API do Google Ads.
3. **Escrita de Variáveis de Ambiente:** Grava os IDs gerados (Tag ID e Conversion Label) no arquivo `.env.local` do seu projeto Next.js.
4. **Verificação da Tag:** Executa um teste automatizado (usando Playwright) ou guia o teste manual (com Google Tag Assistant) para validar se o pixel de conversão dispara corretamente na página de agradecimento.
5. **Criação do Público Morno:** Cria uma lista de público de remarketing (UserList) contendo qualquer usuário que tenha visitado seu domínio nos últimos 540 dias (limite máximo permitido).
6. **Vínculo no Grupo de Anúncios (RLSA):** Associa essa audiência morna ao seu grupo de anúncios da campanha SKAG em modo de **Observação**, adicionando um ajuste de lance de +50% (para dar lances maiores e garantir que pessoas que já te conhecem vejam seus anúncios de pesquisa novamente).

---

## Pré-requisitos

O Claude deve validar estes itens antes de criar os recursos:
- Conexão ativa com o Google Ads API.
- Projeto de Landing Page/Página de Vendas no mesmo repositório ou pasta indicada.
- Servidor de desenvolvimento Next.js rodando (ou permissão para iniciar).
- Playwright instalado (se for usar o teste automatizado).

---

## Perguntas para o Setup

O assistente coletará os seguintes dados antes de iniciar o mutate da API:

1. **Domínio:**
   > *"Qual o domínio do seu site? (Ex: escolatech.com.br)"*
   * Usado para criar a regra do público-alvo (quem acessa este domínio entra no pixel morno).

2. **Nome da Ação de Conversão:**
   > *"Qual o nome da conversão? (Padrão: 'Compra · Curso Web')"*

3. **Valor da Conversão (Preço do Produto):**
   > *"Qual o valor do seu produto ou o valor médio estimado de um lead em reais? (Ex: 197)"*
   * Crucial para que estratégias de Smart Bidding entendam o valor das vendas geradas.

4. **Grupo de Anúncios ID (Opcional):**
   > *"Qual o ID do grupo de anúncios para vincular a lista de remarketing em modo Observação?"*

---

## Passos Técnicos de Execução

### Passo 1: Criação da Ação de Conversão via API
O script utiliza `ConversionActionService` com a categoria `PURCHASE` (Compra) para cursos ou `SUBMIT_LEAD_FORM` (Inscrição) para páginas de captura.
Os parâmetros recomendados de atribuição de conversão são configurados como **Data-Driven (Baseada em Dados)**.

### Passo 2: Injeção de Variáveis de Ambiente
O script grava no arquivo `.env.local` do projeto web as chaves:
```env
NEXT_PUBLIC_GTAG_ID=AW-XXXXXXXXXX
NEXT_PUBLIC_GADS_CONVERSION_LABEL=AW-XXXXXXXXXX/LabelGeradoPelaAPI
```

### Passo 3: Verificação Automatizada (Playwright)
O script roda um browser em headless que:
1. Acessa a página da oferta.
2. Simula o preenchimento dos dados do formulário de checkout/cadastro.
3. Clica em enviar e aguarda o redirecionamento para a página `/obrigado` (ou similar).
4. Verifica se a chamada de rede para `googletagmanager.com/transport/sender` contendo o parâmetro `conversion` foi realizada com sucesso.

### Passo 4: Criação do Público-Alvo de Pixel Morno
Cria o público "Pixel Morno · Todos os Visitantes · {dominio} · 540d" via `UserListService` com regras flexíveis de URL contendo o seu domínio.
*Nota: A lista morna demora entre 24h a 72h após as primeiras visitas reais para começar a reportar tamanho no Google Ads.*

---

*Última validação: 2026-06-12.*

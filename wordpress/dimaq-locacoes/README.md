# Dimaq Locações — sistema de gestão de locação para WordPress

Plugin de WordPress para locadora de equipamentos, no estilo dos ERPs de locação (como o
PGI-OS da Ridem): **locação, ordens de serviço, vendas, estoque, financeiro e nota fiscal**,
com catálogo no site, pedido de orçamento online e área do cliente.

## Instalação

1. Compacte a pasta `dimaq-locacoes/` em `.zip` (ou copie para `wp-content/plugins/`).
2. No WordPress: **Plugins → Adicionar novo → Enviar plugin** → ativar.
3. Abra **`https://seusite.com.br/sistema`**. É o sistema da locadora, com login próprio e o
   logo da Dimaq, fora do painel do WordPress. (Com links permanentes "simples", o endereço é
   `https://seusite.com.br/?dl_app=1`.)
4. Em **Configurações** (dentro do sistema), preencha os dados da empresa, WhatsApp,
   multa/juros, cláusulas do contrato e dados bancários/PIX.
5. Cadastre a equipe em **Usuários** do WordPress com o perfil **Operador de locação** ou
   **Gerente da locadora**. Eles entram direto pelo `/sistema`; se tentarem abrir o
   `wp-admin`, são mandados para o sistema, e a barra do WordPress não aparece para eles.
6. Configure um SMTP (ex.: plugin WP Mail SMTP) para os e-mails saírem de verdade.
7. Os lembretes diários dependem do WP-Cron. Em hospedagem com pouco tráfego, agende
   `wp-cron.php` no cron do servidor.

Requisitos: WordPress 6.0+, PHP 7.4+, MySQL/MariaDB. A interface já vem com tudo que usa
(Vue 3 e Chart.js dentro do plugin), sem depender de CDN.

## O sistema

- **Painel inicial**: saudação, indicadores clicáveis (em andamento, atrasadas com alerta
  pulsando, vencem hoje, pedidos do site, reservas, frota disponível, utilização, a receber,
  vencido, recebido no mês) e **as locações em andamento em cartões**, cada um com contagem
  regressiva, barra de progresso do período, cor pela urgência, equipamentos, WhatsApp
  pronto e botões de **Devolver** e **Renovar**. Filtros rápidos (atrasadas, hoje, 3 dias,
  semana, em dia), busca, ordenação e visão em lista. Abaixo: agenda de hoje e amanhã
  (saídas e devoluções com botão de ação), funil de orçamentos, alertas (preventiva vencida,
  estoque baixo, OS urgente), gráfico de faturamento de 6 meses e ocupação da frota por
  categoria. Atualiza sozinho a cada minuto.
- **Quadro de locações** (kanban): arraste o cartão de Orçamento para Reservado e o sistema
  reserva; solte em Em locação e abre a entrega; solte em Encerradas e abre a devolução.
- **Agenda da frota**: linha do tempo com cada equipamento numa linha e cada locação numa
  barra (em locação, atrasada, reservada, orçamento). Clique num dia vazio para orçar.
- **Nova locação**: cliente com busca (ou cadastro na hora), duração com um clique,
  equipamentos com **disponibilidade e melhor preço ao vivo** e resumo de valores fixo na tela.
- **Entrega e devolução em janelas**: checklist por item com um toque, horímetro, devolução
  parcial com +/−, prévia das diárias de atraso e das avarias, OS de revisão automática.
- **Rota do dia** para o motorista: entregas e coletas na ordem do caminho, com Maps, Waze,
  ligar e WhatsApp em cada parada, baixa da entrega/coleta pelo celular e a rota completa no
  Google Maps saindo da Dimaq. Filtro por motorista; coleta pode ser agendada para outro dia.
- **Busca geral** no topo (tecla `/`) por contrato, cliente ou equipamento.
- Funciona no celular (menu lateral recolhível e botão flutuante de nova locação).

## O que tem

### Locação (o coração do sistema)
- **Orçamento → reserva → entrega → renovação → devolução → encerramento**.
- **Disponibilidade em tempo real**: bloqueia reservar ou entregar o que já está comprometido
  no período. Funciona para patrimônio unitário (um compactador com nº de série) e por
  quantidade (100 peças de andaime).
- **Tabela de preços** por diária, semanal, quinzenal e mensal, com o botão **"melhor
  tarifa"**, que acha a combinação mais barata para o período (ex.: 10 dias = 1 semana + 3 diárias).
- **Checklist de saída e de retorno** por item, com horímetro.
- **Devolução parcial** (devolve 50 de 60 andaimes e o contrato segue ativo).
- **Atraso**: lança automaticamente as diárias excedentes (com acréscimo % opcional).
- **Avarias**: valor lançado como adicional; pode abrir **OS de revisão** na hora, e o
  equipamento entra em manutenção.
- **Renovação / prorrogação** com recálculo dos valores e checagem de conflito.
- **Caução** (recebida, devolvida, retida).
- **Faturamento** parcelado do contrato, controle de "já faturado" e saldo a faturar.
- Envio do orçamento/contrato por **WhatsApp** ou **e-mail**, com link público assinado.
- Bloqueio de cliente e alertas de inadimplência e de limite de crédito.
- Duplicar contrato como novo orçamento.

### Cobrança por medição (pro-rata) — opcional por locação
Em **Forma de cobrança**, cada locação pode ser *por período* (valor fechado) ou *por medição*.
Na medição, a cada ciclo (padrão 30 dias) o sistema cobra quantidade × dias de cada item que
ficou com o cliente, à diária do contrato (mensal ÷ 30). Devoluções parciais e itens incluídos
entram pela data real (histórico em `dl_movimentos`); frete e desconto vão na 1ª medição;
adicionais na próxima; sem diária de atraso. Cada medição gera conta a receber e um **boletim
de medição**. Só a última medição pode ser cancelada. Regras em `includes/class-dl-measurement.php`;
testes do cálculo em `php tests/test-measurement.php`.

### Rota do dia (entregas e coletas)
Entram as locações reservadas com "Locadora entrega" (ou "entrega e coleta") no dia do início e as
coletas: "entrega e coleta" no dia da devolução prevista ou qualquer locação com **Coleta agendada**
(ação *Agendar coleta*, ou o pedido de coleta da área do cliente, que entra na rota de hoje). O que
ficou para trás aparece hoje como pendente; o que foi feito no dia continua na lista, no fim. A
ordem das paradas fica salva por dia (`dl_rota_ordem`). Endereço: o da entrega, senão o da obra,
senão o do cliente. Regras em `includes/class-dl-route.php`.

### Documentos (imprimir ou salvar em PDF)
Orçamento, contrato de locação (com cláusulas editáveis e variáveis), checklist de saída e
retorno, devolução de equipamento, boletim de medição, fatura de locação, ordem de serviço,
pedido de venda e recibo.

### Ordens de serviço
Preventiva, corretiva, revisão de retorno e serviço para cliente. Peças saem do estoque ao
concluir; o equipamento fica "em manutenção" enquanto houver OS aberta; cobrança opcional ao
cliente. **Preventiva por horímetro** (ex.: a cada 250 h), com alerta no painel.

### Vendas e estoque
Produtos, peças e serviços; entradas, saídas e ajuste de inventário; estoque mínimo. Venda
confirmada baixa o estoque e gera as parcelas; venda cancelada estorna os dois.

### Financeiro
Contas a receber e a pagar, parcelamento, baixa total ou parcial (o saldo vira novo título),
multa e juros calculados automaticamente, estorno, recibo, categorias, fornecedores.

### Nota fiscal
Monta os dados da **NF-e** (venda) e da **NFS-e** (serviço/frete) e envia para um emissor
externo (Focus NFe, PlugNotas, eNotas, NFE.io...) pela URL e pelo token das Configurações.
Com a integração desligada, o registro fica pendente para emitir no portal e anotar
número e chave. O formato de cada emissor muda, então adapte com os filtros
`dl_fiscal_payload` e `dl_fiscal_parse_response` e **teste em homologação antes**.
Locação pura de bem móvel não tem ISS (Súmula Vinculante 31 do STF): para ela, use a
fatura de locação. Confirme o enquadramento com o contador.

### Relatórios (com exportação CSV)
Locados agora · devoluções previstas e atrasadas · **taxa de utilização e receita por
equipamento** (com retorno sobre o valor de aquisição) · faturamento por categoria · fluxo
de caixa · ranking de clientes · inadimplência (com atalho para cobrar no WhatsApp) ·
manutenção da frota · estoque.

### Painel e rotina diária
Indicadores (frota, locados, disponíveis, utilização, atrasos, pedidos do site, a receber,
vencido, recebido no mês) e um e-mail diário com o resumo para a equipe. O cliente recebe
um lembrete antes da devolução e um aviso quando uma fatura vence.

### Site
| Shortcode | O que faz |
|---|---|
| `[dimaq_catalogo]` | Catálogo com busca, filtro por categoria, página do equipamento, especificações, preços e **consulta de disponibilidade com valor** |
| `[dimaq_catalogo destaque="1" limite="6"]` | Vitrine de destaques (home) |
| `[dimaq_categorias]` | Grade de categorias |
| `[dimaq_orcamento]` | Pedido de orçamento: cai no sistema como "Solicitação do site", já precificado, e avisa a equipe por e-mail |
| `[dimaq_area_cliente]` | O cliente logado vê locações, faturas, recibos e notas, aprova orçamento e pede renovação, coleta ou suporte |
| `[dimaq_whatsapp]` | Botão de WhatsApp |

API pública: `GET /wp-json/dimaq/v1/equipamentos` e
`GET /wp-json/dimaq/v1/equipamentos/{id}/disponibilidade?inicio=AAAA-MM-DD&fim=AAAA-MM-DD`.

### Permissões
| Perfil | Acesso |
|---|---|
| Administrador | tudo, inclusive o painel do WordPress |
| Gerente da locadora | sistema completo: operação + financeiro e relatórios |
| Operador de locação | clientes, equipamentos, locações, OS, vendas, estoque (sem financeiro) |
| Cliente da locadora | só a área do cliente no site |

## Para quem for mexer no código

- `includes/class-dl-app.php`: o endereço `/sistema`, o login e a página da aplicação.
- `includes/class-dl-api.php`: a API interna (`/wp-json/dimaq/v1/app/...`), protegida por login,
  nonce e a permissão de cada módulo.
- `assets/app/app.js` e `app.css`: a interface (Vue 3 sem etapa de build; basta editar).
- `includes/class-dl-modules.php` define os campos de cada cadastro; a interface monta listas e
  formulários a partir dele. Para adicionar um campo: coluna em `class-dl-install.php`, suba
  `DL_DB_VERSION` e acrescente o campo no módulo (ou use o filtro `dl_modules`).
- Regras de negócio: `class-dl-contracts.php` (locação), `class-dl-availability.php`,
  `class-dl-finance.php`, `class-dl-service-orders.php`, `class-dl-sales.php`.
- Ganchos úteis: `dl_contract_started`, `dl_contract_returned`, `dl_quote_received`,
  `dl_finance_paid`, `dl_daily_done`.
- Testes: `php tests/test-pricing.php` (preço) e `php tests/test-measurement.php` (medição).
- Rota do dia: `class-dl-route.php` e a rota `GET /wp-json/dimaq/v1/app/route?data=AAAA-MM-DD`.

## Limitações conhecidas

- Não importa os dados do sistema atual: a migração (clientes, frota, contratos em aberto)
  precisa ser feita à parte, por CSV ou script.
- Sem boleto/PIX automático: a baixa é manual (dá para integrar um gateway pelo gancho
  `dl_finance_paid` e pelos lançamentos).
- A emissão de nota depende de contratar um emissor e ajustar o formato dele.

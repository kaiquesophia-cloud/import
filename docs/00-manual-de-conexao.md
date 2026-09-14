# Conectar o Google Ads a um agente de IA (API oficial, sem MCP) — manual mestre

> **Objetivo:** sair do zero (uma conta Google) até o seu agente de IA lendo e escrevendo na
> sua conta de Google Ads pela **API oficial**, com SDK Python e GAQL — sem MCP no meio.
> **Entrada:** uma conta Google e uma conta de anúncios do Google Ads
> **Saída:** `.env` preenchido e `python3 scripts/setup.py test` listando as suas contas.
>
> Todo número aqui foi conferido na documentação do Google em **01/set/2026** e revisto em
> **10/set/2026**, depois que o Google desligou o developer token — inclusive os pontos que
> quase todo tutorial da internet ainda repete errado (ver §11).
>
> 🔴 **Revisado em 10/set/2026, e a revisão é grande.** Em **09/set/2026 o Google desligou o
> developer token**: o acesso à API passou a ser do **projeto no Google Cloud**, a MCC deixou de ser
> obrigatória e o pedido pelo API Center parou de ser processado. Mudaram §2, §3, §4, §5.3, §5.5,
> §7, §10 e a corrente do §0.5; nasceram **§6.5** (inscrever o projeto) e **§6.6** (verificar **e
> publicar** a marca, o pré-requisito do Basic). Os passos do §6.6 foram percorridos na conta da
> casa no dia da revisão — inclusive a recusa que se leva por parar no botão errado.

---

## 0. Como ler isto

| Se você quer… | Vá para |
|---|---|
| **Nunca fez isso na vida** — o que precisa existir antes | **§0.5** — a corrente do zero |
| Entender por que são **duas credenciais** e não uma | **§2** |
| Não confundir nomes parecidos | **§3** — os nomes que colidem |
| Decidir se faz ou contrata | **§1** |
| Decidir **qual nível pedir** | **§4** — a bifurcação que economiza dias |
| Conseguir o **Keyword Planner** (o acesso Basic) | **§6.6** — verificar **e publicar** a marca |
| Executar | **§5** a **§8** |
| Entender por que a automação para sozinha na semana seguinte | **§9** |
| Consertar | **§10** — catálogo de erros |
| Saber o que mudou desde o material antigo | **§11** |

> **Atalho.** Abra esta pasta no Claude Code e diga: *"me conduza por este manual, uma etapa de cada
> vez"*. Ele checa o `.env`, roda o `setup.py` e só avança quando o sinal de sucesso aparece.

---

## 0.5 — A corrente do zero

> Lista **até o passo óbvio de propósito**. É mais barato pular uma linha do que descobrir no meio
> que faltava uma conta. Legenda: `(óbvio)` = quase todo mundo já tem · `⏯️` = candidato a vídeo
> separado · `🧱` = onde trava de verdade.

| # | O que | O que é (para quem nunca viu) | Obrigatório? | Se faltar | |
|---|---|---|---|---|---|
| 1 | Conta Google | um e-mail do Google | sempre *(óbvio)* | não existe nada | — |
| 2 | Conta do **Google Ads** | onde as campanhas rodam | sempre | não há o que ler | — |
| 3 | **Faturamento** configurado nela | cartão ou boleto cadastrado | sempre | a conta existe mas não serve — vira conta suspensa | 🧱 |
| 4 | Conta **administradora (MCC)** | uma conta que gerencia outras contas | **só quem opera várias contas** — deixou de ser obrigatória em 09/set/2026 | você opera uma conta por vez | ⏯️ |
| 5 | A conta de anúncios **vinculada** à MCC | a MCC "adota" a conta onde você anuncia | só quem fez o 4 | a conta não aparece na listagem | — |
| 6 | ~~**Developer token**~~ | ~~o passe que libera a API~~ | ❌ **não existe mais** (§5.5) | — | — |
| 7 | Projeto no **Google Cloud** | a "conta de desenvolvedor" do Google | sempre | não existe OAuth | *(óbvio para quem já programa)* |
| 8 | **Google Ads API ativada** nesse projeto | ligar a API na chavinha | sempre | `SERVICE_DISABLED` | — |
| 9 | **Tela de consentimento OAuth** | a tela que pergunta "permitir acesso?" | sempre | não dá para autorizar | 🧱 |
| 10 | **Credencial OAuth** tipo *App para computador* | o crachá do seu programa | sempre | não há client id nem secret | — |
| 10.5 | **Projeto inscrito na Google Ads API** | é o que define o seu nível de acesso | **sempre** — substituiu o item 6 | a sua conta real não aparece na listagem | 🧱 |
| 11 | **Refresh token** | a autorização, por escrito, para o programa entrar sem você | sempre | login toda hora | 🧱 |
| 12 | **Python 3** + `pip` | o interpretador que roda os scripts | sempre | nada roda | *(óbvio)* |
| 13 | SDK `google-ads` instalado | a biblioteca oficial | sempre | `ModuleNotFoundError` | — |
| 14 | **Claude Code** (ou Codex / Antigravity) | quem conversa e executa | sempre | você digita comando na mão | — |

```
1. conta Google ─┐
2. Google Ads    ├─ o lado do ANÚNCIO — acaba aqui
3. faturamento   │
4. MCC (opcional)┘
                                       ┌──► 10.5 projeto inscrito ──► o seu NÍVEL
7. projeto no Cloud ─┐                 │
8. API ativada       ├─ o lado ────────┤
9. consentimento     │  do CÓDIGO      │
10. credencial ──────┘                 └──► 11. refresh token ──► 13. SDK ──► funciona
```

**A leitura em 5 segundos:** o lado do código carrega quase tudo. Ele dá o *client id*, o *secret*,
o *refresh token* **e o seu nível de acesso**. O lado do anúncio só precisa existir e ter
faturamento. Era diferente até 09/set/2026, quando o developer token saía da MCC e as duas correntes
tinham peso igual.

---

## 1. A bifurcação anterior a tudo: fazer ou contratar

| | Contratar (Optmyzr, Adalysis, agência) | Fazer (este manual) |
|---|---|---|
| **Quanto custa** | assinatura por conta gerenciada | o preço do Claude Code |
| **Quanto demora** | hoje | de 20 minutos a 5 dias úteis (§4) |
| **De quem é a automação** | da ferramenta — você aluga a regra | **sua** — a regra é um arquivo seu |
| **Onde ganha** | relatório e alerta prontos, sem pensar | quando a sua regra é sua mesmo: o nicho, o cliente, o seu jeito de operar |
| **Onde perde** | a ferramenta decide o que é possível | você mantém |

**O critério é posse, não preço:** *de quem é a lógica que decide pausar a campanha?* Se a resposta
precisa ser "minha", é este manual. Se for "tanto faz", pague a ferramenta e vá viver.

> E existe um meio-termo: o **MCP oficial do Google Ads**. Conecta em minutos, mas **só lê** — não
> altera nada. Serve para quem quer análise e nunca operação. Este manual é o outro caso: quando
> você precisa **escrever**.

---

## 2. Por que são duas credenciais, e não uma

É a parte que mais confunde, e fica simples com uma frase: **cada credencial responde a uma pergunta
diferente**.

| Credencial | Responde | Vem de onde | Muda quando |
|---|---|---|---|
| **Client ID + Secret** | *"que programa é esse batendo na porta?"* | **Google Cloud**, credencial OAuth | quase nunca |
| **Refresh token** | *"esse programa tem permissão sua para entrar sem você?"* | o **navegador**, na hora do "Permitir" | 🧱 **em 7 dias, se você não publicar o app** (§9) |
| *(Login) Customer ID* | *"entrando por qual conta?"* | o cabeçalho do painel | quando muda de cliente |

O erro clássico: a pessoa consegue a primeira, acha que terminou, e não entende por que nada
funciona. Faltava a segunda.

> 🔴 **Eram três até 09/set/2026.** O **developer token** respondia a *"o Google liberou você a usar
> a API?"* e vinha da Central de API, dentro de uma MCC. **Ele deixou de ser necessário:** a pergunta
> que ele respondia passou a ser respondida pelo **projeto no Google Cloud** — é o nível do projeto
> que decide o seu acesso (§4). Quem já tem token continua funcionando, porque o campo é aceito e
> ignorado; quem começa hoje **não pede token nenhum**. Bibliotecas atualizadas fazem a chamada sem
> ele. Fonte: [Developer Token](https://developers.google.com/google-ads/api/docs/api-policy/developer-token).

---

## 3. Os nomes que colidem — separe antes de usar

| Isto | **não é** isto |
|---|---|
| **Customer ID** — a conta onde a campanha roda | **Login Customer ID** — a MCC pela qual você entra. Trocar um pelo outro é o erro nº 1 |
| **Conta de teste** do Google Ads — uma conta de anúncios falsa, para desenvolver | **Usuário de teste** da tela de consentimento — o seu e-mail, autorizado a usar um app não publicado |
| ~~**Developer token** — o passe da API~~ | não existe mais no caminho novo (§2). Se um tutorial pedir, ele é de antes de 09/set/2026 |
| **Google Ads API** — o que este manual usa | **Google Ads Scripts** — JavaScript rodando dentro do painel, outra coisa |
| **Manager account** = **MCC** = **conta administradora** | são **três apelidos da mesma coisa** — e o painel usa os três |
| **Verificar a marca** — a tela de consentimento comparada com o seu site | **Publicar a marca** — o botão que vem depois, e sem ele a verificação não vale de nada (§5.6) |
| **Verificar o app** (a marca, no Google Cloud) | **Subir de nível** (o acesso do projeto à API) — são duas filas, e a primeira é pré-requisito da segunda |

---

## 4. A bifurcação que economiza dias: qual nível pedir

Existem **quatro** níveis de acesso, e a maioria dos tutoriais só conhece dois. O que muda entre eles
é *quais contas você alcança* e *quantas operações por dia*.

⚠️ **Desde 09/set/2026 o nível é do PROJETO no Google Cloud, não mais do token.** Quem já usava a
API **herdou** o nível, calculado pela atividade dos últimos 90 dias — confira antes de pedir
qualquer coisa, você pode já estar em Basic.

| Nível | Alcança | Limite diário | Exige marca verificada? | Como se consegue |
|---|---|---|---|---|
| **Test** | só contas de **teste** | 15.000 operações | ❌ não | é como todo projeto nasce |
| ⭐ **Explorer** | contas **de produção** | **2.880 operações** | ❌ **não** | **na própria inscrição**, na hora |
| **Basic** | produção **+ Keyword Planner** | 15.000 operações | ✅ **sim** | minutos, depois da marca **publicada** (§5.6) |
| **Standard** | produção | ilimitado | ✅ sim | auditoria humana, ~10 dias úteis |

**A escada não se pula:** `Test → Explorer → Basic → Standard`. Não existe ir de Test direto para
Basic.

**A decisão:**

```
Quer ler a conta, montar relatório, criar campanha, negativar termo?
   └──► o Explorer resolve. Comece HOJE, sem pedir nada a ninguém.

Quer pesquisa de palavra-chave (volume, CPC, concorrência)?
   └──► 🧱 o Explorer NÃO alcança as ferramentas de planejamento.
        É o único item que cobra o degrau do Basic — e o Basic cobra a marca (§5.6).
```

> **O erro de decisão mais caro, hoje:** pedir o Basic **antes** de publicar a marca. O pedido é
> aceito, some por um tempo e volta como um e-mail de recusa — *"Basic Access requires a successfully
> verified OAuth brand profile"*. Você não perde o acesso que já tem, perde a viagem. E o inverso,
> mais silencioso: montar tudo no Explorer e descobrir no meio da operação que o Keyword Planner
> devolve erro de permissão.

⚠️ **O nível Test engana.** Ele **não dá erro** ao consultar uma conta de produção — a conta
simplesmente **não aparece na lista**. Parece problema de permissão, e não é.

⚠️ **O que conta como "operação".** Um relatório inteiro, por maior que seja, conta como **1**. Quem
consome o limite é a escrita, porque cada item mexido conta um: negativar 200 termos são 200
operações. É o que faz 2.880 render mais do que parece.
[Fonte](https://developers.google.com/google-ads/api/docs/best-practices/quotas)

---

## 5. O lado do anúncio (passos 1 a 6)

### 5.1 A conta de anúncios *(óbvio)*
Você já tem, se já anunciou. **Sinal de sucesso:** abre `ads.google.com` e vê campanhas, mesmo
pausadas.

### 5.2 Faturamento configurado
Conta sem forma de pagamento vira conta suspensa, e a API responde por ela.
**Sinal:** o painel não mostra faixa vermelha pedindo faturamento.

### 5.3 Criar a conta administradora (MCC) — *opcional desde 09/set/2026*
Em `ads.google.com/home/tools/manager-accounts/`, crie uma conta administradora. É uma conta
**separada** da sua conta de anúncios — ela não anuncia, ela gerencia.

- 🔴 **Era obrigatória, e não é mais.** A MCC existia aqui por um motivo só: a **Central de API**,
  único lugar onde se pedia o developer token, só existe dentro dela. Sem token, sem motivo. Hoje a
  MCC só faz falta para quem vai **gerenciar várias contas** pela mesma API — quem opera uma conta
  só pode pular este passo e o 5.4 inteiros.
- ⛔ **Não pode ser uma MCC de teste** — o Google recusa.
- **Sinal de sucesso:** no topo direito aparece um **Customer ID** no formato `123-456-7890`.
  Guarde: é o seu `GOOGLE_ADS_LOGIN_CUSTOMER_ID` (sem hífens).

### 5.4 Vincular a conta de anúncios à MCC *(só quem fez o 5.3)*
Dentro da MCC, procure **Contas → Vincular conta existente** e informe o Customer ID da conta onde
você anuncia. Depois **aceite o convite** — ele chega no e-mail da conta vinculada e também aparece
como notificação no painel dela.

- **Sinal de sucesso:** a conta aparece na lista de subcontas, sem "convite pendente".
- 🧱 Muita gente para aqui sem perceber: o convite fica esperando aceite e nada funciona.

### 5.5 🔴 Pedir o developer token — *este passo deixou de existir*

Todo tutorial que você vai achar manda vir aqui, entrar na **Central de API** da MCC
(`ads.google.com/aw/apicenter`) e pedir um developer token. **Não faça.**

Em **09/set/2026 o Google encerrou esse caminho.** A própria tela avisa, com estas palavras:

> *"O acesso à API Google Ads mudou · Os tokens de desenvolvedor não são mais necessários · Os
> níveis de acesso à API agora são gerenciados exclusivamente no console do Google Cloud."*

O que isso significa na prática:

- **Pedido aberto lá não é processado.** Quem abrir espera uma resposta que nunca chega. Os pedidos
  que estavam pendentes antes da data foram **fechados**.
- **O acesso virou do projeto** no Google Cloud — é o **§6.5**, do outro lado da corrente.
- **Token antigo continua valendo:** o campo é aceito e ignorado. Quem já usa só precisa atualizar a
  biblioteca e parar de mandá-lo.
- **A página continua existindo** por uma exceção só: quem usa a *App Conversion Tracking and
  Remarketing API* ainda pede token por lá. Não é o caso deste manual.

➡️ **Pule para o §6.** O lado do anúncio acabou aqui.

---

## 6. O lado do código (passos 7 a 10)

### 6.1 Criar o projeto no Google Cloud
`console.cloud.google.com` → novo projeto. O nome é indiferente.
**Sinal:** o seletor no topo passa a mostrar o nome do projeto.

### 6.2 Ativar a Google Ads API
Procure por "Google Ads API" na biblioteca de APIs do projeto e clique em **Ativar**.
**Sinal:** o botão vira "Gerenciar".
🧱 Sem isso o erro é `SERVICE_DISABLED` — e ele **não** menciona o Google Ads em lugar nenhum.

### 6.3 Configurar a tela de consentimento OAuth 🧱
Tipo de usuário: **Externo**. Preencha nome do app, e-mail de suporte e e-mail do desenvolvedor.

🔴 **Aqui mora a armadilha mais cara deste manual, e ela só cobra uma semana depois.** Leia o §9
**antes** de escolher o status de publicação.

### 6.4 Criar a credencial OAuth
**Credenciais → Criar credenciais → ID do cliente OAuth**, tipo **App para computador** (*Desktop
app*) — não é "aplicativo da Web".

**Sinal de sucesso:** aparecem o **ID do cliente** (termina em `.apps.googleusercontent.com`) e a
**chave secreta**. Dá para baixar o JSON.

### 6.5 Inscrever o projeto — é aqui que o acesso nasce 🧱

> Substitui o antigo §5.5. Desde 09/set/2026 **o nível de acesso é do projeto que gerou as
> credenciais OAuth do §6.4** — não de um token à parte.

Abra [console.cloud.google.com/google/ads-apis/overview](https://console.cloud.google.com/google/ads-apis/overview)
com o projeto do §6.1 selecionado e inscreva o projeto. É um formulário curto, **em inglês**.

O que ele pergunta, e as respostas que passam:

- **O site da empresa** — o real, o mesmo do cadastro. 🧱 **URL genérica é recusada.** `test.com` ou
  um site vazio derrubam o pedido; perfil do **GitHub** ou do **LinkedIn** é aceito.
- **O que a empresa faz e como usa o Google Ads** — objetivo, sem enrolação.
- **Quem vai usar** — responda **uso interno**, só a sua equipe. Dizer que terceiros vão operar puxa
  uma verificação a mais.
- O e-mail de contato precisa ser um que você **lê**: é por lá que o Google responde.

**O que escrever sobre o uso**, quando é ferramenta interna (o caso deste manual):

> Uso interno para automação e relatórios das nossas próprias contas. A ferramenta puxa relatórios
> de desempenho, avalia palavras-chave, audita termos de pesquisa e aplica palavras-chave negativas
> aprovadas por um operador humano. Não é revendida a terceiros.

**Sinal de sucesso:** a mesma página passa a mostrar o nível. Se disser **Exploração** (2.880
operações/dia), acabou aqui: você já alcança **conta real** e o resto do manual funciona inteiro.
Só quem quer o **Keyword Planner** precisa do §6.6.

### 6.6 Subir para Basic — verificar **e publicar** a marca 🧱

> Só é necessário para **volume e CPC de palavra-chave** (Keyword Planner). Todo o resto —
> ler, relatar, criar campanha, negativar — roda no Explorer.

A verificação de marca é **pré-requisito** do Basic: sem ela o pedido é recusado por e-mail. Ela não
é um formulário sobre a API, é a sua **tela de consentimento OAuth sendo comparada com o seu site**.

| # | Passo | Onde | Sinal de sucesso |
|---|---|---|---|
| a | 🔴 Verificar o **domínio** | [search.google.com/search-console](https://search.google.com/search-console) — com a **mesma conta** que é dona do projeto | o domínio aparece como propriedade verificada |
| b | Público **Externo** + **Em produção** | Tela de permissão OAuth → Público-alvo | não diz mais "Em teste" |
| c | Preencher o **Branding** | [console.cloud.google.com/auth/branding](https://console.cloud.google.com/auth/branding) | nome do app · e-mail de suporte · logo (quadrado, 120×120, até 1 MB) · página inicial · política de privacidade · termos · **domínios autorizados** |
| d | **Verificar branding** | mesma tela, canto superior direito | o status vira **Pronto para publicar** |
| e | 🔴 **PUBLICAR branding** | mesma tela | a marca passa a valer — é só agora que a verificação existe para o Google |
| f | Conferir o nível | a página do §6.5 | virou **Basic**. Se ainda disser Exploração, peça o Basic ali de novo: agora a análise é automática e sai em minutos |

> 🔴 **O passo (e) é onde quase todo mundo para, e a tela não avisa.** Verificar e publicar são
> **dois botões diferentes**. Parando no (d), o console mostra a marca como verificada e o pedido de
> Basic mesmo assim volta recusado, com este e-mail: *"Basic Access requires a successfully verified
> OAuth brand profile. Your application could not be approved because you haven't completed brand
> verification for your project."* Publicado o branding, o Basic sai na sequência.
> **Constatado na conta da casa em 10/set/2026** — o pedido foi recusado exatamente assim, e o
> acesso saiu minutos depois de clicar em publicar.

**A verificação do domínio (passo a) é o item que mais trava**, porque a tela do Cloud não diz onde
se resolve. O caminho curto é propriedade do tipo **Domínio**, com um registro **TXT** no DNS: cobre
o domínio inteiro e os subdomínios de uma vez, e o registro **não pode ser removido depois** — sair
o TXT, cai a propriedade.

**Os dois motivos de recusa mais comuns**, com a frase que a tela devolve:

1. 🔴 *"O site do URL da sua página inicial não está registrado para você"* — é o passo (a) faltando.
2. 🔴 *"O nome do app não corresponde ao nome na sua página inicial"* — nome de rascunho
   (`app-apagar`, `meu-projeto`) reprova. Use **o nome que o site anuncia**, o do `<title>`.

⏱️ **O resultado da verificação vale 7 dias.** Passou disso sem publicar, verifica de novo. A análise
automática sai em minutos; caindo em revisão manual, são 2 a 3 dias úteis.
Fontes: [Brand Verification](https://developers.google.com/google-ads/api/docs/api-policy/brand-verification)
· [Submit for brand verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/brand-verification).

⚠️ **Quem já tinha acesso está isento** da verificação de marca — a cobrança é para pedido novo.

---

## 7. Instalar e preencher

```bash
pip3 install google-ads google-auth-oauthlib protobuf
```

Preencha o `.env` (copie do `.env.exemplo`):

```env
GOOGLE_ADS_CLIENT_ID="....apps.googleusercontent.com"    # §6.4
GOOGLE_ADS_CLIENT_SECRET="..."                           # §6.4
GOOGLE_ADS_REFRESH_TOKEN=""                              # sai no §8, deixe vazio
GOOGLE_ADS_LOGIN_CUSTOMER_ID="1234567890"                # só quem usa MCC, sem hífens (§5.3)
GOOGLE_ADS_CUSTOMER_ID="0987654321"                      # a conta que você opera, sem hífens
```

> **Não existe `GOOGLE_ADS_DEVELOPER_TOKEN` aqui, e não é esquecimento** (§2). Se o seu `.env.exemplo`
> ainda pede, ele é anterior a 09/set/2026. Quem **já tem** um token pode mantê-lo: o campo é aceito
> e ignorado.

**Sinal de sucesso:** `python3 scripts/setup.py check` mostra `OK` em tudo, menos no refresh token.

---

## 8. Gerar o refresh token

```bash
python3 scripts/setup.py oauth
```

O script abre o navegador, você escolhe a conta Google e clica em **Permitir**. Ele recebe o retorno
numa porta local (8080-8090), grava o token no `.env` sozinho e fecha.

**Sinal de sucesso:** a página do navegador diz "Pronto!" e o terminal mostra o token mascarado.

Depois:

```bash
python3 scripts/setup.py test
```

**Sinal de sucesso — e o fim do manual:** a lista das contas acessíveis, formatada `123-456-7890`.
Se a sua conta de anúncios estiver nessa lista, acabou: o agente já lê e escreve nela.

---

## 9. 🔴 A armadilha dos 7 dias

**O fato, apurado na documentação do Google em 01/set/2026:**

> Um projeto com a tela de consentimento configurada como **Externo** e com status de publicação
> **"Testing"** recebe um refresh token que **expira em 7 dias**.

Ou seja: você monta tudo, testa, funciona, comemora — e **na semana seguinte a automação para
sozinha**, com `invalid_grant`. O erro não diz "seu app está em modo de teste"; parece problema de
credencial, e a pessoa refaz o OAuth (que funciona por mais 7 dias) sem nunca entender o ciclo.

**O conserto:** na tela de consentimento, mude o status de publicação para **"In production"**
(*Em produção*).

O que acontece quando você faz isso, dito com honestidade:

- O app fica **não verificado**, e o Google mostra uma tela de aviso na hora de autorizar. Você
  clica em **Avançado → Ir para (não seguro)**. É o seu app, a sua conta, o seu dado.
- Existe um teto de **100 usuários** para app não verificado. Como o usuário é **você**, ele nunca
  te alcança.
- Verificação formal só passa a fazer sentido se um dia você distribuir isso para outras pessoas.

⚠️ **Um refresh token também morre** se ficar **6 meses** sem uso, se você revogar o acesso em
`myaccount.google.com/permissions`, ou se passar de 100 tokens para o mesmo client.

---

## 10. Catálogo de erros — o que a mensagem diz × o que ela significa

| O que aparece | O que é de verdade | Conserto |
|---|---|---|
| Conexão OK, mas **a conta não aparece na lista** | o **projeto** ainda está no nível **Test** — só enxerga conta de teste | inscrever o projeto (§6.5); o Explorer sai na hora |
| `PERMISSION_DENIED` numa conta que você vê no painel | falta o `login_customer_id` — a MCC pela qual você entra | preencher `GOOGLE_ADS_LOGIN_CUSTOMER_ID` |
| `invalid_grant` **uma semana depois** de funcionar | o app OAuth ficou em "Testing" — token de 7 dias | §9, e gerar o token de novo |
| `SERVICE_DISABLED` | a Google Ads API não foi ativada no projeto do Cloud | §6.2 |
| "O Google não retornou refresh token" | o app já tinha sido autorizado antes | revogar em `myaccount.google.com/permissions` e refazer |
| `RESOURCE_EXHAUSTED` | teto diário de operações | no Explorer são 2.880/dia (§4) |
| Erro de permissão **só no Keyword Planner** — *"not allowed for use with explorer access"* | é o único recurso que o Explorer não alcança | subir para Basic (§6.6) |
| Campanha criada pelo botão "Promover" do YouTube **não aparece** | é campanha simplificada; a API a esconde da tabela `campaign` | os dados dela aparecem no nível da conta (`customer`) |
| `DEVELOPER_TOKEN_NOT_APPROVED` | apesar do nome, hoje é o **projeto** em nível insuficiente — não um token | §6.5, ou §6.6 se for no Keyword Planner |
| E-mail **"Basic Access Denied"** falando em *brand profile* | a marca foi verificada mas **não publicada** — são dois botões | §6.6, passo (e) |
| `PAGE_SIZE_NOT_SUPPORTED` | mandou `pageSize` na busca | tirar o campo: a página é fixa em 10.000 linhas |

---

## 11. O que quase todo tutorial ainda ensina errado

### 11.0 A mudança de 09/set/2026 — o developer token acabou

É a correção que envelhece **todo** tutorial de Google Ads API que existe hoje na internet:

1. **"Peça o developer token na Central de API."** Morreu. Pedido aberto lá não é
   processado, e os que estavam na fila foram fechados. O nível agora é do **projeto** que
   gerou o OAuth (§6.5).
2. **"A MCC é obrigatória."** Era, e só por causa do token. Hoje ela serve a quem gerencia
   várias contas (§5.3).
3. **"São três credenciais."** São duas (§2).
4. **"O Basic leva ~5 dias úteis."** Não passa mais por equipe de compliance: sai em
   **minutos**, desde que a marca esteja verificada **e publicada**. O que demora é a
   marca, não a análise (§6.6).
5. **Quem já usava a API herdou o nível**, calculado pela atividade dos últimos 90 dias.
   Vale conferir antes de pedir qualquer coisa.

⚠️ E a armadilha nova: **verificar a marca e publicar a marca são dois botões**, e parar no
primeiro devolve o pedido de Basic recusado, com um e-mail que fala em verificação
incompleta sem dizer qual botão falta. Passo a passo em `docs/06-subir-para-basic.md`.

⚠️ **A biblioteca importa:** abaixo da versão 32, o SDK Python **exige** um developer token
que não é mais emitido, e recusa a chamada com *"developer-token parameter is missing"*.
Por isso o `requirements.txt` pede `google-ads>=32`.

### 11.1 E as de antes *(conferidas em 01/set/2026)*

1. **Existe um nível que quase ninguém cita: o Explorer.** Ele alcança **conta de
   produção** e sai **na hora** — 2.880 operações por dia, sem as ferramentas de
   planejamento. Isso derruba o "pedágio de dois dias" que os tutoriais apresentam como
   inevitável.
2. **"Configure como Externo e adicione seu e-mail como usuário de teste."** Essa
   instrução, sozinha, é a receita do **token de 7 dias** (§9). Não está errada para
   testar — está errada como configuração final, e é o que mais quebra automação de Google
   Ads meses depois de montada.
3. **"A aprovação leva 24-48 horas."** Nunca foi verdade, e hoje a pergunta nem é essa: o
   Explorer sai na inscrição e o Basic depende da marca, não de fila.

> A regra que fica: **número de plataforma se confere na fonte no dia em que se escreve.**
> Os erros acima circulam em documento que parece certo e traz data de validação.

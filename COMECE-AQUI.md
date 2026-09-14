# Começar aqui — a configuração, uma vez só

> 🔴 **Mudou em 09/set/2026.** O Google desligou o **developer token**: quem decide o seu acesso à
> API agora é o **projeto no Google Cloud**. Se você achar um tutorial mandando pedir token na
> Central de API, ele é de antes dessa data — o pedido lá **não é mais processado**.

O lado do **anúncio** hoje quase não pede nada: basta uma conta do Google Ads com faturamento. Quem
carrega a configuração é o lado do **código**, que dá o client id, o secret, o refresh token **e o
seu nível de acesso**.

```
lado do ANÚNCIO   →  conta Google Ads com faturamento   (MCC só se você opera várias contas)
lado do CÓDIGO    →  projeto no Cloud → API ativada → consentimento → credencial → INSCRIÇÃO
                                              ↓
                                     .env → refresh token → funciona
```

> O passo a passo completo, com o que aparece em cada tela, mora em
> **[docs/00-manual-de-conexao.md](docs/00-manual-de-conexao.md)**. Este arquivo é o resumo
> executável: onde travar, volte lá.

---

## Lado do anúncio

### 1. Tenha uma conta do Google Ads com faturamento

Você já tem, se já anunciou. Conta sem forma de pagamento vira conta suspensa, e a API responde por
ela.

✅ **Deu certo quando:** o painel não mostra faixa vermelha pedindo faturamento.

### 2. *(Opcional)* Conta administradora (MCC)

`ads.google.com/home/tools/manager-accounts/`

**Só faça este passo se você vai operar várias contas** pela mesma configuração — a sua e as dos
seus clientes, por exemplo. Quem opera uma conta só pula direto para o passo 3.

> Ela era obrigatória por um motivo que deixou de existir: a Central de API, único lugar onde se
> pedia o developer token, só existe dentro de uma MCC.

Criando a MCC, vincule a conta de anúncios: **Contas → Vincular conta existente** → informe o
Customer ID → e **aceite o convite**, que chega no e-mail da conta vinculada.

✅ **Deu certo quando:** aparece um Customer ID no topo (`123-456-7890`, é o seu
`GOOGLE_ADS_LOGIN_CUSTOMER_ID`, sem hífens) e a conta vinculada aparece na lista **sem** "convite
pendente".
🧱 Convite não aceito = nada funciona depois, e o erro não fala em convite.

---

## Lado do código

### 4. Crie um projeto no Google Cloud

`console.cloud.google.com` → novo projeto. O nome não importa.

### 5. Ative a Google Ads API

Procure "Google Ads API" na biblioteca de APIs e clique em **Ativar**.
✅ **Deu certo quando:** o botão vira "Gerenciar".
🧱 Sem isso, o erro que aparece depois é `SERVICE_DISABLED` — e ele não menciona o Google Ads.

### 6. Configure a tela de consentimento — e **publique o app**

Tipo de usuário: **Externo**. Preencha nome do app e os e-mails.

🔴 **Este é o passo que quase todo tutorial ensina errado.** Depois de preencher, mude o status de
publicação para **"In production"** (*Em produção*).

**Por quê:** um app **Externo** em status **"Testing"** recebe um refresh token que **expira em 7
dias**. Você configura tudo hoje, funciona a semana inteira, e na outra segunda a automação para
sozinha com `invalid_grant` — um erro que não diz o que aconteceu.

Ao publicar, o Google mostra uma tela de "app não verificado" na hora de autorizar. Clique em
**Avançado → Ir para (não seguro)**. É o seu app, a sua conta, o seu dado. O teto de 100 usuários
de app não verificado nunca te alcança, porque o usuário é você.

### 7. Crie a credencial OAuth

**Credenciais → Criar credenciais → ID do cliente OAuth** → tipo **App para computador**
(*Desktop app*). Não é "aplicativo da Web".

✅ **Deu certo quando:** aparecem o **ID do cliente** (termina em `.apps.googleusercontent.com`) e a
**chave secreta**.

### 7.5 Inscreva o projeto — é aqui que o seu acesso nasce 🧱

[console.cloud.google.com/google/ads-apis/overview](https://console.cloud.google.com/google/ads-apis/overview),
com o projeto do passo 4 selecionado. É um formulário curto, **em inglês**.

- **Site da empresa:** o real. 🧱 URL genérica é recusada — `test.com` ou um site vazio derrubam o
  pedido. Seu GitHub ou LinkedIn serve.
- **O que a empresa faz e como usa o Google Ads:** objetivo, sem enrolação.
- **Quem vai usar:** responda **uso interno**, só a sua equipe. Dizer que terceiros vão operar puxa
  uma verificação a mais.

✅ **Deu certo quando:** a página passa a dizer **Exploração** — 2.880 operações por dia, e sua
**conta real** já é alcançada. É o suficiente para tudo que este kit faz.

| Se disser | Significa |
|---|---|
| **Test** | é como todo projeto nasce: só alcança conta de **teste**. Inscreva o projeto |
| ⭐ **Exploração** *(Explorer)* | 🎉 você opera **hoje**, em conta de produção — 2.880 operações/dia |
| **Basic** | 15.000 operações/dia **+ pesquisa de palavra-chave** (volume e CPC) |

⚠️ **Só a pesquisa de palavra-chave fica de fora do Explorer.** Se você precisa de volume e CPC, o
caminho para o Basic está em **[docs/06-subir-para-basic.md](docs/06-subir-para-basic.md)** — e tem
uma armadilha lá que custa uma recusa por e-mail. Todo o resto funciona sem isso.

⚠️ **Se você já usava a API antes de 09/set/2026**, o seu nível foi transferido sozinho, calculado
pelos últimos 90 dias. Confira nessa página antes de pedir qualquer coisa: pode já estar em Basic.

---

## Juntando as duas pontas

### 8. Instale e preencha

```bash
pip3 install -r requirements.txt
cp .env.exemplo .env
```

> 🪟 **No Windows, faça isto uma vez por sessão:** `set PYTHONIOENCODING=utf-8`. Sem isso, o
> terminal (que é cp1252) corrompe os acentos que a API devolve em UTF-8, e o nome da sua
> campanha aparece como `Automa��o`. O dado está certo — quem corrompe é a tela.

Preencha o `.env` com o que você juntou:

| Variável | De onde veio |
|---|---|
| `GOOGLE_ADS_CLIENT_ID` | passo 7 |
| `GOOGLE_ADS_CLIENT_SECRET` | passo 7 |
| `GOOGLE_ADS_CUSTOMER_ID` | a conta que você opera, **sem hífens** |
| `GOOGLE_ADS_REFRESH_TOKEN` | **deixe vazio** — sai no passo 9 |
| `GOOGLE_ADS_LOGIN_CUSTOMER_ID` | *opcional* — passo 2, a MCC, **sem hífens**. Vazio se você não tem |
| `GOOGLE_ADS_DEVELOPER_TOKEN` | *opcional, e você provavelmente não tem* — **deixe vazio** |

> **Sobre o developer token:** ele deixou de ser emitido em 09/set/2026 e o campo só continua no
> arquivo por compatibilidade com quem já tinha um. Vazio é o normal hoje.
> 🧱 Por isso o `requirements.txt` pede **`google-ads>=32`**: as versões anteriores da biblioteca
> **exigem** o token e recusam a chamada com *"developer-token parameter is missing"*.

🧱 **Não troque um ID pelo outro.** `LOGIN_CUSTOMER_ID` é por onde você *entra* (a MCC);
`CUSTOMER_ID` é onde a campanha *roda*. Trocar os dois é o erro nº 1 de quem usa MCC, e o
sintoma é `PERMISSION_DENIED` numa conta que você enxerga no painel.

```bash
python3 scripts/setup.py check
```

✅ **Deu certo quando:** tudo `OK`, menos o refresh token. As linhas marcadas `(opcional)` podem
ficar vazias — elas não travam nada.

### 9. Autorize

```bash
python3 scripts/setup.py oauth
```

Abre o navegador, você escolhe a conta e clica em **Permitir** (passando pela tela de app não
verificado). O script grava o token no `.env` sozinho.

✅ **Deu certo quando:** o navegador diz "Pronto!" e o terminal mostra o token mascarado.

### 10. A prova

```bash
python3 scripts/setup.py test
```

✅ **Acabou quando:** a lista das suas contas aparece, formatada `123-456-7890`. Se a conta que
você quer operar estiver aí, está tudo pronto.

🧱 **A conta não apareceu e não deu erro nenhum?** O seu **projeto** ainda está no nível **Test** —
ele não dá erro, a conta simplesmente não existe para ele. Volte ao passo 7.5 e inscreva o projeto:
o Explorer sai na hora. *(Se você usa MCC, a outra causa é o convite não aceito no passo 2.)*

---

## Agora é conversa

```bash
cp contas.yaml.exemplo contas.yaml     # opcional: apelido → conta, para pedir pelo nome
python3 scripts/setup_perfil.py        # opcional: define negócio local × online (usado pelo create_skag)
claude                                  # e converse
```

Peça em português:

> *"quanto cada campanha gastou nos últimos 30 dias?"*
> *"quais termos de pesquisa gastaram mais de R$ 50 e não converteram?"*
> *"cria uma campanha de busca para 'conserto de notebook', R$ 30 por dia"*

Lembre que **tudo nasce pausado**. Quem liga é você.

# Subir para o acesso Basic — verificar **e publicar** a marca

> **Você só precisa disto para uma coisa: pesquisa de palavra-chave** (volume de busca, CPC,
> concorrência — o Keyword Planner). Ler a conta, montar relatório, criar campanha, negativar termo:
> tudo isso já funciona no **Explorer**, que sai na inscrição do projeto, sem verificação nenhuma.
>
> Se você não precisa de volume e CPC, **feche esta página**. Ela é o degrau mais longo do caminho.

---

## O que é, de verdade

O nome engana. "Verificação de marca" não é um formulário sobre a sua API: é a sua **tela de
consentimento OAuth sendo comparada com o seu site**. O Google confere se o app que pede permissão
pertence mesmo a quem diz pertencer.

Ela é **pré-requisito** do Basic. Pedindo o Basic sem ela, o pedido é aceito, some por um tempo e
volta como e-mail de recusa:

> *"Basic Access requires a successfully verified OAuth brand profile. Your application could not be
> approved because you haven't completed brand verification for your project."*

---

## O passo a passo

| # | Passo | Onde | ✅ Deu certo quando |
|---|---|---|---|
| a | 🔴 Verificar o **domínio** do seu site | [search.google.com/search-console](https://search.google.com/search-console) — com a **mesma conta Google** que é dona do projeto no Cloud | o domínio aparece como propriedade verificada |
| b | Público **Externo** e **Em produção** | Tela de permissão OAuth → **Público-alvo** | não diz mais "Em teste" |
| c | Preencher o **Branding** | [console.cloud.google.com/auth/branding](https://console.cloud.google.com/auth/branding) | nome do app · e-mail de suporte · logo (quadrado, 120×120, até 1 MB) · página inicial · política de privacidade · termos · **domínios autorizados** |
| d | **Verificar branding** | mesma tela, canto superior direito | o status vira **Pronto para publicar** |
| e | 🔴 **PUBLICAR branding** | mesma tela | é só agora que a verificação passa a valer |
| f | Conferir o nível | [a página da Google Ads API](https://console.cloud.google.com/google/ads-apis/overview) | virou **Basic**. Se ainda disser Exploração, peça o Basic ali de novo — agora a análise é automática e sai em minutos |

---

## 🔴 A armadilha: verificar não é publicar

**São dois botões diferentes, e a tela não avisa.** Parando no passo (d), o console mostra a marca
como verificada e o pedido de Basic **mesmo assim volta recusado**, com aquele e-mail falando em
verificação incompleta — sem dizer qual botão falta.

Publicado o branding, o acesso sai na sequência.

---

## Os dois motivos de recusa mais comuns

1. 🔴 **"O site do URL da sua página inicial não está registrado para você"**
   É o passo (a) faltando. Este é o item que mais trava, porque a tela do Cloud não diz onde se
   resolve — a resposta está em outro produto, o Search Console.
   **O caminho curto** é criar propriedade do tipo **Domínio** e publicar um registro **TXT** no DNS:
   cobre o domínio inteiro e os subdomínios de uma vez.
   ⚠️ **O TXT não pode ser removido depois.** Saindo o registro, cai a propriedade — e a verificação
   cai junto.

2. 🔴 **"O nome do app não corresponde ao nome na sua página inicial"**
   Nome de rascunho (`meu-projeto`, `app-apagar`, `teste`) reprova. Use **o nome que o seu site
   anuncia**, o mesmo do título da página.

---

## Prazos, e uma validade que pega gente

- A análise automática sai em **minutos**.
- Caindo em revisão manual, são **2 a 3 dias úteis**.
- ⏱️ **O resultado da verificação vale 7 dias.** Passou disso sem clicar em *Publicar branding*,
  você verifica de novo.
- ⚠️ Quem **já tinha** acesso à API antes de 09/set/2026 está **isento** da verificação — a cobrança
  é para pedido novo.

---

## O que muda quando o Basic entra

| | Explorer | Basic |
|---|---|---|
| Alcança conta real | ✅ | ✅ |
| Ler, relatar, criar campanha, negativar | ✅ | ✅ |
| **Pesquisa de palavra-chave** (volume, CPC) | ❌ | ✅ |
| Operações por dia | 2.880 | 15.000 |

**O que conta como "operação":** um relatório inteiro, por maior que seja, conta como **1**. Quem
consome o limite é a escrita — negativar 200 termos são 200 operações. É o que faz 2.880 render
mais do que parece.

---

**Fontes:**
[Brand Verification](https://developers.google.com/google-ads/api/docs/api-policy/brand-verification) ·
[Submit for brand verification](https://developers.google.com/identity/protocols/oauth2/production-readiness/brand-verification) ·
[Access levels](https://developers.google.com/google-ads/api/docs/access-levels)

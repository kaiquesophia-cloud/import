# Aprendizados — Google Ads

Regras aprendidas durante o uso. O Claude DEVE ler este arquivo antes de criar qualquer objeto.

---

## Campos obrigatórios para criar campanha

> Apurado na API **v23 / SDK 30.0.0**. Em 01/set/2026 a instalação de referência roda **SDK
> 31.0.0, que fala v24 por padrão** — as regras abaixo continuam valendo, mas confira a
> [referência de campos](https://developers.google.com/google-ads/api/fields/v24/overview) da
> versão que o seu SDK usa antes de culpar o script. Descobre assim:
> `python3 -c "from google.ads.googleads import client; print(client._DEFAULT_VERSION)"`

- `contains_eu_political_advertising` é **enum** (não boolean). Usar valor `3` (DOES_NOT_CONTAIN_EU_POLITICAL_ADVERTISING)
- `maximize_clicks` não funciona como atributo direto. Usar `manual_cpc.enhanced_cpc_enabled = False` como fallback
- Budget name deve ser único. Script agora usa timestamp no nome pra evitar colisão com budgets órfãos
- Descriptions do RSA: máximo 90 caracteres. Headlines: máximo 30 caracteres
- **Campanhas de YouTube Studio (Promoções)**: Campanhas criadas diretamente pelo botão "Promover" no YouTube Studio são campanhas simplificadas (Smart/Promoções) e a API do Google Ads as oculta na tabela padrão de campanhas (`campaign`), o que explica por que os scripts de listagem de campanhas não as detectam nominalmente. No entanto, os dados de performance delas são consolidados normalmente no nível da conta (`customer`).

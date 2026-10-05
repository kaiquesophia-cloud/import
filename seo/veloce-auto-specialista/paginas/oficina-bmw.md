# Página `/oficina-bmw/`: ficha de publicação

Conteúdo da página: [`oficina-bmw.html`](oficina-bmw.html) (≈1.000 palavras, dados estruturados incluídos).

## Campos de SEO (Yoast ou Rank Math)

| Campo | Valor |
|---|---|
| Slug | `oficina-bmw` |
| Title SEO (47 caracteres) | `Oficina BMW em São Paulo (Zona Norte) \| Veloce` |
| Meta description (146) | `Oficina especializada em BMW na Casa Verde, Zona Norte de SP: diagnóstico de fábrica, revisão, remap e peças genuínas. Relatório técnico. Agende.` |
| Palavra-chave foco | `oficina bmw` |
| Palavras secundárias | `oficina especializada bmw`, `mecânica bmw`, `revisão bmw`, `remap bmw 320i` |
| H1 | `Oficina especializada em BMW em São Paulo` (já está no HTML; **o título da página no WordPress não pode virar um segundo H1**: no Elementor, Configurações da página → "Ocultar título" = Sim) |

## Antes de publicar: substituir os placeholders

| Placeholder | Onde aparece | Formato |
|---|---|---|
| `[WHATSAPP]` | 2 botões | só números com DDI: `5511999999999` |
| `[HORÁRIO DE FUNCIONAMENTO]` | bloco final | ex.: `Seg a sex 8h–18h · Sáb 8h–12h` |
| `[TELEFONE NO FORMATO +55-11-XXXXX-XXXX]` | dados estruturados | o mesmo número do Google Meu Negócio |

## Confirmar com o dono (o texto afirma isso)

- [ ] Fazem **registro de bateria** e **reset/atualização do CBS** (intervalos de revisão no painel)?
- [ ] Fazem **adaptação do câmbio** pelo scanner?
- [ ] Atendem **M2/M3/M4 e Z4**? Se não, tirar da lista de modelos.
- [ ] "Peças genuínas **ou de fornecedor original**": se usam só genuína BMW, simplificar a frase.

Se alguma resposta for "não", apague a frase. Não deixe promessa que a oficina não cumpre.

## Imagens (subir no WordPress com estes nomes e alts)

| Seção | Arquivo | Alt |
|---|---|---|
| Hero (fundo ou ao lado) | `oficina-bmw-sao-paulo.jpg` | `BMW no elevador da oficina Veloce, na Casa Verde, em São Paulo` |
| Diferenciais | `diagnostico-bmw-scanner.jpg` | `Diagnóstico de BMW com scanner na Veloce Auto Specialista` |
| Serviços | `revisao-bmw-320i.jpg` | `Revisão de BMW 320i na oficina especializada` |

Só fotos **reais** da oficina, em `.webp` ou `.jpg` com menos de 200 KB. Foto de banco de imagem não conta
como prova para o cliente nem para o Google.

## Links internos

A página já aponta para `/remap/`, `/inspecao-pre-compra/`, `/manutencao-preventiva/` e `/oficina-mini/`.
**Essas páginas ainda não existem.** Até serem publicadas:
- troque o `href` delas por `/servicos/` (ou remova o link e deixe só o texto); e
- quando cada página nascer, volte aqui e restaure o link.

E no sentido contrário, depois de publicar:
- [ ] home → `/oficina-bmw/` (logo da BMW no bloco de marcas, âncora "Oficina BMW")
- [ ] `/servicos/` → `/oficina-bmw/`
- [ ] menu: item "Marcas" com submenu, BMW primeiro

## Depois de publicar

1. Testar os dados estruturados em https://search.google.com/test/rich-results (colar a URL).
2. Search Console → Inspeção de URL → "Solicitar indexação".
3. Google Meu Negócio → Serviços → "Oficina BMW" com link para a página.
4. Se o tema já gera um `LocalBusiness`/`AutoRepair` (Rank Math → Local SEO), apague o bloco
   `AutoRepair` do HTML e mantenha só `Service`, `BreadcrumbList` e `FAQPage`, para não duplicar.
5. A palavra `oficina bmw` já está no rastreamento do Ubersuggest. Primeira leitura útil daqui a 30 dias.

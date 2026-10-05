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

## Antes de publicar

| Item | Situação |
|---|---|
| WhatsApp nos 2 botões | ✅ `551199555296` (⚠️ só 8 dígitos depois do DDD: **teste o botão**. Se não abrir a conversa, falta o 9 da frente) |
| Telefone nos dados estruturados | ✅ `+55-11-9955-5296` (mesma ressalva; tem de ser igual ao do Google Meu Negócio) |
| `[HORÁRIO DE FUNCIONAMENTO]` no bloco final | ⏳ falta o dono informar |

## Confirmado com o dono

- [x] **Não** fazem registro de bateria: o card saiu e entrou "Ruídos na suspensão" no lugar
- [x] Atendem **todos os modelos BMW** (lista mantida, texto ajustado)
- [x] **Só peça genuína BMW**: texto, card e FAQ ajustados
- [ ] **Adaptação do câmbio pelo scanner**: ainda sem resposta (card "Câmbio automático com trancos"). Se não fizerem, troque o final por "melhoram com a troca do fluido"

## Imagens (subir no WordPress com estes nomes e alts)

| Seção | Arquivo | Alt |
|---|---|---|
| Hero (fundo ou ao lado) | `oficina-bmw-sao-paulo.jpg` | `BMW no elevador da oficina Veloce, na Casa Verde, em São Paulo` |
| Diferenciais | `diagnostico-bmw-scanner.jpg` | `Diagnóstico de BMW com scanner na Veloce Auto Specialista` |
| Serviços | `revisao-bmw-320i.jpg` | `Revisão de BMW 320i na oficina especializada` |

Só fotos **reais** da oficina, em `.webp` ou `.jpg` com menos de 200 KB. Foto de banco de imagem não conta
como prova para o cliente nem para o Google.

## Links internos

Os links para `/remap/`, `/inspecao-pre-compra/` e `/manutencao-preventiva/` já apontam para `/servicos/`, e
"MINI" está sem link. Quando cada página nascer, volte aqui e aponte para ela.

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

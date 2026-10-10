# Andaimaq — Semana 1: auditoria técnica de SEO

Data: 09/10/2026 · Fonte: Ubersuggest (site audit + PageSpeed), lido nesta data.

## O site hoje: 6 páginas

| Página | Título atual | Observação |
|---|---|---|
| `/` | Andaimaq Locações l Locadora de Equipamentos em SP | home |
| `/locacao-de-andaime/` | Locação de Andaime l Andaimaq Locações | **já existe a página de andaime** |
| `/aluguel-de-andaimes-preco/` | Aluguel de andaimes preço l Andaimaq Locações | página de preço (posições 39ª–48ª) |
| `/equipamentos/` | Equipamentos - Andaimaq - Locação de Equipamentos | conteúdo fraco: 137 palavras |
| `/contato/` | Contato - Andaimaq - Locação de Equipamentos | conteúdo fraco: 118 palavras |
| `/quem-somos/` | Quem somos - Andaimaq - Locação de Equipamentos | — |

Não existe página para **betoneira, martelete ou escora**: confirma o plano das semanas 5 a 7.

## Nota geral: 77/100

- OK: sitemap, certificado SSL, títulos únicos, sem links quebrados, sem erros 4xx.
- 2 erros: conteúdo fraco em `/equipamentos/` e `/contato/`.

## O problema principal: velocidade

| Métrica | Celular | Computador | Bom é |
|---|---:|---:|---:|
| Maior elemento visível (LCP) | **14,6 s** | 5,4 s | até 2,5 s |
| Primeiro conteúdo (FCP) | 2,1 s | 1,2 s | até 1,8 s |
| Página utilizável (TTI) | 14,6 s | 13,7 s | — |
| Tempo bloqueado (TBT) | 144 ms | **5,5 s** | até 200 ms |

No celular, o conteúdo principal leva quase 15 segundos para aparecer. Isso pesa no SEO e também
no Google Ads: quem clica no anúncio e espera 15 s desiste, e o Google cobra o clique do mesmo jeito.

Ganhos apontados:
- Redirecionamento na entrada: ~630 ms no celular (provável `http`→`https` ou `www`→sem `www` em cadeia).
- CSS não usado: ~600 ms no celular (95 KB).
- JavaScript não usado: ~450 ms no celular (243 KB); 755 KB no computador.
- O LCP de 14,6 s indica imagem principal pesada ou carregada tarde: comprimir (WebP), dimensionar e
  não usar carregamento atrasado na imagem do topo.

## Ajustes no plano

1. **Andaime:** em vez de criar `/aluguel-de-andaime-sp/`, otimizar a `/locacao-de-andaime/` que já
   existe, mirando "aluguel andaime". Duas páginas para o mesmo assunto disputariam entre si.
2. **Velocidade entra como prioridade da semana 2**, antes de ativar o Google Ads na semana 2–3.
3. `/equipamentos/` vira a vitrine dos 4 prioritários, com link para cada página nova.

## Precisamos da Andaimaq

- Acesso ao painel do site (parece WordPress pelo padrão dos títulos — confirmar) ou à hospedagem.
- Acesso ao Google Search Console e ao Perfil da Empresa no Google (se não existirem, criamos).

## Executado em 09/10/2026 (velocidade, parte 1)

Feito via REST com `execucao/wp/aplicar.py` (backup antes em `backup-2026-10-09/`):

1. 6 imagens PNG (18,7 MB) convertidas para WebP (~1 MB) e enviadas à mídia (ids 320–325).
   As originais continuam na biblioteca.
2. Imagens trocadas no Elementor: home, locação de andaime, preço, equipamentos, quem somos e
   2 modelos. Cada página foi relida e conferida. Cache do Elementor limpo.
3. Desativados: MetForm, Template Kit Import, Copy & Delete Posts. As 6 páginas abrem (HTTP 200)
   e o formulário de contato (Contact Form 7) continua na página Contato.

| PageSpeed | Antes | Depois |
|---|---:|---:|
| Celular — LCP | 14,6 s | 6,5 s |
| Celular — Speed Index | 9,0 s | 5,3 s |
| Computador — LCP | 5,4 s | 1,7 s |
| Computador — TBT | 5,5 s | 0,29 s |
| Computador — TTI | 13,7 s | 3,9 s |

Pendente: plugin de cache (a instalação pela API foi bloqueada pela trava de permissões;
fazer pelo painel), imagem de fundo menor para celular, JavaScript sem uso (~1,1 s no celular),
redirecionamento de entrada (~630 ms) e o schema do Rank Math ainda apontando para os PNGs.
Para desfazer a troca de imagens: `python3 -I propostas/andaimaq/execucao/wp/aplicar.py desfazer`.

## Executado em 09/10/2026 (velocidade, parte 2)

4. Cache Enabler instalado pelo Kaique no painel; o Site Health do WordPress confirma o cache de página.
5. Fundos de seção/coluna com versão menor para tablet (1024 px) e celular (768 px) nas páginas
   de locação, preço e equipamentos (`aplicar.py fundo-mobile`). As imagens `<img>` já usavam
   `srcset` e escolhem sozinhas o tamanho certo.
6. Imagem destacada definida nas 5 páginas (`aplicar.py destaque`): o schema do Rank Math agora
   aponta para o WebP. As únicas PNG restantes são o logo e o ícone do site (69 KB).
7. O servidor passou para PHP 8.3 (era 7.4).

| PageSpeed (home) | Início | Parte 1 | Agora |
|---|---:|---:|---:|
| Celular — LCP | 14,6 s | 6,5 s | 4,5 s |
| Celular — TBT | 144 ms | 87 ms | 159 ms |
| Computador — LCP | 5,4 s | 1,7 s | 2,1 s |
| Computador — TBT | 5,5 s | 285 ms | 185 ms |
| Computador — TTI | 13,7 s | 3,9 s | 3,8 s |

Medições de laboratório variam ±0,5 s entre rodadas. Ainda pendente no celular: CSS sem uso
(~450 ms, vem do Elementor/ElementsKit) e o redirecionamento de entrada (~630 ms, provável
`http`→`https` no servidor; quem chega pelo Google já cai no `https`).

## Páginas de SEO (10/10/2026)

| Página | Status | Palavra-chave principal |
|---|---|---|
| `/locacao-de-andaime/` | otimizada (título, H1, conteúdo, FAQ) | aluguel andaime |
| `/aluguel-de-betoneira/` (id 326) | nova, modelo de conversão | aluguel de betoneira |
| `/aluguel-de-martelete/` (id 328) | nova, modelo de conversão | aluguel de martelete |
| `/aluguel-de-escora-metalica/` (id 329) | nova, modelo de conversão | aluguel de escora para laje |

Os cards Andaimes, Betoneira, Martelete e Escoramentos (home, andaime, preço, equipamentos e
2 modelos do Elementor) agora levam às páginas de cada equipamento.
Provisório até o cliente enviar: modelos reais de cada equipamento e fotos próprias.

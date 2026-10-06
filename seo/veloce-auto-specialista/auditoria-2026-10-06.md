# Auditoria do site: veloceautospecialista.com.br (06/10/2026)

Fontes: PageSpeed (via Ubersuggest), leitura direta das 23 URLs do sitemap e de todos os links internos,
REST API do WordPress (configurações, plugins, 53 mídias). O rastreamento do Ubersuggest foi iniciado mas não
saiu de 0 páginas; refazer depois.

## ✅ Corrigido nesta auditoria

| O quê | Antes | Depois |
|---|---|---|
| Title da home | `Home - Veloce Auto Specialista` | `Oficina de Carros Importados e Premium em SP \| Veloce` |
| Description da home | `ESPECIALISTAS EM PREMIUM & IMPORTADOS — SP` (rótulo em caixa alta) | texto com marcas, região e serviço (150 caracteres) |
| Title/description de Sobre, Serviços e Contato | genéricos, descrição = rótulo da seção | reescritos com palavra-chave e região |
| Shop, Cart, Checkout, My account, Sample Page, Elementor #23 | indexáveis e no sitemap | `noindex, nofollow` (reversível) |
| `/pvads/` (landing de anúncios) | indexável | `noindex, follow` |
| Fuso horário | vazio (UTC) | America/Sao_Paulo, data dd/mm/aaaa |
| Comentários e pingbacks | abertos (porta de spam) | fechados por padrão |
| Links internos | — | 23 links checados, **nenhum quebrado** |

## 🔴 Crítico: velocidade (o maior problema do site)

| Métrica (PageSpeed) | Celular | Computador | Bom é |
|---|---:|---:|---:|
| LCP (maior elemento visível) | **34,9 s** | 6,3 s | ≤ 2,5 s |
| Speed Index | 14,7 s | 5,0 s | ≤ 3,4 s |
| Tempo até interativo | 35,4 s | 6,3 s | ≤ 3,8 s |

**Causa principal: imagens.** A home carrega **6,3 MB só de imagem**:

| Arquivo | Peso | Problema |
|---|---:|---|
| `ChatGPT-Image-30-de-jun.-de-2026-22_36_40.png` | 2.117 KB | PNG gerado no ChatGPT, deveria ser WebP de ~150 KB |
| `ChatGPT-Image-30-de-jun.-de-2026-21_32_04.png` | 1.968 KB | idem |
| `Design-sem-nome-9.png` | 1.119 KB | idem |
| `cta_car.jpg`, `garage_bg.jpg`, `exhaust_detail.jpg` | 810–895 KB cada | JPG sem compressão |
| `IMG_3246`, `IMG_7563`, `IMG_9423` (fotos de celular) | 724–859 KB cada | 1920×2560, maiores que a tela |

17 das 53 imagens da biblioteca têm mais de 300 KB. Também pesam: 30 scripts e 36 CSS na home (Elementor +
Elementor Pro + Premium Addons + Qi Addons carregando tudo em toda página).

**Como resolver (em ordem):**
1. Plugin de compressão/WebP (ex.: *Converter for Media* ou *EWWW Image Optimizer*): converte a biblioteca
   inteira para WebP. É o ganho mais rápido.
2. Substituir os 3 PNGs do ChatGPT por WebP exportados em 1600 px de largura (≤ 200 KB).
3. Elementor → Configurações → Recursos: ativar *Carregamento otimizado de assets*, *CSS melhorado*,
   *Lazy load de imagens de fundo*.
4. Plugin de cache (o servidor "tupan" com PHP 7.4 não mostra cache nenhum): *LiteSpeed Cache* se a hospedagem
   for LiteSpeed; senão *WP Super Cache*.
5. Avaliar remover *Qi Addons* ou *Premium Addons* se só um deles é usado.

## 🟠 Importante

| # | Problema | Por que importa | Como resolver |
|---|---|---|---|
| 1 | **PHP 7.4** (sem suporte desde nov/2022) | segurança e velocidade; plugins novos deixam de funcionar | pedir à hospedagem PHP 8.2+ (testar o site depois) |
| 2 | **53 de 53 imagens sem texto alternativo (alt)** | Google Imagens e acessibilidade | preencher na Biblioteca de mídia ("BMW X5 no elevador da Veloce, Zona Norte SP") |
| 3 | Home sem link para as 13 páginas novas | o Google dá mais peso a páginas linkadas da home | bloco "Marcas que atendemos" com logos linkando para `/oficina-bmw/` etc. (o dono preferiu não pôr no menu; um bloco na home não é menu) |
| 4 | URL do contato é `/elementor-383/` | URL sem sentido; aparece no Google | renomear para `/contato/` **e** criar redirecionamento 301 no Rank Math (botões apontam para a URL antiga) |
| 5 | H1 da home sem palavra-chave ("Seu carro premium merece precisão.") | o H1 é o sinal mais forte da página | ex.: "Oficina de carros premium e importados em São Paulo" e a frase atual vira subtítulo |
| 6 | Rank Math marca páginas como `Article` e o autor como `Person` | tipo errado para página de serviço | Rank Math → Títulos e Meta → Páginas → Schema = Nenhum |
| 7 | Sem imagem de compartilhamento (og:image) | link no WhatsApp aparece sem foto | Rank Math → Títulos e Meta → Global → imagem padrão (foto da fachada) |
| 8 | Sem link `tel:` no site | no celular, ninguém consegue ligar com um toque | botão "Ligar (11) 99555-2969" no rodapé |
| 9 | Sitemap ainda lista as páginas com noindex | cache do Rank Math | Rank Math → Sitemap → salvar de novo; depois enviar `/sitemap_index.xml` no Search Console |

## 🟡 Limpeza e segurança

| # | Problema | Como resolver |
|---|---|---|
| 1 | Páginas lixo continuam publicadas (agora com noindex) | mandar para a lixeira pelo painel: Shop, Cart, Checkout, My account, Sample Page, Elementor #23, post "Hello world!" e o rascunho "Elementor #328" |
| 2 | 3 plugins de backup ativos (All-in-One WP Migration, Duplicator Pro, UpdraftPlus) | manter só o UpdraftPlus com backup automático semanal |
| 3 | Plugins inativos (Akismet, Hello Dolly, All-in-One WP Migration Pro) | apagar |
| 4 | `/wp-json/wp/v2/users` expõe o usuário "kaique" | Rank Math não resolve; usar um plugin de segurança (ex.: *Solid Security* ou *Wordfence*) que bloqueia enumeração de usuários |
| 5 | `xmlrpc.php` aberto | desativar no mesmo plugin de segurança (alvo comum de ataque de senha) |
| 6 | `www.` e `http://` não puderam ser testados daqui | conferir no navegador que os dois redirecionam para `https://veloceautospecialista.com.br/` |

## 🟡 Conteúdo das páginas antigas

| Página | Situação |
|---|---|
| `/servicos/` | só ~300 palavras e nenhum link para `/remap/`, `/inspecao-pre-compra/`, `/manutencao-preventiva/` → linkar cada serviço para a sua página |
| `/sobre/` | bom texto (~690 palavras); falta link para as marcas |
| Contato | ~220 palavras; ok, falta `tel:` e mapa com o endereço escrito igual ao do Google Meu Negócio |
| `/pvads/` | depoimentos de clientes: conferir se são reais (depoimento inventado viola as regras do Google Ads) |

## Prioridade sugerida

1. **Imagens** (plugin WebP + trocar os 3 PNGs): resolve a maior parte dos 35 s no celular.
2. PHP 8.2 + cache.
3. Bloco de marcas na home + links em `/servicos/`.
4. Alt das imagens, og:image, `tel:`.
5. `/contato/` com 301, limpeza de plugins e páginas, plugin de segurança.

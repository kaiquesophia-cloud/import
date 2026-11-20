# Velocidade no celular — 20/11/2026

Lighthouse 12.6.1 (celular simulado, rede 4G lenta). O GTM e o gtag.js não carregam neste ambiente, então os números reais
ficam um pouco piores. Ubersuggest (PageSpeed, home): LCP 2,6 s, Speed Index 7,6 s, redirecionamento 601 ms.

| Página | Nota | LCP | FCP | Peso |
|---|---:|---:|---:|---:|
| / (home, Elementor 123) | 68 | 5,4 s | 3,5 s | 1.548 KB |
| /oficina-porsche/ | 82 | 4,0 s | 2,7 s | 630 KB |
| /oficina-audi/ | 86 | 3,5 s | 2,7 s | 532 KB |
| /pvads/ (anúncios) | 83 | 3,5 s | 3,3 s | 1.059 KB |
| /blog/cambio-s-tronic/ | 84 | 3,7 s | 2,7 s | 516 KB |

CLS ~0 e TBT baixo em todas. Gargalos:
1. Home: ~950 KB de imagens fora da primeira tela carregam de início (porsche-911-dinamometro, golf-gti-dinamometro,
   Design-sem-nome-6/7.jpg, car-repair…jpg) e em tamanho maior que o exibido (~900 KB de economia).
2. Google Fonts: Chakra Petch e Mulish com 18 pesos cada, bloqueando a página (até 0,9 s).
3. CSS/JS que bloqueiam: jQuery, dashicons (34 KB, inútil para visitante), CSS do Elementor.
4. Resposta do servidor ~450 ms (sem cache de página).
5. http:// → https:// leva ~1,1 s (só afeta quem entra por link antigo com http).

## Feito em 20/11/2026 (com o "pode" do dono)

Home (Elementor 123): 7 fotos trocadas pela versão no tamanho certo que o WordPress já gera (1024 ou 768 px de largura),
sem mudar o visual. Backup: `backup-elementor/123-antes-velocidade.json`. Resultado: nota 68 → 76, LCP 5,4 → 4,6 s,
peso 1.548 → 1.172 KB. Os widgets do SoftLite não têm carregamento adiado (lazy), por isso as fotos de baixo continuam
carregando na abertura. Design-sem-nome-6.jpg ficou no original (exibida a 632×686 no computador).
Pendente com o dono: fontes locais + "swap" no Elementor (item A).

## Depois das fontes locais (20/11/2026, dono ativou no Elementor)

| Página | Nota antes | Nota depois | LCP depois |
|---|---:|---:|---:|
| / | 68 | 88 | 3,1 s |
| /oficina-porsche/ | 82 | 84 | 3,3 s |
| /oficina-audi/ | 86 | 92 | 2,9 s |
| /pvads/ | 83 | 80 | 4,3 s (oscilação de medição; conferir de novo) |
| /blog/cambio-s-tronic/ | 84 | 92 | 2,9 s |

Fontes servidas de `/wp-content/uploads/elementor/google-fonts/`, todas com 200 e aplicadas (Chakra Petch e Mulish).
Próximo, se quiser: cache de página e tirar o dashicons para visitantes (exige plugin; testar cookies e WhatsApp).

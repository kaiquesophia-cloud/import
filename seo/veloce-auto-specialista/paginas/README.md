# Páginas publicadas: Veloce Auto Specialista

Todas no modelo "Elementor Largura total" (cabeçalho e rodapé do site), com WhatsApp 5511995552969, horário,
FAQ e dados estruturados (`AutoRepair`, `Service`, `BreadcrumbList`, `FAQPage`). Title, description e
palavra-chave preenchidos no Rank Math. Publicadas em 06/10/2026.

| ID | URL | Palavra-chave foco (Rank Math) |
|---:|---|---|
| 461 | [/oficina-bmw/](https://veloceautospecialista.com.br/oficina-bmw/) | oficina bmw |
| 463 | [/oficina-land-rover/](https://veloceautospecialista.com.br/oficina-land-rover/) | oficina land rover |
| 464 | [/oficina-volvo/](https://veloceautospecialista.com.br/oficina-volvo/) | oficina volvo |
| 465 | [/oficina-mercedes-benz/](https://veloceautospecialista.com.br/oficina-mercedes-benz/) | oficina mercedes |
| 466 | [/oficina-audi/](https://veloceautospecialista.com.br/oficina-audi/) | oficina audi |
| 467 | [/oficina-porsche/](https://veloceautospecialista.com.br/oficina-porsche/) | oficina porsche |
| 468 | [/oficina-jaguar/](https://veloceautospecialista.com.br/oficina-jaguar/) | oficina jaguar |
| 469 | [/oficina-mini/](https://veloceautospecialista.com.br/oficina-mini/) | oficina mini cooper |
| 470 | [/oficina-lamborghini/](https://veloceautospecialista.com.br/oficina-lamborghini/) | oficina lamborghini |
| 471 | [/remap/](https://veloceautospecialista.com.br/remap/) | remap automotivo |
| 472 | [/inspecao-pre-compra/](https://veloceautospecialista.com.br/inspecao-pre-compra/) | inspeção pré compra |
| 473 | [/manutencao-preventiva/](https://veloceautospecialista.com.br/manutencao-preventiva/) | revisão carros importados |
| 474 | [/oficina-importados-zona-norte/](https://veloceautospecialista.com.br/oficina-importados-zona-norte/) | oficina importados zona norte |

`/pvads/` (433, landing dos anúncios) recebeu `noindex, follow` no Rank Math.

## Como editar

- A BMW foi feita à mão: `oficina-bmw.html`.
- As demais são geradas: edite `../paginas_dados.py` (marcas) ou `../paginas_servicos.py` (serviços e Zona Norte),
  rode `python3 seo/veloce-auto-specialista/gerar_paginas.py` e republique com
  `python3 seo/wp_publicar.py page --slug <slug> --title "<título>" --html <arquivo> --status publish`.

## Confirmado com o dono

- **Não** fazem ajuste/adaptação do câmbio pelo scanner: removido de BMW, Mercedes, Audi, Porsche, Jaguar e da
  inspeção pré-compra (republicadas em 06/10/2026). Não volte a escrever isso.
- Confirmados: reset do aviso de revisão no painel, backup do software original no remap, teste de rodagem na
  inspeção pré-compra, diesel Land Rover/Jaguar, Volvo Recharge e Lamborghini Aventador/Gallardo.
- Já confirmados antes: só peça genuína, sem registro de bateria, sem vistoria cautelar, remap sem preço publicado.

## Ajustes pendentes

- Rank Math gera `Article` para as páginas e `Person + Organization` para o site. O ideal é: Configurações →
  Títulos e Meta → Páginas → Schema padrão = **Nenhum**; Local SEO → tipo **Organização / AutoRepair**.
- Nenhuma página nova está no menu (decisão do dono). Elas se ligam entre si por links internos.
- Mandar indexar no Search Console (Inspeção de URL) e conferir o sitemap do Rank Math (`/sitemap_index.xml`).

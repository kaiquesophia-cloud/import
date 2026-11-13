"""Google Search Console da veloceautospecialista.com.br, de 18/10 a 10/11/2026 (todo o período com dados).
Exportado pelo dono via Claude in Chrome em 13/11/2026 (relatório Desempenho, resultados da pesquisa)."""

PERIODO = ("18/10/2026", "10/11/2026")
TOTAIS = {"cliques": 51, "impressoes": 11600, "ctr": 0.4, "posicao": 8.7}

# (página, cliques, impressões, CTR %, posição média)
PAGINAS = [
    ("/", 24, 1122, 2.1, 7.2),
    ("/blog/remap-stage-1-e-stage-2/", 8, 4683, 0.2, 8.2),
    ("/oficina-mercedes-benz/", 8, 402, 2.0, 11.3),
    ("/blog/o-que-e-remap/", 2, 2705, 0.1, 8.4),
    ("/oficina-land-rover/", 2, 234, 0.9, 14.9),
    ("/oficina-audi/", 2, 124, 1.6, 10.8),
    ("/blog/remap-estraga-o-motor/", 1, 550, 0.2, 8.3),
    ("/blog/quanto-custa-um-remap/", 1, 370, 0.3, 8.1),
    ("/sobre/", 1, 242, 0.4, 5.1),
    ("/remap/", 1, 131, 0.8, 7.4),
    ("/servicos/", 1, 116, 0.9, 4.8),
    ("/oficina-porsche/", 1, 98, 1.0, 11.5),
    ("/contato/", 1, 36, 2.8, 6.1),
    ("/oficina-audi/revisao/", 1, 10, 10.0, 8.9),
    ("/blog/remap-perde-garantia/", 0, 590, 0.0, 6.1),
    ("/oficina-bmw/", 0, 247, 0.0, 18.3),
    ("/oficina-jaguar/", 0, 124, 0.0, 10.0),
    ("/blog/vistoria-cautelar-ou-inspecao-pre-compra/", 0, 102, 0.0, 20.5),
    ("/oficina-lamborghini/", 0, 97, 0.0, 5.6),
    ("/dinamometro/", 0, 61, 0.0, 6.3),
    ("/oficina-volvo/", 0, 26, 0.0, 16.5),
    ("/inspecao-pre-compra/", 0, 17, 0.0, 5.9),
    ("/remap/cambio/", 0, 14, 0.0, 6.9),
    ("/oficina-volvo/revisao/", 0, 10, 0.0, 15.9),
    ("/oficina-land-rover/revisao/", 0, 10, 0.0, 18.9),
    ("/oficina-bmw/revisao/", 0, 9, 0.0, 18.0),
    ("/oficina-mercedes-benz/revisao/", 0, 4, 0.0, 22.3),
    ("/blog/", 0, 3, 0.0, 7.0),
    ("/author/kaique/", 0, 2, 0.0, 6.5),
    ("/manutencao-preventiva/", 0, 1, 0.0, 1.0),
]

# Consultas que geraram clique (10 de 400). (consulta, cliques, impressões, CTR %, posição)
CONSULTAS_CLIQUE = [
    ("veloce auto specialista", 9, 70, 12.9, 1.1),
    ("stage 2", 3, 346, 0.9, 8.8),
    ("veloce pneus", 2, 158, 1.3, 4.9),
    ("o que é remap no carro", 1, 166, 0.6, 9.4),
    ("remap stage 1", 1, 107, 0.9, 8.7),
    ("veloce", 1, 82, 1.2, 3.8),
    ("auto veloce", 1, 10, 10.0, 5.5),
    ("veloce auto", 1, 7, 14.3, 1.4),
    ("stage 1 ou 2", 1, 3, 33.3, 2.0),
    ("range rover remap", 1, 1, 100.0, 2.0),
]

# Consultas com mais impressões e nenhum clique. (consulta, impressões, posição)
CONSULTAS_SEM_CLIQUE = [
    ("remap", 422, 6.0), ("stage 2 o que é", 417, 9.2), ("remap stage 2", 160, 7.6),
    ("o que é remap", 134, 10.3), ("o que é stage 2 no carro", 108, 8.5), ("stage 1", 85, 9.8),
    ("o que é stage 2", 63, 8.8), ("remap estraga o motor", 60, 10.4), ("remap o que é", 55, 9.6),
    ("stage 2 carro", 51, 8.5), ("oficina bmw", 25, 32.0), ("oficina land rover", 19, 22.2),
    ("oficina especializada bmw", 18, 59.1),
]

DISPOSITIVOS = [("Celular", 31, 8504, 0.4, 8.4), ("Computador", 20, 3080, 0.6, 9.6), ("Tablet", 0, 43, 0.0, 9.7)]

# (dia, cliques, impressões, CTR %, posição)
DIAS = [
    ("18/10", 1, 310, 0.3, 10.8), ("19/10", 4, 527, 0.8, 8.3), ("20/10", 3, 493, 0.6, 9.1),
    ("21/10", 1, 591, 0.2, 7.9), ("22/10", 2, 527, 0.4, 8.3), ("23/10", 2, 481, 0.4, 8.1),
    ("24/10", 2, 320, 0.6, 8.9), ("25/10", 4, 267, 1.5, 11.5), ("26/10", 0, 397, 0.0, 7.9),
    ("27/10", 0, 415, 0.0, 9.3), ("28/10", 0, 497, 0.0, 8.8), ("29/10", 4, 449, 0.9, 8.2),
    ("30/10", 1, 374, 0.3, 8.3), ("31/10", 1, 286, 0.3, 7.8), ("01/11", 2, 281, 0.7, 11.0),
    ("02/11", 1, 360, 0.3, 8.4), ("03/11", 6, 674, 0.9, 9.7), ("04/11", 2, 626, 0.3, 8.4),
    ("05/11", 2, 666, 0.3, 7.7), ("06/11", 2, 604, 0.3, 7.9), ("07/11", 1, 523, 0.2, 8.2),
    ("08/11", 1, 482, 0.2, 10.1), ("09/11", 3, 720, 0.4, 7.2), ("10/11", 6, 757, 0.8, 9.9),
]

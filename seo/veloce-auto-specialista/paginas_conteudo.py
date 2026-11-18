"""Blog, subpáginas de revisão e remap de câmbio (07/10/2026). Mesmas regras de paginas_dados.py.

Confirmado com o dono: fazem remap de câmbio (reprogramação da central do câmbio). NÃO fazem ajuste/adaptação
do câmbio pelo scanner. Remap sem preço publicado. Dinamômetro próprio. Não fazem vistoria cautelar.
"""

FOTO_OFICINA = ("oficina-veloce-carros-premium-zona-norte-sp.webp",
                "Oficina Veloce com Porsche Cayenne e carros premium nos elevadores, na Zona Norte de São Paulo", 1448, 1086)
FOTO_911 = ("porsche-911-dinamometro-veloce.webp", "Porsche 911 Carrera Cabriolet no dinamômetro da Veloce", 1200, 1600)
FOTO_GTI = ("golf-gti-dinamometro-veloce.webp", "Volkswagen Golf GTI no dinamômetro da Veloce", 1200, 1600)
FOTO_MINI = ("mini-cooper-oficina-veloce-casa-verde.webp", "MINI Cooper na oficina Veloce Auto Specialista, na Casa Verde", 1200, 1600)
FOTO_EVOQUE = ("range-rover-evoque-oficina-veloce.webp", "Range Rover Evoque preto com o capô aberto na oficina Veloce", 1024, 1024)
FOTO_MOTOR = ("mecanico-veloce-montagem-motor.webp", "Mecânico da Veloce montando um motor na bancada", 739, 1600)

BLOG = {"slug": "blog", "nome": "Blog"}
ATENDE = {"h2": "Como funciona o atendimento", "steps": [
    ("Contato pelo WhatsApp.", "Você manda o modelo, o ano e o que precisa."),
    ("Avaliação na oficina.", "Leitura completa das centrais eletrônicas e inspeção física do carro."),
    ("Orçamento detalhado.", "Peça por peça, mão de obra separada. Você aprova item por item."),
    ("Execução com peças genuínas.", "Se aparecer algo fora do orçamento, avisamos antes de fazer."),
    ("Entrega com relatório técnico.", "O que foi feito, o que foi trocado e o que observar daqui em diante."),
]}


def art(slug, title, description, h1, lead, kicker, leitura, foto, secoes, faq, cta_h2, servico_href, servico_txt, zap):
    return {"slug": slug, "pai": BLOG, "tipo": "artigo", "title": title, "description": description, "h1": h1,
            "lead": lead, "kicker": kicker, "hero_nota": f"Leitura de {leitura} min · Blog Veloce Auto Specialista",
            "foto": foto, "botao": "Tirar dúvidas no WhatsApp", "whatsapp_msg": zap,
            "botao_alt": (servico_txt, servico_href), "breadcrumb": sem_html(h1), "secoes": secoes,
            "faq_h2": "Perguntas frequentes", "faq": faq, "cta_h2": cta_h2,
            "cta_p": "Mande o modelo, o ano e o motor do seu carro. Um especialista responde pelo WhatsApp.",
            "arquivo": f"blog-{slug}", "publicado": "2026-10-07"}


def sem_html(s):
    import re
    return re.sub(r"<[^>]+>", "", s)


def leia(*itens):
    return {"h2": "Leia também", "cards": [(t, f'{d} <a href="{h}">Ler o artigo</a>.') for t, h, d in itens]}


L_OQUE = ("O que é remap?", "/blog/o-que-e-remap/", "Como a reprogramação funciona e quando vale a pena.")
L_CUSTA = ("Quanto custa um remap?", "/blog/quanto-custa-um-remap/", "Do que o preço depende e por que remap barato sai caro.")
L_STAGE = ("Stage 1 x stage 2", "/blog/remap-stage-1-e-stage-2/", "A diferença entre os níveis e qual escolher.")
L_ESTRAGA = ("Remap estraga o motor?", "/blog/remap-estraga-o-motor/", "O que é mito, o que é risco e como evitar.")
L_GARANTIA = ("Remap perde a garantia?", "/blog/remap-perde-garantia/", "O que acontece com a garantia de fábrica.")
L_CAUTELAR = ("Vistoria cautelar ou inspeção pré-compra?", "/blog/vistoria-cautelar-ou-inspecao-pre-compra/",
              "Qual fazer antes de comprar um carro importado.")

ARTIGOS = [
    # ------------------------------------------------------------------ O QUE É REMAP
    art("o-que-e-remap", "O que é Remap no Carro? Como Funciona e Quando Vale a Pena",
        "Remap é a reprogramação da central do motor para ganhar potência e torque. Veja como funciona, o que muda no carro, os riscos e quando vale a pena.",
        "O que é remap e como ele funciona",
        "Remap é a reprogramação do software que controla o motor. Neste guia você entende o que muda no carro, como o "
        "processo é feito com segurança e quando ele vale a pena.",
        "Blog · Remap", 6, FOTO_GTI,
        [
            {"h2": "Remap em uma frase", "p": [
                "Remap é a reprogramação da ECU, a central eletrônica que decide quanto combustível injetar, quando "
                "acender a vela e quanta pressão o turbo pode fazer. Ao ajustar esses parâmetros, o motor entrega mais "
                "potência e torque sem trocar nenhuma peça.",
                "As montadoras calibram os motores com margens largas: o mesmo motor é vendido em países com combustível "
                "pior, clima mais quente e manutenção menos cuidadosa. O remap aproveita parte dessa margem, dentro dos "
                "limites seguros dos componentes.",
            ]},
            {"h2": "Como o remap é feito, passo a passo", "steps": [
                ("Diagnóstico.", "Leitura de todas as centrais, checagem de falhas, velas, bobinas, vazamentos e arrefecimento. Motor com defeito não recebe remap."),
                ("Leitura e backup.", "O software original da ECU é lido e guardado antes de qualquer alteração."),
                ("Ajuste dos mapas.", "Pressão do turbo, ponto de ignição, injeção e limitadores de torque são ajustados para o motor e o combustível."),
                ("Gravação.", "O software ajustado é gravado na central."),
                ("Conferência.", "Nova leitura da ECU, teste de rodagem e medição no dinamômetro para conferir o ganho real."),
            ]},
            {"h2": "O que muda no carro", "cards": [
                ("Potência", "Mais cavalos no topo das rotações, principalmente em motores turbo, cuja pressão é controlada pela ECU."),
                ("Torque", "Mais força em baixa e média rotação: é o que você sente nas retomadas e nas ultrapassagens."),
                ("Resposta", "Acelerador mais imediato e menos atraso do turbo, sem mudar o jeito de dirigir."),
                ("Consumo", "No uso normal fica parecido com o original. Quem usa a potência extra o tempo todo gasta mais."),
            ]},
            {"h2": "Remap, chip de potência e piggyback", "table": [
                ["", "Remap", "Chip / piggyback"],
                ["Como funciona", "Reprograma a própria ECU", "Um módulo externo altera os sinais que chegam à ECU"],
                ["Integração", "A central controla tudo, com as proteções originais", "A ECU recebe leituras \"enganadas\""],
                ["Volta ao original", "Regravando o software guardado", "Retirando o módulo"],
            ], "p_depois": ["Na Veloce trabalhamos com remap: a central continua no controle e as proteções do motor seguem ativas."]},
            {"h2": "Quando o remap vale a pena", "check": [
                "Carro turbo a gasolina em bom estado e com manutenção em dia",
                "Quem quer mais resposta no dia a dia, sem mudar o visual do carro",
                "Antes ou depois de peças de apoio, como escapamento e admissão (stage 2)",
                "Quando o objetivo é um ganho medido, e não um número de catálogo",
            ], "p_depois": [
                "Não vale a pena em motor com falhas, vazamentos ou manutenção atrasada: primeiro se corrige o carro, "
                'depois se reprograma. Veja também <a href="/blog/remap-estraga-o-motor/">se o remap estraga o motor</a>.']},
            {"h2": "Remap nos carros premium", "p": [
                "Os motores turbo a gasolina das marcas premium, como os 2.0 turbo de BMW, MINI, Audi e Mercedes e os "
                "V6 turbo do Porsche Macan, são os que mais respondem ao remap. Neles, a pressão do turbo é controlada "
                "pela própria central, e um ajuste bem feito muda bastante a disposição do carro.",
                "Em contrapartida, são motores com muita eletrônica e proteções. Por isso o remap precisa respeitar a "
                "versão de software de cada carro e ser conferido depois, e não só gravado.",
            ]},
            {"h2": "O papel do dinamômetro", "p": [
                "O dinamômetro mede a potência e o torque reais do carro. É ele que separa o ganho de verdade do ganho "
                "prometido: medimos antes e depois da reprogramação, no mesmo equipamento e nas mesmas condições.",
                'A curva de potência também mostra se o motor está trabalhando bem em toda a faixa de rotação. Veja como funciona o <a href="/dinamometro/">dinamômetro da Veloce</a>.',
            ], "fotos": [("https://veloceautospecialista.com.br/wp-content/uploads/2026/10/porsche-911-dinamometro-veloce.webp", "Porsche 911 Carrera Cabriolet no dinamômetro da Veloce", 1200, 1600),
                       ("https://veloceautospecialista.com.br/wp-content/uploads/2026/10/golf-gti-dinamometro-veloce.webp", "Volkswagen Golf GTI no dinamômetro da Veloce", 1200, 1600)]},
            leia(L_STAGE, L_CUSTA, L_GARANTIA),
        ],
        [("Remap é a mesma coisa que reprogramação?", "Sim. Remap, reprogramação de ECU e reprogramação eletrônica são nomes para o mesmo serviço."),
         ("Quanto ganha de potência?", "Depende do motor, do câmbio e do estado do carro. Por isso medimos no dinamômetro antes e depois, em vez de prometer um número."),
         ("Dá para voltar ao original?", "Sim. O software original é guardado antes da reprogramação e pode ser regravado na central."),
         ("Remap funciona em carro aspirado?", "Funciona, mas o ganho é menor. Os maiores ganhos aparecem nos motores turbo."),
         ("Onde fazer remap em São Paulo?", 'A Veloce faz remap na própria oficina, na Av. Casa Verde, Zona Norte, com dinamômetro próprio. Veja o <a href="/remap/">serviço de remap</a>.')],
        "Quer saber o que o remap faz no seu carro?", "/remap/", "Ver o serviço de remap",
        "Olá, li o artigo sobre remap e quero tirar uma dúvida. Carro (modelo/ano): "),
    # ------------------------------------------------------------------ CAUTELAR x PRÉ-COMPRA
    art("vistoria-cautelar-ou-inspecao-pre-compra", "Vistoria Cautelar ou Inspeção Pré-Compra: Qual Fazer?",
        "Vistoria cautelar e inspeção pré-compra não são a mesma coisa. Veja o que cada uma verifica e por que, em carro importado, o ideal é fazer as duas.",
        "Vistoria cautelar ou inspeção pré-compra: qual fazer?",
        "As duas protegem quem vai comprar um carro usado, mas olham coisas diferentes. A cautelar confere documentos e "
        "estrutura; a pré-compra confere a mecânica. Em um importado, a resposta curta é: faça as duas.",
        "Blog · Compra de carro usado", 6, FOTO_MINI,
        [
            {"h2": "A diferença em uma tabela", "table": [
                ["", "Vistoria cautelar", "Inspeção pré-compra"],
                ["Quem faz", "Empresa de vistoria", "Oficina mecânica especializada"],
                ["Foco", "Documentação, identificação e estrutura", "Motor, câmbio, eletrônica e suspensão"],
                ["Descobre", "Chassi e motor remarcados, sinistro, leilão, restrições", "Falhas nas centrais, vazamentos, desgaste, reparos caros pela frente"],
                ["Resultado", "Laudo de aprovado, com apontamento ou reprovado", "Relatório técnico com prioridades e estimativa de reparos"],
            ], "p_depois": ["A Veloce faz a inspeção pré-compra. Para a cautelar, procure uma empresa de vistoria."]},
            {"h2": "O que a vistoria cautelar verifica", "p": [
                "A cautelar existe para evitar problema de procedência: confere se a numeração do chassi, do motor e dos "
                "vidros bate com os documentos, procura sinais de remarcação e de batida estrutural e consulta registros "
                "de leilão, sinistro e restrições.",
                "Ela não avalia se o câmbio está no fim da vida, se o motor tem vazamento ou se há falhas gravadas nas "
                "centrais. Um carro pode ser aprovado na cautelar e ainda esconder um reparo caro.",
            ]},
            {"h2": "O que a inspeção pré-compra verifica", "check": [
                "Leitura de todas as centrais e histórico de falhas",
                "Coerência da quilometragem entre os módulos",
                "Motor: vazamentos, ruídos, partida a frio, arrefecimento",
                "Câmbio automático: trocas e trancos",
                "Suspensão, inclusive suspensão a ar",
                "Freios, pneus e sistemas de conforto",
                "Teste de rodagem com acompanhamento técnico",
            ], "p_depois": ['Veja como funciona a <a href="/inspecao-pre-compra/">inspeção pré-compra da Veloce</a>.']},
            {"h2": "Por que, no importado, vale fazer as duas", "cards": [
                ("O reparo é caro", "Câmbio, suspensão a ar ou turbo de um carro premium custam muito mais que a inspeção."),
                ("A eletrônica guarda pistas", "Falhas registradas nas centrais mostram problemas que o test drive não revela."),
                ("Procedência importa", "Carro importado de leilão ou com sinistro perde valor de revenda: a cautelar evita isso."),
                ("O relatório negocia", "Os reparos apontados na pré-compra viram argumento para ajustar o preço."),
            ]},
            {"h2": "A ordem ideal", "steps": [
                ("Cautelar primeiro.", "Se a procedência tiver problema, você desiste antes de gastar com o resto."),
                ("Pré-compra em seguida.", "Com a procedência aprovada, a inspeção mecânica mostra o estado real do carro."),
                ("Negociação.", "Use o relatório técnico para negociar ou para decidir com segurança."),
            ]},
            {"h2": "Checklist para comprar um importado usado", "check": [
                "Peça o histórico de revisões e notas fiscais dos serviços",
                "Confira se o manual e a chave reserva estão com o carro",
                "Desconfie de centrais sem nenhuma falha em carro com anos de uso",
                "Compare a quilometragem do painel com a das revisões",
                "Faça o test drive com o motor frio, não só aquecido",
                "Teste todos os modos de condução e o câmbio em manobras",
                "Faça a cautelar antes e a inspeção pré-compra em seguida",
                "Leve o relatório para a negociação do preço",
            ], "p_depois": ["Um importado bem comprado é um carro que dá prazer por anos. Um mal comprado vira uma sequência de reparos caros logo nos primeiros meses."]},
            leia(L_OQUE, L_CUSTA, L_ESTRAGA),
        ],
        [("Vistoria cautelar é obrigatória?", "Não. Ela é opcional e feita por quem quer comprar com segurança."),
         ("A Veloce faz vistoria cautelar?", "Não. A Veloce faz a inspeção pré-compra, que avalia a parte mecânica e eletrônica do carro."),
         ("Carro de concessionária precisa de inspeção?", "Vale fazer. Carro de loja também pode ter falhas registradas ou manutenção atrasada que não aparecem no anúncio."),
         ("Quanto tempo leva a inspeção pré-compra?", "Depende do modelo. Combine pelo WhatsApp que informamos o tempo estimado."),
         ("O vendedor precisa levar o carro?", "Sim. A leitura das centrais e a inspeção por baixo do carro são feitas na oficina, na Av. Casa Verde, 3010.")],
        "Vai comprar um importado? Agende a inspeção", "/inspecao-pre-compra/", "Ver a inspeção pré-compra",
        "Olá, li o artigo sobre vistoria cautelar e quero agendar uma inspeção pré-compra. Carro: "),
    # ------------------------------------------------------------------ QUANTO CUSTA
    art("quanto-custa-um-remap", "Quanto Custa um Remap? Do que o Preço Depende | Veloce",
        "Quanto custa um remap: entenda o que muda o preço (motor, stage, câmbio, peças) e por que remap barato sai caro. Peça o orçamento para o seu carro.",
        "Quanto custa um remap?",
        "Não existe preço único de remap: o valor muda conforme o motor, o nível da reprogramação e o que o carro precisa "
        "antes e depois. Veja do que o preço depende e como pedir um orçamento justo.",
        "Blog · Remap", 5, FOTO_911,
        [
            {"h2": "Do que o preço depende", "cards": [
                ("O motor", "Cada motor tem uma central, um protocolo de leitura e um potencial de ganho diferentes."),
                ("O stage", 'Stage 1 é só software; stage 2 exige peças de apoio e um ajuste mais fino. <a href="/blog/remap-stage-1-e-stage-2/">Veja a diferença</a>.'),
                ("O câmbio", 'Em alguns carros o câmbio limita o torque e precisa de <a href="/remap/cambio/">remap de câmbio</a> junto.'),
                ("O estado do carro", "Velas, bobinas, vazamentos ou falhas precisam ser corrigidos antes: entram no orçamento."),
                ("A conferência", "Medição no dinamômetro e teste de rodagem fazem parte de um remap bem feito."),
                ("As peças de apoio", "No stage 2: escapamento, admissão ou intercooler, conforme o projeto."),
            ]},
            {"h2": "Por que remap barato sai caro", "p": [
                "O remap muito barato costuma ser um arquivo genérico baixado da internet e gravado sem diagnóstico. "
                "Ninguém confere se o motor estava saudável, se o arquivo é para aquela versão de software ou se o ganho "
                "aconteceu de fato.",
                "O resultado aparece depois: falhas de ignição, luz de injeção, câmbio dando trancos ou, no pior caso, "
                'dano ao motor. É isso que dá fama ruim ao remap. <a href="/blog/remap-estraga-o-motor/">Veja o que realmente causa problema</a>.',
            ]},
            {"h2": "O que está incluído no remap da Veloce", "check": [
                "Diagnóstico completo antes da reprogramação",
                "Backup do software original da ECU",
                "Reprogramação feita na própria oficina",
                "Conferência no dinamômetro próprio",
                "Teste de rodagem e nova leitura das centrais",
                "Relatório técnico na entrega",
            ]},
            {"h2": "Como pedir um orçamento justo", "steps": [
                ("Informe o carro.", "Modelo, ano, motor e se já tem alguma modificação."),
                ("Diga o objetivo.", "Mais resposta no dia a dia, stage 2 ou projeto com câmbio."),
                ("Avaliação.", "O carro é avaliado antes; o orçamento fechado vem depois do diagnóstico."),
            ], "p_depois": ["Na Veloce não trabalhamos com tabela fixa: o valor é passado para o seu carro, antes de qualquer serviço."]},
            {"h2": "O que perguntar antes de fechar um remap", "check": [
                "O carro passa por diagnóstico antes da reprogramação?",
                "O software original é guardado? Dá para voltar?",
                "O arquivo é feito para o meu motor e versão de software?",
                "O ganho é medido em dinamômetro, antes e depois?",
                "O câmbio é avaliado junto?",
                "Recebo um relatório do que foi feito?",
            ], "p_depois": ["Se a resposta for não para a maioria dessas perguntas, o preço baixo está saindo caro: o que falta no serviço é justamente o que protege o seu motor."]},
            leia(L_OQUE, L_STAGE, L_GARANTIA),
        ],
        [("Por que a Veloce não publica preço de remap?", "Porque o valor muda conforme o motor, o stage e o estado do carro. Um preço fixo esconderia diferenças importantes."),
         ("O orçamento é cobrado?", "Mande o modelo e o ano pelo WhatsApp que respondemos sem compromisso com a avaliação para o seu carro."),
         ("Stage 2 custa mais que stage 1?", "Sim. Além do software mais elaborado, exige peças de apoio e mais horas de ajuste e conferência."),
         ("O remap de câmbio é cobrado à parte?", "É um serviço separado, recomendado quando o câmbio limita o torque do motor reprogramado."),
         ("Vale a pena pagar mais por um remap com dinamômetro?", "Sim. É o dinamômetro que mostra o ganho real e se o motor está trabalhando dentro do esperado.")],
        "Peça o orçamento do remap para o seu carro", "/remap/", "Ver o serviço de remap",
        "Olá, quero um orçamento de remap. Carro (modelo/ano/motor): "),
    # ------------------------------------------------------------------ STAGE 1 x STAGE 2
    art("remap-stage-1-e-stage-2", "Stage 1 e Stage 2 no Carro: O que é e Qual a Diferença",
        "Stage 1 é o remap só no software; stage 2 exige peças de apoio, como admissão e escapamento. Veja o ganho, os riscos e qual faz sentido para o seu carro.",
        "Remap stage 1 x stage 2: qual a diferença?",
        "Stage é o nível do projeto de remap. O stage 1 mexe só no software; o stage 2 soma peças de apoio para ir além. "
        "Veja o que muda em cada um e como escolher.",
        "Blog · Remap", 6, FOTO_911,
        [
            {"h2": "Stage 1 x stage 2 lado a lado", "table": [
                ["", "Stage 1", "Stage 2"],
                ["O que muda", "Só o software da ECU", "Software + peças de apoio"],
                ["Peças típicas", "Nenhuma", "Downpipe, admissão, intercooler, conforme o carro"],
                ["Ganho", "Moderado", "Maior"],
                ["Uso", "Dia a dia, carro original", "Quem aceita modificar o carro"],
                ["Câmbio", "Normalmente dá conta", "Pode precisar de remap de câmbio"],
            ]},
            {"h2": "Stage 1: o ponto de partida", "p": [
                "No stage 1 o carro continua original por fora e por baixo. A reprogramação ajusta pressão do turbo, "
                "ignição e injeção para aproveitar a margem que a fábrica deixou. É o nível indicado para quem usa o carro "
                "no dia a dia e quer mais resposta sem abrir mão da confiabilidade.",
                "Como não há peças novas, o stage 1 também é o mais simples de reverter: basta regravar o software original.",
            ]},
            {"h2": "Stage 2: quando o software sozinho não basta", "p": [
                "A partir de certo ponto, quem limita o motor são as peças: o escapamento restringe a saída dos gases, a "
                "admissão restringe a entrada de ar e o intercooler não dá conta de resfriar o ar comprimido. O stage 2 troca "
                "essas peças e faz um software sob medida para elas.",
                'Com mais torque, o câmbio pode virar o limite: em vários carros o stage 2 pede <a href="/remap/cambio/">remap de câmbio</a> junto.',
            ]},
            {"h2": "Qual escolher", "cards": [
                ("Uso diário", "Stage 1. Mais força nas retomadas, consumo parecido no uso normal e carro original."),
                ("Projeto de performance", "Stage 2, com peças de apoio, câmbio avaliado e freios à altura."),
                ("Carro na garantia", 'Converse antes: <a href="/blog/remap-perde-garantia/">veja o que acontece com a garantia</a>.'),
                ("Dúvida entre os dois", "Comece pelo stage 1. O stage 2 pode vir depois, aproveitando o diagnóstico já feito."),
            ]},
            {"h2": "Antes de qualquer stage", "check": [
                "Manutenção em dia: óleo, velas, bobinas e filtros",
                "Sem falhas registradas nas centrais",
                "Arrefecimento sem vazamentos",
                "Pneus e freios compatíveis com o novo desempenho",
            ], "p_depois": ['O ganho de cada stage é conferido no <a href="/dinamometro/">dinamômetro da Veloce</a>, antes e depois.']},
            {"h2": "Stage 1 e stage 2 nos carros premium", "cards": [
                ("BMW e MINI", 'Os motores turbo da BMW e do MINI respondem bem ao stage 1. Veja a <a href="/oficina-bmw/">oficina BMW</a> e a <a href="/oficina-mini/">oficina MINI</a>.'),
                ("Audi", 'Os TFSI turbo aceitam bem o remap; no stage 2, o câmbio S tronic entra na conta. Veja a <a href="/oficina-audi/">oficina Audi</a>.'),
                ("Mercedes-Benz", 'Os motores turbo dos Classe A, C, GLA e GLC são candidatos comuns ao stage 1. Veja a <a href="/oficina-mercedes-benz/">oficina Mercedes</a>.'),
                ("Porsche", 'No Macan e no Cayenne, o projeto é avaliado junto com o PDK. Veja a <a href="/oficina-porsche/">oficina Porsche</a>.'),
            ]},
            leia(L_OQUE, L_CUSTA, L_ESTRAGA),
        ],
        [("Existe stage 3?", "Existe, em projetos com turbo maior e mudanças internas no motor. É um projeto sob medida, avaliado caso a caso."),
         ("Stage 2 sem as peças funciona?", "Não deve ser feito. O software de stage 2 é feito para as peças de apoio; sem elas, o motor trabalha fora do previsto."),
         ("Dá para começar no stage 1 e ir para o stage 2 depois?", "Sim. É o caminho mais comum e aproveita o diagnóstico já feito."),
         ("O consumo muda?", "No stage 1, no uso normal, fica parecido com o original. No stage 2 depende muito de como o carro é usado."),
         ("Qual stage a Veloce faz?", 'Stage 1 e stage 2, na própria oficina e com dinamômetro próprio. Veja o <a href="/remap/">serviço de remap</a>.')],
        "Quer saber qual stage faz sentido no seu carro?", "/remap/", "Ver o serviço de remap",
        "Olá, quero saber qual stage de remap faz sentido no meu carro. Modelo/ano/motor: "),
    # ------------------------------------------------------------------ ESTRAGA O MOTOR
    art("remap-estraga-o-motor", "Remap Estraga o Motor? Mito, Risco e Como Evitar | Veloce",
        "Remap estraga o motor? Veja o que é mito, o que realmente causa problema e como fazer um remap seguro, com diagnóstico, backup e dinamômetro.",
        "Remap estraga o motor?",
        "A resposta curta: um remap bem feito, em um motor saudável, não estraga o motor. O que estraga é remap mal feito. "
        "Veja onde está o risco e como evitá-lo.",
        "Blog · Remap", 5, FOTO_GTI,
        [
            {"h2": "Mito x fato", "table": [
                ["Mito", "Fato"],
                ["Todo remap encurta a vida do motor", "Um ajuste dentro dos limites dos componentes mantém o motor trabalhando com folga"],
                ["Remap é só aumentar o turbo", "Pressão, ignição, injeção e limitadores são ajustados em conjunto"],
                ["Qualquer arquivo serve", "Cada motor e versão de software pede um ajuste próprio"],
                ["Se ligou, está certo", "Só a conferência (leitura da ECU, teste e dinamômetro) mostra se ficou certo"],
            ]},
            {"h2": "O que realmente causa problema", "cards": [
                ("Motor com defeito", "Vela, bobina, vazamento ou falha de arrefecimento que já existia piora com mais carga."),
                ("Arquivo genérico", "Um mapa feito para outra versão de software ou outro combustível trabalha fora do previsto."),
                ("Ajuste agressivo demais", "Buscar o número máximo, e não o motor saudável, leva ao limite das peças."),
                ("Manutenção atrasada", "Óleo vencido e filtros sujos já cobram caro no motor original; com remap, mais ainda."),
            ]},
            {"h2": "Como fazer um remap seguro", "steps": [
                ("Diagnóstico antes.", "Motor com falha não recebe remap: primeiro se corrige o carro."),
                ("Backup do original.", "O software de fábrica é guardado e pode ser regravado."),
                ("Ajuste sob medida.", "Mapa feito para aquele motor, versão de software e combustível."),
                ("Conferência no dinamômetro.", "O ganho e o comportamento do motor são medidos, não estimados."),
                ("Manutenção em dia.", "Depois do remap, o plano de revisão continua, às vezes com intervalos menores."),
            ]},
            {"h2": "Sinais de um remap mal feito", "check": [
                "Luz de injeção acendendo depois da reprogramação",
                "Motor falhando ou batendo pino em carga",
                "Câmbio com trancos que não existiam",
                "Perda de força em vez de ganho",
                "Temperatura subindo mais que o normal",
            ], "p_depois": ['Notou algum desses sinais? Traga o carro para um diagnóstico. Veja como trabalhamos no <a href="/remap/">remap da Veloce</a>.']},
            {"h2": "Cuidados depois do remap", "check": [
                "Combustível de boa qualidade, como indicado no ajuste",
                "Óleo trocado no prazo, ou antes em uso esportivo",
                "Velas e bobinas revisadas com mais frequência",
                "Atenção à temperatura e a qualquer luz no painel",
                "Revisão do câmbio e da embreagem quando o torque aumenta",
            ]},
            {"h2": "O dinamômetro mostra a verdade", "p": [
                "Um motor saudável e bem reprogramado mostra uma curva de potência limpa, sem quedas nem falhas. É por "
                'isso que conferimos cada remap no <a href="/dinamometro/">dinamômetro próprio</a>: o resultado é medido, não suposto.',
            ], "fotos": [("https://veloceautospecialista.com.br/wp-content/uploads/2026/10/porsche-911-dinamometro-veloce.webp", "Porsche 911 Carrera Cabriolet no dinamômetro da Veloce", 1200, 1600),
                       ("https://veloceautospecialista.com.br/wp-content/uploads/2026/10/golf-gti-dinamometro-veloce.webp", "Volkswagen Golf GTI no dinamômetro da Veloce", 1200, 1600)]},
            leia(L_STAGE, L_GARANTIA, L_CUSTA),
        ],
        [("Remap diminui a vida útil do motor?", "Um remap dentro dos limites dos componentes, em motor saudável e com manutenção em dia, não deve reduzir a vida útil de forma relevante."),
         ("Remap aumenta o desgaste da embreagem?", "Mais torque exige mais da embreagem e do câmbio. Por isso o câmbio também é avaliado, e às vezes reprogramado."),
         ("Posso voltar ao original se não gostar?", "Sim. O software original é guardado antes e pode ser regravado."),
         ("Preciso trocar o óleo com mais frequência?", "Em carro reprogramado e usado de forma esportiva, vale encurtar o intervalo. Orientamos no relatório de entrega."),
         ("Como saber se meu remap foi bem feito?", "Uma leitura das centrais e uma medição no dinamômetro mostram se o motor está trabalhando dentro do esperado.")],
        "Quer um remap seguro no seu carro?", "/remap/", "Ver o serviço de remap",
        "Olá, li o artigo sobre remap e motor e quero tirar uma dúvida. Carro (modelo/ano): "),
    # ------------------------------------------------------------------ PERDE GARANTIA
    art("remap-perde-garantia", "Remap Perde a Garantia de Fábrica? Entenda | Veloce",
        "Remap perde a garantia? Veja o que a montadora pode recusar, por que esconder a reprogramação não é saída e qual o melhor momento para fazer o remap.",
        "Remap perde a garantia de fábrica?",
        "Esta é uma das dúvidas mais comuns de quem tem carro novo. Veja o que acontece com a garantia, o que a montadora "
        "consegue identificar e qual é o melhor momento para reprogramar.",
        "Blog · Remap", 5, FOTO_911,
        [
            {"h2": "A resposta curta", "p": [
                "A montadora pode recusar a garantia de componentes ligados ao motor e ao câmbio quando encontra o "
                "software da central alterado. A garantia do resto do carro (pintura, multimídia, acabamento) não tem "
                "relação com o remap.",
                "Por isso, se o seu carro ainda está na garantia de fábrica, a decisão de reprogramar deve ser tomada "
                "sabendo desse risco.",
            ]},
            {"h2": "O que a montadora consegue identificar", "p": [
                "As centrais modernas registram a versão do software e, em muitos casos, quantas vezes foram gravadas. "
                "Mesmo regravando o original, pode ficar registro de que houve gravação.",
                "Por isso não recomendamos esconder a reprogramação para usar a garantia: além de arriscado, não é um "
                "jeito honesto de lidar com a montadora.",
            ]},
            {"h2": "Quais são as opções", "cards": [
                ("Esperar a garantia acabar", "É o caminho mais seguro para quem quer o remap sem discussão com a montadora."),
                ("Fazer sabendo do risco", "Para quem prefere o ganho agora e aceita assumir os reparos de motor e câmbio."),
                ("Começar pelo stage 1", 'É o nível mais leve e reversível. <a href="/blog/remap-stage-1-e-stage-2/">Veja a diferença entre os stages</a>.'),
                ("Conversar antes", "Avaliamos o carro, o tempo de garantia restante e o seu objetivo antes de recomendar."),
            ]},
            {"h2": "E o seguro?", "p": [
                "Alterações de desempenho podem precisar ser informadas à seguradora. Antes de reprogramar, confirme com "
                "a sua seguradora como ela trata o remap, para não ter surpresa em caso de sinistro.",
            ]},
            {"h2": "Depois da garantia: como manter o carro protegido", "check": [
                "Diagnóstico completo antes da reprogramação",
                "Backup do software original",
                "Conferência no dinamômetro",
                "Revisões em dia, com relatório técnico",
            ], "p_depois": ['Veja como funciona o <a href="/remap/">remap da Veloce</a> e a <a href="/manutencao-preventiva/">revisão de carros importados</a>.']},
            {"h2": "Garantia de fábrica x garantia do serviço", "p": [
                "A garantia de fábrica cobre defeitos de fabricação do carro. A garantia do serviço cobre o trabalho feito "
                "pela oficina. São coisas diferentes: o remap pode afetar a primeira, e a Veloce responde pela segunda.",
                "Todos os serviços da Veloce têm garantia técnica e saem com relatório do que foi feito, inclusive a "
                "reprogramação. Assim você sabe exatamente o que foi alterado no seu carro.",
            ]},
            leia(L_OQUE, L_ESTRAGA, L_CUSTA),
        ],
        [("Remap perde toda a garantia do carro?", "Não. O risco está nos componentes ligados ao motor e ao câmbio. O restante do carro segue a garantia normal."),
         ("Voltar ao original antes da revisão resolve?", "Não é garantido: a central pode registrar que houve gravação. Não recomendamos esconder a reprogramação."),
         ("Fazer revisão fora da concessionária perde a garantia?", 'Consulte as condições do manual de garantia. Fora dela, a <a href="/manutencao-preventiva/">revisão em oficina especializada</a> segue o plano da montadora.'),
         ("Vale esperar a garantia acabar?", "Para quem não quer correr risco, sim. Enquanto isso, a manutenção em dia prepara o carro para o remap."),
         ("A Veloce faz remap em carro na garantia?", "Fazemos, depois de explicar o risco e de você decidir. A escolha é sempre do dono do carro.")],
        "Quer conversar sobre remap e garantia?", "/remap/", "Ver o serviço de remap",
        "Olá, meu carro está na garantia e quero conversar sobre remap. Modelo/ano: "),
]

BLOG_HUB = {
    "slug": "blog", "tipo": "blog", "title": "Blog da Veloce: Remap, Revisão e Carros Premium",
    "description": "Guias da Veloce Auto Specialista sobre remap, stage 1 e 2, garantia, inspeção pré-compra e manutenção de carros premium e importados.",
    "h1": "Blog da Veloce", "kicker": "Guias e dúvidas de quem tem carro premium",
    "lead": "Respostas diretas para as dúvidas mais comuns sobre remap, compra de carro usado e manutenção de carros "
            "premium e importados, escritas por quem trabalha com eles todos os dias.",
    "foto": FOTO_OFICINA, "botao": "Tirar dúvidas no WhatsApp", "whatsapp_msg": "Olá, tenho uma dúvida sobre o meu carro. Modelo/ano: ",
    "botao_alt": ("Ver o serviço de remap", "/remap/"), "hero_nota": "📍 Veloce Auto Specialista · Av. Casa Verde, 3010 – Zona Norte de SP",
    "breadcrumb": "Blog",
    "secoes": [
        {"h2": "Remap", "p": ["Tudo sobre reprogramação de motor e de câmbio: como funciona, quanto custa, riscos e garantia."],
         "cards": [(t, f'{d} <a href="{h}">Ler o artigo</a>.') for t, h, d in (L_OQUE, L_STAGE, L_CUSTA, L_ESTRAGA, L_GARANTIA)]},
        {"h2": "Compra de carro usado", "cards": [(L_CAUTELAR[0], f'{L_CAUTELAR[2]} <a href="{L_CAUTELAR[1]}">Ler o artigo</a>.')],
         "p_depois": ['Vai comprar um importado? Conheça a <a href="/inspecao-pre-compra/">inspeção pré-compra da Veloce</a>.']},
        {"h2": "Serviços da Veloce", "chips": ['<a href="/remap/">Remap</a>', '<a href="/remap/cambio/">Remap de câmbio</a>',
                                              '<a href="/dinamometro/">Dinamômetro</a>', '<a href="/inspecao-pre-compra/">Inspeção pré-compra</a>',
                                              '<a href="/manutencao-preventiva/">Revisão</a>', '<a href="/oficina-bmw/revisao/">Revisão BMW</a>',
                                              '<a href="/oficina-land-rover/revisao/">Revisão Land Rover</a>']},
    ],
    "faq_h2": "Perguntas frequentes",
    "faq": [("Quem escreve os artigos?", "A equipe da Veloce Auto Specialista, oficina de carros premium e importados na Zona Norte de São Paulo."),
            ("Posso mandar uma dúvida?", "Sim. Mande pelo WhatsApp: as dúvidas mais comuns viram novos artigos."),
            ("Onde fica a Veloce?", "Na Av. Casa Verde, 3010, Casa Verde, Zona Norte de São Paulo.")],
    "cta_h2": "Tem uma dúvida sobre o seu carro?", "cta_p": "Mande o modelo, o ano e a sua pergunta. Um especialista responde pelo WhatsApp.",
}

# ------------------------------------------------------------------ SUBPÁGINAS DE SERVIÇO
SUBPAGINAS = [
    {
        "slug": "revisao", "pai": {"slug": "oficina-bmw", "nome": "Oficina BMW"}, "arquivo": "oficina-bmw-revisao",
        "title": "Revisão BMW em São Paulo: 320i, X1 e Mais | Veloce",
        "description": "Revisão BMW em São Paulo seguindo o CBS: óleo BMW Longlife, filtros, velas, fluido de freio e reset do aviso no painel. Peças genuínas. Agende.",
        "h1": "Revisão BMW em São Paulo", "foto": FOTO_OFICINA,
        "lead": "Revisão da sua BMW no padrão da montadora, seguindo o que o CBS do painel pede, com peças genuínas, "
                "reset do aviso de revisão e relatório técnico de tudo o que foi feito.",
        "botao": "Agendar revisão BMW no WhatsApp", "whatsapp_msg": "Olá, quero agendar a revisão da minha BMW. Modelo/ano/km: ",
        "botao_alt": ("Ver a oficina BMW", "/oficina-bmw/"), "breadcrumb": "Revisão BMW",
        "servico": "Revisão BMW", "servico_tipo": "Revisão periódica de veículos BMW conforme o CBS",
        "secoes": [
            {"h2": "Como funciona a revisão da BMW (CBS)", "p": [
                "As BMW não têm uma tabela fixa de revisão: o CBS (Condition Based Service) acompanha o uso do carro e "
                "avisa no painel o que está vencendo, item por item: óleo do motor, fluido de freio, velas, filtros e a "
                "inspeção geral.",
                "Na revisão, lemos o CBS e as centrais, fazemos o que está vencido ou perto de vencer e, no fim, "
                "atualizamos os intervalos no painel. Você sai sabendo exatamente o que foi feito e o que vem na próxima.",
            ]},
            {"h2": "O que entra na revisão BMW", "check": [
                "Óleo com a especificação BMW Longlife correta para o seu motor",
                "Filtro de óleo, de ar e de cabine conforme o CBS",
                "Velas de ignição no intervalo indicado",
                "Fluido de freio (a BMW pede troca por tempo)",
                "Inspeção de freios, suspensão, direção e pneus",
                "Leitura de todas as centrais e falhas registradas",
                "Reset do aviso de revisão (CBS) no painel",
                "Teste de rodagem e relatório técnico",
            ]},
            {"h2": "Revisão por modelo", "cards": [
                ("BMW 320i", "Das gerações F30 e G20, com motor 2.0 turbo. Atenção a vazamentos de óleo e ao arrefecimento."),
                ("BMW X1", "Com motores 1.5 e 2.0 turbo. Revisão inclui checagem da suspensão e do câmbio automático."),
                ("Série 3, 5 e X3/X5", "Motores quatro e seis cilindros, com os itens do CBS e inspeção de suspensão."),
                ("Esportivos M", "Óleo e fluidos com especificação própria e inspeção de freios de alto desempenho."),
            ]},
            {"h2": "Itens que valem atenção além do CBS", "cards": [
                ("Óleo do câmbio", "Vendido como \"vitalício\", mas a troca no tempo certo evita trancos e reparos caros."),
                ("Arrefecimento", "Bomba d'água e termostato são pontos de atenção em vários motores BMW."),
                ("Vazamentos de óleo", "Juntas da tampa de válvulas e do suporte do filtro ressecam com o tempo."),
                ("Uso severo", "No trânsito de São Paulo, vale conversar sobre encurtar o intervalo do óleo."),
            ]},
            {"h2": "Quanto custa a revisão BMW", "p": [
                "O valor depende do motor, da quilometragem e de quais itens o CBS está pedindo naquela revisão. Uma "
                "revisão só de óleo e filtro custa bem menos que uma que inclui velas, fluido de freio e filtros de ar.",
                "Por isso passamos o orçamento detalhado depois de ler o CBS do seu carro, e você aprova item por item.",
            ]},
            {"h2": "Sinais de que a revisão está atrasada", "check": [
                "Aviso amarelo de revisão no painel",
                "Marcha lenta irregular ou motor menos suave",
                "Pedal de freio mais longo",
                "Consumo maior que o normal",
            ]},
            ATENDE,
        ],
        "faq_h2": "Perguntas frequentes sobre revisão BMW",
        "faq": [("Fazer a revisão da BMW fora da concessionária perde a garantia?", "Se a sua BMW está na garantia de fábrica, consulte as condições do manual de garantia. Fora dela, a revisão segue o mesmo plano da montadora, com peças genuínas e registro do serviço."),
                ("Quanto custa a revisão de uma BMW 320i?", "Depende do motor, da quilometragem e dos itens que o CBS está pedindo. Mande o modelo, o ano e a km pelo WhatsApp que passamos o orçamento."),
                ("De quanto em quanto tempo revisar a BMW?", "Siga o CBS do painel. Em uso severo, como o trânsito de São Paulo, vale encurtar o intervalo do óleo."),
                ("Vocês zeram o aviso de revisão no painel?", "Sim. Ao final da revisão os intervalos do CBS são atualizados."),
                ("Usam peças originais?", "Sim. Somente peças genuínas BMW, com procedência informada no orçamento.")],
        "cta_h2": "Agende a revisão da sua BMW", "cta_p": "Mande o modelo, o ano e a quilometragem. Um especialista responde pelo WhatsApp.",
    },
    {
        "slug": "revisao", "pai": {"slug": "oficina-land-rover", "nome": "Oficina Land Rover"}, "arquivo": "oficina-land-rover-revisao",
        "title": "Revisão Land Rover e Range Rover em SP | Veloce",
        "description": "Revisão Land Rover e Range Rover em São Paulo: óleo, filtros, fluidos, suspensão a ar, tração e reset do aviso no painel. Peças genuínas. Agende.",
        "h1": "Revisão Land Rover e Range Rover em São Paulo", "foto": FOTO_EVOQUE,
        "lead": "Revisão da sua Land Rover no padrão da montadora, com peças genuínas, atenção à suspensão a ar e à tração, "
                "reset do aviso de revisão e relatório técnico de tudo o que foi feito.",
        "botao": "Agendar revisão Land Rover no WhatsApp", "whatsapp_msg": "Olá, quero agendar a revisão da minha Land Rover. Modelo/ano/km: ",
        "botao_alt": ("Ver a oficina Land Rover", "/oficina-land-rover/"), "breadcrumb": "Revisão Land Rover",
        "servico": "Revisão Land Rover", "servico_tipo": "Revisão periódica de veículos Land Rover e Range Rover",
        "secoes": [
            {"h2": "O que entra na revisão Land Rover", "check": [
                "Óleo com a especificação correta para o seu motor (gasolina ou diesel)",
                "Filtros de óleo, ar, cabine e combustível conforme o plano",
                "Fluido de freio e fluido de arrefecimento",
                "Inspeção da suspensão a ar: bolsas, linhas e compressor",
                "Óleo da caixa de transferência e dos diferenciais quando vencido",
                "Leitura de todas as centrais e falhas registradas",
                "Reset do aviso de revisão no painel",
                "Teste de rodagem e relatório técnico",
            ]},
            {"h2": "Por que a revisão da Land Rover é diferente", "p": [
                "Uma Land Rover tem sistemas que um carro comum não tem: suspensão a ar com regulagem de altura, tração "
                "integral com caixa de transferência e programas de terreno, além de muita eletrônica. A revisão precisa "
                "olhar para tudo isso, e não só trocar óleo e filtro.",
                "Nos motores diesel, o uso só urbano ainda pede atenção ao filtro de partículas, que se entope quando o "
                "carro não roda em estrada.",
            ]},
            {"h2": "Revisão por modelo", "cards": [
                ("Range Rover Evoque", "Motores turbo a gasolina e diesel. Atenção a arrefecimento e vazamentos de óleo."),
                ("Discovery Sport", "Revisão inclui checagem da tração integral e da suspensão."),
                ("Range Rover Velar e Sport", "Suspensão a ar, motores maiores e mais eletrônica: inspeção completa a cada revisão."),
                ("Defender", "Revisão com foco em suspensão, tração e uso fora de estrada."),
            ]},
            {"h2": "Itens que valem atenção", "cards": [
                ("Suspensão a ar", "Carro amanhecendo baixo ou compressor trabalhando demais pedem diagnóstico logo."),
                ("Filtro de partículas (diesel)", "Uso só urbano satura o filtro: a revisão inclui a checagem."),
                ("Tração", "Óleo da caixa de transferência e dos diferenciais também vence."),
                ("Bateria", "Bateria fraca gera avisos em vários sistemas: é testada em toda revisão."),
            ]},
            {"h2": "Quanto custa a revisão Land Rover", "p": [
                "O valor depende do modelo, do motor (gasolina ou diesel) e da quilometragem. Revisões que incluem os "
                "óleos da tração ou itens da suspensão a ar custam mais que uma revisão de óleo e filtros.",
                "Por isso passamos o orçamento detalhado depois de avaliar o carro, e você aprova item por item.",
            ]},
            {"h2": "Sinais de que a revisão está atrasada", "check": [
                "Aviso de revisão no painel",
                "Carro amanhecendo mais baixo de um lado",
                "Aviso de filtro de partículas nas versões diesel",
                "Estalos ou zumbidos na tração em manobras",
            ]},
            ATENDE,
        ],
        "faq_h2": "Perguntas frequentes sobre revisão Land Rover",
        "faq": [("Fazer a revisão da Land Rover fora da concessionária perde a garantia?", "Se o carro está na garantia de fábrica, consulte as condições do manual de garantia. Fora dela, a revisão segue o plano da montadora, com peças genuínas e registro do serviço."),
                ("Quanto custa a revisão de uma Land Rover?", "Depende do modelo, do motor e da quilometragem. Mande os dados pelo WhatsApp que passamos o orçamento."),
                ("Vocês atendem Range Rover diesel?", "Sim. Atendemos as versões a gasolina e diesel, incluindo o filtro de partículas."),
                ("Vocês zeram o aviso de revisão no painel?", "Sim. Ao final da revisão o aviso é atualizado."),
                ("Usam peças originais?", "Sim. Somente peças genuínas Land Rover, com procedência informada no orçamento.")],
        "cta_h2": "Agende a revisão da sua Land Rover", "cta_p": "Mande o modelo, o ano e a quilometragem. Um especialista responde pelo WhatsApp.",
    },
    {
        "slug": "cambio", "pai": {"slug": "remap", "nome": "Remap automotivo"}, "arquivo": "remap-cambio",
        "title": "Remap de Câmbio em São Paulo | Veloce Auto Specialista",
        "description": "Remap de câmbio em São Paulo: trocas mais rápidas, pontos de troca ajustados e limite de torque compatível com o remap do motor. Peça orçamento.",
        "h1": "Remap de câmbio em São Paulo", "foto": FOTO_911,
        "lead": "A reprogramação da central do câmbio ajusta como e quando as marchas trocam. É o complemento natural de um "
                "remap de motor, feita na própria oficina da Veloce, por quem trabalha só com carros premium.",
        "botao": "Pedir orçamento de remap de câmbio", "whatsapp_msg": "Olá, quero um orçamento de remap de câmbio. Carro (modelo/ano/motor): ",
        "botao_alt": ("Ver o remap de motor", "/remap/"), "breadcrumb": "Remap de câmbio",
        "servico": "Remap de câmbio (reprogramação da central do câmbio)", "servico_tipo": "Reprogramação de central de câmbio automático e de dupla embreagem",
        "secoes": [
            {"h2": "O que é o remap de câmbio", "p": [
                "Os câmbios automáticos e de dupla embreagem têm uma central própria, a TCU, que decide em que rotação "
                "cada marcha troca, com que rapidez e quanto torque o câmbio aceita. O remap de câmbio reprograma essa "
                "central.",
                "De fábrica, essa calibração privilegia conforto e consumo. Com o remap, ela passa a acompanhar o "
                'desempenho do motor, principalmente depois de um <a href="/remap/">remap de motor</a>.',
            ]},
            {"h2": "O que muda no carro", "cards": [
                ("Trocas mais rápidas", "Menos tempo entre uma marcha e outra, principalmente nos modos esportivos."),
                ("Pontos de troca", "Marchas trocadas na rotação em que o motor reprogramado rende mais."),
                ("Limite de torque", "O câmbio passa a aceitar o torque extra do motor, em vez de cortá-lo."),
                ("Resposta", "Reduções mais rápidas e menos hesitação na hora de retomar."),
            ]},
            {"h2": "Quando faz sentido", "check": [
                "Depois de um remap stage 2, quando o câmbio limita o torque",
                "Quando o câmbio corta potência em aceleração forte",
                "Quando as trocas ficam lentas para o novo desempenho do motor",
                "Em projetos de performance que pedem um conjunto ajustado",
            ], "p_depois": ['Veja a diferença entre os níveis no artigo <a href="/blog/remap-stage-1-e-stage-2/">stage 1 x stage 2</a>.']},
            {"h2": "Cuidados antes do remap de câmbio", "cards": [
                ("Diagnóstico", "Leitura das centrais do motor e do câmbio antes de qualquer alteração."),
                ("Fluido em dia", "Câmbio com fluido vencido ou com defeito é corrigido antes de ser reprogramado."),
                ("Embreagens", "Nos câmbios de dupla embreagem, o estado das embreagens entra na avaliação."),
                ("Câmbios atendidos", "Automáticos e de dupla embreagem dos carros premium que atendemos: consulte o seu modelo."),
            ]},
            {"h2": "Remap de motor e de câmbio: o conjunto", "p": [
                "Motor e câmbio trabalham juntos. Quando o motor ganha torque e o câmbio continua com a calibração de "
                "fábrica, o carro pode cortar potência, trocar marchas na hora errada ou demorar nas reduções.",
                "Ajustar os dois juntos faz o carro entregar o ganho de forma suave e previsível. O resultado é conferido "
                'em teste de rodagem e no <a href="/dinamometro/">dinamômetro próprio</a>.',
            ]},
            ATENDE,
        ],
        "faq_h2": "Perguntas frequentes sobre remap de câmbio",
        "faq": [("Preciso fazer remap de câmbio junto com o de motor?", "Nem sempre. No stage 1 o câmbio original costuma dar conta; no stage 2 ele com frequência passa a limitar o torque."),
                ("Remap de câmbio perde a garantia?", 'Assim como o remap de motor, a montadora pode recusar a garantia do câmbio. <a href="/blog/remap-perde-garantia/">Veja o artigo sobre garantia</a>.'),
                ("Quanto custa o remap de câmbio?", "Depende do câmbio e do carro. Mande o modelo, o ano e o motor pelo WhatsApp que passamos o orçamento."),
                ("O câmbio fica mais \"duro\"?", "As trocas ficam mais rápidas e firmes nos modos esportivos; no modo normal o carro continua confortável no dia a dia."),
                ("Meu câmbio pode ser reprogramado?", "Depende do modelo. Mande os dados do carro pelo WhatsApp que confirmamos.")],
        "cta_h2": "Peça o orçamento do remap de câmbio", "cta_p": "Mande o modelo, o ano e o motor. Um especialista responde pelo WhatsApp.",
    },
]


def revisao(pai_slug, pai_nome, marca, arquivo, title, description, foto, lead, como, itens, modelos, atencao, sinais, faq_extra):
    """Subpágina /oficina-<marca>/revisao/ no mesmo molde das revisões BMW e Land Rover."""
    return {
        "slug": "revisao", "pai": {"slug": pai_slug, "nome": pai_nome}, "arquivo": arquivo,
        "title": title, "description": description, "h1": f"Revisão {marca} em São Paulo", "foto": foto, "lead": lead,
        "botao": f"Agendar revisão {marca} no WhatsApp", "whatsapp_msg": f"Olá, quero agendar a revisão do meu {marca}. Modelo/ano/km: ",
        "botao_alt": (f"Ver a oficina {marca}", f"/{pai_slug}/"), "breadcrumb": f"Revisão {marca}",
        "servico": f"Revisão {marca}", "servico_tipo": f"Revisão periódica de veículos {marca}",
        "secoes": [
            como,
            {"h2": f"O que entra na revisão {marca}", "check": itens},
            {"h2": "Revisão por modelo", "cards": modelos},
            {"h2": "Itens que valem atenção", "cards": atencao},
            {"h2": f"Quanto custa a revisão {marca}", "p": [
                "O valor depende do modelo, do motor e da quilometragem, e de quais itens vencem naquela revisão. Uma "
                "revisão de óleo e filtros custa bem menos que uma que inclui velas, fluido de freio e óleo do câmbio.",
                "Por isso passamos o orçamento detalhado depois de avaliar o carro, e você aprova item por item.",
            ]},
            {"h2": "Sinais de que a revisão está atrasada", "check": sinais},
            ATENDE,
        ],
        "faq_h2": f"Perguntas frequentes sobre revisão {marca}",
        "faq": [(f"Fazer a revisão do {marca} fora da concessionária perde a garantia?",
                 "Se o carro está na garantia de fábrica, consulte as condições do manual de garantia. Fora dela, a revisão "
                 "segue o plano da montadora, com peças genuínas e registro do serviço."),
                (f"Quanto custa a revisão de um {marca}?", "Depende do modelo, do motor e da quilometragem. Mande os dados pelo WhatsApp que passamos o orçamento."),
                ] + faq_extra + [
                ("Vocês zeram o aviso de revisão no painel?", "Sim. Ao final da revisão o aviso é atualizado."),
                ("Usam peças originais?", f"Sim. Somente peças genuínas {marca}, com procedência informada no orçamento.")],
        "cta_h2": f"Agende a revisão do seu {marca}", "cta_p": "Mande o modelo, o ano e a quilometragem. Um especialista responde pelo WhatsApp.",
    }


SUBPAGINAS += [
    revisao("oficina-volvo", "Oficina Volvo", "Volvo", "oficina-volvo-revisao",
            "Revisão Volvo em São Paulo: XC40, XC60 e XC90 | Veloce",
            "Revisão Volvo em São Paulo para XC40, XC60, XC90, S60 e V60: óleo com especificação Volvo, filtros, fluidos e reset do aviso. Peças genuínas. Agende.",
            FOTO_OFICINA,
            "Revisão do seu Volvo no padrão da montadora, com peças genuínas, leitura de todas as centrais, reset do aviso "
            "de revisão e relatório técnico de tudo o que foi feito.",
            {"h2": "Como funciona a revisão do Volvo", "p": [
                "A Volvo define a revisão por tempo ou por quilometragem, o que vencer primeiro, e o painel avisa quando ela "
                "se aproxima. Cada revisão tem itens próprios: em umas é óleo e filtros, em outras entram velas, fluido de "
                "freio, filtro de combustível ou óleo do câmbio.",
                "Na Veloce seguimos o plano do seu modelo e motor, fazemos o que está vencido ou perto de vencer e, no fim, "
                "atualizamos o aviso no painel.",
            ]},
            ["Óleo com a especificação Volvo correta para o seu motor", "Filtros de óleo, ar e cabine conforme o plano",
             "Velas de ignição no intervalo indicado", "Fluido de freio, que vence por tempo",
             "Inspeção de freios, suspensão, direção e pneus", "Leitura de todas as centrais e falhas registradas",
             "Reset do aviso de revisão no painel", "Teste de rodagem e relatório técnico"],
            [("Volvo XC60", "O SUV mais comum da marca. Revisão inclui checagem da suspensão e do câmbio automático."),
             ("Volvo XC40", "Motores turbo a gasolina. Atenção a arrefecimento e ao estado dos freios no uso urbano."),
             ("Volvo XC90", "Mais peso e mais eletrônica: inspeção completa de suspensão, freios e sistemas de assistência."),
             ("S60 e V60", "Sedã e perua com os mesmos motores turbo: revisão pelo plano da montadora.")],
            [("Óleo do câmbio", "O câmbio automático também tem óleo com prazo: trocar no tempo certo evita trancos."),
             ("Freios", "No trânsito de São Paulo, pastilhas e discos se desgastam antes do previsto."),
             ("Bateria", "Os Volvo têm muita eletrônica: bateria fraca gera avisos em vários sistemas e é testada em toda revisão."),
             ("Versões híbridas", "As versões Recharge têm itens próprios: consulte o plano do seu modelo pelo WhatsApp.")],
            ["Aviso de revisão no painel", "Marcha lenta irregular ou motor menos suave", "Pedal de freio mais longo", "Trancos nas trocas de marcha"],
            [("Vocês atendem Volvo XC60 e XC90?", "Sim. Atendemos XC40, XC60, XC90, S60, V60 e os demais modelos Volvo.")]),

    revisao("oficina-audi", "Oficina Audi", "Audi", "oficina-audi-revisao",
            "Revisão Audi em São Paulo: A3, Q3 e Q5 | Veloce",
            "Revisão Audi em São Paulo para A3, A4, Q3, Q5 e mais: óleo VW/Audi correto, filtros, fluido do S tronic e reset do aviso. Peças genuínas. Agende.",
            FOTO_MOTOR,
            "Revisão do seu Audi no padrão da montadora, com peças genuínas, atenção ao câmbio S tronic, reset do aviso de "
            "revisão e relatório técnico de tudo o que foi feito.",
            {"h2": "Como funciona a revisão do Audi", "p": [
                "O Audi avisa no painel quando a troca de óleo e a inspeção estão chegando, por tempo ou por quilometragem. "
                "Além delas, alguns itens têm prazo próprio, como o fluido de freio, as velas e o óleo do câmbio S tronic.",
                "Na Veloce seguimos o plano do seu modelo e motor, fazemos o que está vencido ou perto de vencer e, no fim, "
                "atualizamos o aviso no painel.",
            ]},
            ["Óleo com a especificação VW/Audi correta para o seu motor", "Filtros de óleo, ar e cabine conforme o plano",
             "Velas de ignição no intervalo indicado", "Fluido de freio, que vence por tempo",
             "Óleo do câmbio S tronic ou automático quando vencido", "Leitura de todas as centrais e falhas registradas",
             "Reset do aviso de revisão no painel", "Teste de rodagem e relatório técnico"],
            [("Audi A3", "Sedã e Sportback com motores turbo e câmbio S tronic: atenção ao óleo do câmbio."),
             ("Audi Q3", "Revisão inclui checagem da suspensão e do câmbio, que trabalha mais no uso urbano."),
             ("Audi A4 e A5", "Motores 2.0 turbo: atenção a consumo de óleo e ao sistema de arrefecimento."),
             ("Audi Q5 e Q7", "SUVs com tração quattro: revisão inclui a inspeção da tração e da suspensão.")],
            [("Câmbio S tronic", "O óleo e o filtro do câmbio de dupla embreagem têm intervalo próprio e não podem atrasar."),
             ("Consumo de óleo", "Alguns motores turbo consomem óleo entre as revisões: checamos o nível e a causa."),
             ("Arrefecimento", "Bomba d'água e termostato são pontos de atenção em vários motores Audi."),
             ("Tração quattro", "Nos modelos quattro, o óleo da tração também vence.")],
            ["Aviso de troca de óleo ou inspeção no painel", "Trancos ou hesitação no câmbio S tronic", "Luz de nível de óleo acendendo", "Pedal de freio mais longo"],
            [("Vocês fazem a troca de óleo do câmbio S tronic?", "Sim. O óleo e o filtro do S tronic entram na revisão quando estão vencidos.")]),

    revisao("oficina-mercedes-benz", "Oficina Mercedes-Benz", "Mercedes-Benz", "oficina-mercedes-benz-revisao",
            "Revisão Mercedes-Benz em SP: C180, GLA e GLC | Veloce",
            "Revisão Mercedes-Benz em São Paulo: serviço A e B do ASSYST, óleo MB aprovado, filtros, fluido de freio e reset no painel. Peças genuínas. Agende.",
            FOTO_OFICINA,
            "Revisão do seu Mercedes-Benz no padrão da montadora, seguindo o serviço A ou B que o painel pede, com peças "
            "genuínas, reset do aviso e relatório técnico de tudo o que foi feito.",
            {"h2": "Como funciona a revisão Mercedes (serviço A e B)", "p": [
                "Os Mercedes-Benz usam o ASSYST, que avisa no painel quando a revisão vence e se é um serviço A ou B. O "
                "serviço A é o mais simples, com óleo, filtro e inspeção; o serviço B é mais completo e inclui filtros de "
                "ar e de cabine, fluido de freio e mais itens de inspeção.",
                "Na Veloce lemos o que o painel pede, fazemos o serviço indicado e, no fim, atualizamos o ASSYST.",
            ]},
            ["Óleo com aprovação Mercedes-Benz (MB) para o seu motor", "Filtro de óleo e, no serviço B, filtros de ar e de cabine",
             "Fluido de freio, que vence por tempo", "Velas de ignição no intervalo indicado",
             "Inspeção de freios, suspensão, direção e pneus", "Leitura de todas as centrais e falhas registradas",
             "Reset do ASSYST no painel", "Teste de rodagem e relatório técnico"],
            [("Classe C (C180, C200, C300)", "O Mercedes mais comum no Brasil. Atenção ao câmbio automático e ao arrefecimento."),
             ("GLA e Classe A", "Motores turbo e câmbio de dupla embreagem: atenção ao óleo do câmbio."),
             ("GLC", "SUV com tração 4MATIC em muitas versões: revisão inclui a inspeção da tração."),
             ("Classe E e GLE", "Mais conforto e eletrônica: inspeção completa de suspensão e sistemas de assistência.")],
            [("Óleo do câmbio", "Câmbios automáticos e de dupla embreagem também têm prazo de troca do óleo."),
             ("Coxins e buchas", "Ruídos em lombadas e vibrações costumam vir de coxins e buchas cansados."),
             ("Bateria", "Bateria fraca gera avisos em vários sistemas: é testada em toda revisão."),
             ("Uso severo", "No trânsito de São Paulo, vale conversar sobre encurtar o intervalo do óleo.")],
            ["Aviso de serviço A ou B no painel", "Trancos nas trocas de marcha", "Ruídos na suspensão em lombadas", "Pedal de freio mais longo"],
            [("Qual a diferença entre o serviço A e o B?", "O serviço A é a revisão mais simples, com óleo, filtro e inspeção. O B é mais completo e inclui filtros, fluido de freio e mais itens de inspeção.")]),
]

ARTIGOS.append(art(
    "o-que-e-dinamometro", "O que é Dinamômetro Automotivo e Para que Serve | Veloce",
    "O que é dinamômetro automotivo, o que ele mede, para que serve e como é feita a medição de potência e torque do carro. Guia da Veloce, com dinamômetro próprio.",
    "O que é dinamômetro automotivo e para que serve",
    "O dinamômetro automotivo mede a potência e o torque reais de um carro. Neste guia você entende o que ele mede, "
    "como é feita a medição e quando vale a pena levar o seu carro para medir.",
    "Blog · Dinamômetro", 5, FOTO_911,
    [
        {"h2": "Dinamômetro em uma frase", "p": [
            "Dinamômetro é o equipamento que mede a força. No carro, ele mede quanta potência e quanto torque o motor "
            "entrega, em toda a faixa de rotação, e mostra isso em um gráfico: a curva de potência.",
            "Existem dinamômetros para outras coisas, como o de mão usado na fisioterapia. Neste artigo falamos do "
            "dinamômetro automotivo, usado em oficinas e em preparação de carros.",
        ]},
        {"h2": "O que o dinamômetro mede", "cards": [
            ("Potência", "Em cv: a força máxima que o carro entrega, normalmente perto do fim das rotações."),
            ("Torque", "Em kgfm ou Nm: a força nas retomadas e nas saídas, que é o que você sente no dia a dia."),
            ("Curva", "Como potência e torque se comportam do início ao fim das rotações, e não só o número máximo."),
            ("Comparação", "O antes e o depois de um remap ou de uma peça, medidos no mesmo equipamento."),
        ]},
        {"h2": "Para que serve medir o carro", "check": [
            "Conferir o ganho real de um remap, em vez de confiar em um número de catálogo",
            "Comparar o carro antes e depois de trocar escapamento, admissão ou turbo",
            "Investigar perda de potência que o scanner não explica",
            "Saber quanto o seu carro rende hoje, mesmo sem fazer nenhuma modificação",
        ]},
        {"h2": "Como é feita a medição, passo a passo", "steps": [
            ("Checagem do carro.", "Pneus, níveis e temperatura do motor são conferidos antes de começar."),
            ("Fixação.", "O carro é preso com cintas para ficar seguro durante a aceleração."),
            ("Puxadas.", "O carro acelera em uma marcha fixa, do início ao fim das rotações, algumas vezes."),
            ("Correção.", "O resultado é corrigido para temperatura e pressão do ar, para poder comparar medições."),
            ("Relatório.", "Você recebe a curva de potência e torque e a explicação do que ela mostra."),
        ]},
        {"h2": "Potência na roda e potência no motor", "p": [
            "O dinamômetro de chassi mede a força que chega às rodas. Entre o motor e as rodas existem câmbio, "
            "diferencial e pneus, que consomem parte da potência. Por isso o número na roda é menor que o do catálogo, "
            "e isso é normal.",
            "O que importa é comparar medições feitas no mesmo equipamento e nas mesmas condições: é assim que se vê se "
            "o carro ganhou ou perdeu potência.",
        ]},
        {"h2": "Por que o resultado pode variar", "table": [
            ["Fator", "O que acontece"],
            ["Temperatura do ar", "Ar quente tem menos oxigênio, e o motor rende menos"],
            ["Combustível", "Gasolina de octanagem diferente muda o resultado nos motores turbo"],
            ["Temperatura do motor", "Motor frio ou superaquecido não mostra o rendimento real"],
            ["Pneus e fixação", "Pressão dos pneus e cintas mal presas alteram a leitura"],
        ], "p_depois": ["Por isso a medição é corrigida e repetida algumas vezes, para chegar a um resultado confiável."]},
        {"h2": "O dinamômetro da Veloce", "p": [
            "A Veloce tem dinamômetro próprio na oficina da Av. Casa Verde, na Zona Norte de São Paulo. Usamos para medir "
            "o antes e o depois de cada remap e também fazemos a medição avulsa, para quem quer conhecer o rendimento do carro.",
            'Veja como funciona o <a href="/dinamometro/">serviço de dinamômetro</a> e o <a href="/remap/">remap com ganho medido</a>. '
            'O Porsche 911 da foto é um dos carros que passam pela nossa <a href="/oficina-porsche/">oficina Porsche em São Paulo</a>.',
        ], "fotos": [("https://veloceautospecialista.com.br/wp-content/uploads/2026/10/porsche-911-dinamometro-veloce.webp", "Porsche 911 Carrera Cabriolet no dinamômetro da Veloce", 1200, 1600),
                   ("https://veloceautospecialista.com.br/wp-content/uploads/2026/10/golf-gti-dinamometro-veloce.webp", "Volkswagen Golf GTI no dinamômetro da Veloce", 1200, 1600)]},
        leia(L_OQUE, L_STAGE, L_ESTRAGA),
    ],
    [("O dinamômetro mede potência ou torque?", "Os dois. Ele mede a força nas rodas em cada rotação e calcula a potência e o torque em toda a faixa."),
     ("Por que o carro deu menos potência que o catálogo?", "Porque o dinamômetro de chassi mede na roda, depois das perdas do câmbio e dos pneus. O importante é comparar medições no mesmo equipamento."),
     ("Medir no dinamômetro faz mal ao carro?", "Não, se o carro estiver em bom estado. São poucas acelerações, com o carro preso e monitorado."),
     ("Posso medir o carro sem fazer remap?", 'Sim. A Veloce faz a medição avulsa. Veja o <a href="/dinamometro/">serviço de dinamômetro</a>.'),
     ("Onde tem dinamômetro em São Paulo?", "A Veloce tem dinamômetro próprio na Av. Casa Verde, 3010, Zona Norte de São Paulo.")],
    "Quer saber quanto o seu carro rende?", "/dinamometro/", "Ver o serviço de dinamômetro",
    "Olá, li o artigo sobre dinamômetro e quero medir o meu carro. Modelo/ano: "))
ARTIGOS[-1]["publicado"] = "2026-11-05"

L_DINAMO = ("O que é dinamômetro?", "/blog/o-que-e-dinamometro/", "O que ele mede e como é feita a medição do carro.")
BLOG_HUB["secoes"].insert(1, {"h2": "Dinamômetro", "cards": [(L_DINAMO[0], f'{L_DINAMO[2]} <a href="{L_DINAMO[1]}">Ler o artigo</a>.')]})
BLOG_HUB["secoes"][-1]["chips"] += ['<a href="/oficina-volvo/revisao/">Revisão Volvo</a>', '<a href="/oficina-audi/revisao/">Revisão Audi</a>',
                                    '<a href="/oficina-mercedes-benz/revisao/">Revisão Mercedes-Benz</a>']

# ------------------------------------------------------------------ CÂMBIO AUTOMÁTICO (preparados em 16/11/2026)
# Confirmado com o dono: FAZEM troca de óleo do câmbio automático. NÃO fazem conserto de câmbio nem adaptação pelo
# scanner. Não escrever que usam máquina de troca (não confirmado). Sem preço.
L_TROCA = ("Troca de óleo do câmbio automático", "/troca-de-oleo-cambio-automatico/", "Como a Veloce faz a troca, com o óleo da especificação da montadora.")
L_QUANDO = ("Quando trocar o óleo do câmbio automático?", "/blog/quando-trocar-oleo-cambio-automatico/", "Intervalos, uso severo e os sinais de que passou da hora.")
L_OLEO = ("Qual o óleo certo para câmbio automático?", "/blog/oleo-de-cambio-automatico/", "Por que cada câmbio tem o seu óleo e o risco de usar o errado.")
L_TRANCO = ("Câmbio automático dando tranco: o que pode ser?", "/blog/cambio-automatico-dando-tranco/", "As causas mais comuns e o que fazer primeiro.")

ARTIGOS.append(art(
    "quando-trocar-oleo-cambio-automatico", "Quando Trocar o Óleo do Câmbio Automático? Guia Completo",
    "Câmbio automático precisa trocar óleo? Veja quando trocar, o que muda no uso severo, os sinais de óleo vencido e por que o “vitalício” engana.",
    "Quando trocar o óleo do câmbio automático?",
    "O óleo do câmbio automático também vence, mesmo quando o manual fala em óleo para a vida toda. Neste guia você "
    "entende quando trocar, o que encurta esse prazo e quais sinais mostram que passou da hora.",
    "Blog · Câmbio automático", 5, FOTO_OFICINA,
    [
        {"h2": "Câmbio automático precisa trocar óleo?", "p": [
            "Precisa. O óleo do câmbio automático faz três trabalhos ao mesmo tempo: lubrifica as engrenagens, resfria o "
            "câmbio e transmite pressão para as trocas de marcha. Com o calor e o tempo, ele perde essas propriedades.",
            "Muitos carros saem de fábrica com a informação de que o óleo do câmbio dura a vida toda. Na prática, essa "
            "\"vida toda\" costuma ser o período de garantia do carro. Depois disso, o óleo velho é uma das causas mais "
            "comuns de trancos e desgaste.",
        ]},
        {"h2": "De quanto em quanto tempo trocar", "p": [
            "Não existe um número único: cada câmbio tem o seu plano. Como referência, muitos fabricantes de câmbio "
            "indicam a troca entre 60 mil e 100 mil km nos automáticos convencionais. Alguns câmbios de dupla embreagem "
            "banhados a óleo têm troca prevista no plano da montadora a cada 60 mil km.",
            "O primeiro passo é sempre o manual e o plano de manutenção do seu modelo. O segundo é olhar para o uso do carro.",
        ], "table": [
            ["Tipo de câmbio", "Referência comum", "Exemplos"],
            ["Automático convencional", "60 mil a 100 mil km, ou antes no uso severo", "BMW, Mercedes-Benz 7G e 9G, Land Rover, Volvo"],
            ["Dupla embreagem banhada a óleo", "Em muitos casos, a cada 60 mil km", "Audi S tronic, Porsche PDK"],
        ], "p_depois": ["Os valores são uma referência geral. O que vale é o plano do seu câmbio, conferido na avaliação."]},
        {"h2": "O que encurta a vida do óleo", "cards": [
            ("Trânsito pesado", "O anda e para de São Paulo esquenta o câmbio muito mais que a estrada."),
            ("Calor", "Dias quentes e congestionamento somados são o pior cenário para o óleo."),
            ("Carro com remap", "Mais torque passa pelo câmbio, que trabalha mais quente."),
            ("Subidas e carga", "Serra, carro cheio e reboque forçam o câmbio e o óleo."),
        ]},
        {"h2": "Sinais de que o óleo passou da hora", "check": [
            "Trocas mais duras ou com tranco, principalmente em baixa velocidade",
            "Demora para engatar ao colocar em D ou R",
            "Rotação que sobe antes de o carro responder",
            "Câmbio que piora quando está quente",
            "Carro usado sem nenhum registro de troca do óleo do câmbio",
        ], "p_depois": [
            'Esses sinais nem sempre são só do óleo. Veja as causas mais comuns em <a href="/blog/cambio-automatico-dando-tranco/">câmbio automático dando tranco</a>.']},
        {"h2": "Troca parcial e troca completa", "p": [
            "Em muitos câmbios automáticos, quando o óleo é drenado, só uma parte dele sai: o resto fica no conversor de "
            "torque e nas galerias do câmbio. Por isso alguns câmbios pedem mais de uma troca seguida, ou outro método, "
            "para renovar a maior parte do óleo.",
            "O método certo depende do câmbio. Na Veloce, a troca segue o que a montadora indica para o seu câmbio, com o "
            "nível conferido na temperatura que o fabricante manda.",
        ]},
        {"h2": "Como é a troca na Veloce", "p": [
            "Antes da troca, lemos a central do câmbio com o scanner para ver se há falhas registradas. Depois trocamos "
            "o óleo da especificação da montadora, com peças genuínas, e conferimos as trocas de marcha num teste de rodagem.",
            'Veja como funciona a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio automático na Veloce</a>.',
        ]},
        leia(L_OLEO, L_TRANCO, ("Revisão de importados", "/manutencao-preventiva/", "O plano completo de manutenção do seu carro.")),
    ],
    [("Óleo de câmbio automático vence?", "Vence. Ele perde as propriedades com o calor e o tempo, mesmo quando o manual fala em óleo para a vida toda."),
     ("Posso trocar só o óleo do motor e deixar o do câmbio?", "São óleos e prazos diferentes. O do motor vence bem antes, mas o do câmbio também tem prazo e não deve ser esquecido."),
     ("Trocar o óleo de um câmbio que nunca foi trocado faz mal?", "Depende do estado do câmbio. Por isso a avaliação vem antes: lemos a central e vemos o histórico antes de recomendar a troca."),
     ("Quanto custa trocar o óleo do câmbio automático?", "Depende do câmbio, da quantidade de óleo e de o câmbio ter filtro e juntas. Mande o modelo, o ano e a quilometragem pelo WhatsApp que respondemos com o orçamento."),
     ("A Veloce faz a troca?", 'Sim. Veja a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio automático</a>.')],
    "Quer saber se está na hora de trocar?", "/troca-de-oleo-cambio-automatico/", "Ver a troca de óleo do câmbio",
    "Olá, li o artigo sobre troca de óleo do câmbio. Quero saber se está na hora no meu carro. Modelo/ano/km: "))

ARTIGOS.append(art(
    "oleo-de-cambio-automatico", "Óleo de Câmbio Automático: Qual Usar e Por que Importa",
    "Qual óleo usar no câmbio automático? Entenda por que cada câmbio pede um óleo próprio, o que acontece com o errado e como saber o certo para o seu carro.",
    "Óleo de câmbio automático: qual usar no seu carro",
    "Não existe um óleo de câmbio automático que sirva para todos. Neste guia você entende por que cada câmbio pede "
    "o seu óleo, o que acontece quando se usa o errado e como descobrir o certo para o seu carro.",
    "Blog · Câmbio automático", 5, FOTO_MOTOR,
    [
        {"h2": "Por que o óleo do câmbio é diferente do óleo do motor", "p": [
            "O óleo do motor lubrifica e protege peças que giram e deslizam. O óleo do câmbio automático faz isso e mais: "
            "ele também é o fluido hidráulico que comanda as trocas de marcha e controla o atrito das embreagens internas.",
            "Por isso a fórmula de cada óleo é pensada para um câmbio específico. Um detalhe no atrito do óleo muda a forma "
            "como a marcha entra.",
        ]},
        {"h2": "Os tipos de óleo de câmbio", "cards": [
            ("ATF", "Óleo dos automáticos convencionais, com conversor de torque. Cada fabricante de câmbio tem a sua norma."),
            ("Óleo de dupla embreagem", "Para câmbios como o S tronic e o PDK. Tem atrito próprio para as embreagens banhadas a óleo."),
            ("Óleo de CVT", "Para o câmbio de polias e correia metálica. Não serve em automático convencional, nem o contrário."),
            ("Óleo de diferencial e tração", "Alguns carros têm óleo separado na tração ou no diferencial, com prazo próprio."),
        ]},
        {"h2": "O que acontece com o óleo errado", "check": [
            "Trancos e trocas de marcha duras ou demoradas",
            "Patinação das embreagens internas, que aquecem e se desgastam",
            "Superaquecimento do câmbio no trânsito",
            "Desgaste acelerado de peças caras, como o corpo de válvulas e a mecatrônica",
        ], "p_depois": [
            "Óleo \"universal\" ou \"compatível com tudo\" é um risco em câmbio de carro premium: ele pode até funcionar no "
            "começo, mas não tem o atrito exato que o câmbio espera."]},
        {"h2": "Como saber qual é o óleo certo", "steps": [
            ("Identifique o câmbio.", "O mesmo modelo de carro pode ter câmbios diferentes conforme o ano e o motor."),
            ("Consulte a norma do fabricante.", "Cada câmbio tem uma especificação de óleo, definida pela montadora ou pelo fabricante do câmbio."),
            ("Confira a quantidade.", "Ela muda muito de um câmbio para outro, e o nível certo depende da temperatura do óleo."),
            ("Use óleo genuíno ou aprovado.", "Na Veloce usamos o óleo da especificação da montadora, com peças genuínas."),
        ]},
        {"h2": "Tabela de óleo de câmbio: cuidado", "p": [
            "Tabelas na internet que indicam um óleo por modelo ajudam a ter uma ideia, mas erram com frequência: o mesmo "
            "carro pode ter câmbios diferentes conforme o ano e o motor. Em carro premium, o certo é confirmar pelo câmbio "
            "instalado no seu carro.",
        ]},
        {"h2": "Troca de óleo do câmbio na Veloce", "p": [
            "Identificamos o câmbio do seu carro, lemos a central com o scanner, trocamos o óleo da especificação da "
            "montadora e conferimos o nível do jeito que o fabricante manda.",
            'Veja como funciona a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio automático na Veloce</a>.',
        ]},
        leia(L_QUANDO, L_TRANCO, L_TROCA),
    ],
    [("Posso usar qualquer óleo ATF no meu câmbio?", "Não. Cada câmbio tem a sua especificação. Um ATF de outra norma pode causar trancos e desgaste."),
     ("Óleo de câmbio de dupla embreagem é igual ao do automático?", "Não. O câmbio de dupla embreagem usa um óleo com atrito próprio para as embreagens. Os dois não são intercambiáveis."),
     ("Óleo genuíno é melhor?", "O óleo genuíno ou aprovado pela montadora garante a especificação que o câmbio espera. É o que a Veloce usa."),
     ("Quanto óleo leva um câmbio automático?", "Varia muito de um câmbio para outro. Por isso o orçamento é feito para o seu modelo."),
     ("A Veloce troca o óleo do câmbio?", 'Sim, com o óleo da especificação da montadora. Veja a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio automático</a>.')],
    "Quer saber qual óleo o seu câmbio usa?", "/troca-de-oleo-cambio-automatico/", "Ver a troca de óleo do câmbio",
    "Olá, li o artigo sobre óleo de câmbio. Quero saber qual o óleo certo para o meu carro. Modelo/ano/motor: "))

ARTIGOS.append(art(
    "cambio-automatico-dando-tranco", "Câmbio Automático Dando Tranco: O que Pode Ser",
    "Câmbio automático dando tranco ao engatar ou trocar de marcha? Veja as causas mais comuns, do óleo aos coxins, e o que fazer antes de o problema crescer.",
    "Câmbio automático dando tranco: o que pode ser?",
    "Tranco no câmbio automático é um aviso. Às vezes a causa é simples, como o óleo vencido; às vezes é outra peça "
    "que só parece ser o câmbio. Neste guia você vê as causas mais comuns e o que fazer primeiro.",
    "Blog · Câmbio automático", 5, FOTO_EVOQUE,
    [
        {"h2": "Quando o tranco aparece", "cards": [
            ("Ao engatar D ou R", "Pancada ao tirar do P ou do N, com o carro parado."),
            ("Em baixa velocidade", "Solavanco nas trocas entre a 1ª, a 2ª e a 3ª, no anda e para."),
            ("Com o câmbio frio", "Trocas duras nos primeiros minutos, que melhoram depois."),
            ("Com o câmbio quente", "Trocas que pioram no trânsito ou depois de rodar bastante."),
        ], "p_depois": ["Anotar quando o tranco acontece ajuda muito no diagnóstico."]},
        {"h2": "As causas mais comuns", "table": [
            ["Causa", "O que acontece", "Sinal típico"],
            ["Óleo vencido ou nível errado", "O óleo perde o atrito certo e a pressão das trocas", "Trocas duras que pioram quando o câmbio esquenta"],
            ["Coxins do motor e do câmbio gastos", "O conjunto se mexe e a troca vira pancada", "Tranco forte ao engatar D ou R"],
            ["Falha elétrica ou de sensor", "A central do câmbio recebe informação errada", "Luz no painel ou falha gravada na central"],
            ["Bateria fraca", "Tensão baixa confunde as centrais eletrônicas", "Vários avisos no painel ao mesmo tempo"],
            ["Desgaste interno", "Embreagens e válvulas do câmbio gastas", "Patinação: a rotação sobe e o carro não responde"],
        ]},
        {"h2": "O que fazer primeiro", "steps": [
            ("Não ignore.", "Tranco que aparece e some costuma voltar maior. Quanto antes avaliar, mais barato tende a ser."),
            ("Leia a central do câmbio.", "Um scanner profissional mostra falhas gravadas e ajuda a separar defeito elétrico de mecânico."),
            ("Confira o óleo.", "Nível, estado e histórico de troca. Óleo vencido é uma das causas mais comuns."),
            ("Olhe os coxins.", "Coxim gasto dá tranco que parece ser do câmbio e é bem mais simples de resolver."),
        ]},
        {"h2": "A troca de óleo resolve o tranco?", "p": [
            "Em muitos casos melhora, principalmente quando o óleo está vencido ou nunca foi trocado. Mas não resolve "
            "peça gasta nem defeito elétrico. Por isso a leitura da central e a avaliação vêm antes: você sabe o que "
            "esperar antes de aprovar qualquer serviço.",
            "Se a avaliação mostrar desgaste interno do câmbio, a gente explica o que foi encontrado e qual é o próximo passo.",
        ]},
        {"h2": "Remap de câmbio resolve tranco?", "p": [
            'Não é para isso. O <a href="/remap/cambio/">remap de câmbio</a> muda o comportamento de um câmbio em bom '
            "estado, para trocas mais rápidas e para acompanhar um motor com mais torque. Tranco por defeito ou óleo "
            "vencido precisa de diagnóstico, não de reprogramação.",
        ]},
        {"h2": "Diagnóstico na Veloce", "p": [
            "Na Veloce lemos a central do câmbio com scanner profissional, avaliamos o óleo e os coxins, fazemos um teste "
            "de rodagem e explicamos o que foi encontrado antes de qualquer serviço.",
            'Quando a causa é o óleo, fazemos a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio automático</a> '
            "com o óleo da especificação da montadora.",
        ]},
        leia(L_QUANDO, L_OLEO, L_TROCA),
    ],
    [("Câmbio automático dando tranco é grave?", "Nem sempre, mas é um aviso. Pode ser óleo vencido ou um coxim gasto, que são simples, ou o começo de um desgaste. Avaliar cedo evita que piore."),
     ("Tranco ao engatar a ré é do câmbio?", "Pode ser, mas coxins gastos são uma causa muito comum desse sintoma. A avaliação separa uma coisa da outra."),
     ("Trocar o óleo resolve o tranco?", "Melhora em muitos casos, principalmente com óleo vencido. Não resolve peça gasta nem defeito elétrico, por isso a avaliação vem antes."),
     ("Posso continuar rodando com o câmbio dando tranco?", "Por pouco tempo e com cuidado. Se houver patinação ou luz no painel, o ideal é avaliar logo."),
     ("A Veloce faz o diagnóstico?", 'Sim. Lemos a central do câmbio, avaliamos óleo e coxins e explicamos o que foi encontrado. Veja a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio automático</a>.')],
    "O seu câmbio está dando tranco?", "/troca-de-oleo-cambio-automatico/", "Ver a troca de óleo do câmbio",
    "Olá, meu câmbio automático está dando tranco. Modelo/ano/km e quando acontece: "))
for _a in ARTIGOS[-3:]:
    _a["publicado"] = "2026-11-16"

BLOG_HUB["secoes"].insert(2, {"h2": "Câmbio automático", "cards": [(t, f'{d} <a href="{h}">Ler o artigo</a>.') for t, h, d in (L_QUANDO, L_OLEO, L_TRANCO)]})
BLOG_HUB["secoes"][-1]["chips"].append('<a href="/troca-de-oleo-cambio-automatico/">Troca de óleo do câmbio</a>')

# ------------------------------------------------------------------ S TRONIC (preparado em 18/11/2026)
# Foco pedido pelo dono: Porsche e Audi. "câmbio s tronic" + variações ~740 buscas/mês (Ubersuggest).
# Fazem troca de óleo e remap de câmbio. NÃO fazem conserto de câmbio (embreagem, mecatrônica) nem adaptação pelo scanner.
L_STRONIC = ("Câmbio S tronic: como funciona e é bom?", "/blog/cambio-s-tronic/", "O câmbio de dupla embreagem da Audi e os cuidados que ele pede.")
ARTIGOS.append(art(
    "cambio-s-tronic", "Câmbio S tronic da Audi: Como Funciona, é Bom e Manutenção",
    "Câmbio S tronic é bom? Entenda como funciona o câmbio de dupla embreagem da Audi, os cuidados no trânsito, os sinais de problema e a manutenção.",
    "Câmbio S tronic: como funciona, é bom e qual a manutenção",
    "O S tronic é o câmbio automático de dupla embreagem da Audi. Ele troca de marcha muito rápido e economiza "
    "combustível, mas pede alguns cuidados. Neste guia você entende como ele funciona, se ele é bom e como fazer ele durar.",
    "Blog · Audi", 6, FOTO_MOTOR,
    [
        {"h2": "O que é o câmbio S tronic", "p": [
            "S tronic é o nome que a Audi dá ao seu câmbio automatizado de dupla embreagem. É da mesma família do DSG "
            "da Volkswagen: por dentro ele parece um câmbio manual, mas quem troca as marchas é uma central eletrônica, "
            "e não o motorista.",
            "Ele aparece em boa parte da linha Audi vendida no Brasil, como A3, A4, A5, Q3 e Q5, e também nos "
            "esportivos S e RS. Os modelos maiores, como Q7 e A6 em algumas versões, usam o tiptronic, que é outro tipo de câmbio.",
            'O Porsche Macan a combustão usa um câmbio PDK da mesma família. Veja a <a href="/oficina-porsche/">oficina Porsche da Veloce</a>.',
        ]},
        {"h2": "Como ele funciona", "p": [
            "O S tronic tem duas embreagens. Uma cuida das marchas ímpares (1ª, 3ª, 5ª, 7ª) e a outra das pares "
            "(2ª, 4ª, 6ª e ré). Enquanto você roda numa marcha, a próxima já fica engatada na outra embreagem, esperando.",
            "Na hora da troca, uma embreagem solta e a outra fecha ao mesmo tempo. Por isso a troca é quase "
            "instantânea e sem perda de força, bem mais rápida que num automático comum.",
        ]},
        {"h2": "S tronic x tiptronic", "table": [
            ["", "S tronic", "Tiptronic"],
            ["Tipo", "Dupla embreagem", "Automático com conversor de torque"],
            ["Trocas", "Muito rápidas", "Suaves"],
            ["No anda e para", "Pede mais cuidado", "Lida melhor"],
            ["Onde aparece", "A3, A4, A5, Q3, Q5, S e RS", "Modelos maiores, como Q7 e algumas versões do A6"],
        ], "p_depois": ["O câmbio exato depende do ano, do motor e da versão. Na avaliação conferimos qual é o do seu carro."]},
        {"h2": "Câmbio S tronic é bom?", "p": [
            "É um bom câmbio: rápido, eficiente e muito agradável de dirigir na estrada e em uso esportivo. O ponto de "
            "atenção é o trânsito pesado. No anda e para, as embreagens trabalham muito, esquentam e se desgastam mais "
            "rápido.",
            "Com o uso certo e a manutenção no prazo, ele dura bastante. O problema costuma aparecer quando o óleo passa "
            "da hora e o carro vive no congestionamento.",
        ]},
        {"h2": "Dois tipos de S tronic", "cards": [
            ("Embreagem seca", "Usado nos motores menores, como alguns 1.4 TFSI. As embreagens trabalham a seco e o plano de manutenção do óleo é diferente."),
            ("Embreagem banhada a óleo", "Usado nos motores mais fortes. O óleo resfria as embreagens, e a troca tem prazo próprio no plano da montadora."),
        ], "p_depois": ["Saber qual dos dois o seu Audi tem muda os cuidados e a manutenção."]},
        {"h2": "Cuidados no dia a dia", "check": [
            "Na subida, segure o carro no freio, e não no acelerador: segurar no acelerador esquenta as embreagens",
            "No anda e para, prefira andar e parar de vez, sem ficar rodando bem devagar com o pé no acelerador",
            "Para manobrar, use o freio para controlar a velocidade",
            "Não deixe a troca de óleo do câmbio atrasar",
            "Se aparecer tranco ou aviso no painel, avalie logo, antes de o problema crescer",
        ]},
        {"h2": "Sinais de que o S tronic pede atenção", "check": [
            "Trancos nas trocas da 1ª para a 2ª, em baixa velocidade",
            "Solavanco ao sair com o carro parado",
            "Demora para engatar a ré",
            "Aviso de câmbio no painel ou câmbio em modo de emergência",
            "Cheiro de queimado depois de muito anda e para",
        ], "p_depois": ['Veja também as causas mais comuns de <a href="/blog/cambio-automatico-dando-tranco/">câmbio automático dando tranco</a>.']},
        {"h2": "Manutenção do S tronic na Veloce", "p": [
            "Lemos a central do câmbio com scanner profissional para ver as falhas registradas, avaliamos o óleo e "
            "fazemos a troca com o óleo da especificação da Audi e peças genuínas, quando o plano pede.",
            "Para quem quer trocas ainda mais rápidas ou fez remap no motor, também fazemos o "
            '<a href="/remap/cambio/">remap de câmbio</a>, sempre num câmbio em bom estado. Se a avaliação mostrar '
            "desgaste interno, explicamos o que foi encontrado e qual é o próximo passo.",
            'Veja a <a href="/oficina-audi/">oficina Audi da Veloce</a> e a <a href="/troca-de-oleo-cambio-automatico/">troca de óleo do câmbio</a>.',
        ]},
        leia(L_TROCA, L_TRANCO, ("Revisão Audi", "/oficina-audi/revisao/", "O plano de revisão do seu Audi, modelo por modelo.")),
    ],
    [("O câmbio S tronic é confiável?", "É, com a manutenção no prazo e cuidado no trânsito pesado. A maior parte dos problemas vem de óleo vencido e de muito anda e para."),
     ("Qual a diferença entre S tronic e DSG?", "São da mesma família: DSG é o nome usado pela Volkswagen e S tronic é o nome usado pela Audi."),
     ("De quanto em quanto tempo trocar o óleo do S tronic?", "Depende do tipo de S tronic e do plano do seu modelo. Nos de embreagem banhada a óleo, a troca tem prazo próprio. Conferimos isso na avaliação."),
     ("O S tronic aguenta remap?", "Depende do câmbio e do quanto o motor vai ganhar. Em vários casos o remap de câmbio entra junto. Avaliamos antes."),
     ("A Veloce faz a manutenção do S tronic?", 'Fazemos a leitura da central, a troca de óleo e o remap de câmbio. Veja a <a href="/oficina-audi/">oficina Audi</a>.')],
    "Tem um Audi com câmbio S tronic?", "/oficina-audi/", "Ver a oficina Audi",
    "Olá, li o artigo sobre o câmbio S tronic e quero avaliar o meu Audi. Modelo/ano/km: "))
ARTIGOS[-1]["publicado"] = "2026-11-18"
for _s in BLOG_HUB["secoes"]:
    if _s.get("h2") == "Câmbio automático":
        _s["cards"].append((L_STRONIC[0], f'{L_STRONIC[2]} <a href="{L_STRONIC[1]}">Ler o artigo</a>.'))

# ------------------------------------------------------------------ AUDI Q3 (preparado em 18/11/2026)
# "audi q3 problemas crônicos" ~170/mês. Só defeitos conhecidos dos TFSI e do câmbio; não afirmar o câmbio de cada
# versão (varia por ano e motor). Sem conserto de câmbio.
L_Q3 = ("Audi Q3: problemas mais comuns", "/blog/audi-q3-problemas-comuns/", "Os defeitos mais conhecidos do Q3 e como evitar cada um.")
ARTIGOS.append(art(
    "audi-q3-problemas-comuns", "Audi Q3: Problemas Mais Comuns e Como Evitar",
    "Audi Q3 dá problema? Veja os problemas mais comuns do Q3, das duas gerações, os sinais de alerta e a manutenção que evita cada um deles.",
    "Audi Q3: problemas mais comuns e como evitar",
    "O Audi Q3 é um dos SUVs premium mais vendidos no Brasil. Como todo carro, ele tem pontos que pedem atenção. Neste "
    "guia você vê os problemas mais comuns do Q3, os sinais de alerta e o que fazer para evitar cada um.",
    "Blog · Audi", 6, FOTO_OFICINA,
    [
        {"h2": "O Audi Q3 dá muito problema?", "p": [
            "Não. O Q3 é um carro bem construído, e os chamados problemas crônicos são, na maioria, pontos conhecidos "
            "dos motores TFSI e do câmbio que aparecem quando a manutenção atrasa ou o carro roda muito no anda e para.",
            "Conhecer esses pontos ajuda a cuidar do seu Q3 e também a avaliar um Q3 usado antes da compra.",
        ]},
        {"h2": "As gerações do Q3 no Brasil", "table": [
            ["Geração", "Período", "O que saber"],
            ["1ª geração", "Vendida até 2019", "Motores 1.4 e 2.0 TFSI. Os pontos de atenção mais conhecidos estão nessa geração"],
            ["2ª geração", "Desde 2019, também na versão Sportback", "Mais eletrônica e assistências de condução. Pede a revisão sempre no prazo"],
        ], "p_depois": ["O câmbio muda conforme a versão: S tronic ou tiptronic. Na avaliação confirmamos qual é o do seu Q3."]},
        {"h2": "Os problemas mais comuns do Q3", "cards": [
            ("Consumo de óleo", "Algumas versões do motor 2.0 TFSI das primeiras gerações ficaram conhecidas por consumir óleo. Medir o consumo vem antes de qualquer conclusão."),
            ("Vazamento de água", "A carcaça plástica da bomba d'água e do termostato pode trincar com o calor. Cheiro adocicado e nível de água baixando são os sinais."),
            ("Câmbio com trancos", 'Nas versões com S tronic, solavancos em baixa velocidade e trocas hesitantes são comuns no trânsito pesado. Veja o <a href="/blog/cambio-s-tronic/">guia do S tronic</a>.'),
            ("Falhas de ignição", "Motor tremendo e luz de injeção piscando costumam ser bobinas ou velas. Nos motores com injeção só direta, a carbonização das válvulas também entra no diagnóstico."),
            ("Ruídos na suspensão", "Buracos e lombadas de São Paulo cansam buchas, bieletas e amortecedores. Ruído seco em lombada é o primeiro aviso."),
            ("Avisos elétricos", "Bateria fraca gera avisos em vários sistemas ao mesmo tempo. O diagnóstico separa defeito real de tensão baixa."),
        ]},
        {"h2": "Sinais de alerta no seu Q3", "check": [
            "Aviso de nível de óleo acendendo entre as revisões",
            "Nível do reservatório de água baixando ou cheiro adocicado depois de rodar",
            "Trancos ao sair com o carro parado ou nas trocas da 1ª para a 2ª",
            "Motor tremendo em marcha lenta ou luz de injeção acesa",
            "Ruído seco na suspensão ao passar em lombadas",
            "Vários avisos no painel ao mesmo tempo",
        ]},
        {"h2": "Como evitar os problemas", "steps": [
            ("Óleo certo, no prazo.", "Use o óleo com a especificação VW/Audi para o seu motor e não deixe a troca atrasar."),
            ("Câmbio também tem óleo.", 'O óleo do câmbio tem prazo próprio. Veja <a href="/blog/quando-trocar-oleo-cambio-automatico/">quando trocar</a>.'),
            ("Arrefecimento em dia.", "Confira o nível de água com o motor frio e trate qualquer vazamento cedo."),
            ("Leitura das centrais.", "Uma leitura com scanner na revisão mostra falhas antes de virarem defeito."),
            ("Cuidado no anda e para.", "Na subida, segure o carro no freio, e não no acelerador."),
        ]},
        {"h2": "Vai comprar um Q3 usado?", "p": [
            "Peça o histórico de revisões, confira se a troca do óleo do câmbio foi feita e faça uma avaliação antes de "
            "fechar negócio. A leitura das centrais mostra falhas que não aparecem no test drive.",
            'A Veloce faz a <a href="/inspecao-pre-compra/">inspeção pré-compra</a> de carros importados, com relatório técnico.',
        ]},
        {"h2": "O seu Q3 na Veloce", "p": [
            "Na Veloce o seu Q3 passa por leitura completa das centrais, revisão pelo plano da Audi com peças genuínas e "
            "relatório técnico de tudo o que foi feito.",
            'Veja a <a href="/oficina-audi/">oficina Audi da Veloce</a> e a <a href="/oficina-audi/revisao/">revisão Audi</a>.',
        ]},
        leia(L_STRONIC, L_TRANCO, ("Inspeção pré-compra", "/inspecao-pre-compra/", "Avaliação completa antes de comprar um importado usado.")),
    ],
    [("O Audi Q3 é um carro confiável?", "É, com a manutenção no prazo. A maior parte dos problemas conhecidos aparece quando o óleo, o câmbio ou o arrefecimento ficam sem cuidado."),
     ("Qual o problema mais comum do Audi Q3?", "Entre os mais conhecidos estão o consumo de óleo em algumas versões do 2.0 TFSI antigo, vazamentos no sistema de arrefecimento e trancos no câmbio no trânsito pesado."),
     ("Q3 da 2ª geração tem os mesmos problemas?", "Os pontos mais conhecidos são da 1ª geração. A 2ª geração tem mais eletrônica e pede a revisão sempre no prazo."),
     ("Vale a pena comprar um Audi Q3 usado?", "Pode valer, com histórico de revisões e uma avaliação antes da compra. A inspeção pré-compra mostra o estado real do carro."),
     ("A Veloce atende Audi Q3?", 'Sim, das duas gerações. Veja a <a href="/oficina-audi/">oficina Audi</a>.')],
    "Tem um Audi Q3?", "/oficina-audi/", "Ver a oficina Audi",
    "Olá, li o artigo sobre problemas do Audi Q3 e quero avaliar o meu. Ano/km: "))
ARTIGOS[-1]["publicado"] = "2026-11-18"
BLOG_HUB["secoes"].insert(3, {"h2": "Audi", "cards": [(t, f'{d} <a href="{h}">Ler o artigo</a>.') for t, h, d in (L_Q3, L_STRONIC)]})

# ------------------------------------------------------------------ CHAMADA NO MEIO DOS ARTIGOS (17/11/2026)
# O GA4 mostrou que o blog atrai visitas e quase não gera contato. A mensagem começa com "li o artigo sobre…"
# para separar, no WhatsApp e no GA4 (linkUrl), o contato que veio do blog.
CTA_MEIO = {
    "o-que-e-remap": ("Quer saber quanto o seu carro pode ganhar com remap?",
        "Mande o modelo, o ano e o motor. Um especialista diz o que dá para fazer com segurança no seu carro.",
        "Olá, li o artigo sobre remap e quero saber quanto o meu carro pode ganhar. Modelo/ano/motor: "),
    "vistoria-cautelar-ou-inspecao-pre-compra": ("Vai comprar um importado usado?",
        "Antes de fechar negócio, a inspeção pré-compra mostra o estado real do motor, do câmbio e da eletrônica.",
        "Olá, li o artigo sobre vistoria cautelar e quero agendar uma inspeção pré-compra. Carro (modelo/ano): "),
    "quanto-custa-um-remap": ("Quer o orçamento do remap para o seu carro?",
        "O valor depende do motor e do stage. Mande o modelo, o ano e o motor que respondemos com o orçamento.",
        "Olá, li o artigo sobre quanto custa um remap e quero um orçamento. Modelo/ano/motor: "),
    "remap-stage-1-e-stage-2": ("Stage 1 ou stage 2: qual faz sentido no seu carro?",
        "Mande o modelo, o motor e o que você quer melhorar. Um especialista indica o caminho mais seguro.",
        "Olá, li o artigo sobre stage 1 e stage 2 e quero saber qual faz sentido no meu carro. Modelo/ano/motor: "),
    "remap-estraga-o-motor": ("Quer fazer remap sem colocar o motor em risco?",
        "Avaliamos o carro antes, guardamos o software original e medimos o resultado no nosso dinamômetro.",
        "Olá, li o artigo sobre remap e segurança do motor e quero avaliar o meu carro. Modelo/ano/motor: "),
    "remap-perde-garantia": ("O seu carro ainda está na garantia?",
        "Conte o modelo e o ano. Explicamos o que considerar antes de fazer o remap.",
        "Olá, li o artigo sobre remap e garantia. Meu carro (modelo/ano): "),
    "o-que-e-dinamometro": ("Quer saber quantos cavalos o seu carro tem de verdade?",
        "A Veloce tem dinamômetro próprio e faz a medição avulsa, com ou sem remap.",
        "Olá, li o artigo sobre dinamômetro e quero medir o meu carro. Modelo/ano: "),
    "quando-trocar-oleo-cambio-automatico": ("Não sabe quando o óleo do câmbio foi trocado?",
        "Mande o modelo, o ano e a quilometragem. Dizemos se está na hora, antes de virar problema.",
        "Olá, li o artigo sobre troca de óleo do câmbio e quero saber se está na hora no meu carro. Modelo/ano/km: "),
    "oleo-de-cambio-automatico": ("Quer saber qual óleo o câmbio do seu carro usa?",
        "Identificamos o câmbio pelo modelo, o ano e o motor, e indicamos o óleo da especificação da montadora.",
        "Olá, li o artigo sobre óleo de câmbio e quero saber o óleo certo para o meu carro. Modelo/ano/motor: "),
    "cambio-automatico-dando-tranco": ("O seu câmbio está dando tranco?",
        "Conte quando acontece. A leitura da central e a avaliação mostram a causa antes de qualquer serviço.",
        "Olá, li o artigo sobre câmbio dando tranco. Meu carro (modelo/ano/km) e quando acontece: "),
    "audi-q3-problemas-comuns": ("O seu Q3 está dando algum desses sinais?",
        "Mande o ano, a quilometragem e o que está acontecendo. Um especialista Audi responde pelo WhatsApp.",
        "Olá, li o artigo sobre problemas do Audi Q3 e quero avaliar o meu. Ano/km e o que está acontecendo: "),
    "cambio-s-tronic": ("Tem um Audi com câmbio S tronic?",
        "Mande o modelo, o ano e a quilometragem. Dizemos qual é o seu S tronic e a manutenção que ele pede.",
        "Olá, li o artigo sobre o câmbio S tronic e quero avaliar o meu Audi. Modelo/ano/km: "),
}
for _a in ARTIGOS:
    _t, _x, _m = CTA_MEIO[_a["slug"]]
    _a["cta_meio"] = {"titulo": _t, "texto": _x, "botao": "Falar com um especialista", "msg": _m}

CONTEUDO = ARTIGOS + [BLOG_HUB] + SUBPAGINAS

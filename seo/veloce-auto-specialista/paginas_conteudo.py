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
    art("o-que-e-remap", "O que é Remap? Como Funciona e Quando Vale a Pena | Veloce",
        "O que é remap, como funciona a reprogramação da central do motor, o que muda no carro e quando vale a pena. Guia da Veloce, com dinamômetro próprio.",
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
    art("remap-stage-1-e-stage-2", "Remap Stage 1 x Stage 2: Diferença e Qual Escolher | Veloce",
        "Stage 1 ou stage 2? Veja a diferença entre os níveis de remap, o que cada um exige do carro e qual faz sentido para o seu uso. Guia da Veloce.",
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

CONTEUDO = ARTIGOS + [BLOG_HUB] + SUBPAGINAS

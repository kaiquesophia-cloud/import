"""Páginas de serviço e regional da Veloce (ver regras em paginas_dados.py)."""

MARCAS_LINKS = ('<a href="/oficina-bmw/">BMW</a>', '<a href="/oficina-mini/">MINI</a>',
                '<a href="/oficina-audi/">Audi</a>', '<a href="/oficina-mercedes-benz/">Mercedes-Benz</a>',
                '<a href="/oficina-porsche/">Porsche</a>', '<a href="/oficina-land-rover/">Land Rover</a>',
                '<a href="/oficina-jaguar/">Jaguar</a>', '<a href="/oficina-volvo/">Volvo</a>',
                '<a href="/oficina-lamborghini/">Lamborghini</a>')

ATENDIMENTO = {"h2": "Como funciona o atendimento", "steps": [
    ("Contato pelo WhatsApp.", "Você manda o modelo, o ano e o que precisa."),
    ("Avaliação na oficina.", "Leitura completa das centrais eletrônicas e inspeção física do carro."),
    ("Orçamento detalhado.", "Peça por peça, mão de obra separada. Você aprova item por item."),
    ("Execução com peças genuínas.", "Se aparecer algo fora do orçamento, avisamos antes de fazer."),
    ("Entrega com relatório técnico.", "O que foi feito, o que foi trocado e o que observar daqui em diante."),
]}

SERVICOS = [
    # ------------------------------------------------------------------ REMAP
    {
        "slug": "remap",
        "title": "Remap Automotivo em São Paulo | Stage 1 e 2 | Veloce",
        "description": "Remap automotivo em São Paulo feito na nossa oficina: stage 1 e stage 2 para carros premium, com leitura da ECU antes e depois. Peça seu orçamento.",
        "h1": "Remap automotivo em São Paulo, feito na nossa oficina",
        "lead": "Mais potência, mais torque e respostas mais rápidas, com responsabilidade técnica. O remap da Veloce é "
                "feito aqui mesmo, na oficina, por quem trabalha só com carros premium, com leitura completa da ECU "
                "antes e depois da reprogramação.",
        "botao": "Pedir orçamento de remap no WhatsApp",
        "whatsapp_msg": "Olá, quero um orçamento de remap. Modelo/ano/motor: ",
        "breadcrumb": "Remap automotivo",
        "servico": "Remap automotivo (reprogramação de ECU)",
        "servico_tipo": "Reprogramação de central de motor (remap) stage 1 e stage 2",
        "secoes": [
            {"h2": "O que é remap?", "p": [
                "Remap é a reprogramação do software da central do motor (ECU). A fábrica calibra o motor com margens "
                "amplas para atender a todos os mercados, combustíveis e climas. No remap, ajustamos parâmetros como "
                "pressão do turbo, ponto de ignição, injeção e limitadores de torque para extrair o desempenho que o "
                "motor já tem, dentro dos limites seguros dos componentes.",
                "Nos motores turbo modernos, como os das BMW, Audi, Mercedes, Porsche e MINI, o ganho costuma ser "
                "expressivo, porque a pressão do turbo é controlada pela própria central. Em motores aspirados, o "
                "ganho existe, mas é menor.",
            ]},
            {"h2": "Stage 1 x Stage 2: qual a diferença?", "table": [
                ["", "Stage 1", "Stage 2"],
                ["O que muda", "Só o software da ECU", "Software + peças de apoio (como downpipe, admissão e intercooler)"],
                ["Para quem é", "Uso diário, carro original", "Quem quer mais desempenho e aceita modificar o carro"],
                ["Ganho", "Moderado, com o carro mantendo a confiabilidade original", "Maior, dependendo das peças instaladas"],
                ["Cuidados", "Manutenção em dia e combustível de qualidade", "Embreagem/câmbio, arrefecimento e freios dimensionados para o ganho"],
             ], "p_depois": ["O ganho exato depende do motor, do câmbio e do estado do carro. Por isso avaliamos cada "
                             "carro antes de recomendar um stage, e às vezes a recomendação é corrigir algo antes de "
                             "reprogramar."]},
            {"h2": "Como fazemos o remap com segurança", "cards": [
                ("Diagnóstico antes", "Leitura completa da ECU e das demais centrais, checagem de falhas registradas, "
                 "velas, bobinas, vazamentos e arrefecimento. Motor com defeito não recebe remap."),
                ("Backup do original", "O software original é lido e guardado antes de qualquer alteração."),
                ("Feito na oficina", "A reprogramação é feita aqui, na Veloce, sem terceirizar o seu carro."),
                ("Conferência depois", "Nova leitura da ECU, teste de rodagem e checagem dos parâmetros após a "
                 "reprogramação, com relatório técnico na entrega."),
            ]},
            {"h2": "Remap para carros premium", "p": [
                "Fazemos remap nas marcas que atendemos na oficina. Veja a página de cada marca para conhecer os "
                "serviços completos:"],
             "chips": list(MARCAS_LINKS),
             "p_depois": ["Os modelos mais procurados para remap são os de motor turbo a gasolina, como BMW 320i e "
                          "330i, Audi A3/S3 e Q3, Mercedes C200/C300 e GLA, MINI Cooper S/JCW e Porsche Macan."]},
            {"h2": "Quanto custa um remap?", "p": [
                "O preço do remap depende do motor, do stage escolhido, de o câmbio precisar de ajuste junto e das "
                "peças de apoio necessárias no stage 2. Por isso não trabalhamos com tabela fixa: avaliamos o seu carro "
                "e passamos um orçamento fechado antes de qualquer serviço.",
                "Desconfie de remap muito barato: arquivo genérico baixado da internet, sem diagnóstico antes e sem "
                "conferência depois, é o que dá fama ruim ao remap. Mande o modelo, o ano e o motor pelo WhatsApp e "
                "receba o orçamento para o seu carro.",
            ]},
            ATENDIMENTO,
        ],
        "faq_h2": "Perguntas frequentes sobre remap",
        "faq": [
            ("Remap estraga o motor?",
             "Um remap bem feito, em motor saudável e dentro dos limites dos componentes, não estraga o motor. O que "
             "causa problema é arquivo agressivo, motor com defeito prévio ou manutenção atrasada. Por isso fazemos "
             "diagnóstico antes e conferência depois de cada reprogramação."),
            ("Remap perde a garantia de fábrica?",
             "A montadora pode recusar a garantia de componentes ligados ao motor e ao câmbio se encontrar software "
             "alterado. Se o seu carro ainda está na garantia, converse com a gente antes para avaliar o melhor momento."),
            ("Dá para voltar ao original?",
             "Sim. Guardamos o software original antes da reprogramação, e ele pode ser regravado na central."),
            ("Quanto tempo leva?",
             "Depende do carro, do stage e do resultado do diagnóstico feito antes. Combine a data pelo WhatsApp que "
             "informamos o prazo para o seu carro."),
            ("O consumo aumenta?",
             "No uso normal, o consumo costuma ficar parecido com o original, e em estrada pode até melhorar. Quem usa "
             "a potência extra o tempo todo vai gastar mais combustível, como em qualquer carro."),
        ],
        "cta_h2": "Peça o orçamento do remap para o seu carro",
        "cta_p": "Mande o modelo, o ano e o motor. Um especialista responde pelo WhatsApp com a avaliação para o seu carro.",
    },
    # ------------------------------------------------------------------ INSPEÇÃO PRÉ-COMPRA
    {
        "slug": "inspecao-pre-compra",
        "title": "Inspeção Pré-Compra de Carros Importados em SP | Veloce",
        "description": "Inspeção pré-compra de carros importados e premium em São Paulo: motor, câmbio, eletrônica e histórico de falhas. Relatório técnico antes do negócio.",
        "h1": "Inspeção pré-compra de carros importados em São Paulo",
        "lead": "Antes de fechar negócio num carro premium usado, saiba o que você está comprando. Avaliamos motor, "
                "câmbio, eletrônica, suspensão e o histórico de falhas registrado nas centrais, e entregamos um "
                "relatório técnico para você decidir com segurança.",
        "botao": "Agendar inspeção pré-compra no WhatsApp",
        "whatsapp_msg": "Olá, quero agendar uma inspeção pré-compra. Carro (modelo/ano): ",
        "breadcrumb": "Inspeção pré-compra",
        "servico": "Inspeção pré-compra de veículos importados e premium",
        "servico_tipo": "Avaliação mecânica e eletrônica de veículo usado antes da compra",
        "secoes": [
            {"h2": "Por que fazer a inspeção antes de comprar", "p": [
                "Um BMW, um Audi ou um Range Rover usado pode ser uma ótima compra ou uma dor de cabeça cara. A "
                "diferença costuma estar em detalhes que não aparecem no test drive: falhas gravadas nas centrais, "
                "vazamentos no início, câmbio com adaptação no limite, suspensão a ar cansada, manutenção atrasada.",
                "A inspeção pré-compra da Veloce é feita por quem trabalha só com carros premium. O valor de uma "
                "inspeção é pequeno perto de um reparo de câmbio ou de motor descoberto depois da compra, e o relatório "
                "também serve para negociar o preço quando há reparos a fazer.",
            ]},
            {"h2": "O que verificamos", "check": [
                "Leitura de todas as centrais eletrônicas e histórico de falhas",
                "Coerência da quilometragem entre módulos",
                "Motor: vazamentos, ruídos, partida a frio, arrefecimento",
                "Câmbio automático: trocas, trancos e adaptações",
                "Suspensão, buchas, amortecedores e suspensão a ar",
                "Freios: discos, pastilhas e sensores",
                "Pneus: desgaste irregular e data de fabricação",
                "Sistemas de conforto, multimídia e assistências",
                "Sinais de reparos de funilaria visíveis na inspeção",
                "Teste de rodagem com acompanhamento técnico",
            ], "p_depois": ["A inspeção pré-compra avalia a parte mecânica e eletrônica do carro. Ela não substitui a "
                            "vistoria cautelar (laudo documental e estrutural feito por empresa credenciada), que "
                            "nós não fazemos. Para uma compra segura, o ideal é ter as duas."]},
            {"h2": "O que você recebe", "cards": [
                ("Relatório técnico", "Tudo o que foi verificado, com o estado de cada item e as falhas encontradas."),
                ("Prioridades", "O que precisa ser feito já, o que pode esperar e o que é só desgaste normal."),
                ("Estimativa de reparos", "Quando há reparos, uma estimativa para você negociar o preço com o vendedor."),
                ("Opinião de especialista", "Uma conversa franca sobre o carro, para você decidir com segurança."),
            ]},
            {"h2": "O que a inspeção costuma revelar", "p": [
                "Alguns achados comuns em importados usados, que mudam o preço ou a decisão de compra:"], "cards": [
                ("Falhas apagadas às pressas", "Centrais sem nenhuma falha registrada num carro com anos de uso podem "
                 "indicar que o histórico foi apagado pouco antes da venda. Isso merece atenção."),
                ("Quilometragem incoerente", "Diferenças entre a quilometragem do painel e a gravada em outros módulos "
                 "são um sinal de alerta importante."),
                ("Câmbio no limite", "Trancos leves no test drive podem esconder adaptações no limite e um reparo caro "
                 "pela frente."),
                ("Manutenção atrasada", "Fluidos vencidos, velas e filtros fora do prazo: não impedem a compra, mas "
                 "entram na negociação do preço."),
            ]},
            {"h2": "Marcas que avaliamos", "p": ["Fazemos inspeção pré-compra em todas as marcas premium que atendemos:"],
             "chips": list(MARCAS_LINKS)},
            {"h2": "Como agendar", "steps": [
                ("Mande o anúncio pelo WhatsApp.", "Modelo, ano e, se tiver, o link do anúncio."),
                ("Combine a data.", "Combine com o vendedor de levar o carro até a oficina, na Casa Verde."),
                ("Inspeção.", "Leitura das centrais, inspeção física e teste de rodagem."),
                ("Relatório e conversa.", "Você recebe o relatório e tira as dúvidas com o especialista."),
            ]},
        ],
        "faq_h2": "Perguntas frequentes sobre inspeção pré-compra",
        "faq": [
            ("Qual a diferença entre inspeção pré-compra e vistoria cautelar?",
             "A vistoria cautelar é um laudo feito por empresa credenciada que verifica documentação, numeração e "
             "estrutura do veículo. A inspeção pré-compra avalia o estado mecânico e eletrônico: motor, câmbio, "
             "suspensão, centrais e histórico de falhas. A Veloce faz a inspeção pré-compra, não a cautelar."),
            ("Quanto custa a inspeção pré-compra?",
             "Depende do modelo. Mande o carro que você vai avaliar pelo WhatsApp que passamos o valor antes de agendar."),
            ("Quanto tempo leva?",
             "Depende do modelo. Combine o horário pelo WhatsApp que informamos o tempo estimado para o carro que você vai avaliar."),
            ("O vendedor precisa levar o carro até a oficina?",
             "Sim. A leitura completa das centrais e a inspeção por baixo do carro são feitas na oficina, na Av. Casa "
             "Verde, 3010."),
            ("Vale a pena para carro de concessionária?",
             "Vale. Carro de loja também pode ter falhas registradas, manutenção atrasada ou reparos que não aparecem "
             "no anúncio. O relatório protege a sua compra."),
        ],
        "cta_h2": "Vai comprar um importado? Agende a inspeção",
        "cta_p": "Mande o modelo, o ano e o link do anúncio. Um especialista responde pelo WhatsApp.",
    },
    # ------------------------------------------------------------------ MANUTENÇÃO PREVENTIVA
    {
        "slug": "manutencao-preventiva",
        "title": "Revisão de Carros Importados em São Paulo | Veloce",
        "description": "Revisão e manutenção preventiva de carros importados em SP: plano da montadora, óleo com a especificação correta e peças genuínas. Relatório técnico.",
        "h1": "Revisão e manutenção preventiva de carros importados",
        "lead": "Revisão no padrão da montadora, sem preço de concessionária e com transparência total. Seguimos o plano "
                "de manutenção do seu carro, usamos peças genuínas e entregamos um relatório técnico de cada revisão.",
        "botao": "Agendar revisão no WhatsApp",
        "whatsapp_msg": "Olá, quero agendar uma revisão. Modelo/ano/km: ",
        "breadcrumb": "Manutenção preventiva",
        "servico": "Revisão e manutenção preventiva de carros importados",
        "servico_tipo": "Revisão periódica conforme plano de manutenção da montadora",
        "secoes": [
            {"h2": "Por que a revisão do importado é diferente", "p": [
                "Carros premium têm planos de manutenção próprios, com intervalos controlados pela eletrônica (como o "
                "CBS da BMW e o Service A/B da Mercedes) e óleos com especificações aprovadas por cada montadora. Usar o "
                "óleo errado ou pular itens do plano não aparece no dia seguinte, mas aparece no desgaste do motor, do "
                "turbo e do câmbio.",
                "Na Veloce, a revisão segue o que a montadora pede para o seu motor, com peças genuínas, e você recebe "
                "um relatório com o que foi feito e o que vem na próxima revisão.",
            ]},
            {"h2": "O que entra na revisão", "check": [
                "Óleo do motor com a especificação aprovada pela montadora",
                "Filtros de óleo, ar, cabine e combustível conforme o plano",
                "Velas de ignição no intervalo correto",
                "Fluido de freio (troca por tempo, não só por km)",
                "Fluido de arrefecimento e checagem do sistema",
                "Inspeção de freios, suspensão, direção e pneus",
                "Leitura das centrais eletrônicas e falhas registradas",
                "Atualização dos intervalos de revisão no painel",
                "Checagem de vazamentos por baixo do carro",
                "Teste de rodagem e relatório técnico",
            ]},
            {"h2": "Itens que a concessionária nem sempre lembra", "cards": [
                ("Óleo do câmbio", "Muitos câmbios automáticos são vendidos como \"lubrificados para a vida toda\", "
                 "mas a troca do fluido no tempo certo evita trancos e reparos caros."),
                ("Fluido de freio", "Absorve umidade com o tempo e perde eficiência. A troca é por tempo, mesmo com "
                 "pouca quilometragem."),
                ("Diferenciais e tração", "Carros com tração integral têm óleos de diferencial e caixa de "
                 "transferência que também vencem."),
                ("Carro que roda pouco", "Fluidos envelhecem mesmo parados. Para quem roda pouco, o prazo da revisão "
                 "conta mais que a quilometragem."),
            ]},
            {"h2": "Sinais de que a revisão está atrasada", "cards": [
                ("Aviso no painel", "O carro avisa quando a revisão vence. Ignorar o aviso por meses acelera o "
                 "desgaste de óleo, velas e filtros."),
                ("Motor menos suave", "Marcha lenta irregular e respostas mais lentas costumam ser velas e bobinas "
                 "no fim da vida útil."),
                ("Freio mais \"borrachudo\"", "Pedal mais longo ou esponjoso pode ser fluido de freio vencido, que "
                 "absorveu umidade."),
                ("Consumo maior", "Filtro de ar sujo, velas gastas e pneus com pressão errada aumentam o consumo aos poucos."),
            ]},
            {"h2": "Revisão por marca", "p": [
                "Cada marca tem particularidades no plano de manutenção. Veja a página da sua:"],
             "chips": list(MARCAS_LINKS)},
            ATENDIMENTO,
        ],
        "faq_h2": "Perguntas frequentes sobre revisão de importados",
        "faq": [
            ("Fazer a revisão fora da concessionária faz perder a garantia?",
             "Se o seu carro ainda está na garantia de fábrica, consulte as condições do manual de garantia. Nós "
             "orientamos o que pode ser feito aqui sem colocar a garantia em risco. Fora da garantia, a revisão segue o "
             "mesmo plano da montadora, com peças genuínas e registro do serviço."),
            ("De quanto em quanto tempo fazer a revisão?",
             "Siga o que o painel ou o manual indicam para o seu modelo. Em geral, uma vez por ano ou na quilometragem "
             "indicada, o que vier primeiro. Para uso severo (trânsito pesado de São Paulo), o intervalo do óleo "
             "costuma ser menor."),
            ("Quanto custa a revisão de um carro importado?",
             "Depende do modelo, do motor e de quais itens do plano estão vencendo. Mande o modelo, o ano e a "
             "quilometragem pelo WhatsApp que respondemos com o orçamento."),
            ("Vocês usam peças originais?",
             "Sim. Usamos somente peças genuínas, e você sabe a procedência de cada peça antes de aprovar o orçamento."),
            ("A revisão fica registrada?",
             "Sim. Você recebe o relatório técnico do serviço, e os intervalos de revisão do painel são atualizados."),
        ],
        "cta_h2": "Agende a revisão do seu importado",
        "cta_p": "Mande o modelo, o ano e a quilometragem. Um especialista responde pelo WhatsApp.",
    },
    # ------------------------------------------------------------------ ZONA NORTE
    {
        "slug": "oficina-importados-zona-norte",
        "title": "Oficina de Carros Importados na Zona Norte de SP | Veloce",
        "description": "Oficina de carros importados e premium na Zona Norte de São Paulo, na Av. Casa Verde: BMW, Audi, Mercedes, Porsche, Land Rover, Volvo e mais.",
        "h1": "Oficina de carros importados na Zona Norte de São Paulo",
        "lead": "A Veloce Auto Specialista fica na Av. Casa Verde, 3010, pertinho de Santana, Limão, Freguesia do Ó e da "
                "Marginal Tietê. Uma oficina dedicada só a carros premium e importados, com diagnóstico completo, peças "
                "genuínas e relatório técnico em cada entrega.",
        "botao": "Falar com a oficina no WhatsApp",
        "whatsapp_msg": "Olá, quero um orçamento. Carro (modelo/ano): ",
        "breadcrumb": "Oficina de importados na Zona Norte",
        "servico": "Oficina mecânica de carros importados e premium",
        "servico_tipo": "Manutenção, diagnóstico e reparo de veículos importados",
        "secoes": [
            {"h2": "Mecânica de importados perto de você", "p": [
                "Quem tem carro importado na Zona Norte muitas vezes atravessa a cidade até a concessionária ou acaba "
                "numa oficina genérica que \"mexe em tudo\". A Veloce nasceu justamente para oferecer o meio-termo que "
                "faltava: nível técnico de montadora, atendimento próximo e transparência total, aqui na Casa Verde.",
                "Atendemos clientes de toda a Zona Norte (Santana, Casa Verde, Limão, Freguesia do Ó, Vila Maria, "
                "Tucuruvi, Mandaqui, Parada Inglesa, Jardim São Paulo) e de toda São Paulo.",
            ]},
            {"h2": "Marcas que atendemos", "p": ["Trabalhamos só com marcas premium. Veja a página de cada uma:"],
             "chips": list(MARCAS_LINKS)},
            {"h2": "Serviços", "cards": [
                ("Diagnóstico eletrônico", "Scanner profissional e leitura completa das centrais para achar a causa "
                 "real de qualquer defeito."),
                ("Revisão e manutenção preventiva", 'Plano da montadora, óleo com a especificação correta e peças '
                 'genuínas. <a href="/manutencao-preventiva/">Ver revisão</a>.'),
                ("Manutenção corretiva", "Motor, câmbio, arrefecimento, suspensão, freios e elétrica, com garantia "
                 "técnica do serviço."),
                ("Remap", 'Reprogramação de motor feita na própria oficina, com leitura da ECU antes e depois. '
                 '<a href="/remap/">Ver remap</a>.'),
                ("Inspeção pré-compra", 'Avaliação completa antes de comprar um importado usado. '
                 '<a href="/inspecao-pre-compra/">Ver inspeção</a>.'),
                ("Performance e personalização", "Escapamentos, rodas, freios e acertos técnicos com critério."),
            ]},
            {"h2": "Por que escolher a Veloce", "cards": [
                ("Só carros premium", "Equipe e equipamentos dedicados a importados, e não uma oficina que faz de tudo."),
                ("Peças genuínas", "Você sabe a procedência de cada peça antes de aprovar o orçamento."),
                ("Orçamento aprovado item a item", "Nada é feito sem o seu \"pode\". Se surgir algo novo, avisamos antes."),
                ("Relatório técnico", "Cada entrega sai com o registro do que foi feito e do que acompanhar."),
            ]},
            {"h2": "Oficina especializada x concessionária x oficina comum", "table": [
                ["", "Concessionária", "Oficina comum", "Veloce"],
                ["Foco", "Uma marca", "Todos os carros", "Só carros premium e importados"],
                ["Peças", "Genuínas", "Varia", "Genuínas"],
                ["Diagnóstico", "Scanner da marca", "Muitas vezes genérico", "Scanner profissional e leitura completa das centrais"],
                ["Orçamento", "Pacote fechado", "Varia", "Item por item, com aprovação do cliente"],
                ["Entrega", "Ordem de serviço", "Varia", "Relatório técnico do que foi feito"],
                ["Atendimento", "Consultor", "Varia", "Contato direto com quem cuida do seu carro"],
            ]},
            {"h2": "Como chegar", "p": [
                "Estamos na <strong>Av. Casa Verde, 3010</strong>, no bairro Casa Verde. Para quem vem pela Marginal "
                "Tietê, o acesso é rápido, e de Santana, do Limão e da Freguesia do Ó são poucos minutos até a oficina. "
                "O mapa abaixo mostra o caminho.",
                "Horário: segunda a sexta, das 8h às 18h, e sábado, das 8h às 12h.",
            ]},
        ],
        "faq_h2": "Perguntas frequentes",
        "faq": [
            ("Onde fica a Veloce Auto Specialista?",
             "Na Av. Casa Verde, 3010, bairro Casa Verde, Zona Norte de São Paulo, CEP 02520-300."),
            ("Qual o horário de funcionamento?",
             "De segunda a sexta, das 8h às 18h, e aos sábados, das 8h às 12h."),
            ("Vocês atendem carros nacionais?",
             "Nosso foco são carros premium e importados: BMW, MINI, Audi, Mercedes-Benz, Porsche, Land Rover, Jaguar, "
             "Volvo e Lamborghini."),
            ("Atendem clientes de outras regiões de São Paulo?",
             "Sim. Muitos clientes vêm de outras zonas da cidade pela especialização em carros premium."),
            ("Como faço um orçamento?",
             "Pelo WhatsApp: mande o modelo, o ano e o que está acontecendo. Para um orçamento exato, fazemos o "
             "diagnóstico na oficina e você aprova item por item."),
        ],
        "cta_h2": "Fale com a oficina",
        "cta_p": "Mande o modelo, o ano e o que precisa. Respondemos pelo WhatsApp.",
    },
]

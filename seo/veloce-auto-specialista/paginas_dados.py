"""Conteúdo das páginas de SEO da Veloce. Cada página tem texto próprio (não é a mesma página com o
nome da marca trocado). Só afirme aqui o que o dono confirmou:
- oficina na Av. Casa Verde, 3010 (Zona Norte); atende todas as marcas premium
- peças genuínas; orçamento aprovado item a item; relatório técnico na entrega; garantia técnica do serviço
- diagnóstico com scanner profissional e leitura completa de ECU
- remap feito na própria oficina, SEM preço publicado; NÃO fazem vistoria cautelar; NÃO fazem registro de bateria
"""

REMAP = '<a href="/remap/">remap</a>'
PRECOMPRA = '<a href="/inspecao-pre-compra/">inspeção pré-compra</a>'
PREVENTIVA = '<a href="/manutencao-preventiva/">manutenção preventiva</a>'
LINK = {
    "BMW": '<a href="/oficina-bmw/">BMW</a>', "MINI": '<a href="/oficina-mini/">MINI</a>',
    "Audi": '<a href="/oficina-audi/">Audi</a>', "Mercedes-Benz": '<a href="/oficina-mercedes-benz/">Mercedes-Benz</a>',
    "Porsche": '<a href="/oficina-porsche/">Porsche</a>', "Land Rover": '<a href="/oficina-land-rover/">Land Rover</a>',
    "Jaguar": '<a href="/oficina-jaguar/">Jaguar</a>', "Volvo": '<a href="/oficina-volvo/">Volvo</a>',
    "Lamborghini": '<a href="/oficina-lamborghini/">Lamborghini</a>',
}


def passos(marca):
    return {"h2": "Como funciona o atendimento", "steps": [
        ("Contato pelo WhatsApp.", f"Você manda o modelo da sua {marca}, o ano e o sintoma (ou o serviço que precisa)."),
        ("Diagnóstico na oficina.", "Leitura completa das centrais eletrônicas e inspeção física do carro."),
        ("Orçamento detalhado.", "Peça por peça, mão de obra separada. Você aprova item por item."),
        ("Execução com peças genuínas.", "Se aparecer algo fora do orçamento, avisamos antes de fazer."),
        ("Entrega com relatório técnico.", "O que foi feito, o que foi trocado e o que observar daqui em diante."),
    ]}


def faq_padrao(marca, pergunta_modelo, resposta_modelo):
    return [
        (f"Fazer a revisão da {marca} fora da concessionária faz perder a garantia?",
         f"Se a sua {marca} ainda está na garantia de fábrica, consulte as condições do manual de garantia. Nós "
         "orientamos o que pode ser feito aqui sem colocar a garantia em risco. Fora da garantia, a revisão em uma "
         "oficina especializada segue o mesmo plano da montadora, com peças genuínas e registro do serviço."),
        (f"Quanto custa a revisão de uma {marca}?",
         "Depende do modelo, do motor e da quilometragem. Por isso passamos o orçamento detalhado antes de começar, "
         "e você aprova item por item. Mande o modelo e o ano pelo WhatsApp que respondemos com uma estimativa."),
        ("Vocês usam peças originais?",
         f"Sim. Usamos somente peças genuínas {marca}, e você sabe a procedência de cada peça antes de aprovar o orçamento."),
        (pergunta_modelo, resposta_modelo),
        ("Onde fica a oficina?",
         "Na Av. Casa Verde, 3010, bairro Casa Verde, Zona Norte de São Paulo, com acesso fácil por Santana, Limão, "
         "Freguesia do Ó e pela Marginal Tietê. Atendemos clientes de toda a cidade."),
    ]


def marca(slug, nome, titulo_curto, servico_tipo, lead, intro, diferenciais, modelos, modelos_p, servicos,
          problemas_intro, problemas, faq_modelo, extra=None):
    secoes = [
        {"h2": f"Mecânica {nome} com padrão de concessionária", "p": [intro], "cards": diferenciais},
        {"h2": f"Modelos {nome} que atendemos", "p": [modelos_p[0]], "chips": modelos, "p_depois": modelos_p[1:]},
        {"h2": f"Serviços para sua {nome}", "cards": servicos},
        {"h2": f"Problemas comuns em {nome} (e o que fazer)", "p": [problemas_intro], "cards": problemas},
    ]
    if extra:
        secoes.append(extra)
    secoes.append(passos(nome))
    return {
        "slug": slug,
        "title": f"Oficina {titulo_curto} em São Paulo (Zona Norte) | Veloce",
        "description": None,  # preenchido abaixo
        "h1": f"Oficina especializada em {nome} em São Paulo",
        "lead": lead,
        "botao": f"Falar com um especialista {titulo_curto} no WhatsApp",
        "whatsapp_msg": f"Olá, tenho um {nome} e quero um orçamento. Modelo/ano: ",
        "breadcrumb": f"Oficina {nome}",
        "servico": f"Oficina especializada em {nome}",
        "servico_tipo": servico_tipo,
        "secoes": secoes,
        "faq_h2": f"Perguntas frequentes sobre oficina {nome}",
        "faq": faq_padrao(nome, *faq_modelo),
        "cta_h2": f"Agende o diagnóstico da sua {nome}",
        "cta_p": "Mande o modelo, o ano e o que está acontecendo. Um especialista responde pelo WhatsApp.",
    }


def servicos_marca(nome, revisao, remap_txt, extra_card):
    return [
        (f"Revisão {nome}", revisao + f" Ver {PREVENTIVA}."),
        ("Diagnóstico eletrônico", "Luz de motor, mensagens no painel, falhas intermitentes: leitura completa das "
         "centrais e teste dos componentes antes de qualquer troca."),
        ("Manutenção corretiva", "Motor, arrefecimento, suspensão, freios, câmbio e direção, com garantia técnica do "
         "serviço e peças genuínas."),
        (f"Remap {nome}", remap_txt + f" Ganho medido no nosso dinamômetro próprio. Conheça o {REMAP}."),
        ("Inspeção pré-compra", f"Vai comprar uma {nome} usada? Avaliamos motor, câmbio, eletrônica e histórico de "
         f"falhas antes do negócio. Ver {PRECOMPRA}."),
        extra_card,
    ]


PAGINAS = [
    # ------------------------------------------------------------------ LAND ROVER
    marca(
        "oficina-land-rover", "Land Rover", "Land Rover", "Manutenção, diagnóstico e reparo de veículos Land Rover e Range Rover",
        "Oficina especializada em Land Rover e Range Rover, com diagnóstico completo da eletrônica, peças genuínas e "
        "relatório técnico em cada entrega. Suspensão a ar, motores Ingenium e toda a parte elétrica tratados por quem "
        "trabalha só com carros premium.",
        "Um Land Rover é um carro de muitos sistemas: suspensão a ar com regulagem de altura, tração integral com "
        "programas de terreno, dezenas de módulos eletrônicos conversando entre si. Quando algo falha, o painel costuma "
        "acender vários avisos ao mesmo tempo, e trocar peça no chute sai caro. A Veloce nasceu da insatisfação com o "
        "serviço genérico das oficinas comuns: aqui o diagnóstico vem antes do orçamento, e o orçamento vem antes de "
        "qualquer serviço.",
        [("Diagnóstico de verdade", "Scanner profissional e leitura completa das centrais (motor, câmbio, suspensão, "
          "tração, conforto) para achar a causa, e não só apagar o aviso do painel."),
         ("Peças genuínas", "Usamos somente peças genuínas Land Rover. Você sabe a procedência de tudo antes de aprovar."),
         ("Relatório técnico", "Cada entrega sai com o que foi encontrado, o que foi trocado e o que precisa de atenção "
          "nas próximas revisões."),
         ("Orçamento sem surpresa", "Você recebe o orçamento detalhado e aprova item por item. Nada é feito sem o seu \"pode\".")],
        ["Range Rover Evoque", "Range Rover Velar", "Range Rover Sport", "Range Rover", "Discovery Sport", "Discovery",
         "Defender", "Freelander 2"],
        ["Atendemos a linha Land Rover e Range Rover vendida no Brasil, a gasolina e diesel, atuais e de gerações anteriores:",
         f"A Land Rover divide motores e boa parte da eletrônica com a {LINK['Jaguar']}, que também atendemos na mesma oficina."],
        servicos_marca("Land Rover",
            "Revisão preventiva conforme o plano da montadora: óleo com a especificação correta para o seu motor, filtros, "
            "fluidos de freio e arrefecimento, e inspeção da suspensão.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois, muito procurada para "
            "Evoque, Velar e Discovery Sport.",
            ("Suspensão a ar", "Diagnóstico de vazamentos nas bolsas, nas válvulas e nas linhas, teste do compressor e "
             "calibração das alturas pelo scanner.")),
        "São os defeitos que mais chegam à oficina. Quase todos têm conserto simples quando tratados cedo.",
        [("Suspensão a ar baixando", "Carro amanhecendo mais baixo de um lado, compressor trabalhando demais ou aviso "
          "de suspensão no painel indicam vazamento de ar. Ignorar costuma queimar o compressor."),
         ("Vazamentos de óleo", "Juntas de tampa de válvulas e retentores ressecam com o calor e o tempo. Uma gota "
          "embaixo do carro hoje pode virar uma correia contaminada amanhã."),
         ("Superaquecimento", "Mangueiras, conexões plásticas e a válvula termostática são pontos de atenção no "
          "arrefecimento. Aviso de temperatura exige parar e diagnosticar."),
         ("Diesel e filtro de partículas", "Nos motores diesel, uso só urbano entope o filtro de partículas (DPF). "
          "Aviso de filtro e perda de potência pedem diagnóstico antes que o filtro precise ser trocado."),
         ("Avisos elétricos em cascata", "Bateria fraca em um Land Rover gera avisos aleatórios em vários sistemas. "
          "O diagnóstico separa o que é defeito real do que é tensão baixa."),
         ("Ruídos na tração", "Estalos em manobras e zumbido em velocidade podem vir da caixa de transferência ou do "
          "diferencial traseiro. Trocar o óleo no prazo evita o reparo grande.")],
        ("Vocês atendem Land Rover antiga e Defender?",
         "Sim. Atendemos do Freelander 2 e Discovery mais antigos ao novo Defender. Fale com a gente pelo WhatsApp "
         "informando modelo e ano para confirmarmos a disponibilidade de peças."),
    ),
    # ------------------------------------------------------------------ VOLVO
    marca(
        "oficina-volvo", "Volvo", "Volvo", "Manutenção, diagnóstico e reparo de veículos Volvo",
        "Oficina especializada em Volvo, com diagnóstico completo da eletrônica, peças genuínas e relatório técnico "
        "em cada entrega. Revisão, correção de defeitos e manutenção dos XC40, XC60, XC90 e sedãs, com transparência "
        "do começo ao fim.",
        "O Volvo é construído para durar, e dura, desde que a manutenção acompanhe o que a fábrica pede. Os motores "
        "Drive-E de quatro cilindros, turbo e às vezes com compressor, trabalham com óleo e intervalos específicos, e "
        "os sistemas de segurança dependem de sensores calibrados. A Veloce trabalha só com carros premium: o seu "
        "Volvo é diagnosticado com scanner, orçado peça por peça e entregue com relatório técnico.",
        [("Diagnóstico de verdade", "Leitura completa das centrais (motor, câmbio, freios, assistências de condução) "
          "para achar a origem real do defeito."),
         ("Peças genuínas", "Usamos somente peças genuínas Volvo, com procedência informada no orçamento."),
         ("Relatório técnico", "Você recebe o registro do que foi encontrado, trocado e do que observar nas próximas revisões."),
         ("Orçamento sem surpresa", "Aprovação item por item. Se aparecer algo novo durante o serviço, avisamos antes de fazer.")],
        ["XC40", "XC60", "XC90", "C40", "S60", "S90", "V60", "V40", "XC60 e XC90 de gerações anteriores"],
        ["Atendemos a linha Volvo vendida no Brasil, atual e de gerações anteriores:",
         "Tem um Volvo híbrido (Recharge)? Fale com a gente pelo WhatsApp informando o modelo para combinarmos o serviço."],
        servicos_marca("Volvo",
            "Revisão preventiva conforme o plano da montadora: óleo com a especificação Volvo, filtros, velas, fluido "
            "de freio e inspeção completa.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois e responsabilidade técnica.",
            ("Freios e segurança", "Pastilhas, discos e sensores de desgaste com peças genuínas, e conferência dos "
             "avisos dos sistemas de assistência após o serviço.")),
        "Os defeitos que mais aparecem nos Volvo da última década. Detectados cedo, têm reparo simples.",
        [("Consumo de óleo", "Alguns motores Drive-E de quatro cilindros podem consumir óleo acima do normal. Medir o "
          "consumo e checar o sistema de respiro do motor vem antes de qualquer conclusão."),
         ("Vazamentos de óleo", "Retentores e juntas ressecam com o tempo. O cheiro de queimado depois de rodar é o "
          "primeiro aviso de óleo pingando no escapamento."),
         ("Câmbio com trancos", "Trocas duras ou demoradas no câmbio automático costumam melhorar com a troca do "
          "fluido no intervalo certo, mesmo quando o manual fala em óleo de longa duração."),
         ("Arrefecimento", "Bomba d'água, termostato e reservatório de expansão merecem atenção. Aviso de temperatura "
          "exige parar e diagnosticar, não completar água e seguir."),
         ("Falhas de ignição", "Motor tremendo e luz de injeção acesa costumam ser bobinas ou velas. Na injeção "
          "direta, a carbonização das válvulas também entra no diagnóstico."),
         ("Ruídos na suspensão", "Batidas em lombadas e direção vibrando geralmente são buchas, bieletas ou coxins "
          "cansados. Revisar cedo evita desgaste irregular dos pneus.")],
        ("Vocês atendem Volvo antigo?",
         "Sim. Atendemos modelos atuais e gerações anteriores, como XC60 e XC90 da primeira geração e o V40. Fale com "
         "a gente pelo WhatsApp informando modelo e ano para confirmarmos a disponibilidade de peças."),
    ),
    # ------------------------------------------------------------------ MERCEDES-BENZ
    marca(
        "oficina-mercedes-benz", "Mercedes-Benz", "Mercedes-Benz", "Manutenção, diagnóstico e reparo de veículos Mercedes-Benz",
        "Oficina especializada em Mercedes-Benz, com diagnóstico completo da eletrônica, peças genuínas e relatório "
        "técnico em cada entrega. Dos Classe A e C aos SUVs GLA, GLC e GLE, e aos AMG, com o rigor que a marca exige.",
        "A Mercedes-Benz tem um plano de manutenção próprio (Service A e Service B) e uma eletrônica que registra cada "
        "falha nas centrais. Isso ajuda quem sabe ler e atrapalha quem trabalha no chute. Na Veloce, cada Mercedes passa "
        "por leitura completa das centrais, recebe orçamento detalhado para aprovação e sai com relatório técnico do que "
        "foi feito.",
        [("Diagnóstico de verdade", "Leitura completa das centrais (motor, câmbio, SAM, suspensão, conforto) para achar "
          "a causa e não só apagar a luz."),
         ("Peças genuínas", "Usamos somente peças genuínas Mercedes-Benz, com procedência informada antes da aprovação."),
         ("Relatório técnico", "O que foi encontrado, o que foi trocado e o que acompanhar daqui em diante, por escrito."),
         ("Orçamento sem surpresa", "Você aprova item por item. Nada é feito sem o seu \"pode\".")],
        ["Classe A (A200, A250)", "Classe B", "CLA", "Classe C (C180, C200, C250, C300)", "Classe E", "GLA", "GLB",
         "GLC / GLC Coupé", "GLE", "Classe S", "AMG (C43, C63, GLA 45, A45)"],
        ["Atendemos a linha Mercedes-Benz de passeio vendida no Brasil, atual e de gerações anteriores:",
         "Também atendemos as outras marcas premium na mesma oficina: veja a nossa página de "
         f"{LINK['BMW']} e de {LINK['Audi']}."],
        servicos_marca("Mercedes-Benz",
            "Service A e Service B conforme o plano da montadora: óleo com a especificação Mercedes (MB 229), filtros, "
            "velas, fluido de freio e inspeção completa.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois. Muito procurada para "
            "C200, C300, GLA e A250.",
            ("Suspensão e direção", "Buchas, bandejas, amortecedores e, nos modelos com suspensão a ar (Airmatic), "
             "diagnóstico de vazamentos e do compressor.")),
        "Os defeitos que mais chegam à oficina nos Mercedes recentes. Tratados cedo, saem mais baratos.",
        [("Vazamento nos eletroímãs do comando", "Em vários motores quatro cilindros (como os M270 e M274), os "
          "eletroímãs do comando variável podem vazar óleo para o chicote. Trocar cedo protege a parte elétrica."),
         ("Corrente de comando", "Ruído metálico na partida a frio pode indicar desgaste da corrente ou do tensor, "
          "principalmente em motores mais antigos. É reparo para fazer logo, não para esperar."),
         ("Câmbio com trancos", "Nos câmbios automáticos 7G e 9G, trocas duras costumam melhorar com a troca do fluido "
          "no intervalo certo. O diagnóstico vem antes de qualquer reparo maior."),
         ("Arrefecimento", "Termostato, bomba d'água e conexões plásticas são pontos de atenção. Aviso de temperatura "
          "exige parar o carro."),
         ("Suspensão a ar (Airmatic)", "Carro baixando parado ou aviso de suspensão indicam vazamento. Tratar logo "
          "evita queimar o compressor."),
         ("Avisos elétricos", "Mensagens aleatórias no painel podem vir de bateria fraca, módulo SAM ou conectores "
          "com umidade. A leitura das centrais separa uma coisa da outra.")],
        ("Vocês atendem Mercedes AMG?",
         "Sim. Atendemos os AMG das linhas A, C, GLA e GLC, além dos modelos de gerações anteriores. Fale com a gente "
         "pelo WhatsApp informando modelo e ano para confirmarmos a disponibilidade de peças."),
    ),
    # ------------------------------------------------------------------ AUDI
    marca(
        "oficina-audi", "Audi", "Audi", "Manutenção, diagnóstico e reparo de veículos Audi",
        "Oficina especializada em Audi, com diagnóstico completo da eletrônica, peças genuínas e relatório técnico em "
        "cada entrega. A3, A4, A5, Q3, Q5, Q7 e os esportivos S e RS cuidados por quem trabalha só com carros premium.",
        "Os Audi têm motores TFSI turbo com injeção direta, câmbios S tronic e tiptronic e tração quattro: tecnologia "
        "que entrega desempenho e pede manutenção precisa. Óleo fora da especificação, fluido do câmbio vencido ou um "
        "diagnóstico apressado custam caro depois. Na Veloce, o seu Audi é diagnosticado com scanner, orçado item por "
        "item e entregue com relatório técnico.",
        [("Diagnóstico de verdade", "Leitura completa das centrais (motor, câmbio, quattro, freios, conforto) para "
          "achar a origem do defeito."),
         ("Peças genuínas", "Usamos somente peças genuínas Audi, com procedência informada no orçamento."),
         ("Relatório técnico", "Registro do que foi encontrado, do que foi trocado e do que acompanhar nas próximas revisões."),
         ("Orçamento sem surpresa", "Aprovação item por item, sem serviço feito sem o seu \"pode\".")],
        ["A1", "A3 Sportback / Sedan", "A4 / A4 Avant", "A5 Sportback", "A6", "Q3", "Q5", "Q7", "Q8", "TT",
         "S3 / S4 / S5", "RS3 / RS4 / RS5 / RS6 / RS Q3"],
        ["Atendemos a linha Audi vendida no Brasil, atual e de gerações anteriores:",
         f"Os Audi dividem motores e componentes com outras marcas do grupo, como {LINK['Porsche']} e "
         f"{LINK['Lamborghini']}, que também atendemos."],
        servicos_marca("Audi",
            "Revisão preventiva conforme o plano da montadora: óleo com a especificação VW/Audi correta, filtros, velas, "
            "fluido de freio e inspeção completa.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois. Muito procurada para "
            "A3, S3, A4 e Q3.",
            ("Câmbio S tronic", "Troca de óleo do câmbio no intervalo correto e diagnóstico de trancos pela leitura "
             "da central do câmbio.")),
        "Os defeitos que mais chegam à oficina nos Audi com motor TFSI. Nenhum é motivo para pânico se tratado cedo.",
        [("Consumo de óleo", "Algumas gerações do motor 2.0 TFSI (EA888) ficaram conhecidas por consumir óleo. Medir "
          "o consumo e revisar o sistema de respiro do motor vem antes de qualquer reparo grande."),
         ("Vazamento na bomba d'água", "A carcaça plástica da bomba d'água e do termostato pode trincar com o calor. "
          "Cheiro adocicado e nível de água baixando são os sinais."),
         ("Corrente e tensor", "Ruído metálico de poucos segundos na partida a frio pede verificação do tensor da "
          "corrente de comando, principalmente nos motores mais antigos."),
         ("Câmbio com trancos", "No S tronic, solavancos em baixa velocidade e trocas hesitantes pedem diagnóstico e "
          "troca do fluido antes de se pensar na mecatrônica."),
         ("Falhas de ignição", "Motor tremendo e luz de injeção piscando costumam ser bobinas ou velas. A carbonização "
          "das válvulas, comum na injeção direta, também entra no diagnóstico."),
         ("Vazamentos de óleo", "Juntas da tampa de válvulas, do cárter e do filtro de óleo ressecam com o tempo. "
          "Tratar cedo evita contaminar correias e coxins.")],
        ("Vocês atendem Audi S e RS?",
         "Sim. Atendemos as versões esportivas S e RS e os modelos de gerações anteriores. Fale com a gente pelo "
         "WhatsApp informando modelo e ano para confirmarmos a disponibilidade de peças."),
    ),
    # ------------------------------------------------------------------ PORSCHE
    marca(
        "oficina-porsche", "Porsche", "Porsche", "Manutenção, diagnóstico e reparo de veículos Porsche",
        "Oficina especializada em Porsche em São Paulo, com diagnóstico completo da eletrônica, peças genuínas e "
        "relatório técnico em cada entrega. Macan, Cayenne, Panamera, 911 e 718 cuidados com o rigor que a marca exige.",
        "Um Porsche não pede só manutenção: pede critério. Câmbio PDK, suspensão ativa, motores de alto desempenho e uma "
        "eletrônica que registra tudo exigem diagnóstico preciso e peça certa. A Veloce trabalha exclusivamente com "
        "carros premium, com transparência total: você aprova cada item do orçamento e recebe um relatório técnico na "
        "entrega.",
        [("Diagnóstico de verdade", "Leitura completa das centrais (motor, PDK, PASM, suspensão a ar, conforto) para "
          "achar a causa real do defeito."),
         ("Peças genuínas", "Usamos somente peças genuínas Porsche, com procedência informada antes da aprovação."),
         ("Relatório técnico", "O que foi feito, o que foi trocado e o que acompanhar, registrado a cada entrega."),
         ("Orçamento sem surpresa", "Aprovação item por item. Se aparecer algo novo, avisamos antes de fazer.")],
        ["Macan / Macan S / GTS", "Cayenne / Cayenne Coupé", "Panamera", "911 (Carrera, Targa, Turbo)",
         "718 Boxster", "718 Cayman", "Cayenne e 911 de gerações anteriores"],
        ["Atendemos a linha Porsche vendida no Brasil, atual e de gerações anteriores:",
         f"O Macan e o Cayenne dividem plataforma e componentes com modelos da {LINK['Audi']}, que também atendemos."],
        servicos_marca("Porsche",
            "Revisão preventiva conforme o plano da Porsche: óleo com a especificação aprovada pela Porsche "
            "para o seu motor, filtros, velas, fluido de freio e inspeção completa.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois e responsabilidade técnica, "
            "muito procurada para Macan e Cayenne.",
            ("Câmbio PDK", "Troca de fluido no intervalo correto e diagnóstico de trancos pela leitura da central do câmbio.")),
        "Os pontos que mais pedem atenção nos Porsche. Prevenção sai muito mais barato que reparo.",
        [("Arrefecimento", "Em alguns Cayenne V8 de gerações anteriores, os tubos de arrefecimento sob o coletor são "
          "um ponto conhecido de vazamento. Nos modelos atuais, termostato e bomba d'água merecem atenção."),
         ("Suspensão a ar", "Cayenne, Panamera e Macan com suspensão a ar podem perder altura por vazamentos nas "
          "bolsas ou nas linhas. Tratar cedo protege o compressor."),
         ("Fluido do PDK", "O PDK é robusto, mas o fluido tem prazo de troca. Trancos em manobra e trocas hesitantes "
          "pedem diagnóstico antes de qualquer reparo maior."),
         ("Rolamento IMS (911 e Boxster antigos)", "Nos 911 (996/997) e Boxster com motores M96/M97, o rolamento do "
          "eixo intermediário é um ponto conhecido. A inspeção e a avaliação de troca preventiva valem a pena."),
         ("Vazamentos de óleo", "Retentores e juntas de tampa de válvulas ressecam com o calor. Gotejamento no chão "
          "da garagem é o primeiro aviso."),
         ("Freios", "Discos e pastilhas de alto desempenho se desgastam de forma diferente. Peças genuínas e sensores "
          "de desgaste corretos mantêm a frenagem como saiu de fábrica.")],
        ("Vocês atendem Porsche antigo ou clássico?",
         "Atendemos os modelos atuais e as gerações anteriores de Cayenne, 911, Boxster e Cayman. Para clássicos, fale "
         "com a gente pelo WhatsApp informando modelo e ano para avaliarmos o serviço."),
    ),
    # ------------------------------------------------------------------ JAGUAR
    marca(
        "oficina-jaguar", "Jaguar", "Jaguar", "Manutenção, diagnóstico e reparo de veículos Jaguar",
        "Oficina especializada em Jaguar, com diagnóstico completo da eletrônica, peças genuínas e relatório técnico em "
        "cada entrega. F-Pace, E-Pace, XE, XF e F-Type cuidados por quem trabalha só com carros premium.",
        "Encontrar quem entenda de Jaguar fora da concessionária não é simples. São carros com motores Ingenium, "
        "eletrônica sofisticada e muitas peças em comum com a Land Rover, o que exige conhecimento das duas marcas. Na "
        "Veloce, o seu Jaguar passa por leitura completa das centrais, recebe orçamento detalhado e sai com relatório "
        "técnico do que foi feito.",
        [("Diagnóstico de verdade", "Leitura completa das centrais (motor, câmbio, freios, conforto) para achar a causa "
          "real do defeito."),
         ("Peças genuínas", "Usamos somente peças genuínas Jaguar, com procedência informada no orçamento."),
         ("Relatório técnico", "Registro do que foi encontrado, do que foi trocado e do que observar nas próximas revisões."),
         ("Orçamento sem surpresa", "Você aprova item por item. Nada é feito sem o seu \"pode\".")],
        ["F-Pace", "E-Pace", "XE", "XF", "F-Type", "XJ", "modelos de gerações anteriores"],
        ["Atendemos a linha Jaguar vendida no Brasil, atual e de gerações anteriores:",
         f"A Jaguar divide motores e eletrônica com a {LINK['Land Rover']}, que também atendemos na mesma oficina."],
        servicos_marca("Jaguar",
            "Revisão preventiva conforme o plano da montadora: óleo com a especificação correta para o seu motor, "
            "filtros, velas, fluidos e inspeção completa.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois e responsabilidade técnica.",
            ("Freios e suspensão", "Discos, pastilhas, buchas e amortecedores com peças genuínas, preservando o "
             "comportamento esportivo do carro.")),
        "Os defeitos que mais aparecem nos Jaguar recentes. Detectados cedo, têm reparo mais simples.",
        [("Arrefecimento", "Conexões e mangueiras plásticas, termostato e bomba d'água são pontos de atenção nos "
          "motores Ingenium. Aviso de temperatura exige parar o carro."),
         ("Vazamentos de óleo", "Juntas de tampa de válvulas e retentores ressecam com o calor. O cheiro de queimado "
          "depois de rodar é o primeiro sinal."),
         ("Diesel e filtro de partículas", "Nas versões diesel, uso só urbano satura o filtro de partículas. Aviso no "
          "painel e perda de potência pedem diagnóstico logo."),
         ("Câmbio com trancos", "Trocas duras no câmbio automático costumam melhorar com a troca do fluido no "
          "intervalo certo."),
         ("Avisos elétricos", "Bateria fraca gera avisos em vários sistemas ao mesmo tempo. O diagnóstico separa "
          "defeito real de tensão baixa."),
         ("Ruídos na suspensão", "Batidas e estalos em lombadas geralmente são buchas e bieletas. Revisar cedo "
          "preserva a dirigibilidade e os pneus.")],
        ("Vocês atendem Jaguar antigo?",
         "Sim. Atendemos os modelos atuais e de gerações anteriores, como XF e XJ mais antigos. Fale com a gente pelo "
         "WhatsApp informando modelo e ano para confirmarmos a disponibilidade de peças."),
    ),
    # ------------------------------------------------------------------ MINI
    marca(
        "oficina-mini", "MINI", "MINI", "Manutenção, diagnóstico e reparo de veículos MINI",
        "Oficina especializada em MINI, com diagnóstico completo da eletrônica, peças genuínas e relatório técnico em "
        "cada entrega. Cooper, Cooper S, Countryman e John Cooper Works cuidados por quem conhece a mecânica BMW por dentro.",
        "O MINI é pequeno por fora e BMW por dentro: motores turbo, eletrônica do grupo BMW e componentes compartilhados "
        "com a marca alemã. Por isso a manutenção pede o mesmo nível de diagnóstico de uma BMW. Na Veloce, o seu MINI é "
        "diagnosticado com scanner, orçado peça por peça e entregue com relatório técnico.",
        [("Diagnóstico de verdade", "Leitura completa das centrais para achar a causa real do defeito, e não só apagar "
          "a luz do painel."),
         ("Peças genuínas", "Usamos somente peças genuínas MINI, com procedência informada no orçamento."),
         ("Relatório técnico", "O que foi encontrado, o que foi trocado e o que observar nas próximas revisões."),
         ("Orçamento sem surpresa", "Aprovação item por item. Se aparecer algo novo, avisamos antes de fazer.")],
        ["Cooper", "Cooper S", "John Cooper Works", "Countryman", "Clubman", "Cabrio", "Paceman",
         "gerações R56 / R60 e F55 / F56 / F60"],
        ["Atendemos a linha MINI vendida no Brasil, das gerações mais antigas às atuais:",
         f"Como o MINI é do grupo BMW, ele usa motores e eletrônica da {LINK['BMW']}, que também atendemos."],
        servicos_marca("MINI",
            "Revisão preventiva conforme o plano da montadora: óleo com a especificação BMW Longlife, filtros, velas e "
            "fluido de freio.",
            "Reprogramação de motor feita aqui na oficina, com leitura da ECU antes e depois, muito procurada para "
            "Cooper S e JCW.",
            ("Arrefecimento", "Bomba d'água, termostato e mangueiras com peças genuínas, um dos pontos que mais pedem "
             "atenção nos MINI.")),
        "Os defeitos que mais chegam à oficina nos MINI. Quase todos têm conserto simples quando tratados cedo.",
        [("Corrente de comando (geração R56)", "Nos MINI com motor 1.6 da geração R56, um ruído de \"chocalho\" na "
          "partida a frio pode indicar desgaste da corrente e do tensor. É reparo para fazer logo."),
         ("Consumo de óleo", "Motores turbo mais antigos podem consumir óleo. Medir o nível com frequência e revisar o "
          "respiro do motor evita danos maiores."),
         ("Vazamentos de óleo", "A junta da tampa de válvulas e a do suporte do filtro de óleo ressecam com o tempo. "
          "Cheiro de queimado depois de rodar é o primeiro aviso."),
         ("Superaquecimento", "Bomba d'água e termostato são pontos conhecidos. Aviso de temperatura exige parar e "
          "diagnosticar."),
         ("Carbonização", "Na injeção direta, as válvulas acumulam carvão com o tempo e o motor perde suavidade. A "
          "limpeza entra no diagnóstico de falhas de marcha lenta."),
         ("Suspensão e direção", "Estalos em lombadas costumam ser bieletas e buchas. Revisar cedo preserva o "
          "\"go-kart feeling\" do MINI.")],
        ("Vocês atendem MINI antigo?",
         "Sim. Atendemos desde as gerações R50/R56 até os MINI atuais. Fale com a gente pelo WhatsApp informando modelo "
         "e ano para confirmarmos a disponibilidade de peças."),
    ),
    # ------------------------------------------------------------------ LAMBORGHINI
    marca(
        "oficina-lamborghini", "Lamborghini", "Lamborghini", "Manutenção e diagnóstico de veículos Lamborghini",
        "Manutenção de Lamborghini em São Paulo com diagnóstico completo, peças genuínas e relatório técnico em cada "
        "entrega. Urus, Huracán e Aventador recebidos com o cuidado e a discrição que um superesportivo exige.",
        "Um Lamborghini roda pouco, mas cada quilômetro conta. Fluidos vencem por tempo e não só por quilometragem, "
        "pneus e baterias sofrem com o carro parado, e qualquer serviço precisa de critério. A Veloce trabalha "
        "exclusivamente com carros premium e trata o seu Lamborghini com o mesmo método de sempre: diagnóstico antes do "
        "orçamento, orçamento aprovado item por item e relatório técnico na entrega.",
        [("Diagnóstico completo", "Leitura das centrais e inspeção física detalhada antes de qualquer serviço."),
         ("Peças genuínas", "Usamos somente peças genuínas Lamborghini, com procedência informada no orçamento."),
         ("Relatório técnico", "Registro do que foi encontrado, do que foi feito e do que acompanhar, a cada entrega."),
         ("Discrição e cuidado", "Seu carro é tratado com o cuidado de quem trabalha só com veículos de alta gama.")],
        ["Urus", "Huracán", "Aventador", "Gallardo"],
        ["Atendemos os modelos Lamborghini que rodam no Brasil:",
         f"O Urus divide plataforma e boa parte da mecânica com o Porsche Cayenne e o Audi Q8. Veja também as nossas "
         f"páginas de {LINK['Porsche']} e {LINK['Audi']}."],
        servicos_marca("Lamborghini",
            "Revisão preventiva conforme o plano da montadora, com atenção aos fluidos que vencem por tempo em carros "
            "que rodam pouco.",
            "Projetos de performance avaliados com responsabilidade técnica e leitura da ECU antes e depois.",
            ("Carro parado", "Checagem de bateria, pneus, fluidos e vazamentos para quem usa o carro só nos fins de "
             "semana ou em eventos.")),
        "Os pontos que mais pedem atenção num superesportivo usado com pouca frequência.",
        [("Fluidos vencidos", "Óleo, fluido de freio e de arrefecimento envelhecem mesmo com pouco uso. Respeitar o "
          "prazo por tempo é tão importante quanto a quilometragem."),
         ("Bateria descarregada", "Carros que ficam parados descarregam a bateria e geram avisos eletrônicos. Um "
          "mantenedor de carga e a checagem periódica evitam o problema."),
         ("Pneus ressecados", "Pneus de alto desempenho envelhecem mesmo com sulco bom. A data de fabricação entra na "
          "inspeção."),
         ("Freios", "Discos e pastilhas de alto desempenho, inclusive carbono-cerâmicos, exigem avaliação específica "
          "de desgaste."),
         ("Vazamentos", "Retentores e juntas ressecam com o carro parado. A inspeção por baixo faz parte de toda revisão."),
         ("Suspensão e altura", "Sistemas de elevação de dianteira e amortecedores ativos merecem checagem para "
          "evitar danos em rampas e lombadas.")],
        ("Vocês buscam o carro?",
         "Fale com a gente pelo WhatsApp informando o modelo e onde o carro está para combinarmos a melhor forma de "
         "atendimento."),
    ),
]

DESCRICOES = {
    "oficina-land-rover": "Oficina especializada em Land Rover e Range Rover na Zona Norte de SP: suspensão a ar, diagnóstico, revisão e peças genuínas. Agende.",
    "oficina-volvo": "Oficina especializada em Volvo na Casa Verde, Zona Norte de SP: diagnóstico, revisão, reparos e peças genuínas Volvo. Relatório técnico.",
    "oficina-mercedes-benz": "Oficina especializada em Mercedes-Benz na Zona Norte de SP: diagnóstico, Service A e B, AMG e peças genuínas. Relatório técnico.",
    "oficina-audi": "Oficina especializada em Audi na Casa Verde, Zona Norte de SP: diagnóstico, revisão, S tronic, remap e peças genuínas. Agende.",
    "oficina-porsche": "Oficina especializada em Porsche em São Paulo: Macan, Cayenne, Panamera, 911 e 718. Diagnóstico, PDK, revisão e peças genuínas.",
    "oficina-jaguar": "Oficina especializada em Jaguar em São Paulo: F-Pace, E-Pace, XE, XF e F-Type. Diagnóstico, revisão e peças genuínas. Agende.",
    "oficina-mini": "Oficina especializada em MINI Cooper na Zona Norte de SP: diagnóstico, revisão, corrente de comando, remap e peças genuínas.",
    "oficina-lamborghini": "Manutenção de Lamborghini em São Paulo: Urus, Huracán e Aventador. Diagnóstico, revisão e peças genuínas com relatório técnico.",
}
for _p in PAGINAS:
    _p["description"] = DESCRICOES[_p["slug"]]

from paginas_servicos import SERVICOS  # noqa: E402

PAGINAS += SERVICOS

# Land Rover: o card de revisão aponta para a subpágina /oficina-land-rover/revisao/
for _p in PAGINAS:
    if _p["slug"] == "oficina-land-rover":
        _t, _d = _p["secoes"][2]["cards"][0]
        _p["secoes"][2]["cards"][0] = (_t, _d.replace(f"Ver {PREVENTIVA}.", 'Ver <a href="/oficina-land-rover/revisao/">revisão Land Rover</a>.'))

from paginas_conteudo import CONTEUDO  # noqa: E402

PAGINAS += CONTEUDO

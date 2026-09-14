#!/usr/bin/env python3
"""
Google Ads - Script de criação automática de campanha SKAG
Lê as configurações de perfil.json para decidir se cria uma campanha
Nacional de Infoproduto/Curso ou uma campanha de Proximidade Local.
"""

import json
import os
import sys
import time

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)
# Adiciona ao path para importar a lib
sys.path.insert(0, SCRIPT_DIR)
from lib import init_client, resolve_customer_id, print_error, safe_delay
PROFILE_PATH = os.path.join(PROJECT_ROOT, "perfil.json")

def load_profile():
    """Le o perfil do negocio. Gere o seu com: python3 scripts/setup_perfil.py"""
    if not os.path.isfile(PROFILE_PATH):
        print_error("Perfil do negócio não encontrado. Por favor, rode primeiro: python scripts/setup_perfil.py")
        sys.exit(1)
    with open(PROFILE_PATH, "r", encoding="utf-8") as f:
        return json.load(f)

def get_country_constant(country_name):
    # Dicionário simplificado de países comuns. Em produção, buscar via API ou expandir.
    mapping = {
        "brasil": "geoTargetConstants/2076",
        "brazil": "geoTargetConstants/2076",
        "portugal": "geoTargetConstants/2620",
        "angola": "geoTargetConstants/2024",
        "mocambique": "geoTargetConstants/2508",
    }
    return mapping.get(country_name.lower(), "geoTargetConstants/2076")

def main():
    profile = load_profile()
    
    print("=" * 60)
    print("  CRIADOR DE CAMPANHA SKAG AUTOMATIZADA")
    print("=" * 60)
    print(f"Perfil carregado: {profile['tipo_negocio'].upper()} em {profile['pais']}")
    print()

    # Perguntar Keyword
    keyword = input(f"Digite a palavra-chave exata da SKAG [{profile['nicho']}]: ").strip()
    if not keyword:
        keyword = profile["nicho"]

    # Perguntar Orçamento Diário em reais
    while True:
        try:
            budget_reais = float(input("Digite o orçamento diário em R$ [50.00]: ").strip() or "50.00")
            break
        except ValueError:
            print("Digite um valor numérico válido.")

    # Confirmar URL Final
    final_url = input(f"Confirme a URL final do anúncio [{profile['site_url']}]: ").strip()
    if not final_url:
        final_url = profile["site_url"]

    # Iniciar API Client
    client = init_client()
    customer_id = resolve_customer_id()

    campaign_service = client.get_service("CampaignService")
    campaign_budget_service = client.get_service("CampaignBudgetService")

    # 1. Criar Orçamento
    print("\n1/6 Criando orçamento da campanha...")
    budget_operation = client.get_type("CampaignBudgetOperation")
    budget = budget_operation.create
    budget.name = f"Orçamento-SKAG-{keyword.replace(' ', '-')}-{int(time.time())}"
    # Google Ads API usa micro centavos (micros) para moedas
    budget.amount_micros = int(budget_reais * 1_000_000)
    budget.explicitly_shared = False

    budget_response = campaign_budget_service.mutate_campaign_budgets(
        customer_id=customer_id, operations=[budget_operation]
    )
    budget_resource = budget_response.results[0].resource_name
    print(f"  ✓ Orçamento criado: {budget_resource}")
    safe_delay()

    # 2. Criar Campanha
    print("\n2/6 Criando campanha...")
    campaign_operation = client.get_type("CampaignOperation")
    campaign = campaign_operation.create
    campaign.name = f"Pesquisa-SKAG-{keyword.title()}-{int(time.time())}"
    campaign.campaign_budget = budget_resource
    campaign.status = client.enums.CampaignStatusEnum.PAUSED
    campaign.advertising_channel_type = client.enums.AdvertisingChannelTypeEnum.SEARCH
    campaign.contains_eu_political_advertising = 3 # DOES_NOT_CONTAIN_EU_POLITICAL_ADVERTISING

    # Configurações de rede recomendadas (Apenas Rede de Pesquisa, sem parceiros/display)
    campaign.network_settings.target_google_search = True
    campaign.network_settings.target_search_network = False
    campaign.network_settings.target_content_network = False
    campaign.network_settings.target_partner_search_network = False

    # Lance Inteligente
    campaign.maximize_conversions.target_cpa_micros = 0

    campaign_response = campaign_service.mutate_campaigns(
        customer_id=customer_id, operations=[campaign_operation]
    )
    campaign_resource = campaign_response.results[0].resource_name
    campaign_id = campaign_resource.split("/")[-1]
    print(f"  ✓ Campanha criada: {campaign_resource}")
    safe_delay()

    # 3. Geolocalização
    print("\n3/6 Configurando geolocalização...")
    criterion_service = client.get_service("CampaignCriterionService")
    geo_operations = []

    if profile["tipo_negocio"] == "curso":
        # Segmentação por país
        country_const = get_country_constant(profile["pais"])
        op = client.get_type("CampaignCriterionOperation")
        c = op.create
        c.campaign = campaign_resource
        c.location.geo_target_constant = country_const
        geo_operations.append(op)
    else:
        # Segmentação local por proximidade/raio
        # Nota: Raio exige coordenadas ou constante geográfica. Para simplificar,
        # adicionamos a geolocalização padrão do país, cabendo ao usuário refinar no painel se necessário.
        country_const = get_country_constant(profile["pais"])
        op = client.get_type("CampaignCriterionOperation")
        c = op.create
        c.campaign = campaign_resource
        c.location.geo_target_constant = country_const
        geo_operations.append(op)

    criterion_service.mutate_campaign_criteria(
        customer_id=customer_id, operations=geo_operations
    )
    print("  ✓ Geolocalização configurada")
    safe_delay()

    # 4. Negativas Universais
    print("\n4/6 Adicionando 15 negativas universais...")
    negativas = ["gratis", "gratuito", "emprego", "vagas", "salario", "trabalho", "estagio", 
                 "como fazer sozinho", "pdf download", "torrent", "crack", "login", "area do aluno", 
                 "suporte hotmart", "reclame aqui"]
    
    neg_operations = []
    for neg in negativas:
        op = client.get_type("CampaignCriterionOperation")
        c = op.create
        c.campaign = campaign_resource
        c.negative = True
        c.keyword.text = neg
        c.keyword.match_type = client.enums.KeywordMatchTypeEnum.PHRASE
        neg_operations.append(op)

    criterion_service.mutate_campaign_criteria(
        customer_id=customer_id, operations=neg_operations
    )
    print(f"  ✓ {len(negativas)} palavras-chave negativas adicionadas à campanha.")
    safe_delay()

    # 5. Criar Grupo de Anúncios
    print("\n5/6 Criando grupo de anúncios...")
    ad_group_service = client.get_service("AdGroupService")
    ad_group_operation = client.get_type("AdGroupOperation")
    ad_group = ad_group_operation.create
    ad_group.name = f"SKAG - {keyword.title()}"
    ad_group.campaign = campaign_resource
    ad_group.status = client.enums.AdGroupStatusEnum.PAUSED
    ad_group.type_ = client.enums.AdGroupTypeEnum.SEARCH_STANDARD

    ad_group_response = ad_group_service.mutate_ad_groups(
        customer_id=customer_id, operations=[ad_group_operation]
    )
    ad_group_resource = ad_group_response.results[0].resource_name
    print(f"  ✓ Grupo de anúncios criado: {ad_group_resource}")
    safe_delay()

    # Adicionar a palavra-chave no grupo em correspondência de frase
    criterion_service = client.get_service("AdGroupCriterionService")
    keyword_operation = client.get_type("AdGroupCriterionOperation")
    kw = keyword_operation.create
    kw.ad_group = ad_group_resource
    kw.status = client.enums.AdGroupCriterionStatusEnum.ENABLED
    kw.keyword.text = keyword
    kw.keyword.match_type = client.enums.KeywordMatchTypeEnum.PHRASE

    criterion_service.mutate_ad_group_criteria(
        customer_id=customer_id, operations=[keyword_operation]
    )
    print(f"  ✓ Palavra-chave em correspondência de frase adicionada: \"{keyword}\"")
    safe_delay()

    # 6. Criar Anúncios Responsivos (RSAs)
    print("\n6/6 Criando anúncios responsivos de pesquisa...")
    ad_group_ad_service = client.get_service("AdGroupAdService")
    ad_group_ad_operation = client.get_type("AdGroupAdOperation")
    ad_group_ad = ad_group_ad_operation.create
    ad_group_ad.ad_group = ad_group_resource
    ad_group_ad.status = client.enums.AdGroupAdStatusEnum.PAUSED

    ad = ad_group_ad.ad
    ad.final_urls.append(final_url)

    # Definir títulos
    custom_hl = profile.get("custom_headlines", [])
    if custom_hl:
        # Começa com as de palavra-chave fixadas na pos 1
        headlines_text = [
            (keyword.title()[:30], 1),
            (f"Aprenda {keyword.title()}"[:30], 1),
            (f"{keyword.title()} Online"[:30], 1)
        ]
        # Adiciona as demais do hub (sem fixar)
        for h in custom_hl:
            # Evita duplicar a palavra-chave se já adicionou
            if h.lower() not in [keyword.lower(), f"aprenda {keyword.lower()}", f"{keyword.lower()} online"]:
                headlines_text.append((h[:30], None))
    else:
        headlines_text = [
            # Keywords no Slot 1 (Fixados na Posição 1)
            (keyword.title()[:30], 1),
            (f"Aprenda {keyword.title()}"[:30], 1),
            (f"{keyword.title()} Online"[:30], 1),
            # Ofertas e Benefícios (Sem fixar)
            ("Acesso Vitalício Hoje", None),
            ("Certificado Incluso", None),
            ("7 Dias de Garantia Total", None),
            ("Aulas Práticas do Zero", None),
            ("Aprenda com Projetos Reais", None),
            ("Matrículas Abertas", None),
            ("Assista à Aula Grátis", None),
            ("Suporte Ativo aos Alunos", None),
            ("Sem Mensalidades Ocultas", None),
            ("Acesso Imediato", None),
            ("Melhores Práticas 2026", None),
            ("Inscreva-se Hoje", None)
        ]

    for text, pin_pos in headlines_text[:15]: # Limite máximo de 15 do Google Ads
        headline = client.get_type("AdTextAsset")
        headline.text = text
        if pin_pos == 1:
            headline.pinned_field = client.enums.ServedAssetFieldTypeEnum.HEADLINE_1
        ad.responsive_search_ad.headlines.append(headline)

    # Definir descrições
    custom_desc = profile.get("custom_descriptions", [])
    if custom_desc:
        descriptions_text = custom_desc
    else:
        descriptions_text = [
            "Aprenda programação web do zero ao profissional. Certificado incluso e suporte ativo.",
            "Comece a estudar hoje com acesso vitalício. Mais de 10.000 alunos formados. Garanta sua vaga.",
            "Sem mensalidades ocultas. 7 dias de garantia incondicional para você testar sem riscos.",
            "Assista às aulas práticas e desenvolva seus projetos. Inscreva-se agora e ganhe os bônus."
        ]

    for desc_text in descriptions_text[:4]: # Limite máximo de 4 do Google Ads
        desc = client.get_type("AdTextAsset")
        desc.text = desc_text
        ad.responsive_search_ad.descriptions.append(desc)

    # URLs amigáveis
    ad.responsive_search_ad.path1 = "curso"
    ad.responsive_search_ad.path2 = keyword.split()[0][:15]

    ad_group_ad_service.mutate_ad_group_ads(
        customer_id=customer_id, operations=[ad_group_ad_operation]
    )
    print("  ✓ Anúncio responsivo de pesquisa criado e associado ao grupo.")

    print("\n" + "=" * 60)
    print("  PROCESSO CONCLUÍDO COM SUCESSO!")
    print("=" * 60)
    print(f"Campanha criada e PAUSADA: ID {campaign_id}")
    print("Acesse o painel do Google Ads para revisar e ativar.")

if __name__ == "__main__":
    main()

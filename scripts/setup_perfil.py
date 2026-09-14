#!/usr/bin/env python3
"""
Google Ads - Setup interativo de perfil do negócio
Pergunta se é um negócio local ou infoproduto/curso online,
e salva as preferências para guiar a criação de campanhas SKAG e anúncios.
"""

import json
import os
import sys

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)
PROFILE_PATH = os.path.join(PROJECT_ROOT, "perfil.json")

def prompt(question, default=""):
    suffix = f" [{default}]" if default else ""
    val = input(f"{question}{suffix}: ").strip()
    return val if val else default

def main():
    print("=" * 60)
    print("  SETUP DE PERFIL DO NEGÓCIO - GOOGLE ADS")
    print("=" * 60)
    print("Este setup define os padrões de geolocalização e copies.")
    print()

    # 1. Tipo de negócio
    while True:
        tipo = prompt("Tipo do negócio (1 - Curso/Infoproduto online, 2 - Serviço Local)", "1")
        if tipo in ("1", "curso", "infoproduto"):
            tipo_negocio = "curso"
            break
        elif tipo in ("2", "local"):
            tipo_negocio = "local"
            break
        print("Opção inválida. Digite 1 ou 2.")

    # 2. País
    pais = prompt("País de segmentação principal", "Brasil")

    # 3. Idiomas
    idiomas_str = prompt("Idiomas (separados por vírgula)", "Português")
    idiomas = [i.strip() for i in idiomas_str.split(",") if i.strip()]

    # 4. URL do site
    site_url = prompt("URL da Landing Page / Página de Vendas", "https://meusite.com.br")

    # 5. Nicho/Produto
    nicho = prompt("Nicho ou Nome do Produto/Serviço", "curso de programacao")

    # Dados adicionais se for local
    cidade = ""
    raio_km = 50
    if tipo_negocio == "local":
        cidade = prompt("Cidade de atuação (Ex: Porto Alegre)", "Porto Alegre")
        while True:
            try:
                raio_km = int(prompt("Raio de alcance em km", "50"))
                break
            except ValueError:
                print("Por favor, digite um número inteiro.")

    profile = {
        "tipo_negocio": tipo_negocio,
        "pais": pais,
        "idiomas": idiomas,
        "site_url": site_url,
        "nicho": nicho
    }
    if tipo_negocio == "local":
        profile["cidade"] = cidade
        profile["raio_km"] = raio_km

    # Salva no arquivo JSON
    with open(PROFILE_PATH, "w", encoding="utf-8") as f:
        json.dump(profile, f, indent=2, ensure_ascii=False)

def main():
    import setup_hub
    setup_hub.main()

if __name__ == "__main__":
    main()

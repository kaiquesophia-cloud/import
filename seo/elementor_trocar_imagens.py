#!/usr/bin/env python3
"""Troca imagens pesadas pelas versões WebP dentro do _elementor_data de uma página ou template.

Uso: python3 seo/elementor_trocar_imagens.py <tipo:pages|elementor_library> <id> <mapa.json>
O mapa é {id_antigo: {"id": id_novo, "url": url_nova}}. Faz cópia do JSON original antes.
"""
import json
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
import wp_publicar as w  # noqa: E402

tipo, pid, mapa_arq = sys.argv[1], int(sys.argv[2]), sys.argv[3]
mapa = {int(k): v for k, v in json.load(open(mapa_arq)).items()}
antigos = {}
for old in mapa:
    m = w._req("GET", f"media/{old}?_fields=source_url")
    base = re.sub(r"\.(png|jpe?g)$", "", m["source_url"])
    base = re.sub(r"-scaled$", "", base)
    antigos[old] = base

doc = w._req("GET", f"{tipo}/{pid}?context=edit&_fields=meta")
bruto = doc["meta"]["_elementor_data"]
dados = json.loads(bruto) if isinstance(bruto, str) else bruto
os.makedirs("seo/veloce-auto-specialista/backup-elementor", exist_ok=True)
with open(f"seo/veloce-auto-specialista/backup-elementor/{pid}-antes-webp.json", "w") as f:
    json.dump(dados, f)

trocas = 0


def novo_para(url):
    for old, base in antigos.items():
        if isinstance(url, str) and url.startswith(base) and re.search(r"\.(png|jpe?g)$", url):
            return old
    return None


def andar(x):
    global trocas
    if isinstance(x, dict):
        if "url" in x and novo_para(x["url"]) is not None:
            old = novo_para(x["url"])
            x["url"], x["id"] = mapa[old]["url"], mapa[old]["id"]
            x.pop("size", None)
            trocas += 1
        for k, v in list(x.items()):
            if isinstance(v, str) and novo_para(v) is not None:  # ex.: image_external_url
                x[k] = mapa[novo_para(v)]["url"]
                trocas += 1
            else:
                andar(v)
    elif isinstance(x, list):
        for v in x:
            andar(v)


andar(dados)
resto = [u for u in re.findall(r"https?://[^\"']+\.(?:png|jpe?g)", json.dumps(dados)) if novo_para(u.replace("\\/", "/"))]
print(f"{tipo} {pid}: {trocas} trocas; referências antigas que sobraram: {len(resto)}")
if trocas and not resto:
    w._req("POST", f"{tipo}/{pid}", {"meta": {"_elementor_data": json.dumps(dados, ensure_ascii=False)}})
    print("salvo")

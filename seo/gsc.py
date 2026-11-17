#!/usr/bin/env python3
"""Lê o Google Search Console pela API oficial (só leitura), com uma conta de serviço.

A chave JSON da conta de serviço NUNCA entra no repositório: passe o caminho por variável de ambiente.
  GSC_KEY_FILE   caminho do JSON da conta de serviço
A conta de serviço (client_email do JSON) precisa estar em Search Console → Configurações →
Usuários e permissões, com permissão Restrita (basta para ler) ou Total.

Uso:
  python3 seo/gsc.py sites
  python3 seo/gsc.py query --site sc-domain:exemplo.com.br --inicio 2026-10-18 --fim 2026-11-15 \
      [--dim page|query|date|device|country ...] [--limite 1000] [--csv saida.csv]
"""
import argparse
import csv
import json
import os
import sys
import urllib.parse
import urllib.request

ESCOPO = "https://www.googleapis.com/auth/webmasters.readonly"
API = "https://searchconsole.googleapis.com/webmasters/v3"


def _token():
    try:
        from google.oauth2 import service_account
        from google.auth.transport.requests import Request
    except ImportError:
        sys.exit("Falta a biblioteca: pip3 install google-auth requests")
    caminho = os.environ.get("GSC_KEY_FILE")
    if not caminho or not os.path.exists(caminho):
        sys.exit("Defina GSC_KEY_FILE com o caminho do JSON da conta de serviço.")
    cred = service_account.Credentials.from_service_account_file(caminho, scopes=[ESCOPO])
    cred.refresh(Request())
    return cred.token, cred.service_account_email


def _req(metodo, caminho, corpo=None):
    token, _ = _token()
    req = urllib.request.Request(f"{API}/{caminho}", method=metodo,
                                 data=json.dumps(corpo).encode() if corpo is not None else None,
                                 headers={"Authorization": f"Bearer {token}", "Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        sys.exit(f"HTTP {e.code}: {e.read().decode(errors='replace')[:600]}")


def sites(_):
    _, email = _token()
    print(f"Conta de serviço: {email}")
    lista = _req("GET", "sites").get("siteEntry", [])
    if not lista:
        print("Nenhum site. Adicione a conta de serviço em Search Console → Configurações → Usuários e permissões.")
    for s in lista:
        print(f"{s['siteUrl']}\t{s['permissionLevel']}")


def query(a):
    corpo = {"startDate": a.inicio, "endDate": a.fim, "dimensions": a.dim or [], "rowLimit": a.limite,
             "dataState": "all"}
    site = urllib.parse.quote(a.site, safe="")
    r = _req("POST", f"sites/{site}/searchAnalytics/query", corpo)
    linhas = r.get("rows", [])
    cab = (a.dim or []) + ["cliques", "impressoes", "ctr", "posicao"]
    saida = [list(l.get("keys", [])) + [l["clicks"], l["impressions"], round(l["ctr"] * 100, 2), round(l["position"], 1)]
             for l in linhas]
    if a.csv:
        with open(a.csv, "w", newline="", encoding="utf-8") as f:
            w = csv.writer(f)
            w.writerow(cab)
            w.writerows(saida)
        print(f"{len(saida)} linhas em {a.csv}")
    else:
        print("\t".join(cab))
        for l in saida:
            print("\t".join(str(x) for x in l))


def main():
    p = argparse.ArgumentParser()
    sub = p.add_subparsers(dest="cmd", required=True)
    sub.add_parser("sites").set_defaults(fn=sites)
    q = sub.add_parser("query")
    q.add_argument("--site", required=True, help='ex.: "sc-domain:exemplo.com.br" ou "https://exemplo.com.br/"')
    q.add_argument("--inicio", required=True)
    q.add_argument("--fim", required=True)
    q.add_argument("--dim", nargs="*", choices=["page", "query", "date", "device", "country", "searchAppearance"])
    q.add_argument("--limite", type=int, default=1000)
    q.add_argument("--csv")
    q.set_defaults(fn=query)
    a = p.parse_args()
    a.fn(a)


if __name__ == "__main__":
    main()

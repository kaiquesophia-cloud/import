#!/usr/bin/env python3
"""Lê o Google Analytics 4 pela Data API (só leitura), com uma conta de serviço.

A chave JSON NUNCA entra no repositório: passe o caminho por variável de ambiente.
  GA_KEY_FILE (ou GSC_KEY_FILE)   caminho do JSON da conta de serviço
A conta de serviço precisa estar em Analytics → Administrador → Gerenciamento de acesso (função Leitor),
e a "Google Analytics Data API" ativada no projeto do Google Cloud.

Uso:
  python3 seo/ga.py propriedades
  python3 seo/ga.py relatorio --prop 559470561 --inicio 2026-10-01 --fim today \
      --metricas sessions totalUsers --dims sessionDefaultChannelGroup [--filtro sessionDefaultChannelGroup=Organic\\ Search] \
      [--limite 50] [--csv saida.csv]
"""
import argparse
import csv
import json
import os
import sys
import urllib.error
import urllib.request

ESCOPO = "https://www.googleapis.com/auth/analytics.readonly"


def _token():
    try:
        from google.oauth2 import service_account
        from google.auth.transport.requests import Request
    except ImportError:
        sys.exit("Falta a biblioteca: pip3 install google-auth requests")
    caminho = os.environ.get("GA_KEY_FILE") or os.environ.get("GSC_KEY_FILE")
    if not caminho or not os.path.exists(caminho):
        sys.exit("Defina GA_KEY_FILE com o caminho do JSON da conta de serviço.")
    cred = service_account.Credentials.from_service_account_file(caminho, scopes=[ESCOPO])
    cred.refresh(Request())
    return cred.token


def _call(url, corpo=None):
    req = urllib.request.Request(url, method="POST" if corpo is not None else "GET",
                                 data=json.dumps(corpo).encode() if corpo is not None else None,
                                 headers={"Authorization": f"Bearer {_token()}", "Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        sys.exit(f"HTTP {e.code}: {e.read().decode(errors='replace')[:600]}")


def propriedades(_):
    r = _call("https://analyticsadmin.googleapis.com/v1beta/accountSummaries")
    lista = [(p["property"].split("/")[1], p.get("displayName"), a.get("displayName"))
             for a in r.get("accountSummaries", []) for p in a.get("propertySummaries", [])]
    if not lista:
        print("Nenhuma propriedade. Adicione a conta de serviço em Administrador → Gerenciamento de acesso.")
    for pid, nome, conta in lista:
        print(f"{pid}\t{nome}\t(conta: {conta})")


def relatorio(a):
    corpo = {"dateRanges": [{"startDate": a.inicio, "endDate": a.fim}],
             "metrics": [{"name": m} for m in a.metricas],
             "dimensions": [{"name": d} for d in (a.dims or [])],
             "limit": a.limite}
    if a.dims:
        corpo["orderBys"] = [{"metric": {"metricName": a.metricas[0]}, "desc": True}]
        if "date" in a.dims:
            corpo["orderBys"] = [{"dimension": {"dimensionName": "date"}}]
    if a.filtro:
        campo, valor = a.filtro.split("=", 1)
        corpo["dimensionFilter"] = {"filter": {"fieldName": campo, "stringFilter": {"value": valor}}}
    r = _call(f"https://analyticsdata.googleapis.com/v1beta/properties/{a.prop}:runReport", corpo)
    cab = (a.dims or []) + a.metricas
    linhas = [[d["value"] for d in l.get("dimensionValues", [])] + [m["value"] for m in l["metricValues"]]
              for l in r.get("rows", [])]
    if a.csv:
        with open(a.csv, "w", newline="", encoding="utf-8") as f:
            w = csv.writer(f)
            w.writerow(cab)
            w.writerows(linhas)
        print(f"{len(linhas)} linhas em {a.csv}")
    else:
        print("\t".join(cab))
        for l in linhas:
            print("\t".join(l))


def main():
    p = argparse.ArgumentParser()
    sub = p.add_subparsers(dest="cmd", required=True)
    sub.add_parser("propriedades").set_defaults(fn=propriedades)
    q = sub.add_parser("relatorio")
    q.add_argument("--prop", required=True)
    q.add_argument("--inicio", required=True)
    q.add_argument("--fim", default="today")
    q.add_argument("--metricas", nargs="+", default=["sessions"])
    q.add_argument("--dims", nargs="*")
    q.add_argument("--filtro", help="dimensão=valor exato, ex.: sessionDefaultChannelGroup=Organic Search")
    q.add_argument("--limite", type=int, default=50)
    q.add_argument("--csv")
    q.set_defaults(fn=relatorio)
    a = p.parse_args()
    a.fn(a)


if __name__ == "__main__":
    main()

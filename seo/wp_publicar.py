#!/usr/bin/env python3
"""Publica (ou atualiza) uma página no WordPress pela REST API, como RASCUNHO por padrão.

Credenciais pelo ambiente, nunca pelo chat nem pelo repositório:
  WP_URL           ex.: https://veloceautospecialista.com.br
  WP_USER          usuário do WordPress (administrador ou editor)
  WP_APP_PASSWORD  "Senha de aplicativo" gerada em Usuários > Perfil (não é a senha de login)

Uso:
  python3 seo/wp_publicar.py check
  python3 seo/wp_publicar.py page --slug oficina-bmw --title "Oficina BMW" \
      --html seo/veloce-auto-specialista/paginas/oficina-bmw.html [--status publish]
"""
import argparse
import base64
import json
import os
import re
import sys
import urllib.error
import urllib.request


def _env():
    faltando = [k for k in ("WP_URL", "WP_USER", "WP_APP_PASSWORD") if not os.environ.get(k)]
    if faltando:
        sys.exit(f"Faltam variáveis de ambiente: {', '.join(faltando)}")
    return os.environ["WP_URL"].rstrip("/"), os.environ["WP_USER"], os.environ["WP_APP_PASSWORD"]


def _req(method, path, body=None):
    base, user, pwd = _env()
    token = base64.b64encode(f"{user}:{pwd}".encode()).decode()
    req = urllib.request.Request(
        f"{base}/wp-json/wp/v2/{path}",
        method=method,
        data=json.dumps(body).encode() if body is not None else None,
        headers={"Authorization": f"Basic {token}", "Content-Type": "application/json",
                 "User-Agent": "veloce-seo/1.0"},
    )
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.load(r)
    except urllib.error.HTTPError as e:
        sys.exit(f"HTTP {e.code} em {method} {path}: {e.read().decode(errors='replace')[:500]}")
    except urllib.error.URLError as e:
        sys.exit(f"Sem conexão com {base}: {e.reason} (o domínio está liberado na rede do ambiente?)")


def check(_):
    me = _req("GET", "users/me?context=edit")
    print(f"Conectado como: {me.get('name')} (papéis: {', '.join(me.get('roles', []))})")
    caps = me.get("capabilities", {})
    print("Pode publicar páginas:", "sim" if caps.get("publish_pages") else "NÃO")
    print("Pode salvar <script>/HTML livre:", "sim" if caps.get("unfiltered_html") else
          "NÃO (os dados estruturados seriam removidos)")


def page(a):
    raw = open(a.html, encoding="utf-8").read()
    raw = re.sub(r"^\s*<!--.*?-->\s*", "", raw, count=1, flags=re.S)  # tira o cabeçalho de instruções
    pendentes = sorted(set(re.findall(r"\[[A-ZÁÉÍÓÚÇÃÕ ]{5,}[^\]]*\]", raw)))
    if pendentes:
        print("⚠️  Ainda há placeholders no HTML:", ", ".join(pendentes))
    content = f"<!-- wp:html -->\n{raw}\n<!-- /wp:html -->"
    existentes = _req("GET", f"pages?slug={a.slug}&status=any&context=edit")
    body = {"title": a.title, "slug": a.slug, "content": content, "status": a.status}
    if existentes:
        pid = existentes[0]["id"]
        r = _req("POST", f"pages/{pid}", body)
        print(f"Atualizada a página {pid} ({r['status']}): {r['link']}")
    else:
        r = _req("POST", "pages", body)
        print(f"Criada a página {r['id']} ({r['status']}): {r['link']}")


def main():
    p = argparse.ArgumentParser()
    sub = p.add_subparsers(dest="cmd", required=True)
    sub.add_parser("check").set_defaults(fn=check)
    s = sub.add_parser("page")
    s.add_argument("--slug", required=True)
    s.add_argument("--title", required=True)
    s.add_argument("--html", required=True)
    s.add_argument("--status", default="draft", choices=["draft", "publish"])
    s.set_defaults(fn=page)
    a = p.parse_args()
    a.fn(a)


if __name__ == "__main__":
    main()

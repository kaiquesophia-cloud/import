"""Otimizações de velocidade no WordPress da Andaimaq, via REST API.

Uso (lê WP_USER e WP_APP_PASSWORD do ambiente; nunca grava credencial em arquivo):
    python3 -I propostas/andaimaq/execucao/wp/aplicar.py status
    python3 -I propostas/andaimaq/execucao/wp/aplicar.py upload        # envia as imagens WebP
    python3 -I propostas/andaimaq/execucao/wp/aplicar.py trocar        # troca PNG -> WebP no Elementor
    python3 -I propostas/andaimaq/execucao/wp/aplicar.py plugins-off   # desativa plugins sem uso
    python3 -I propostas/andaimaq/execucao/wp/aplicar.py cache         # limpa o cache do Elementor
    python3 -I propostas/andaimaq/execucao/wp/aplicar.py desfazer      # volta o _elementor_data do backup

Só fala com andaimaq.com.br. Tudo é reversível: as imagens originais ficam na mídia, o
_elementor_data anterior fica em backup-2026-10-09/, e plugin desativado se reativa.
"""
import base64
import json
import os
import sys
import time
import urllib.error
import urllib.request

BASE = "https://andaimaq.com.br/wp-json"
AQUI = os.path.dirname(os.path.abspath(__file__))
EXEC = os.path.dirname(AQUI)
BACKUP = os.path.join(EXEC, "backup-2026-10-09")
WEBP = os.path.join(EXEC, "imagens-webp")
MAPA = os.path.join(AQUI, "mapping.json")

# id da mídia original -> arquivo
IMAGENS = {76: "2.png", 77: "3.png", 78: "4.png", 71: "Design-sem-nome-61.png",
           169: "Design-sem-nome-62.png", 301: "ChatGPT-Image-12-de-ago.-de-2025-11_14_38.png"}
PLUGINS_SEM_USO = ["metform/metform", "template-kit-import/template-kit-import",
                   "copy-delete-posts/copy-delete-posts"]


def _senha():
    p = os.environ["WP_APP_PASSWORD"].replace(" ", "")
    # senha de aplicativo do WP tem 24 caracteres; a salva no ambiente veio com 1 a mais no início
    if len(p) == 25:
        p = p[1:]
    return p


_AUTH = base64.b64encode(f"{os.environ['WP_USER']}:{_senha()}".encode()).decode()


def req(method, path, data=None, raw=None, headers=None):
    h = {"Authorization": "Basic " + _AUTH, "User-Agent": "seoamplify"}
    body = None
    if data is not None:
        body = json.dumps(data).encode()
        h["Content-Type"] = "application/json"
    if raw is not None:
        body = raw
    h.update(headers or {})
    for tentativa in range(5):
        r = urllib.request.Request(BASE + path, data=body, method=method, headers=h)
        try:
            with urllib.request.urlopen(r, timeout=180) as resp:
                t = resp.read().decode()
                return json.loads(t) if t else None
        except urllib.error.HTTPError as e:
            raise RuntimeError(f"{method} {path} -> {e.code}: {e.read().decode()[:400]}")
        except (urllib.error.URLError, ConnectionError) as e:
            # a hospedagem derruba conexões em sequência; espera e tenta de novo
            if tentativa == 4:
                raise
            print(f"  conexão caiu ({e}); nova tentativa em {5 * 2 ** tentativa}s")
            time.sleep(5 * 2 ** tentativa)


def rest_base(tipo):
    return req("GET", "/wp/v2/types")[tipo]["rest_base"]


def status():
    me = req("GET", "/wp/v2/users/me?context=edit")
    print("logado como:", me["slug"], me["roles"])
    for p in req("GET", "/wp/v2/plugins"):
        if p["plugin"] in PLUGINS_SEM_USO:
            print("plugin:", p["plugin"], p["status"])
    if os.path.exists(MAPA):
        print("mapeamento:", json.load(open(MAPA)))


def upload():
    media = {m["id"]: m for m in json.load(open(os.path.join(BACKUP, "media.json")))}
    mapa = json.load(open(MAPA)) if os.path.exists(MAPA) else {}
    for mid, nome in IMAGENS.items():
        if str(mid) in mapa:
            print(mid, "já enviado ->", mapa[str(mid)]["new_url"])
            continue
        webp = nome.rsplit(".", 1)[0] + ".webp"
        r = req("POST", "/wp/v2/media", raw=open(os.path.join(WEBP, webp), "rb").read(),
                headers={"Content-Type": "image/webp",
                         "Content-Disposition": f'attachment; filename="{webp}"'})
        alt = media[mid].get("alt_text") or ""
        if alt:
            req("POST", f"/wp/v2/media/{r['id']}", data={"alt_text": alt})
        mapa[str(mid)] = {"old_url": media[mid]["source_url"], "new_id": r["id"],
                          "new_url": r["source_url"]}
        json.dump(mapa, open(MAPA, "w"), indent=1)
        print(mid, "->", r["id"], r["source_url"])


def _trocar_no(obj, mapa):
    """Troca url+id das imagens dentro da estrutura do Elementor. Retorna nº de trocas."""
    n = 0
    if isinstance(obj, dict):
        url = obj.get("url")
        if isinstance(url, str):
            for old_id, m in mapa.items():
                if url == m["old_url"] or url.endswith("/" + m["old_url"].split("/")[-1]):
                    obj["url"] = m["new_url"]
                    if "id" in obj:
                        obj["id"] = m["new_id"]
                    n += 1
                    break
        for k, v in obj.items():
            if isinstance(v, str):
                for m in mapa.values():
                    if m["old_url"] in v:
                        obj[k] = v = v.replace(m["old_url"], m["new_url"])
                        n += 1
            else:
                n += _trocar_no(v, mapa)
    elif isinstance(obj, list):
        for v in obj:
            n += _trocar_no(v, mapa)
    return n


def _alvos():
    lib = rest_base("elementor_library")
    alvos = []
    for arq, rb in (("pages.json", "pages"), ("elementor_library.json", lib)):
        for it in json.load(open(os.path.join(BACKUP, arq))):
            ed = (it.get("meta") or {}).get("_elementor_data") or ""
            if any(n in ed for n in IMAGENS.values()):
                alvos.append((rb, it["id"], it.get("slug")))
    return alvos


def trocar():
    mapa = json.load(open(MAPA))
    if len(mapa) != len(IMAGENS):
        sys.exit("rode 'upload' antes: faltam imagens no mapeamento")
    for rb, pid, slug in _alvos():
        atual = req("GET", f"/wp/v2/{rb}/{pid}?context=edit")["meta"]["_elementor_data"]
        dados = json.loads(atual)
        n = _trocar_no(dados, mapa)
        if not n:
            print(slug, "nada a trocar")
            continue
        novo = json.dumps(dados, ensure_ascii=False)
        req("POST", f"/wp/v2/{rb}/{pid}", data={"meta": {"_elementor_data": novo}})
        lido = json.loads(req("GET", f"/wp/v2/{rb}/{pid}?context=edit")["meta"]["_elementor_data"])
        ok = lido == dados
        print(f"{slug} ({rb}/{pid}): {n} trocas, conferido={'OK' if ok else 'DIFERENTE'}")
        if not ok:
            sys.exit("o conteúdo gravado não bate com o enviado; pare e rode 'desfazer'")


def desfazer():
    lib = rest_base("elementor_library")
    for arq, rb in (("pages.json", "pages"), ("elementor_library.json", lib)):
        for it in json.load(open(os.path.join(BACKUP, arq))):
            ed = (it.get("meta") or {}).get("_elementor_data")
            if ed and any(n in ed for n in IMAGENS.values()):
                req("POST", f"/wp/v2/{rb}/{it['id']}", data={"meta": {"_elementor_data": ed}})
                print("restaurado:", it.get("slug"))
    cache()


def plugins_off():
    for p in PLUGINS_SEM_USO:
        r = req("POST", f"/wp/v2/plugins/{p}", data={"status": "inactive"})
        print(p, "->", r["status"])


def _tamanhos(mid):
    s = req("GET", f"/wp/v2/media/{mid}")["media_details"]["sizes"]
    return {k: v["source_url"] for k, v in s.items()}


def _fundos_responsivos(obj, tam):
    """Em section/column/container com fundo WebP, define versão menor p/ tablet e celular."""
    n = 0
    if isinstance(obj, dict):
        st = obj.get("settings")
        if isinstance(st, dict) and obj.get("elType") in ("section", "column", "container"):
            bg = st.get("background_image")
            if isinstance(bg, dict) and bg.get("id") in tam:
                t = tam[bg["id"]]
                for chave, size in (("background_image_tablet", "large"),
                                    ("background_image_mobile", "medium_large")):
                    if size in t and (st.get(chave) or {}).get("url") != t[size]:
                        st[chave] = {"url": t[size], "id": bg["id"], "size": "", "alt": "",
                                     "source": "library"}
                        n += 1
        for v in obj.values():
            n += _fundos_responsivos(v, tam)
    elif isinstance(obj, list):
        for v in obj:
            n += _fundos_responsivos(v, tam)
    return n


def fundo_mobile():
    mapa = json.load(open(MAPA))
    tam = {m["new_id"]: _tamanhos(m["new_id"]) for m in mapa.values()}
    for rb, pid, slug in _alvos():
        dados = json.loads(req("GET", f"/wp/v2/{rb}/{pid}?context=edit")["meta"]["_elementor_data"])
        n = _fundos_responsivos(dados, tam)
        if not n:
            print(slug, "sem fundo para ajustar")
            continue
        req("POST", f"/wp/v2/{rb}/{pid}", data={"meta": {"_elementor_data": json.dumps(dados, ensure_ascii=False)}})
        lido = json.loads(req("GET", f"/wp/v2/{rb}/{pid}?context=edit")["meta"]["_elementor_data"])
        print(f"{slug}: {n} fundos responsivos, conferido={'OK' if lido == dados else 'DIFERENTE'}")
    cache()


# imagem destacada por página (o Rank Math usa no schema e no compartilhamento)
DESTAQUE = {11: "71", 239: "169", 295: "169", 124: "169", 119: "169"}


def destaque():
    mapa = json.load(open(MAPA))
    for pid, old in DESTAQUE.items():
        novo = mapa[old]["new_id"]
        r = req("POST", f"/wp/v2/pages/{pid}", data={"featured_media": novo})
        print(r["slug"], "-> imagem destacada", r["featured_media"])


def _aplicar_widget(el, mudancas):
    st = el.setdefault("settings", {})
    for chave, valor in mudancas.items():
        if chave == "__replace":
            def troca(o):
                if isinstance(o, dict):
                    return {k: troca(v) for k, v in o.items()}
                if isinstance(o, list):
                    return [troca(v) for v in o]
                if isinstance(o, str):
                    for a, b in valor:
                        o = o.replace(a, b)
                return o
            el["settings"] = st = troca(st)
        else:
            st[chave] = valor


def editar(arquivo):
    """Aplica um arquivo de conteúdo (conteudo/*.json): textos de widgets + meta do Rank Math."""
    spec = json.load(open(arquivo))
    pid = spec["page_id"]
    atual = req("GET", f"/wp/v2/pages/{pid}?context=edit")
    dados = json.loads(atual["meta"]["_elementor_data"])
    copia = os.path.join(BACKUP, f"page-{pid}-antes-{time.strftime('%Y%m%d-%H%M%S')}.json")
    json.dump(dados, open(copia, "w"), ensure_ascii=False, indent=1)
    print("backup da página:", os.path.relpath(copia, EXEC))
    achados = set()

    def walk(lista):
        for el in lista:
            if el.get("id") in spec["widgets"]:
                _aplicar_widget(el, spec["widgets"][el["id"]])
                achados.add(el["id"])
            walk(el.get("elements", []))
    walk(dados)
    faltando = set(spec["widgets"]) - achados
    if faltando:
        sys.exit(f"widgets não encontrados, nada gravado: {sorted(faltando)}")
    req("POST", f"/wp/v2/pages/{pid}", data={"meta": {"_elementor_data": json.dumps(dados, ensure_ascii=False)}})
    lido = json.loads(req("GET", f"/wp/v2/pages/{pid}?context=edit")["meta"]["_elementor_data"])
    print(f"{len(achados)} widgets editados, conferido={'OK' if lido == dados else 'DIFERENTE'}")
    if spec.get("rank_math"):
        r = req("POST", "/rankmath/v1/updateMeta",
                data={"objectType": "post", "objectID": pid, "meta": spec["rank_math"]})
        print("Rank Math:", r)
    cache()


def instalar_cache():
    """Instala e ativa o Cache Enabler (wordpress.org). Desfazer: desativar o plugin."""
    ja = [p for p in req("GET", "/wp/v2/plugins") if p["plugin"].startswith("cache-enabler/")]
    if ja:
        r = req("POST", f"/wp/v2/plugins/{ja[0]['plugin']}", data={"status": "active"})
    else:
        r = req("POST", "/wp/v2/plugins", data={"slug": "cache-enabler", "status": "active"})
    print(r["plugin"], r["version"], "->", r["status"])


def cache():
    try:
        req("DELETE", "/elementor/v1/cache")
        print("cache do Elementor limpo")
    except RuntimeError as e:
        print("não consegui limpar o cache pela API:", e)


if __name__ == "__main__":
    cmds = {"status": status, "upload": upload, "trocar": trocar, "plugins-off": plugins_off,
            "cache": cache, "desfazer": desfazer, "instalar-cache": instalar_cache,
            "fundo-mobile": fundo_mobile, "destaque": destaque}
    if len(sys.argv) == 3 and sys.argv[1] == "editar":
        editar(sys.argv[2])
    elif len(sys.argv) != 2 or sys.argv[1] not in cmds:
        sys.exit("uso: aplicar.py " + "|".join(cmds) + "|editar <conteudo.json>")
    else:
        cmds[sys.argv[1]]()

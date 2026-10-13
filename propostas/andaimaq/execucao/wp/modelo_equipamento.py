"""Modelo de página de equipamento (landing page) para o Elementor da Andaimaq.

Gera o _elementor_data a partir de um arquivo de conteúdo (paginas/<equipamento>.json).
A mesma estrutura serve para Betoneira, Martelete e Escora: muda só o conteúdo.
"""
import hashlib
import json
from urllib.parse import quote

LARANJA = "#FF9C00"
ESCURO = "#1C1C1C"
CINZA_CLARO = "#F5F5F5"
TEXTO = "#4A4A4A"
WHATS = "5511997405191"
TEL = "+5511997405191"
TEL_TXT = "(11) 99740-5191"

_contador = [0]


def _id(semente):
    _contador[0] += 1
    return hashlib.md5(f"{semente}-{_contador[0]}".encode()).hexdigest()[:7]


def _px(v):
    return {"unit": "px", "size": v, "sizes": []}


def _pad(t, r, b, l):
    return {"unit": "px", "top": str(t), "right": str(r), "bottom": str(b), "left": str(l), "isLinked": False}


def whats_link(msg):
    return f"https://wa.me/{WHATS}?text={quote(msg)}"


def widget(tipo, settings):
    return {"id": _id(tipo), "elType": "widget", "widgetType": tipo, "settings": settings, "elements": []}


def coluna(tamanho, elementos, extra=None):
    s = {"_column_size": tamanho, "_inline_size": None}
    s.update(extra or {})
    return {"id": _id("col"), "elType": "column", "settings": s, "elements": elementos, "isInner": False}


def secao(colunas, fundo=None, pad=(70, 20, 70, 20), extra=None):
    s = {"padding": _pad(*pad), "padding_mobile": _pad(pad[0] // 2 + 10, 15, pad[2] // 2 + 10, 15),
         "content_width": {"unit": "px", "size": 1140, "sizes": []}, "gap": "extended"}
    if fundo:
        s.update({"background_background": "classic", "background_color": fundo})
    s.update(extra or {})
    return {"id": _id("sec"), "elType": "section", "settings": s, "elements": colunas, "isInner": False}


def titulo(texto, tag="h2", cor=ESCURO, tam=36, tam_mob=26, alinhar="left", peso="700"):
    return widget("heading", {
        "title": texto, "header_size": tag, "title_color": cor, "align": alinhar,
        "typography_typography": "custom", "typography_font_family": "Sora",
        "typography_font_size": _px(tam), "typography_font_size_mobile": _px(tam_mob),
        "typography_font_weight": peso, "typography_line_height": {"unit": "em", "size": 1.2, "sizes": []}})


def texto(html, cor=TEXTO, tam=17, alinhar="left"):
    return widget("text-editor", {
        "editor": html, "text_color": cor, "align": alinhar,
        "typography_typography": "custom", "typography_font_family": "Roboto",
        "typography_font_size": _px(tam), "typography_line_height": {"unit": "em", "size": 1.6, "sizes": []}})


def botao(rotulo, url, cor_fundo=LARANJA, cor_texto="#FFFFFF", icone="fab fa-whatsapp",
          biblioteca="fa-brands", alinhar="left", tamanho="lg"):
    s = {"text": rotulo, "link": {"url": url, "is_external": "", "nofollow": "", "custom_attributes": ""},
         "align": alinhar, "align_mobile": "justify", "size": tamanho,
         "background_color": cor_fundo, "button_text_color": cor_texto,
         "border_radius": {"unit": "px", "top": "8", "right": "8", "bottom": "8", "left": "8", "isLinked": True},
         "typography_typography": "custom", "typography_font_family": "Sora",
         "typography_font_size": _px(17), "typography_font_weight": "700", "hover_animation": "grow"}
    if icone:
        s.update({"selected_icon": {"value": icone, "library": biblioteca}, "icon_align": "left",
                  "icon_indent": _px(10)})
    return widget("button", s)


def lista_check(itens, cor_texto="#FFFFFF"):
    return widget("icon-list", {
        "icon_list": [{"text": t, "selected_icon": {"value": "fas fa-check-circle", "library": "fa-solid"},
                       "_id": _id("li")} for t in itens],
        "icon_color": LARANJA, "text_color": cor_texto, "space_between": _px(12),
        "icon_size": _px(20), "text_indent": _px(10),
        "icon_typography_typography": "custom", "icon_typography_font_family": "Roboto",
        "icon_typography_font_size": _px(18)})


def caixa_icone(icone, titulo_txt, desc, cor_titulo=ESCURO, cor_icone=LARANJA, cor_desc=TEXTO,
                biblioteca="fa-solid", posicao="top"):
    return widget("icon-box", {
        "selected_icon": {"value": icone, "library": biblioteca}, "title_text": titulo_txt,
        "description_text": desc, "position": posicao, "title_size": "h3",
        "primary_color": cor_icone, "title_color": cor_titulo, "description_color": cor_desc,
        "icon_size": _px(40), "text_align": "center",
        "title_typography_typography": "custom", "title_typography_font_family": "Sora",
        "title_typography_font_size": _px(19), "title_typography_font_weight": "700",
        "description_typography_typography": "custom", "description_typography_font_family": "Roboto",
        "description_typography_font_size": _px(15)})


def imagem(mid, url, alt):
    return widget("image", {
        "image": {"url": url, "id": mid, "alt": alt, "source": "library", "size": ""},
        "image_size": "large",
        "image_border_radius": {"unit": "px", "top": "12", "right": "12", "bottom": "12", "left": "12", "isLinked": True}})


def sanfona(perguntas):
    return widget("elementskit-accordion", {
        "ekit_accordion_items": [{"acc_title": p, "acc_content": f"<p>{r}</p>", "_id": _id("acc"),
                                  **({"ekit_acc_is_active": "yes"} if i == 0 else {})}
                                 for i, (p, r) in enumerate(perguntas)]})


def construir(c):
    """c = conteúdo do equipamento (dict carregado de paginas/<x>.json)."""
    _contador[0] = 0
    w_orc = whats_link(c["whats_msg"])
    s = []

    # 1. Topo: título, promessa, checks, botões e foto
    s.append(secao([
        coluna(55, [
            texto(f"<p><strong>{c['kicker']}</strong></p>", cor=LARANJA, tam=15),
            titulo(c["h1"], tag="h1", cor="#FFFFFF", tam=46, tam_mob=32),
            texto(f"<p>{c['subtitulo']}</p>", cor="#DDDDDD", tam=19),
            lista_check(["Entrega e retirada na obra", "Atendemos toda a Grande São Paulo",
                         "Orçamento rápido pelo WhatsApp"]),
            botao("Pedir orçamento no WhatsApp", w_orc),
            botao(f"Ligar {TEL_TXT}", f"tel:{TEL}", cor_fundo="transparent", icone="fas fa-phone-alt",
                  biblioteca="fa-solid", tamanho="md"),
        ], {"content_position": "center"}),
        coluna(45, [imagem(c["imagem_id"], c["imagem_url"], c["h1"])], {"content_position": "center"}),
    ], fundo=ESCURO, pad=(90, 20, 90, 20)))

    # 2. Faixa de confiança
    s.append(secao([
        coluna(33, [caixa_icone("fas fa-truck", "Entrega na obra", "Levamos e buscamos no endereço da obra",
                                cor_titulo="#FFFFFF", cor_icone="#FFFFFF", cor_desc="#FFFFFF")]),
        coluna(33, [caixa_icone("fas fa-map-marker-alt", "Grande São Paulo",
                                "Base no Jardim Ângela, zona sul", cor_titulo="#FFFFFF",
                                cor_icone="#FFFFFF", cor_desc="#FFFFFF")]),
        coluna(33, [caixa_icone("fab fa-whatsapp", "Orçamento rápido", "Fale direto com a nossa equipe",
                                cor_titulo="#FFFFFF", cor_icone="#FFFFFF", cor_desc="#FFFFFF",
                                biblioteca="fa-brands")]),
    ], fundo=LARANJA, pad=(35, 20, 35, 20)))

    # 3. Modelos
    cards = []
    for m in c["modelos"]:
        cards.append(coluna(int(100 / len(c["modelos"])), [
            titulo(m["nome"], tag="h3", tam=22, tam_mob=20),
            texto(f"<p>{m['descricao']}</p>", tam=16),
            lista_check(m["indicado"], cor_texto=TEXTO),
            botao("Pedir orçamento deste", whats_link(f"Olá! Quero um orçamento de {m['nome'].lower()}."),
                  tamanho="md"),
        ], {"background_background": "classic", "background_color": "#FFFFFF",
            "padding": _pad(30, 30, 30, 30),
            "border_radius": {"unit": "px", "top": "12", "right": "12", "bottom": "12", "left": "12",
                              "isLinked": True},
            "box_shadow_box_shadow_type": "yes",
            "box_shadow_box_shadow": {"horizontal": 0, "vertical": 6, "blur": 24, "spread": 0,
                                      "color": "rgba(0,0,0,0.08)"},
            "margin": _pad(10, 10, 10, 10)}))
    s.append(secao([coluna(100, [titulo(c["h2_modelos"], alinhar="center"),
                                 texto(f"<p>{c['intro_modelos']}</p>", alinhar="center")])],
                   fundo=CINZA_CLARO, pad=(70, 20, 10, 20)))
    s.append(secao(cards, fundo=CINZA_CLARO, pad=(10, 20, 70, 20)))

    # 4. Como funciona
    passos = [("fab fa-whatsapp", "1. Orçamento", "Conte o serviço, o endereço e o prazo pelo WhatsApp", "fa-brands"),
              ("fas fa-truck", "2. Entrega", "Levamos o equipamento até a obra", "fa-solid"),
              ("fas fa-hard-hat", "3. Uso", "Fica com você durante o período contratado", "fa-solid"),
              ("fas fa-undo-alt", "4. Retirada", "Terminou o serviço, buscamos na obra", "fa-solid")]
    s.append(secao([coluna(100, [titulo(f"Como funciona o aluguel de {c['nome']}", alinhar="center")])],
                   pad=(70, 20, 10, 20)))
    s.append(secao([coluna(25, [caixa_icone(i, t, d, biblioteca=b)]) for i, t, d, b in passos],
                   pad=(10, 20, 70, 20)))

    # 5. Conteúdo (SEO): para que serve + preço
    s.append(secao([
        coluna(50, [titulo(c["h2_uso"], tam=30, tam_mob=24), texto(c["texto_uso"])]),
        coluna(50, [titulo(c["h2_preco"], tam=30, tam_mob=24), texto(c["texto_preco"])]),
    ], fundo=CINZA_CLARO))

    # 6. Avaliações do Google (mesmo widget da home)
    s.append(secao([coluna(100, [
        titulo("O que nossos clientes dizem", alinhar="center"),
        widget("shortcode", {"shortcode": "[trustindex no-registration=google]"}),
    ])]))

    # 7. Perguntas frequentes em sanfona
    s.append(secao([coluna(100, [
        titulo(f"Perguntas frequentes sobre aluguel de {c['nome']}", alinhar="center"),
        sanfona(c["faq"]),
    ])], fundo=CINZA_CLARO))

    # 8. Chamada final
    s.append(secao([coluna(100, [
        titulo(c["cta_final"], cor="#FFFFFF", alinhar="center"),
        texto("<p>Resposta rápida pelo WhatsApp, com entrega e retirada na obra.</p>",
              cor="#DDDDDD", alinhar="center"),
        botao("Pedir orçamento no WhatsApp", w_orc, alinhar="center"),
    ])], fundo=ESCURO, pad=(80, 20, 80, 20)))
    return s


if __name__ == "__main__":
    import sys
    print(json.dumps(construir(json.load(open(sys.argv[1]))), ensure_ascii=False, indent=1)[:2000])
